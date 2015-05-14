<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPasswordField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$mod = $formdata->pwfmodule;
		$this->Type = 'PasswordField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_regex_match')=>'regex_match',
            $mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
           );
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$ro = '';
		if($this->GetOption('readonly','0') == '1')
		{
			$ro = ' readonly="readonly"';
		}
		if($this->GetOption('hide','1') == '0')
		{
			return $mod->CreateInputText($id, 'pwfp__'.$this->Id,
					($this->Value?$this->Value:''),
					$this->GetOption('length'),
					255,
					$js.$ro.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->CreateInputPassword($id, 'pwfp__'.$this->Id,
					($this->Value?$this->Value:''), $this->GetOption('length'),
					255, $js.$ro.$this->GetCSSIdTag());
		}
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length','80'));
		if(strlen($this->ValidationType)>0)
		{
			$ret .= ", ".array_search($this->ValidationType,$this->ValidationTypes);
		}
		if($this->GetOption('readonly','0') == '1')
		{
			$ret .= ", ".$mod->Lang('title_read_only');
		}
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
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
			array($mod->Lang('title_read_only'),
			      $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_readonly','0').
            $mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_readonly',
            		'1',$this->GetOption('readonly','0')))
		);
		$adv = array(
			array($mod->Lang('title_field_regex'),
			      $mod->CreateInputText($formDescriptor, 'pwfp_opt_regex',
							  $this->GetOption('regex'),25,1024),
			      $mod->Lang('title_regex_help')),
		);
		return array('main'=>$main,'adv'=>$adv);
	}


	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		$mod = $this->formdata->pwfmodule;
		switch ($this->ValidationType)
		{
		 case 'none':
			break;
		 case 'regex_match':
			if($this->Value !== false &&
				!preg_match($this->GetOption('regex','/.*/'), $this->Value))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		 case 'regex_nomatch':
			if($this->Value !== false &&
				preg_match($this->GetOption('regex','/.*/'), $this->Value))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		}
		if($this->GetOption('min_length',0) > 0 && strlen($this->Value) < $this->GetOption('min_length',0))
		{
			$this->validated = false;
			$this->validationErrorText = $mod->Lang('please_enter_at_least',$this->GetOption('min_length',0));
		}
		return array($this->validated, $this->validationErrorText);
	}
}

?>