<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Pulldown extends FieldBase
{
	private $optionAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->MultiChoice = TRUE;
		$this->Type = 'Pulldown';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		$ret = parent::GetMutables($nobase) + [
			'select_label' => 12,
			'sort' => 10,
		];
		$mkey1 = 'indexed_name';
		$mkey2 = 'indexed_value';
		if ($actual) {
			$opt = $this->GetPropArray($mkey1);
			if ($opt) {
				$suff = array_keys($opt);
			} else {
				return $ret;
			}
		} else {
			$suff = range(1, 10);
		}
		foreach ($suff as $one) {
			$ret[$mkey1.$one] = 12;
		}
		foreach ($suff as $one) {
			$ret[$mkey2.$one] = 12;
		}
		return $ret;
	}

	public function GetSynopsis()
	{
		$opt = $this->GetPropArray('indexed_name');
		$num = ($opt) ? count($opt) : 0;
		return $this->formdata->pwfmod->Lang('options', $num);
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->pwfmod->Lang('add_options');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->pwfmod->Lang('delete_options');
	}

	public function HasComponentAdd()
	{
		return TRUE;
	}

	public function ComponentAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	public function HasComponentDelete()
	{
		return $this->GetPropArray('indexed_name') != FALSE;
	}

	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('indexed_name', $indx);
				$this->RemovePropIndexed('indexed_value', $indx);
			}
		}
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			$ret = $this->GetPropIndexed('indexed_value', $this->Value);
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->pwfmod->Lang('unspecified'));
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

		$main[] = [$mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id, 'fp_select_label',
			  $this->GetProperty('select_label', $mod->Lang('select_one')), 25, 128)];
		$main[] = [$mod->Lang('sort_options'),
			$mod->CreateInputDropdown($id, 'fp_sort',
			  [$mod->Lang('yes')=>1, $mod->Lang('no')=>0], -1,
			  $this->GetProperty('sort', 0))];
		if ($this->optionAdd) {
			$this->AddPropIndexed('indexed_name', '');
			$this->AddPropIndexed('indexed_value', '');
			$this->optionAdd = FALSE;
		}
		$opt = $this->GetPropArray('indexed_name');
		if ($opt) {
			$dests = [];
			$dests[] = [
				$mod->Lang('title_indexed_name'),
				$mod->Lang('title_indexed_value'),
				$mod->Lang('title_select')
				];
			foreach ($opt as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = [
				$mod->CreateInputText($id, 'fp_indexed_name'.$arf, $one, 30, 128),
				$mod->CreateInputText($id, 'fp_indexed_value'.$arf, $this->GetPropIndexed('indexed_value', $i), 30, 128),
				$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
			unset($one);
			$this->MultiComponent = TRUE;
			return ['main'=>$main,'adv'=>$adv,'table'=>$dests];
		} else {
			$this->MultiComponent = FALSE;
			$main[] = ['','',$mod->Lang('missing_type', $mod->Lang('member'))];
			return ['main'=>$main,'adv'=>$adv];
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('indexed_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('indexed_value', $i)) {
					$this->RemovePropIndexed('indexed_name', $i);
					$this->RemovePropIndexed('indexed_value', $i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id, &$params)
	{
		$subjects = $this->GetPropArray('indexed_name');
		if ($subjects) {
			$choices = array_flip($subjects);
			if (count($choices) > 1 && $this->GetProperty('sort')) {
				ksort($choices);
			}
			$mod = $this->formdata->pwfmod;
			$choices = [' '.$this->GetProperty('select_label', $mod->Lang('select_one'))=>-1] + $choices;

			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}
}
