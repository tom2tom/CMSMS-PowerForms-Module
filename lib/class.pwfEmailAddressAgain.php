<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailAddressAgain extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->ModifiesOtherFields = FALSE;
		$this->Type = 'EmailAddressAgain';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('title_field_id') . ': ' . $this->GetOption('field_to_validate');
	}

	function PrePopulateAdminForm($id)
	{
		$choices = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->IsInput && $one->Id != $this->Id)
			{
				$tn = $one->GetName();
				$choices[$tn] = $tn;
			}
		}
		unset($one);
		$mod = $this->formdata->formsmodule;
		$main = array(
			array(
				$mod->Lang('title_field_to_validate'),
				$mod->CreateInputDropdown($id,
					'opt_field_to_validate',$choices,-1,$this->GetOption('field_to_validate'))
			)
		);
		$adv = array(
			array(
				$mod->Lang('title_field_default_value'),
				$mod->CreateInputText($id,'opt_default',$this->GetOption('default'),25,1024)),
			array(
				$mod->Lang('title_html5'),
				$mod->CreateInputHidden($id,'opt_html5',0).
				$mod->CreateInputCheckbox($id,'opt_html5',1,
					$this->GetOption('html5',0))),
			array(
				$mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($id,'opt_clear_default',0).
				$mod->CreateInputCheckbox($id,'opt_clear_default',1,
					$this->GetOption('clear_default',0)),
				$mod->Lang('help_clear_default'))
		);

		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$html5 = $this->GetOption('html5',0) ? ' placeholder="'.$this->GetOption('default').'"' : '';
		$default = $html5 ? '' : htmlspecialchars($this->GetOption('default'),ENT_QUOTES);

		return $this->formdata->formsmodule->CustomCreateInputType(
			$id,$this->formdata->current_prefix.$this->Id,
			($this->HasValue()?htmlspecialchars($this->Value,ENT_QUOTES):$default),
			25,128,$html5.$this->GetIdTag().$this->GetScript(),'email');
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
					if($$one->GetValue() != $this->Value)
					{
						$this->validated = FALSE;
						$this->ValidationMessage = $this->formdata->formsmodule->Lang('email_address_does_not_match',$field_to_validate);
					}
					break;
				}
			}
			unset($one);
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
