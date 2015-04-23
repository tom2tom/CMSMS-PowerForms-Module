<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$params['fbrp_xml_file'] = $_FILES[$id.'fbrp_xmlfile']['tmp_name'];

$funcs = new pwfUtils($this, $params, true);
if($funcs->newID ($params['fbrp_import_formname'],$params['fbrp_import_formalias']))
{
	if($funcs->ImportXML($params))
		$params['fbrp_message'] = $this->Lang('form_imported');
	else
		$params['fbrp_message'] = $this->Lang('form_import_failed');
}
else
	$params['fbrp_message'] = $this->Lang('duplicate_identifier');

$this->Redirect($id, 'defaultadmin', '', $params);
?>
