<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPasswordAgain extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'PasswordAgain';
	}

	function GetFieldStatus()
	{
		return $this->formdata->formsmodule->Lang('title_field_id').
			': '.$this->GetOption('field_to_validate');
	}

	function AdminPopulate($id)
	{
		$choices = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->GetFieldType() == 'Password')
			{
				$tn = $one->GetName();
				$choices[$tn] = $tn;
			}
		}
		unset($one);
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array(
					$mod->Lang('title_field_to_validate'),
					$mod->CreateInputDropdown($id,'opt_field_to_validate',$choices,-1,
						$this->GetOption('field_to_validate')));
		$main[] = array($mod->Lang('title_display_length'),
					$mod->CreateInputText($id,'opt_length',
						$this->GetOption('length','12'),3,3));
		$main[] = array($mod->Lang('title_minimum_length'),
					$mod->CreateInputText($id,'opt_min_length',
						$this->GetOption('min_length','8'),3,3));
		$main[] = array($mod->Lang('title_hide'),
					$mod->CreateInputHidden($id,'opt_hide',0).
					$mod->CreateInputCheckbox($id,'opt_hide',1,
						$this->GetOption('hide',1)),
					$mod->Lang('title_hide_help'));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$ln = $this->GetOption('length',16);
		if($this->GetOption('hide',1))
		{
			$tmp = $mod->CreateInputPassword($id,$this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''),$ln,$ln,
				$this->GetScript());
		}
		else
		{
			$tmp = $mod->CreateInputText($id,$this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''),$ln,$ln,
				$this->GetScript());
		}
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp);
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		$field_to_validate = $this->GetOption('field_to_validate');
		if($field_to_validate)
		{
			foreach($this->formdata->Fields as &$one)
			{
				if($one->Name == $field_to_validate)
				{
					if($one->GetValue() != $this->Value)
					{
						$this->validated = FALSE;
						$this->ValidationMessage = $this->formdata->formsmodule->Lang('password_does_not_match',$field_to_validate);
					}
				}
			}
			unset($one);
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
