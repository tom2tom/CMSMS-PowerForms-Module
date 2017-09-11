<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

if (!$this->_CheckAccess('ModifyPFForms')) {
	exit;
}

$funcs = new PWForms\FormOperations();
$fp = $_FILES[$id.'xmlfile']['tmp_name'];
list ($state, $msg) = $funcs->ImportXML($this, $fp, $params['import_formname'], $params['import_formalias']);

$this->Redirect($id, 'defaultadmin', '', [
	'message' => $this->_PrettyMessage($msg, $state, FALSE)]);
