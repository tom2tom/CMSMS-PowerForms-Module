<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSequenceEnd extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->Type = 'SequenceEnd';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[End FieldSequence: '.$this->Value.']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		//TODO select sequence start field
/*		$main[] = array($mod->Lang(''),
						$mod->CreateInputDropdown($id,'opt_starter',
							$this->GetOption('starter'),50));
*/
		return array('main'=>$main,'adv'=>$adv);
	}

}

?>
