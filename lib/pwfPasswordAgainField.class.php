<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPasswordAgainField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'PasswordAgainField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array();
		$this->modifiesOtherFields = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');
		if($this->GetOption('hide','1') == '0')
		{
			return $mod->fbCreateInputText($id, 'pwfp__'.$this->Id,
				($this->Value?$this->Value:''),
				$this->GetOption('length'),
				255,
				$js.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->CreateInputPassword($id, 'pwfp__'.$this->Id,
				($this->Value?$this->Value:''), $this->GetOption('length'),
				255, $js.$this->GetCSSIdTag());
		}
	}

	function StatusInfo()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('title_field_id') . ': ' . $this->GetOption('field_to_validate','');
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;
		$flds = $this->form_ptr->GetFields();
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
					$mod->CreateInputDropdown($formDescriptor,
					'pwfp_opt_field_to_validate', $opts, -1, $this->GetOption('field_to_validate'))
			),
			array($mod->Lang('title_display_length'),
				$mod->CreateInputText($formDescriptor,
				'pwfp_opt_length',
				$this->GetOption('length','12'),25,25)),
			array($mod->Lang('title_minimum_length'),
				$mod->CreateInputText($formDescriptor,
				'pwfp_opt_min_length',
				$this->GetOption('min_length','8'),25,25)),
			array($mod->Lang('title_hide'),
				$mod->CreateInputHidden($formDescriptor, 'pwfp_opt_hide','0').
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_hide',
				'1',$this->GetOption('hide','1')),
				$mod->Lang('title_hide_help')),
		);

		return array('main'=>$main);
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';

		$field_to_validate = $this->GetOption('field_to_validate','');

		if($field_to_validate != '')
		{
			$mod = $this->form_ptr->module_ptr;
			foreach($this->form_ptr->Fields as &$one_field)
			{
				if($one_field->Name == $field_to_validate)
				{
					$value = $one_field->GetValue();
					if($value != $this->Value)
					{
						$this->validated = false;
						$this->validationErrorText = $mod->Lang('password_does_not_match', $field_to_validate);
					}
				}
			}
			unset ($one_field);
		}
		return array($this->validated, $this->validationErrorText);
	}
}

?>
