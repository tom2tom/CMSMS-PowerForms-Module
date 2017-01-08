<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->_CheckAccess('ModifyPFForms')) {
	exit;
}

$funcs = new PWForms\FormOperations();
$funcs->Delete($this, $params['form_id']);

$this->Redirect($id, 'defaultadmin', $returnid, array(
	'message' => $this->_PrettyMessage('form_deleted')));
