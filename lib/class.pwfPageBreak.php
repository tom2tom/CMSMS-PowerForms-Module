<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPageBreakField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Required = FALSE;
		$this->Type = 'PageBreakField';
		$this->IsSortable = FALSE;
//		$mod = $formdata->formsmodule;
//		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
	}

	function GetFieldInput($id,&$params,$return_id)
	{
	}

	function GetFieldStatus()
	{
		return '';
	}

	function PrePopulateAdminForm($module_id)
	{
		return array();
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function Validate()
	{
		return array(TRUE,'');
	}

}

?>
