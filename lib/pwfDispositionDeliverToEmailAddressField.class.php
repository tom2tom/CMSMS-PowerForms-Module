<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

require_once (cms_join_path(dirname(__FILE__),'DispositionEmailBase.class.php'));

class pwfDispositionDeliverToEmailAddressField extends pwfDispositionEmailBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$mod = $form_ptr->module_ptr;
		$this->Type = 'DispositionDeliverToEmailAddressField';
		$this->IsDisposition = true;
		$this->DisplayInForm = true;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->ValidationType = 'email';
		$this->modifiesOtherFields = false;
		$this->Required = 1;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');
		return $mod->fbCreateInputText($id, 'fbrp__'.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES),
           25,128,$js.$this->GetCSSIdTag());
	}

	function DisposeForm($returnid)
	{
		if($this->HasValue() != false)
		{
			return $this->SendForm($this->Value,$this->GetOption('email_subject'));
		}
		else
		{
			return array(true,'');
		}
	}

	function StatusInfo()
	{
		return $this->TemplateStatus();
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		return $this->PrePopulateAdminFormBase($formDescriptor);
	}

	function Validate()
	{
  		$this->validated = true;
  		$this->validationErrorText = '';
		$result = true;
		$message = '';
		$mod = $this->form_ptr->module_ptr;
		if($this->Value !== false &&
        	!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $this->Value))
		{
			$this->validated = false;
			$this->validationErrorText = $mod->Lang('please_enter_an_email',$this->Name);
		}

		return array($this->validated, $this->validationErrorText);
	}
}

?>
