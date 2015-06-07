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
		$this->Type = 'PasswordAgain';
	}

	function GetFieldStatus()
	{
		return $this->formdata->formsmodule->Lang('title_field_id').
			': '.$this->GetOption('field_to_validate');
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$flds = $this->formdata->Fields;
		$opts = array();
		foreach($flds as $one)
		{
			if($one->GetFieldType() == 'Password')
			{
				$tn = $one->GetName();
				$opts[$tn] = $tn;
			}
		}
		$main = array(
			array(
				$mod->Lang('title_field_to_validate'),
					$mod->CreateInputDropdown($id,
					'opt_field_to_validate',$opts,-1,$this->GetOption('field_to_validate'))
			),
			array($mod->Lang('title_display_length'),
				$mod->CreateInputText($id,
				'opt_length',
				$this->GetOption('length','12'),25,25)),
			array($mod->Lang('title_minimum_length'),
				$mod->CreateInputText($id,
				'opt_min_length',
				$this->GetOption('min_length','8'),25,25)),
			array($mod->Lang('title_hide'),
				$mod->CreateInputHidden($id,'opt_hide',0).
				$mod->CreateInputCheckbox($id,'opt_hide',1,
					$this->GetOption('hide','1')),
				$mod->Lang('title_hide_help')),
		);

		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		if($this->GetOption('hide',1))
		{
			$tmp = $mod->CreateInputPassword($id,$this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''),
				$this->GetOption('length',16),255,
				$this->GetScript());
		}
		else
		{
			$tmp = $mod->CreateInputText($id,$this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''),
				$this->GetOption('length',16),255,
				$this->GetScript());
		}
		return preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		$field_to_validate = $this->GetOption('field_to_validate');

		if($field_to_validate)
		{
			$mod = $this->formdata->formsmodule;
			foreach($this->formdata->Fields as &$one_field)
			{
				if($one_field->Name == $field_to_validate)
				{
					$value = $one_field->GetValue();
					if($value != $this->Value)
					{
						$this->validated = FALSE;
						$this->ValidationMessage = $mod->Lang('password_does_not_match',$field_to_validate);
					}
				}
			}
			unset ($one_field);
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
