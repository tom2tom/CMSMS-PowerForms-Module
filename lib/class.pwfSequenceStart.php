<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSequenceStart extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'SequenceStart';
	}

	function GetFieldInput($id,&$params)
	{
		return '';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Begin FieldSequence: '.$this->Value.']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			  array($mod->Lang('title_name'),
					$mod->CreateInputText($module_id,'opt_sequencename',
					  $this->GetOption('legend'), 50)));
//TODO repeatcount
		return array('main'=>$main);
	}
}

?>
