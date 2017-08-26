<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class PasswordAgain extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'PasswordAgain';
	}

	public function GetSynopsis()
	{
		return $this->formdata->formsmodule->Lang('title_field_id').
			': '.$this->GetProperty('field_to_validate');
	}

	public function AdminPopulate($id)
	{
		$choices = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->GetFieldType() == 'Password') {
				$tn = $one->GetName();
				$choices[$tn] = $tn;
			}
		}
		unset($one);
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = [
					$mod->Lang('title_field_to_validate'),
					$mod->CreateInputDropdown($id, 'fp_field_to_validate', $choices, -1,
						$this->GetProperty('field_to_validate'))];
		$main[] = [$mod->Lang('title_display_length'),
					$mod->CreateInputText($id, 'fp_length',
						$this->GetProperty('length', '12'), 3, 3)];
		$main[] = [$mod->Lang('title_minimum_length'),
					$mod->CreateInputText($id, 'fp_min_length',
						$this->GetProperty('min_length', '8'), 3, 3)];
		$main[] = [$mod->Lang('title_hide'),
					$mod->CreateInputHidden($id, 'fp_hide', 0).
					$mod->CreateInputCheckbox($id, 'fp_hide', 1,
						$this->GetProperty('hide', 1)),
					$mod->Lang('title_hide_help')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		$ln = $this->GetProperty('length', 16);
		if ($this->GetProperty('hide', 1)) {
			$tmp = $mod->CreateInputPassword($id, $this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''), $ln, $ln,
				$this->GetScript());
		} else {
			$tmp = $mod->CreateInputText($id, $this->formdata->current_prefix.$this->Id,
				($this->Value?$this->Value:''), $ln, $ln,
				$this->GetScript());
		}
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
		}
		$this->valid = TRUE;
		$this->ValidationMessage = '';

		$field_to_validate = $this->GetProperty('field_to_validate');
		if ($field_to_validate) {
			foreach ($this->formdata->Fields as &$one) {
				if ($one->Name == $field_to_validate) {
					if ($one->GetValue() != $this->Value) {
						$this->valid = FALSE;
						$this->ValidationMessage = $this->formdata->formsmodule->Lang('password_does_not_match', $field_to_validate);
					}
				}
			}
			unset($one);
		}
		return [$this->valid,$this->ValidationMessage];
	}
}
