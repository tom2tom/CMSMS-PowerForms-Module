<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$feu = cms_utils::get_module('FrontEndUsers');
if (!$feu || !$feu->LoggedIn())
	return;
$this->Redirect($id, 'default', $returnid, $params);
