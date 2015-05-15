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
		$this->NonRequirableField = TRUE;
		$this->Type = 'SequenceStart';
		$this->sortable = FALSE;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		return '';
	}

	function StatusInfo()
	{
		return '';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		// there's nothing human readable about a sequence of fields
		$ret = '[Begin FieldSequence: '.$this->Value.']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
			  array($mod->Lang('title_name'),
					$mod->CreateInputText($formDescriptor,'opt_sequencename',
					  $this->GetOption('legend',''), 50)));
//TODO repeatcount
		return array('main'=>$main);
	}
}

?>
