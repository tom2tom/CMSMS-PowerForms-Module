<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPasswordField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'PasswordField';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_regex_match')=>'regex_match',
            $mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
           );
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$ro = '';
		if($this->GetOption('readonly','0') == '1')
		{
			$ro = ' readonly="readonly"';
		}
		if($this->GetOption('hide','1') == '0')
		{
			return $mod->CreateInputText($id,'pwfp_'.$this->Id,
					($this->Value?$this->Value:''),
					$this->GetOption('length'),
					255,
					$js.$ro.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->CreateInputPassword($id,'pwfp_'.$this->Id,
					($this->Value?$this->Value:''),$this->GetOption('length'),
					255,$js.$ro.$this->GetCSSIdTag());
		}
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length','80'));
		if(strlen($this->ValidationType)>0)
		{
			$ret .= ",".array_search($this->ValidationType,$this->ValidationTypes);
		}
		if($this->GetOption('readonly','0') == '1')
		{
			$ret .= ",".$mod->Lang('title_read_only');
		}
		return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
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
			array($mod->Lang('title_read_only'),
			      $mod->CreateInputHidden($module_id,'opt_readonly','0').
            $mod->CreateInputCheckbox($module_id,'opt_readonly',
            		'1',$this->GetOption('readonly','0')))
		);
		$adv = array(
			array($mod->Lang('title_field_regex'),
			      $mod->CreateInputText($module_id,'opt_regex',
							  $this->GetOption('regex'),25,1024),
			      $mod->Lang('help_regex_use')),
		);
		return array('main'=>$main,'adv'=>$adv);
	}


	function Validate()
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		switch ($this->ValidationType)
		{
		 case 'none':
			break;
		 case 'regex_match':
			if($this->Value !== FALSE &&
				!preg_match($this->GetOption('regex','/.*/'),$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		 case 'regex_nomatch':
			if($this->Value !== FALSE &&
				preg_match($this->GetOption('regex','/.*/'),$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		}
		if($this->GetOption('min_length',0) > 0 && strlen($this->Value) < $this->GetOption('min_length',0))
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('please_enter_at_least',$this->GetOption('min_length',0));
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>