<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Text extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'Text';
		$this->ValidationType = 'none';
		$mod = $formdata->pwfmod;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_numeric')=>'numeric',
			$mod->Lang('validation_integer')=>'integer',
			$mod->Lang('validation_usphone')=>'usphone',
			$mod->Lang('validation_email_address')=>'email',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		];
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'length' => 11,
		'size' => 11,
		'default' => 12,
		'regex' => 12,
		'clear_default' => 10,
		'readonly' => 10,
		'html5' => 10,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$ret = $mod->Lang('abbreviation_length', $this->GetProperty('length', 80));

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
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_maximum_length'),
					$mod->CreateInputText($id, 'fp_length',
						$this->GetProperty('length', 80), 3, 3)];
		$main[] = [$mod->Lang('title_display_length'),
					$mod->CreateInputText($id, 'fp_size',
						$this->GetProperty('size'), 3, 3),
					$mod->Lang('help_display_length')];
		$main[] = [$mod->Lang('title_read_only'),
					$mod->CreateInputHidden($id, 'fp_readonly', 0).
					$mod->CreateInputCheckbox($id, 'fp_readonly', 1,
						$this->GetProperty('readonly', 0))];
		$main[] = [$mod->Lang('title_field_default_value'),
					$mod->CreateInputText($id, 'fp_default',
						$this->GetProperty('default'), 25, 1024)];
		$main[] = [$mod->Lang('title_clear_default'),
					$mod->CreateInputHidden($id, 'fp_clear_default', 0).
					$mod->CreateInputCheckbox($id, 'fp_clear_default', 1,
						$this->GetProperty('clear_default', 0)),
					$mod->Lang('help_clear_default')];
		$adv[] = [$mod->Lang('title_field_regex'),
					$mod->CreateInputText($id, 'fp_regex',
						$this->GetProperty('regex'), 25, 1024),
					$mod->Lang('help_regex_use')];
		$adv[] = [$mod->Lang('title_html5'),
					$mod->CreateInputHidden($id, 'fp_html5', 0).
					$mod->CreateInputCheckbox($id, 'fp_html5', 1,
						$this->GetProperty('html5', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;

		if ($this->GetProperty('readonly', 0)) {
			$ro = ' readonly="readonly"';
		} else {
			$ro = '';
		}

		$len = $this->GetProperty('length');
		$size = $this->GetProperty('size', $len);
		if ($this->GetProperty('html5', 0)) {
			$tmp = $mod->CreateInputText(
				$id, $this->formdata->current_prefix.$this->Id, $this->Value,
				$size, $len,
				' placeholder="'.$this->GetProperty('default').'"'.$ro.$this->GetScript());
		} else {
			$js = $this->GetScript();
			if ($this->GetProperty('clear_default', 0)) {
				$js = ' onfocus="if (this.value==this.defaultValue) this.value=\'\';" onblur="if (this.value==\'\') this.value=this.defaultValue;"'.$js;
			}
			$tmp = $mod->CreateInputText(
				$id, $this->formdata->current_prefix.$this->Id,
				($this->HasValue()?$this->Value:$this->GetProperty('default')),
				$size, $len,
				$ro.$js);
		}
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		$val = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->pwfmod;
		switch ($this->ValidationType) {
		 case 'none':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
			}
			break;
		 case 'numeric':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_NUMBER_FLOAT);
			}
			if ($this->Value === '') {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_a_number', $this->Name);
			}
			break;
		 case 'integer':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_NUMBER_INT);
			}
			if ($this->Value === '') {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_an_integer', $this->Name);
			}
			break;
		 case 'email':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_EMAIL);
			}
			if ($this->Value && !preg_match($mod->email_regex, $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_an_email', $this->Name);
			}
			break;
		 case 'usphone':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
			}
			if ($this->Value &&
				!preg_match('/^([0-9][\s\.-]?)?(\(?[0-9]{3}\)?|[0-9]{3})[\s\.-]?([0-9]{3}[\s\.-]?[0-9]{4}|[a-zA-Z0-9]{7})(\s?(x|ext|ext.)\s?[a-zA-Z0-9]+)?$/',
					$this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_a_phone', $this->Name);
			}
			break;
		 case 'regex_match':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
			}
			if ($this->Value &&
				!preg_match($this->GetProperty('regex', '/.*/'), $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_valid', $this->Name);
			}
			break;
		 case 'regex_nomatch':
			if ($this->Value !== '') {
				$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
			}
			if ($this->Value &&
				preg_match($this->GetProperty('regex', '/.*/'), $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_valid', $this->Name);
			}
			break;
		}

		$lm = $this->GetProperty('length', 0);
		if ($lm && strlen($this->Value) > $lm) {
			$val = FALSE;
			$this->ValidationMessage = $mod->Lang('enter_no_longer', $lm);
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
