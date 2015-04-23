<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCCEmailAddressField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$mod = $form_ptr->module_ptr;
		$this->Type = 'CCEmailAddressField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->ValidationType = 'email';
		$this->modifiesOtherFields = true;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');

		return $mod->fbCreateInputText($id, 'pwfp__'.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES),
           25,128,$js.$this->GetCSSIdTag(),'text');
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;
		$main = array();
		$fieldlist = array();
		$others = $this->form_ptr->GetFields();
		foreach($others as &$thisField)
		{
			if($thisField->IsDisposition()
				&& is_subclass_of($thisField,'fbDispositionEmailBase'))
			{
				$txt = $thisField->GetName().': '.$thisField->GetDisplayType();
				$alias = $thisField->GetAlias();
				if(!empty($alias))
				{
					$txt .= ' ('.$alias.')';
				}
				$fieldlist[$txt] = $thisField->GetId();
			}
		}

		unset ($thisField);
		$main[] = array($mod->Lang('title_field_to_modify'),
			$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_field_to_modify', $fieldlist, -1, $this->GetOption('field_to_modify')));

		return array('main'=>$main);
	}

	function ModifyOtherFields()
	{
		$mod = $this->form_ptr->module_ptr;
		$others = $this->form_ptr->GetFields();

		if($this->Value !== false)
		{
			for($i=0;$i<count($others);$i++)
			{
				if($others[$i]->IsDisposition()
               		&& is_subclass_of($others[$i],'fbDispositionEmailBase')
					&& $others[$i]->GetId() == $this->GetOption('field_to_modify'))
				{
					$cc = $others[$i]->GetOption('email_cc_address','');
					if(!empty($cc))
					{
						$cc .= ',';
					}
					$cc .= $this->Value;
					$others[$i]->SetOption('email_cc_address',$this->Value);
				}
			}
		}
	}

	function StatusInfo()
	{
		$mod = $this->form_ptr->module_ptr;
		$others = $this->form_ptr->GetFields();
	  	for($i=0;$i<count($others);$i++)
		{
			if($others[$i]->GetId() == $this->GetOption('field_to_modify'))
			{
				return $mod->Lang('title_modifies',$others[$i]->GetName());
			}
		}
	  	return $mod->Lang('unspecified');
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			$mod = $this->form_ptr->module_ptr;
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
