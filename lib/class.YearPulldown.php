<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) Tapio "Stikki" L�ytty <tapsa@blackmilk.fi>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class YearPulldown extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'YearPulldown';
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = [$mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id, 'fp_select_one',
						  $this->GetProperty('select_one', $mod->Lang('select_one')), 25, 128)];
		$main[] = [$mod->Lang('title_year_end_message'),
						$mod->CreateInputText($id, 'fp_year_start',
						  $this->GetProperty('year_start', 1900), 25, 128)];
		$main[] = [$mod->Lang('sort_options'),
						$mod->CreateInputDropdown($id, 'fp_sort',
						  [$mod->Lang('yes')=>1, $mod->Lang('no')=>0], -1,
						  $this->GetProperty('sort', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		if ($this->GetProperty('year_start')) {
			$count_from = $this->GetProperty('year_start');
		} else {
			$count_from = 1900;
		}

		$choices = [];
		for ($i=date('Y'); $i>=$count_from; $i--) {
			$choices[$i] = $i;
		}

		if ($this->GetProperty('sort')) {
			ksort($choices);
		}

		$mod = $this->formdata->formsmodule;
		$choices = [$this->GetProperty('select_one', $mod->Lang('select_one'))=>-1] + $choices;
		$tmp = $mod->CreateInputDropdown(
			$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
