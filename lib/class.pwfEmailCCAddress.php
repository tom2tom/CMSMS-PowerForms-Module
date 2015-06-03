<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for an optional CC-address input (single address, not ','-separated)

class pwfEmailCCAddress extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->ModifiesOtherFields = TRUE;
		$this->Type = 'EmailCCAddress';
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

	function PrePopulateAdminForm($id)
	{
		$choices = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->IsDisposition() && is_subclass_of($one,'pwfEmailBase'))
			{
				$txt = $one->GetName().': '.$one->GetDisplayType().
					' ('.$one->ForceAlias().')';
				$choices[$txt] = $one->GetId();
			}
		}
		unset($one);

		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_field_to_modify'),
			$mod->CreateInputDropdown($id,'opt_field_to_modify',$choices,-1,$this->GetOption('field_to_modify')));

		return array('main'=>$main);
	}

	function ModifyOtherFields()
	{
		if($this->Value !== FALSE)
		{
			foreach($this->formdata->Fields as &$one)
			{
				if($one->IsDisposition()
               		&& is_subclass_of($one,'pwfEmailBase')
					&& $one->GetId() == $this->GetOption('field_to_modify'))
				{
					$cc = $one->GetOption('email_cc_address');
					if($cc)
						$cc .= ',';
					$cc .= $this->Value;
					$one->SetOption('email_cc_address',$cc);
				}
			}
			unset($one);
		}
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		return $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,$this->GetCSSId().$this->GetScript());
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		switch($this->ValidationType)
		{
		 case 'email':
			$mod = $this->formdata->formsmodule;
			//no ','-separator support
			if($this->Value && !preg_match($mod->email_regex,$this->Value))
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
