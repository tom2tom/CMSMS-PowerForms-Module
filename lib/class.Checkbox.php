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
		$this->Type = 'Checkbox';
		$this->ValidationTypes = array();
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = ($this->GetProperty('is_checked',0)?$mod->Lang('checked_by_default'):$mod->Lang('unchecked_by_default'));
		return $ret;
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		if (!property_exists($this,'Value') || !$this->Value)
			$ret = $this->GetProperty('unchecked_value',$mod->Lang('value_unchecked'));
		else
			$ret = $this->GetProperty('checked_value',$mod->Lang('value_checked'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_checkbox_label'),
						$mod->CreateInputText($id,'pdt_label',
							$this->GetProperty('label'),25,255));
		$main[] = array($mod->Lang('title_checked_value'),
						$mod->CreateInputText($id,'pdt_checked_value',
							$this->GetProperty('checked_value',$mod->Lang('value_checked')),25,255));
		$main[] = array($mod->Lang('title_unchecked_value'),
						$mod->CreateInputText($id,'pdt_unchecked_value',
							$this->GetProperty('unchecked_value',$mod->Lang('value_unchecked')),25,255));
		$main[] = array($mod->Lang('title_default_set'),
						$mod->CreateInputHidden($id,'pdt_is_checked',0).
						$mod->CreateInputCheckbox($id,'pdt_is_checked',1,
							$this->GetProperty('is_checked',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		if ((!property_exists($this,'Value') || !$this->Value) && $this->GetProperty('is_checked',0))
			$this->Value = 't';

		$tid = $this->GetInputId();
		$tmp = $this->formdata->formsmodule->CreateInputCheckbox(
			$id,$this->formdata->current_prefix.$this->Id,'t',$this->Value,
			'id="'.$tid.'"'.$this->GetScript());
		$tmp = $this->SetClass($tmp);
		$label = $this->GetProperty('label');
		if ($label) {
			$label = '<label for="'.$tid.'">'.$label.'</label>';
			$label = '&nbsp;'.$this->SetClass($label);
		}
		return $tmp.$label;
	}
}
