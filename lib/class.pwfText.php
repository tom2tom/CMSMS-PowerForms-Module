<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfText extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'Text';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_numeric')=>'numeric',
            $mod->Lang('validation_integer')=>'integer',
            $mod->Lang('validation_usphone')=>'usphone',
            $mod->Lang('validation_email_address')=>'email',
            $mod->Lang('validation_regex_match')=>'regex_match',
            $mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
           );
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length',80));

		if($this->ValidationType)
		  	$ret .= ','.array_search($this->ValidationType,$this->ValidationTypes);

		if($this->GetOption('readonly',0))
			$ret .= ','.$mod->Lang('title_read_only');

		return $ret;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$ro = '';

		if($this->GetOption('readonly',0))
			$ro = ' readonly="readonly"';

		if($this->GetOption('html5',0))
		{
			return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,$this->Value,$this->GetOption('length')<25?$this->GetOption('length'):25,
				$this->GetOption('length'),
					' placeholder="'.$this->GetOption('default').'"'.$js.$ro.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,($this->HasValue()?$this->Value:$this->GetOption('default')),$this->GetOption('length')<25?$this->GetOption('length'):25,$this->GetOption('length'),
				($this->GetOption('clear_default',0)?(' onfocus="if(this.value==this.defaultValue) this.value=\'\';" onblur="if(this.value==\'\') this.value=this.defaultValue;"'):' ').$js.$ro.$this->GetCSSIdTag());
		}
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$main = array(
			array($mod->Lang('title_maximum_length'),
				$mod->CreateInputText($module_id,'opt_length',$this->GetOption('length',80),25,25)),
			array($mod->Lang('title_read_only'),
				$mod->CreateInputHidden($module_id,'opt_readonly',0).
				$mod->CreateInputCheckbox($module_id,'opt_readonly',1,$this->GetOption('readonly',0)))
		);

		$adv = array(
			array($mod->Lang('title_field_regex'),
				$mod->CreateInputText($module_id,'opt_regex',$this->GetOption('regex'),25,1024),
				$mod->Lang('help_regex_use')),
			array($mod->Lang('title_field_default_value'),
				$mod->CreateInputText($module_id,'opt_default',$this->GetOption('default'),25,1024)),
			array($mod->Lang('title_html5'),
				$mod->CreateInputHidden($module_id,'opt_html5',0).
				$mod->CreateInputCheckbox($module_id,'opt_html5',1,$this->GetOption('html5',0))),
			array($mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($module_id,'opt_clear_default',0).
				$mod->CreateInputCheckbox($module_id,'opt_clear_default',1,$this->GetOption('clear_default',0)),
				$mod->Lang('help_clear_default'))
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
		 case 'numeric':
			if($this->Value !== FALSE) $this->Value = trim($this->Value);
			if($this->Value !== FALSE &&
				!preg_match("/^([\d\.\,])+$/i",$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_a_number',$this->Name);
			}
			break;
		 case 'integer':
			if($this->Value !== FALSE) $this->Value = trim($this->Value);
			if($this->Value !== FALSE &&
				!preg_match("/^([\d])+$/i",$this->Value) ||
				intval($this->Value) != $this->Value)
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_integer',$this->Name);
			}
			break;
		 case 'email':
			if($this->Value !== FALSE) $this->Value = trim($this->Value);
			if($this->Value !== FALSE &&
				!preg_match($mod->email_regex,$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		 case 'usphone':
			if($this->Value !== FALSE) $this->Value = trim($this->Value);
			if($this->Value !== FALSE &&
				!preg_match('/^([0-9][\s\.-]?)?(\(?[0-9]{3}\)?|[0-9]{3})[\s\.-]?([0-9]{3}[\s\.-]?[0-9]{4}|[a-zA-Z0-9]{7})(\s?(x|ext|ext.)\s?[a-zA-Z0-9]+)?$/',
			$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_a_phone',$this->Name);
			}
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

		$lm = $this->GetOption('length',0);
		if($lm && strlen($this->Value) > $lm)
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('please_enter_no_longer',$lm);
		}

		return array($this->validated,$this->ValidationMessage);
	}
}

?>
