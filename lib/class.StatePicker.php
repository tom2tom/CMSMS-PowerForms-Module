<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class StatePicker extends FieldBase
{
	private $States;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'StatePicker';
		$this->States = [
		'Alabama'=>'AL','Alaska'=>'AK','Arizona'=>'AZ','Arkansas'=>'AR',
		'California'=>'CA','Colorado'=>'CO','Connecticut'=>'CT','Delaware'=>'DE',
		'Florida'=>'FL','Georgia'=>'GA','Hawaii'=>'HI','Idaho'=>'ID',
		'Illinois'=>'IL','Indiana'=>'IN','Iowa'=>'IA',
		'Kansas'=>'KS','Kentucky'=>'KY','Louisiana'=>'LA','Maine'=>'ME',
		'Maryland'=>'MD','Massachusetts'=>'MA',
		'Michigan'=>'MI','Minnesota'=>'MN','Mississippi'=>'MS',
		'Missouri'=>'MO','Montana'=>'MT','Nebraska'=>'NE',
		'Nevada'=>'NV','New Hampshire'=>'NH','New Jersey'=>'NJ',
		'New Mexico'=>'NM','New York'=>'NY',
		'North Carolina'=>'NC','North Dakota'=>'ND','Ohio'=>'OH',
		'Oklahoma'=>'OK','Oregon'=>'OR',
		'Pennsylvania'=>'PA','Rhode Island'=>'RI','South Carolina'=>'SC',
		'South Dakota'=>'SD','Tennessee'=>'TN','Texas'=>'TX','Utah'=>'UT',
		'Vermont'=>'VT','Virginia'=>'VA','Washington'=>'WA',
		'District of Columbia'=>'DC','West Virginia'=>'WV','Wisconsin'=>'WI',
		'Wyoming'=>'WY'];
//		ksort($this->States);
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value, $this->States);
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

		$choices = array_merge(['No Default'=>''], $this->States);
		$main[] = [$mod->Lang('title_select_default_state'),
						$mod->CreateInputDropdown($id, 'fp_default_state', $choices, -1,
							$this->GetProperty('default_state'))];
		$main[] = [$mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id, 'fp_select_one',
							$this->GetProperty('select_one', $mod->Lang('select_one')))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;

		$choices = array_merge([$this->GetProperty('select_one', $mod->Lang('select_one'))=>-1], $this->States);
		if (!$this->HasValue() && $this->GetProperty('default_state')) {
			$this->SetValue($this->GetProperty('default_state'));
		}
		$tmp = $mod->CreateInputDropdown(
			$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
