<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$aeform = new pwfForm($this, $params, true);
$aeform->Delete();

$params['fbrp_message'] = $this->Lang('form_deleted');
$this->Redirect($id, 'defaultadmin', '', $params);

?>
