<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPageBreakField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
//		$mod = $form_ptr->module_ptr;
		$this->Type = 'PageBreakField';
		$this->DisplayInForm = false;
		$this->Required = false;
//		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->ValidationTypes = array();
		$this->NonRequirableField = true;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $return_id)
	{
	}

	function StatusInfo()
	{
		return '';
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		return array();
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->form_ptr->module_ptr;
		// remove the "required" field
		$this->RemoveAdminField($mainArray, $mod->Lang('title_field_required'));
		$this->HiddenDispositionFields($mainArray, $advArray);
	}

	function Validate()
	{
		return array(true,'');
	}

}

?>
