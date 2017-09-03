<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Password extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'Password';
		$this->ValidationType = 'none';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_minlength')=>'length',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length', $this->GetProperty('length', '80'));
		if ($this->ValidationType) {
//			$this->EnsureArray($this->ValidationTypes);
			if (is_object($this->ValidationTypes)) {
				$this->ValidationTypes = (array)$this->ValidationTypes;
			}
			$ret .= ','.array_search($this->ValidationType, $this->ValidationTypes);
		}
		if ($this->GetProperty('readonly', 0)) {
			$ret .= ','.$mod->Lang('title_read_only');
		}
		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = [$mod->Lang('title_display_length'),
						$mod->CreateInputText($id, 'fp_length',
							$this->GetProperty('length', 12), 3, 3)];
		$main[] = [$mod->Lang('title_minimum_length'),
						$mod->CreateInputText($id, 'fp_min_length',
							$this->GetProperty('min_length', 8), 3, 3)];
		$main[] = [$mod->Lang('title_hide'),
						$mod->CreateInputHidden($id, 'fp_hide', 0).
						$mod->CreateInputCheckbox($id, 'fp_hide', 1,
							$this->GetProperty('hide', 1)),
					  $mod->Lang('title_hide_help')];
		$main[] = [$mod->Lang('title_read_only'),
						$mod->CreateInputHidden($id, 'fp_readonly', 0).
						$mod->CreateInputCheckbox($id, 'fp_readonly', 1,
							$this->GetProperty('readonly', 0))];
		$adv[] = [$mod->Lang('title_field_regex'),
						$mod->CreateInputText($id, 'fp_regex',
							$this->GetProperty('regex'), 25, 1024),
						$mod->Lang('help_regex_use')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		if ($this->GetProperty('readonly', 0)) {
			$ro = ' readonly="readonly"';
		} else {
			$ro = '';
		}

		$ln = $this->GetProperty('length', 16);
		if ($this->GetProperty('hide', 1)) {
			$tmp = $mod->CreateInputPassword($id, $this->formdata->current_prefix.$this->Id,
					($this->Value?$this->Value:''), $ln, $ln,
					$ro.$this->GetScript());
		} else {
			$tmp = $mod->CreateInputText($id, $this->formdata->current_prefix.$this->Id,
					($this->Value?$this->Value:''), $ln, $ln,
					$ro.$this->GetScript());
		}
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
		}
		$val = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		switch ($this->ValidationType) {
		 case 'none':
			break;
		 case 'length':
			$ln = $this->GetProperty('min_length', 0);
			if ($ln > 0 && strlen($this->Value) < $ln) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_at_least', $ln);
			}
			break;
		 case 'regex_match':
			if (!preg_match($this->GetProperty('regex', '/.*/'), $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_valid', $this->Name);
			}
			break;
		 case 'regex_nomatch':
			if (preg_match($this->GetProperty('regex', '/.*/'), $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_valid', $this->Name);
			}
			break;
		}
		$this->SetStatus('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
