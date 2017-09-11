<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class CheckWhen extends Checkbox
{
	protected $defaultfmt = 'Y-m-d H:i:s';

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'CheckWhen';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'label' => 12,
		'unchecked_value' => 12,
		'dtfmt' => 12
		];
	}

	public function GetSynopsis()
	{
		$fmt = $this->GetProperty('dtfmt');
		return $this->formdata->pwfmod->Lang('checked_format', $fmt);
	}

	public function SetValue($newvalue)
	{
		if ($newvalue == 't') {
			if (!$this->Value) {
				$dt = new \DateTime('@0', NULL);
				$dt->SetTimestamp($time());
				$fmt = $this->GetProperty('dtfmt', $this->defaultfmt);
				$this->Value = $dt->format($fmt);
			}
			//otherwise, no change to stored value
			return;
		} elseif (is_string($newvalue)) {
			$dt = new \DateTime('@0', NULL);
			$lvl = error_reporting(0);
			$res = $dt->modify($newvalue);
			error_reporting($lvl);
			if ($res) {
				$fmt = $this->GetProperty('dtfmt', $this->defaultfmt);
				$this->Value = $dt->format($fmt);
				return;
			}
		}
		$this->Value = FALSE;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_checkbox_label'),
						$mod->CreateInputText($id, 'fp_label',
							$this->GetProperty('label'), 25, 255)];

		$main[] = [$mod->Lang('title_unchecked_value'),
						$mod->CreateInputText($id, 'fp_unchecked_value',
							$this->GetProperty('unchecked_value', $mod->Lang('value_unchecked')), 25, 255)];

		$adv[] = [$mod->Lang('title_dateformat'),
						$mod->CreateInputText($id, 'fp_dtfmt',
							$this->GetProperty('dtfmt', $this->defaultfmt), 25, 255),
						$this->Lang('help_date')];

		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$hidden = $this->formdata->pwfmod->CreateInputHidden(
			$id, $this->formdata->current_prefix.$this->Id, 0);
		$val = ($this->Value) ? 't' : -1;
		$tid = $this->GetInputId();
		$tmp = $this->formdata->pwfmod->CreateInputCheckbox(
			$id, $this->formdata->current_prefix.$this->Id, 't', $val,
			'id="'.$tid.'"'.$this->GetScript());
		$tmp = $this->SetClass($tmp);
		if (!$this->Value) {
			return $hidden.$tmp;
		}
		$label = '<label for="'.$tid.'">'.$this->Value.'</label>';
		$label = '&nbsp;'.$this->SetClass($label);
		return $hidden.$tmp.$label;
	}
}
