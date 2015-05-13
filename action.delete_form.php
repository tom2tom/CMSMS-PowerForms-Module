<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

$funcs = new pwfFormOperations();
$funcs->Delete($this,$params['form_id']);

$this->Redirect($id,'defaultadmin',$returnid,array(
	'message' => $this->PrettyMessage('form_deleted')));

?>
