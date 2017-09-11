<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class EmailAddressAgain extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailAddressAgain';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'default' => 12,
		'field_to_validate' => 12,
		'clear_default' => 10,
		'html5' => 10,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		return $mod->Lang('title_field_id') . ': ' . $this->GetProperty('field_to_validate');
	}

	public function AdminPopulate($id)
	{
		$choices = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsInput && $one->Id != $this->Id) {
				$tn = $one->GetName();
				$choices[$tn] = $tn;
			}
		}
		unset($one);

		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_field_to_validate'),
					$mod->CreateInputDropdown($id, 'fp_field_to_validate', $choices, -1,
						$this->GetProperty('field_to_validate'))];
		$adv[] = [$mod->Lang('title_field_default_value'),
					$mod->CreateInputText($id, 'fp_default',
						$this->GetProperty('default'), 25, 1024)];
		$adv[] = [$mod->Lang('title_clear_default'),
					$mod->CreateInputHidden($id, 'fp_clear_default', 0).
					$mod->CreateInputCheckbox($id, 'fp_clear_default', 1,
						$this->GetProperty('clear_default', 0)),
					$mod->Lang('help_clear_default')];
		$adv[] = [$mod->Lang('title_html5'),
					$mod->CreateInputHidden($id, 'fp_html5', 0).
					$mod->CreateInputCheckbox($id, 'fp_html5', 1,
						$this->GetProperty('html5', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$this->SetEmailJS();
		if ($this->GetProperty('html5', 0)) {
			$addr = ($this->HasValue()) ? $this->Value : '';
			$place = 'placeholder="'.$this->GetProperty('default').'"';
		} else {
			$addr = ($this->HasValue()) ? $this->Value : $this->GetProperty('default');
			$place = '';
		}
		$tmp = $this->formdata->pwfmod->CreateInputEmail(
			$id, $this->formdata->current_prefix.$this->Id,
			htmlspecialchars($addr, ENT_QUOTES), 25, 128,
			$place.$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp, 'emailaddr');
	}

	public function Validate($id)
	{
		$val = TRUE;
		$this->ValidationMessage = '';

		$field_to_validate = $this->GetProperty('field_to_validate');

		if ($field_to_validate) {
			foreach ($this->formdata->Fields as &$one) {
				if ($one->Name == $field_to_validate) {
					if ($one->GetValue() != $this->Value) {
						$val = FALSE;
						$this->ValidationMessage = $this->formdata->pwfmod->Lang('email_address_does_not_match', $field_to_validate);
					}
					break;
				}
			}
			unset($one);
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
