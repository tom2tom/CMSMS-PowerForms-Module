<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldsetEnd extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = 0;
		$this->IsSortable = FALSE;
		$this->NeedsDiv = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'FieldsetEnd';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[End Fieldset: '.$this->Value.']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		return array();
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function Populate($id,&$params)
	{
		return '</fieldset>';
	}
}

?>
