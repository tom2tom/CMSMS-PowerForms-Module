<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailBCCAddress extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->ModifiesOtherFields = TRUE;
		$this->Type = 'EmailBCCAddress';
		$this->ValidationType = 'email';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
	  	foreach($others as &$one)
		{
			if($one->GetId() == $this->GetOption('field_to_modify'))
			{
				$ret = $mod->Lang('title_modifies',$one->GetName());
				unset($one);
				return $ret;
			}
		}
		unset($one);
	  	return '';
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		return $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),
           25,128,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array();
		$fieldlist = array();
		$others = $this->formdata->Fields;
		foreach($others as &$one)
		{
			if($one->IsDisposition()
				&& is_subclass_of($one,'pwfEmailBase'))
			{
				$txt = $one->GetName().': '.$one->GetDisplayType().
					' ('.$one->ForceAlias().')';
				$fieldlist[$txt] = $one->GetId();
			}
		}
		unset ($one);

		$main[] = array($mod->Lang('title_field_to_modify'),
			$mod->CreateInputDropdown($module_id,'opt_field_to_modify',$fieldlist,-1,$this->GetOption('field_to_modify')));

		return array('main'=>$main);
	}

	function ModifyOtherFields()
	{
		if($this->Value !== FALSE)
		{
			$others = $this->formdata->Fields;
			foreach($others as &$one)
			{
				if($one->IsDisposition()
               		&& is_subclass_of($one,'pwfEmailBase')
					&& $one->GetId() == $this->GetOption('field_to_modify'))
				{
					$cc = $one->GetOption('email_cc_address');
					if(!empty($cc))
						$cc .= ',';
					$cc .= $this->Value;
					$one->SetOption('email_cc_address',$this->Value);
				}
			}
			unset($one);
		}
	}

	function Validate()
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			$mod = $this->formdata->formsmodule;
			if($this->Value !== FALSE &&
				!preg_match($mod->email_regex,$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
