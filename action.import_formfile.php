<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

$funcs = new pwfFormOperations();
if($funcs->NewID($this,$params['import_formname'],$params['import_formalias']))
{
	$params['xml_file'] = $_FILES[$id.'xmlfile']['tmp_name'];
	$key = ($funcs->ImportXML($this,$params)) ? 'form_imported':'form_import_failed';
}
else
	$key = 'duplicate_identifier';

$params['message'] = $this->Lang($key);
$this->Redirect($id,'defaultadmin','',$params);

?>
