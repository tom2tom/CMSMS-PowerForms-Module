<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!isset($params['form_id']) && isset($params['form']))
{
	// get the form by name, not ID
	$params['form_id'] = $this->GetFormIDFromAlias($params['form']);
}

$funcs = new pwfUtils($this,$params,true);
$spec = $funcs->GetName().".xml";
$spec = preg_replace('/[^\w\d\.\-\_]/','_',$spec);
$xmlstr = $funcs->ExportXML(isset($params['fbrp_export_values'])?true:false);

@ob_clean();
@ob_clean();
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-Description: File Transfer');
header('Content-Type: application/force-download; charset=utf-8');
header('Content-Length: ' . strlen($xmlstr));
header('Content-Disposition: attachment; filename=' . $spec);
echo $xmlstr;
exit;
?>
