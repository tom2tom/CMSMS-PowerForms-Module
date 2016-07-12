<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class EmailAddressAgain extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailAddressAgain';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('title_field_id') . ': ' . $this->GetOption('field_to_validate');
	}

	public function AdminPopulate($id)
	{
		$choices = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsInput && $one->Id != $this->Id) {
				$tn = $one->GetName();
				$choices[$tn] = $tn;
			}
		}
		unset($one);

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_field_to_validate'),
						$mod->CreateInputDropdown($id,'opt_field_to_validate',$choices,-1,
							$this->GetOption('field_to_validate')));
		$adv[] = array($mod->Lang('title_field_default_value'),
						$mod->CreateInputText($id,'opt_default',
					  		$this->GetOption('default'),25,1024));
		$adv[] = array($mod->Lang('title_clear_default'),
						$mod->CreateInputHidden($id,'opt_clear_default',0).
						$mod->CreateInputCheckbox($id,'opt_clear_default',1,
							$this->GetOption('clear_default',0)),
						$mod->Lang('help_clear_default'));
		$adv[] = array($mod->Lang('title_html5'),
						$mod->CreateInputHidden($id,'opt_html5',0).
						$mod->CreateInputCheckbox($id,'opt_html5',1,
							$this->GetOption('html5',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$this->formdata->jscripts['mailcheck'] = 'construct'; //flag to generate & include js for this type of field
		if ($this->GetOption('html5',0)) {
			$addr = ($this->HasValue()) ? $this->Value : '';
			$place = 'placeholder="'.$this->GetOption('default').'"';
		} else {
			$addr = ($this->HasValue()) ? $this->Value : $this->GetOption('default');
			$place = '';
		}
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($addr,ENT_QUOTES),25,128,
			$place.$this->GetScript());
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp,'emailaddr');
	}

	public function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		$field_to_validate = $this->GetOption('field_to_validate');

		if ($field_to_validate) {
			foreach ($this->formdata->Fields as &$one) {
				if ($one->Name == $field_to_validate) {
					if ($one->GetValue() != $this->Value) {
						$this->validated = FALSE;
						$this->ValidationMessage = $this->formdata->formsmodule->Lang('email_address_does_not_match',$field_to_validate);
					}
					break;
				}
			}
			unset($one);
		}
		return array($this->validated,$this->ValidationMessage);
	}
}
