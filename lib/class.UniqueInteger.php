<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class UniqueInteger extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->Type = 'UniqueInteger';
	}

	private function generate_numbers($min, $max, $times)
	{
		$output = '';
		$array = range($min, $max);
		srand((double)microtime()*10000);
		for ($x = 0; $x < $times; $x++) {
			$i = mt_rand(1, count($array))-1;
			$output .= $array[$i];
		}
		return $output;
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'show_to_user' => 10,
		'use_random_generator' => 10,
		'numbers_to_generate' => 12,
		];
	}

/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}
*/
	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_show_to_user'),
					$mod->CreateInputHidden($id, 'fp_show_to_user', 0).
					$mod->CreateInputCheckbox($id, 'fp_show_to_user', 1,
						$this->GetProperty('show_to_user', 0))];
		$adv[] = [$mod->Lang('title_use_random_generator'),
					$mod->CreateInputHidden($id, 'fp_use_random_generator', 0).
					$mod->CreateInputCheckbox($id, 'fp_use_random_generator', 1,
						$this->GetProperty('use_random_generator', 0))];
		$adv[] = [$mod->Lang('title_numbers_to_generate'),
					$mod->CreateInputText($id, 'fp_numbers_to_generate',
						$this->GetProperty('numbers_to_generate', 5), 25, 25)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		if ($this->Value) {
			$ret = $mod->CreateInputHidden($id, $this->formdata->current_prefix.$this->Id, $this->Value);
			if ($this->GetProperty('show_to_user', 0)) {
				$ret .= $this->Value;
			}
		} elseif ($this->GetProperty('use_random_generator', 0)) {
			$times = $this->GetProperty('numbers_to_generate', 5);
			$number = $this->generate_numbers(0, 9, $times);
			$ret = $mod->CreateInputHidden($id, $this->formdata->current_prefix.$this->Id, $number);
			if ($this->GetProperty('show_to_user', 0)) {
				$ret .= $number;
			}
		} else {
			$db = \cmsms()->GetDb();
			$pre = \cms_db_prefix();
			$seq = $db->GenID($pre.'module_pwf_uniquefield_seq');
			$ret = $mod->CreateInputHidden($id, $this->formdata->current_prefix.$this->Id, $seq);
			if ($this->GetProperty('show_to_user', 0)) {
				$ret .= $seq;
			}
		}

		return $ret;
	}
}
