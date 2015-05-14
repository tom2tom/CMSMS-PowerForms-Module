<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFromEmailAddressAgainField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'FromEmailAddressAgainField';
		$this->DisplayInForm = true;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->modifiesOtherFields = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$html5 = $this->GetOption('html5','0') == '1' ? ' placeholder="'.$this->GetOption('default').'"' : '';
		$default = $html5 ? '' : htmlspecialchars($this->GetOption('default'), ENT_QUOTES);

		return $mod->CustomCreateInputText($id, 'pwfp__'.$this->Id,
			($this->HasValue()?htmlspecialchars($this->Value, ENT_QUOTES):$default),
			25,128,$html5.$js.$this->GetCSSIdTag(),'email');
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->Lang('title_field_id') . ': ' . $this->GetOption('field_to_validate','');
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$flds = $this->formdata->GetFields();
		$opts = array();
		foreach($flds as $tf)
		{
			$opts[$tf->GetName()]=$tf->GetName();
		}
		$main = array(
			array(
				$mod->Lang('title_field_to_validate'),
				$mod->CreateInputDropdown($formDescriptor,
					'pwfp_opt_field_to_validate', $opts, -1, $this->GetOption('field_to_validate'))
			)
		);
		$adv = array(
			array(
				$mod->Lang('title_field_default_value'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_default',$this->GetOption('default'),25,1024)),
			array(
				$mod->Lang('title_html5'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_html5','0').
					$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_html5','1',$this->GetOption('html5','0'))),
			array(
				$mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_clear_default','0').
					$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_clear_default','1',$this->GetOption('clear_default','0')),
					$mod->Lang('help_clear_default'))
		);

		return array('main'=>$main,'adv'=>$adv);
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';

		$field_to_validate = $this->GetOption('field_to_validate','');

		if($field_to_validate != '')
		{
			$mod = $this->formdata->pwfmodule;
			foreach($this->formdata->Fields as &$one_field)
			{
				if($one_field->Name == $field_to_validate)
				{
					$value = $one_field->GetValue();
					if($value != $this->Value)
					{
						$this->validated = false;
						$this->validationErrorText = $mod->Lang('email_address_does_not_match', $field_to_validate);
					}
				}
			}
			unset ($one_field);
		}
		return array($this->validated, $this->validationErrorText);
	}
}

?>