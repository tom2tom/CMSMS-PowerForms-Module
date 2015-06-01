<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailOne extends pwfEmailBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Required = TRUE;
		$this->Type = 'EmailOne';
		$this->ValidationType = 'email';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	function GetFieldStatus()
	{
		return $this->TemplateStatus();
	}

	function PrePopulateAdminForm($id)
	{
//		return $this->PrePopulateAdminFormCommonEmail($id); //TODO
		return array();
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		return $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,$js.$this->GetCSSIdTag());
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		if($this->Value)
		{
			list($rv,$msg) = $this->validateEmailAddr($this->Value);
			if(!$rv)
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $msg;
			}
		}
		else
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $this->formdata->formsmodule->Lang('please_enter_an_email',$this->Name);
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function Dispose($id,$returnid)
	{
		if($this->HasValue())
			return $this->SendForm($this->Value,$this->GetOption('email_subject'));
		else
			return array(TRUE,'');
	}
}

?>
