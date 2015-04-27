<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFromEmailAddressField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'FromEmailAddressField';
		$this->DisplayInForm = true;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->ValidationType = 'email';
		$this->modifiesOtherFields = true;
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

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array();
		$hopts = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');
		$main[] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_headers_to_modify', $hopts, -1, $this->GetOption('headers_to_modify','f')));
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

	function ModifyOtherFields()
	{
		$mod = $this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		$htm = $this->GetOption('headers_to_modify','f');

		if($this->Value !== false)
		{
			for($i=0;$i<count($others);$i++)
			{
				$replVal = '';
				if($others[$i]->IsDisposition()
			 	  && is_subclass_of($others[$i],'pwfDispositionEmailBase'))
				{
					if($htm == 'f' || $htm == 'b')
					{
						$others[$i]->SetOption('email_from_address',$this->Value);
					}
					if($htm == 'r' || $htm == 'b')
					{
						$others[$i]->SetOption('email_reply_to_address',$this->Value);
					}
				}
			}
		}
	}

	function StatusInfo()
	{
		return '';
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		$mod = $this->formdata->pwfmodule;
		switch ($this->ValidationType)
		{
		 case 'email':
			if($this->Value !== false &&
				!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $this->Value))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated, $this->validationErrorText);
	}
}

?>
