<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCaptchaField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'CaptchaField';
		$this->DisplayInForm = true;
		$this->NonRequirableField = true;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
	}

	function GetHumanReadableValue($as_string=true)
	{
	}

	function StatusInfo()
	{
	}

	function PrePopulateAdminForm($formDescriptor)
	{
	}

	function Validate()
	{
	}
}

?>
