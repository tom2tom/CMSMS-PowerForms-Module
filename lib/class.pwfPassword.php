<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPassword extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'Password';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_minlength')=>'length',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		);
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length','80'));
		if($this->ValidationType)
			$ret .= ','.array_search($this->ValidationType,$this->ValidationTypes);
		if($this->GetOption('readonly',0))
			$ret .= ','.$mod->Lang('title_read_only');
		return $ret;
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_display_length'),
						$mod->CreateInputText($id,'opt_length',
							$this->GetOption('length',12),3,3));
		$main[] = array($mod->Lang('title_minimum_length'),
						$mod->CreateInputText($id,'opt_min_length',
							$this->GetOption('min_length',8),3,3));
		$main[] = array($mod->Lang('title_hide'),
						$mod->CreateInputHidden($id,'opt_hide',0).
						$mod->CreateInputCheckbox($id,'opt_hide',1,
							$this->GetOption('hide',1)),
					  $mod->Lang('title_hide_help'));
		$main[] = array($mod->Lang('title_read_only'),
						$mod->CreateInputHidden($id,'opt_readonly',0).
						$mod->CreateInputCheckbox($id,'opt_readonly',1,
							$this->GetOption('readonly',0)));
		$adv[] = array($mod->Lang('title_field_regex'),
						$mod->CreateInputText($id,'opt_regex',
							$this->GetOption('regex'),25,1024),
						$mod->Lang('help_regex_use'));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		if($this->GetOption('readonly',0))
			$ro = ' readonly="readonly"';
		else
			$ro = '';

		$ln = $this->GetOption('length',16);
		if($this->GetOption('hide',1))
		{
			$tmp = $mod->CreateInputPassword($id,$this->formdata->current_prefix.$this->Id,
					($this->Value?$this->Value:''),$ln,$ln,
					$ro.$this->GetScript());
		}
		else
		{
			$tmp = $mod->CreateInputText($id,$this->formdata->current_prefix.$this->Id,
					($this->Value?$this->Value:''),$ln,$ln,
					$ro.$this->GetScript());
		}
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp);
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		switch ($this->ValidationType)
		{
		 case 'none':
			break;
		 case 'length':
		 	$ln = $this->GetOption('min_length',0);
			if($ln > 0 && strlen($this->Value) < $ln)
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_at_least',$ln);
			}
			break;
		 case 'regex_match':
			if(property_exists($this,'Value') &&
				!preg_match($this->GetOption('regex','/.*/'),$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		 case 'regex_nomatch':
			if(property_exists($this,'Value') &&
				preg_match($this->GetOption('regex','/.*/'),$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_valid',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
