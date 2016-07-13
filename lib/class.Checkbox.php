<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Checkbox extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type =  'Checkbox';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
		$mod->Lang('validation_none')=>'none',
		$mod->Lang('validation_must_check')=>'checked');
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = ($this->GetOption('is_checked',0)?$mod->Lang('checked_by_default'):$mod->Lang('unchecked_by_default'));
		if (strlen($this->ValidationType) > 0)
		  	$ret .= ",".array_search($this->ValidationType,$this->ValidationTypes);

		return $ret;
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		if (!property_exists($this,'Value') || !$this->Value)
			$ret = $this->GetOption('unchecked_value',$mod->Lang('value_unchecked'));
		else
			$ret = $this->GetOption('checked_value',$mod->Lang('value_checked'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_checkbox_label'),
						$mod->CreateInputText($id,'opt_label',
							$this->GetOption('label'),25,255));
		$main[] = array($mod->Lang('title_checked_value'),
						$mod->CreateInputText($id,'opt_checked_value',
							$this->GetOption('checked_value',$mod->Lang('value_checked')),25,255));
		$main[] = array($mod->Lang('title_unchecked_value'),
						$mod->CreateInputText($id,'opt_unchecked_value',
							$this->GetOption('unchecked_value',$mod->Lang('value_unchecked')),25,255));
		$main[] = array($mod->Lang('title_default_set'),
						$mod->CreateInputHidden($id,'opt_is_checked',0).
						$mod->CreateInputCheckbox($id,'opt_is_checked',1,
							$this->GetOption('is_checked',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		if ((!property_exists($this,'Value') || !$this->Value) && $this->GetOption('is_checked',0))
			$this->Value = 't';

		$tid = $this->GetInputId();
		$tmp = $this->formdata->formsmodule->CreateInputCheckbox(
			$id,$this->formdata->current_prefix.$this->Id,'t',$this->Value,
			'id="'.$tid.'"'.$this->GetScript());
		$tmp = $this->SetClass($tmp);
		$label = $this->GetOption('label');
		if ($label) {
			$label = '<label for="'.$tid.'">'.$label.'</label>';
			$label = '&nbsp;'.$this->SetClass($label);
		}
		return $tmp.$label;
	}

	public function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		switch ($this->ValidationType) {
		 case 'checked':
			if (!property_exists($this,'Value') || !$this->Value) {
				$this->validated = FALSE;
				$mod = $this->formdata->formsmodule;
				$label = $this->GetOption('label',$mod->Lang('thebox'));
				$this->ValidationMessage = $mod->Lang('you_must_check',$label);
			}
			break;
		 default:
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

}

