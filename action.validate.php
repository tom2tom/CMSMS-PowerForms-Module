<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$paramkeys = array_keys($params);
$matched = preg_grep('/^pwfp_\d{3}_[cdfr]$/',$paramkeys);
if(count($matched) != 4)
{
	echo $this->Lang('validation_param_error');
	return;
}
$key = reset($matched);
$key = substr($key,0,9); //prefix
$code = $params[$key.'c'];
$form_id = $params[$key.'f'];
$response_id = $params[$key.'r'];
if(!pwfUtils::CheckResponse($form_id,$response_id,$code))
{
	echo $this->Lang('validation_response_error');
	return;
}

$funcs = new pwfFormOperations();
//TODO get cached form data, including field values
$formdata = $funcs->Load($this,$id,$params,$form_id);
$field_id = $params[$key.'d'];
$obfield = $formdata->Fields[$field_id];
$obfield->ApproveToGo($response_id); //block another disposition of this field
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
