<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class HTML5Number extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'HTML5Number';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'min_number'=>11,
		'max_number'=>11,
		'step_number'=>11,
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
		$main[] = [$mod->Lang('title_min_number'),
					$mod->CreateInputText($id, 'fp_min_number',
						$this->GetProperty('min_number', 0))];
		$main[] = [$mod->Lang('title_max_number'),
					$mod->CreateInputText($id, 'fp_max_number',
						$this->GetProperty('max_number', 500))];
		$main[] = [$mod->Lang('title_step_number'),
					$mod->CreateInputText($id, 'fp_step_number',
						$this->GetProperty('step_number', 50))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function AdminValidate($id)
	{
		$messages = [];
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}

		$mod = $this->formdata->pwfmod;
		$min = $this->GetProperty('min_number');
		if (!$min || !is_numeric($min)) {
			$ret = FALSE;
			$messages[] = $mod->Lang('err_typed', $mod->Lang('minimum'));
		}
		$max = $this->GetProperty('max_number');
		if (!$max || !is_numeric($max) || $max <= $min) {
			$ret = FALSE;
			$messages[] = $mod->Lang('err_typed', $mod->Lang('maximum'));
		}
		$step = $this->GetProperty('step_number');
		if (!$step || !is_numeric($step) || $step >= $max) {
			$ret = FALSE;
			$messages[] = $mod->Lang('err_typed', $mod->Lang('increment'));
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$min = $this->GetProperty('min_number', 0);
		$max = $this->GetProperty('max_number', 500);
		$step = $this->GetProperty('step_number', 50);

		$tmp = '<input type="number" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.
			'" min="'.$min.'" max="'.$max.'" step="'.$step.'"'.$this->GetScript().' />';
		return $this->SetClass($tmp);
	}
}
