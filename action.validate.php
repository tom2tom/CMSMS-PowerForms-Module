<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

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
$sql = 'SELECT code,content FROM '.$pre.'module_pwf_record WHERE record_id=?';
$row = $db->GetRow($sql,array($record_id));
$sql = 'DELETE FROM '.$pre.'module_pwf_record WHERE record_id=?';
if(!$row || $row['code'] != $params[$key.'c'])
{
	if($row)
		$db->Execute($sql,array($record_id));
	echo $this->Lang('validation_response_error');
	return;
}

$db->Execute($sql,array($record_id));

$formdata = pwfUtils::Decrypt($row['content'],$row['code'].$this->GetPreference('default_phrase'));
if($formdata === FALSE)
{
	echo $this->Lang('validation_response_error');
	return;
}
$formdata->formsmodule = $this; //restore un-cached content

$field_id = $params[$key.'d'];
$obfield = $formdata->Fields[$field_id];
$obfield->ApproveToGo($record_id); //block another disposition of this field

$res = $whole-form-Dispose($returnid); //TODO 'really' dispose, this time
if($res[0])
{
	$ret = $obfield->GetOption('redirect_page',-1);
	if($ret != -1)
		$this->RedirectContent($ret);
	else
	{
$this->Crash();
	}
}
else
{
	$msg = $this->Lang('error').'<br />'.implode('<br />',$res[1]);
	echo $msg;
}

?>
