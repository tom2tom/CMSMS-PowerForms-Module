<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class EmailOne extends EmailBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Required = TRUE;
		$this->Type = 'EmailOne';
		$this->ValidationType = 'email';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	public function GetFieldStatus()
	{
		return $this->TemplateStatus();
	}

	public function AdminPopulate($id)
	{
		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id);
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
	}

	public function Populate($id,&$params)
	{
		$this->formdata->jscripts['mailcheck'] = 'construct'; //flag to generate & include js for this type of field
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp,'emailaddr');
	}

	public function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		if ($this->Value) {
			list($rv,$msg) = $this->validateEmailAddr($this->Value);
			if (!$rv) {
				$this->validated = FALSE;
				$this->ValidationMessage = $msg;
			}
		} else {
			$this->validated = FALSE;
			$this->ValidationMessage = $this->formdata->formsmodule->Lang('please_enter_an_email',$this->Name);
		}
		return array($this->validated,$this->ValidationMessage);
	}

	public function Dispose($id,$returnid)
	{
		if ($this->HasValue())
			return $this->SendForm($this->Value,$this->GetOption('email_subject'));
		else
			return array(TRUE,'');
	}
}
