<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class fbFieldsetEnd extends fbFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'FieldsetEnd';
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->ValidationTypes = array();
		$this->HasLabel = 0;
		$this->NeedsDiv = 0;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		return '</fieldset>';
	}

	function StatusInfo()
	{
		return '';
	}

	function GetHumanReadableValue($as_string=true)
	{
		// there's nothing human readable about a fieldset.
		$ret = '[End Fieldset: '.$this->Value.']';
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		return array();
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->form_ptr->module_ptr;
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_javascript'));
		$this->CheckForAdvancedTab($advArray);
	}

}

?>
