<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->CheckAccess('ModifyPFForms')) exit;

$funcs = new PWForms\FormOperations();
if ($funcs->NewID($params['import_formname'],$params['import_formalias'])) {
	$fp = $_FILES[$id.'xmlfile']['tmp_name'];
	$state = $funcs->ImportXML($this,$fp);
	$key = ($state)?'form_imported':'error_form_import';
} else {
	$state = FALSE;
	$key = 'duplicate_identifier';
}

$this->Redirect($id,'defaultadmin','',array(
	'message' => $this->PrettyMessage($key,$state)));
