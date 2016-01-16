<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

try
{
	$cache = pwfUtils::GetCache();
}
catch (Exception $e)
{
	echo $this->Lang('error_system');
	return;
}
$paramkeys = array_keys($params);
$matched = preg_grep('/^pwfp_\d{3}_[cdr]$/',$paramkeys);
if(count($matched) != 3)
{
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
if(!$row || $row['pubkey'] != $params[$key.'c'])
{
	if($row)
		$db->Execute($sql,array($record_id));
	echo $this->Lang('validation_response_error');
	return;
}

$db->Execute($sql,array($record_id));

$pw = $row['pubkey'].pwfUtils::Unfusc($this->GetPreference('masterpass'));
$formdata = pwfUtils::Decrypt($row['content'],$pw);
if($formdata === FALSE)
{
	echo $this->Lang('validation_response_error');
	return;
}
$formdata->formsmodule = &$this; //restore un-cached content

$field_id = $params[$key.'d'];
$obfield = $formdata->Fields[$field_id];
$obfield->ApproveToGo($record_id); //setup to 'really' dispose the form

//cache data for next action
if(!empty($_SERVER['SERVER_ADDR']))
	$token = $_SERVER['SERVER_ADDR'];
else
	$token = mt_rand(0,999999).'.'.mt_rand(0,999999);
$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100,2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
$cache_key = md5($token);
unset($formdata->formsmodule);
$cache->set($cache_key,$formdata);

$prefix = $formdata->current_prefix;
$this->Redirect($id,'default',$returnid,array(
	'form_id'=>$formdata->Id,
	$prefix.'formdata'=>$cache_key,
	$prefix.'done'=>1
	));

?>
