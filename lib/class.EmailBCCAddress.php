<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for an optional BCC-address input (single address, not ','-separated)

namespace PWForms;

class EmailBCCAddress extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailBCCAddress';
		$this->ValidationType = 'email';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	public function GetFieldStatus()
	{
//TODO advice about email addr
		$target = $this->GetOption('field_to_modify');
		foreach ($this->formdata->Fields as &$one) {
			if ($one->GetId() == $target) {
				$ret = $this->formdata->formsmodule->Lang('title_modifies',$one->GetName());
				unset($one);
				return $ret;
			}
		}
		unset($one);
	  	return '';
	}

	public function AdminPopulate($id)
	{
		$choices = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsDisposition() && is_subclass_of($one,'EmailBase')) {
				$txt = $one->GetName().': '.$one->GetDisplayType().
					' ('.$one->ForceAlias().')';
				$choices[$txt] = $one->GetId();
			}
		}
		unset($one);

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_field_to_modify2'),
						$mod->CreateInputDropdown($id,'opt_field_to_modify',$choices,-1,
							$this->GetOption('field_to_modify')));
		return array('main'=>$main,'adv'=>$adv);
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
		switch ($this->ValidationType) {
		 case 'email':
			$mod = $this->formdata->formsmodule;
			//no ','-separator support
			if ($this->Value && !preg_match($mod->email_regex,$this->Value)) {
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

	public function PreDisposeAction()
	{
		if (property_exists($this,'Value')) {
			foreach ($this->formdata->Fields as &$one) {
				if ($one->IsDisposition()
				 && is_subclass_of($one,'pwfEmailBase')
				 && $one->GetId() == $this->GetOption('field_to_modify'))
				{
					$bc = $one->GetOption('email_bcc_address');
					if ($bc)
						$bc .= ',';
					$bc .= $this->Value;
					$one->SetOption('email_bcc_address',$bc);
				}
			}
			unset($one);
		}
	}
}