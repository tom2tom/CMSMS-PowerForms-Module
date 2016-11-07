<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo $this->Lang('err_system');
	return;
}
$paramkeys = array_keys($params);
$matched = preg_grep('/^pwfp_\d{3}_[cdr]$/',$paramkeys);
if (count($matched) != 3) {
	echo $this->Lang('validation_param_error');
	return;
}
$key = reset($matched);
$key = substr($key,0,9); //prefix
$record_id = $params[$key.'r'];

$pre = cms_db_prefix();
$sql = 'SELECT pubkey,content FROM '.$pre.'module_pwf_record WHERE record_id=?';
$row = $db->GetRow($sql,array($record_id));
$sql = 'DELETE FROM '.$pre.'module_pwf_record WHERE record_id=?';
if (!$row || $row['pubkey'] != $params[$key.'c']) {
	if ($row)
		$db->Execute($sql,array($record_id));
	echo $this->Lang('validation_response_error');
	return;
}

$db->Execute($sql,array($record_id));

$pw = $row['pubkey'].PWForms\Utils::Unfusc($this->GetPreference('masterpass'));
$formdata = unserialize(PWForms\Utils::Decrypt($row['content'],$pw));
if ($formdata === FALSE) {
	echo $this->Lang('validation_response_error');
	return;
}

$field_id = $params[$key.'d'];
$obfld = $formdata->Fields[$field_id];
$obfld->ApproveToGo($record_id); //setup to 'really' dispose the form

//cache data for next action
if (!empty($_SERVER['SERVER_ADDR']))
	$token = $_SERVER['SERVER_ADDR'];
else
	$token = mt_rand(0,999999).'.'.mt_rand(0,999999);
$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100,2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
$cache_key = md5($token);
$cache->set($cache_key,$formdata,84600); //expiry ?

$prefix = $formdata->current_prefix;
$this->Redirect($id,'default',$returnid,array(
	'form_id'=>$formdata->Id,
	$prefix.'datakey'=>$cache_key,
	$prefix.'done'=>1
	));
