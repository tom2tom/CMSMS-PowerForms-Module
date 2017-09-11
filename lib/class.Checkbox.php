<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Checkbox extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'Checkbox';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'is_checked' => 10,
		'checked_value' => 14,
		'unchecked_value' => 14,
		'label' => 12,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$ret = ($this->GetProperty('is_checked', 0) ?
			$mod->Lang('checked_by_default'):
			$mod->Lang('unchecked_by_default'));
		return $ret;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->pwfmod;
		if ($this->Value) {
			$ret = $this->GetProperty('checked_value', $mod->Lang('value_checked'));
		} else {
			$ret = $this->GetProperty('unchecked_value', $mod->Lang('value_unchecked'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_checkbox_label'),
					$mod->CreateInputText($id, 'fp_label',
						$this->GetProperty('label'), 25, 255)];
		$main[] = [$mod->Lang('title_checked_value'),
					$mod->CreateInputText($id, 'fp_checked_value',
						$this->GetProperty('checked_value', $mod->Lang('value_checked')), 25, 255)];
		$main[] = [$mod->Lang('title_unchecked_value'),
					$mod->CreateInputText($id, 'fp_unchecked_value',
						$this->GetProperty('unchecked_value', $mod->Lang('value_unchecked')), 25, 255)];
		$main[] = [$mod->Lang('checked_by_default'),
					$mod->CreateInputHidden($id, 'fp_is_checked', 0).
					$mod->CreateInputCheckbox($id, 'fp_is_checked', 1,
						$this->GetProperty('is_checked', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		if ($this->Value || $this->GetProperty('is_checked', 0)) {
			$this->Value = 't';
		}

		$hidden = $this->formdata->pwfmod->CreateInputHidden(
			$id, $this->formdata->current_prefix.$this->Id, 0);
		$tid = $this->GetInputId();
		$tmp = $this->formdata->pwfmod->CreateInputCheckbox(
			$id, $this->formdata->current_prefix.$this->Id, 't', $this->Value,
			'id="'.$tid.'"'.$this->GetScript());
		$tmp = $this->SetClass($tmp);
		$label = $this->GetProperty('label');
		if ($label) {
			$label = '<label for="'.$tid.'">'.$label.'</label>';
			$label = '&nbsp;'.$this->SetClass($label);
		}
		return $hidden.$tmp.$label;
	}
}
