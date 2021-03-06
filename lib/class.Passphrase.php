<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Passphrase extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'Passphrase';
		$this->ValidationType = 'none';
		$mod = $formdata->pwfmod;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_minlength')=>'length',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		];
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'delay' => 11,
		'rows' => 11,
		'cols' => 11,
		'length' => 11,
		'min_length' => 11,
		'style' => 12,
		'masker' => 12,
		'regex' => 12,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$ret = $mod->Lang('abbreviation_length', $this->GetProperty('min_length', '8'));
		if ($this->ValidationType) {
			//			$this->EnsureArray($this->ValidationTypes);
			if (is_object($this->ValidationTypes)) {
				$this->ValidationTypes = (array)$this->ValidationTypes;
			}
			$ret .= ','.array_search($this->ValidationType, $this->ValidationTypes);
		}
		$ret .= ','.$mod->Lang('rows', $this->GetProperty('rows', 2)).
		','.$mod->Lang('columns', $this->GetProperty('cols', 40));
		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_minimum_length'),
					$mod->CreateInputText($id, 'fp_min_length', $this->GetProperty('min_length', 8), 3, 3)];
		$main[] = [$mod->Lang('title_textarea_rows'),
					$mod->CreateInputText($id, 'fp_rows', $this->GetProperty('rows', 2), 2, 2)];
		$main[] = [$mod->Lang('title_textarea_cols'),
					$mod->CreateInputText($id, 'fp_columns', $this->GetProperty('cols', 40), 3, 3)];
		$choices = [
		'*****'=>'all',
		'*1234'=>'credit',
		'***-**-1234'=>'ssn',
		'****1234'=>'see4',
		'*******4'=>'see1'
		];
		$main[] = [$mod->Lang('title_cloak_type'),
					$mod->CreateInputDropdown($id, 'fp_style', $choices, -1, $this->GetProperty('style', 'all'))];

		$adv[] = [$mod->Lang('title_field_regex'),
					$mod->CreateInputText($id, 'fp_regex',
							$this->GetProperty('regex'), 25, 1024),
					$mod->Lang('help_regex_use')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$baseurl = $mod->GetModuleURLPath();
		$this->formdata->Jscript->jsincs['cloak'] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery-inputCloak.min.js"></script>
EOS;
		$htmlid = $id.$this->GetInputId(); //html may get id="$id.$htmlid", or maybe not ...
		$style = $this->GetProperty('style', 'all');
		$char = $this->GetProperty('masker', '*');
		$ms = $this->GetProperty('delay', 0);
		$this->formdata->Jscript->jsloads[] = <<<EOS
 $('#{$htmlid}').inputCloak({
  type: '{$style}',
  symbol: '{$char}',
  delay: $ms
 });
EOS;
		$rows = $this->GetProperty('rows', 2) * 1.2;
		$cols = $this->GetProperty('cols', 40);
		$add = ' style="overflow:auto;height:'.$rows.'em;width:'.$cols.'em;"';

		$tmp = $mod->CreateTextArea(FALSE, $id,
			($this->Value?$this->Value:''),
			$this->formdata->current_prefix.$this->Id,
			'cloakarea', $htmlid, '', '', $cols, $rows, '', '', $add);
		$xclass = 'cloakarea';
		return $this->SetClass($tmp, $xclass);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
		}

		$val = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->pwfmod;
		switch ($this->ValidationType) {
		 case 'none':
			break;
		 case 'length':
			$length = $this->GetProperty('length');
			if (is_numeric($length) && $length > 0) {
				if (strlen($this->Value) < $length) {
					$val = FALSE;
					$this->ValidationMessage = $mod->Lang('enter_no_shorter', $length);
				}
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
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
