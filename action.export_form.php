<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!isset($params['form_id']) && isset($params['form']))
{
	// got the form by alias, not ID
	$params['form_id'] = pwfUtils::GetFormIDFromAlias($params['form']);
}
if(empty($params['form_id'])) exit; //TODO feedback

$fn = $this->GetName().$this->Lang('export').'-'.
	pwfUtils::GetFormNameFromID($params['form_id']).'-'.date('Y-m-d-H-i').'.xml';
$fn = preg_replace('/[^\w\-.]/','_',$fn);

$funcs = new pwfFormOperations();
//TODO $param['export_values'] not used anywhere
$xmlstr = $funcs->CreateXML($this,$params['form_id'],date('Y-m-d H:i:s'),
	isset($params['export_values'])); //no charset
if($xmlstr)
{
	@ob_clean();
	@ob_clean();
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private',FALSE);
	header('Content-Description: File Transfer');
	header('Content-Type: application/force-download; charset=utf-8');
	header('Content-Length: '.strlen($xmlstr));
	header('Content-Disposition: attachment; filename='.$fn);
	echo $xmlstr;
	exit;
}

$this->Redirect($id,'defaultadmin'); //TODO feedback

?>
