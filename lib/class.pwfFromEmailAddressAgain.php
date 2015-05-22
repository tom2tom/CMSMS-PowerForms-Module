<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFromEmailAddressAgainField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'FromEmailAddressAgainField';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->ModifiesOtherFields = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$html5 = $this->GetOption('html5','0') == '1' ? ' placeholder="'.$this->GetOption('default').'"' : '';
		$default = $html5 ? '' : htmlspecialchars($this->GetOption('default'),ENT_QUOTES);

		return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,
			($this->HasValue()?htmlspecialchars($this->Value,ENT_QUOTES):$default),
			25,128,$html5.$js.$this->GetCSSIdTag(),'email');
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
			$opts[$tf->GetName()]=$tf->GetName();
		}
		$main = array(
			array(
				$mod->Lang('title_field_to_validate'),
				$mod->CreateInputDropdown($module_id,
					'opt_field_to_validate',$opts,-1,$this->GetOption('field_to_validate'))
			)
		);
		$adv = array(
			array(
				$mod->Lang('title_field_default_value'),
				$mod->CreateInputText($module_id,'opt_default',$this->GetOption('default'),25,1024)),
			array(
				$mod->Lang('title_html5'),
				$mod->CreateInputHidden($module_id,'opt_html5','0').
					$mod->CreateInputCheckbox($module_id,'opt_html5','1',$this->GetOption('html5','0'))),
			array(
				$mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($module_id,'opt_clear_default','0').
					$mod->CreateInputCheckbox($module_id,'opt_clear_default','1',$this->GetOption('clear_default','0')),
					$mod->Lang('help_clear_default'))
		);

		return array('main'=>$main,'adv'=>$adv);
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
						$this->ValidationMessage = $mod->Lang('email_address_does_not_match',$field_to_validate);
					}
				}
			}
			unset ($one_field);
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
