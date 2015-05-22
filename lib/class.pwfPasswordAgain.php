<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPasswordAgainField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'PasswordAgainField';
		$this->ModifiesOtherFields = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		if($this->GetOption('hide','1') == '0')
		{
			return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,
				($this->Value?$this->Value:''),
				$this->GetOption('length'),
				255,
				$js.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->CreateInputPassword($id,'pwfp_'.$this->Id,
				($this->Value?$this->Value:''),$this->GetOption('length'),
				255,$js.$this->GetCSSIdTag());
		}
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('title_field_id') . ': ' . $this->GetOption('field_to_validate');
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$flds = $this->formdata->Fields;
		$opts = array();
		foreach($flds as $tf)
		{
			if($tf->GetFieldType() == 'PasswordField')
			{
				$opts[$tf->GetName()]=$tf->GetName();
			}
		}
		$main = array(
			array(
				$mod->Lang('title_field_to_validate'),
					$mod->CreateInputDropdown($module_id,
					'opt_field_to_validate',$opts,-1,$this->GetOption('field_to_validate'))
			),
			array($mod->Lang('title_display_length'),
				$mod->CreateInputText($module_id,
				'opt_length',
				$this->GetOption('length','12'),25,25)),
			array($mod->Lang('title_minimum_length'),
				$mod->CreateInputText($module_id,
				'opt_min_length',
				$this->GetOption('min_length','8'),25,25)),
			array($mod->Lang('title_hide'),
				$mod->CreateInputHidden($module_id,'opt_hide','0').
				$mod->CreateInputCheckbox($module_id,'opt_hide',
				'1',$this->GetOption('hide','1')),
				$mod->Lang('title_hide_help')),
		);

		return array('main'=>$main);
	}

	function Validate()
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
