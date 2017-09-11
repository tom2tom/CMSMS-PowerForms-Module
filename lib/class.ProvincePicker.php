<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class ProvincePicker extends FieldBase
{
	public $Provinces;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'ProvincePicker';
		$this->Provinces = [
		 'Alberta'=>'AB','British Columbia'=>'BC','Manitoba'=>'MB',
		 'New Brunswick'=>'NB','Newfoundland and Labrador'=>'NL',
		 'Northwest Territories'=>'NT','Nova Scotia'=>'NS','Nunavut'=>'NU',
		 'Ontario'=>'ON','Prince Edward Island'=>'PE','Quebec'=>'QC',
		 'Saskatchewan'=>'SK','Yukon'=>'YT'];
//		ksort($this->Provinces);
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'default_province' => 12,
		'select_label' => 12,
		];
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value, $this->Provinces);
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

		$choices = array_merge(['No Default'=>''], $this->Provinces);
		$main[] = [$mod->Lang('title_select_default_province'),
					$mod->CreateInputDropdown($id, 'fp_default_province', $choices, -1,
						$this->GetProperty('default_province'))];
		$main[] = [$mod->Lang('title_select_one_message'),
					$mod->CreateInputText($id, 'fp_select_label',
						$this->GetProperty('select_label', $mod->Lang('select_one')))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;

		$choices = array_merge([$this->GetProperty('select_label', $mod->Lang('select_one'))=>-1], $this->Provinces);

		if (!$this->HasValue() && $this->GetProperty('default_province')) {
			$this->SetValue($this->GetProperty('default_province'));
		}

		$tmp = $mod->CreateInputDropdown(
			$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
