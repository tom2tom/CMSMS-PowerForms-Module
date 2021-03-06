<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class DatePicker extends FieldBase
{
	private $Months;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->LabelSubComponents = FALSE;
		$this->Type = 'DatePicker';
		$mod = $formdata->pwfmod;
		$months = explode(',', $mod->Lang('all_months'));
		$this->Months = array_flip($months); //0-based
		foreach ($this->Months as $name=>&$val) {
			$val++;	//1-based
		}
		unset($val);
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'default_blank' => 10,
		'date_format' => 12,
		'date_order' => 12,
		'start_year' => 11,
		'end_year' => 11,
		'default_year' => 11,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$today = getdate();
		return $mod->Lang('date_range', [$this->GetProperty('start_year', ($today['year']-10)),
		 $this->GetProperty('end_year', ($today['year']+10))]).
		 ($this->GetProperty('default_year', '-1')!=='-1'?' ('.$this->GetProperty('default_year', '-1').')':'');
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->pwfmod;
		if ($this->HasValue()) {
			// Original:  Day,Month,Year
			//$theDate = mktime (1,1,1,$this->GetArrayValue(1), $this->GetArrayValue(0),$this->GetArrayValue(2));
			// Month,Day,Year
			//$theDate = mktime (1,1,1,$this->GetArrayValue(0), $this->GetArrayValue(1),$this->GetArrayValue(2));
			$user_order = $this->GetProperty('date_order', 'd-m-y');
			$arrUserOrder = explode("-", $user_order);
			$theDate = mktime(1, 1, 1,
				$this->GetArrayValue(array_search('m', $arrUserOrder)),
				$this->GetArrayValue(array_search('d', $arrUserOrder)),
				$this->GetArrayValue(array_search('y', $arrUserOrder)));
			$ret = date($this->GetProperty('date_format', 'j F Y'), $theDate);

			$ret = str_replace(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
					explode(',', $mod->Lang('all_months')), $ret);
			$ret = html_entity_decode($ret, ENT_QUOTES, 'UTF-8');
		} else {
			$ret = $this->GetFormProperty('unspecified', $mod->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function HasValue($deny_blank_response=FALSE)
	{
		if (!$this->Value) {
			return FALSE;
		}
		if (!is_array($this->Value)) {
			return FALSE;
		}

		if ($this->GetArrayValue(1) == '' ||
			$this->GetArrayValue(0) == '' ||
			$this->GetArrayValue(2) == '') {
			return FALSE;
		}

		return TRUE;
	}

	public function AdminPopulate($id)
	{
		$today = getdate();

		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_default_blank'),
					$mod->CreateInputHidden($id, 'fp_default_blank', 0).
					$mod->CreateInputCheckbox($id, 'fp_default_blank', 1,
						$this->GetProperty('default_blank', 0)),
					$mod->Lang('help_default_today')];
		$main[] = [$mod->Lang('title_start_year'),
					$mod->CreateInputText($id, 'fp_start_year',
						$this->GetProperty('start_year', ($today['year']-10)), 10, 10)];
		$main[] = [$mod->Lang('title_end_year'),
					$mod->CreateInputText($id, 'fp_end_year',
						$this->GetProperty('end_year', ($today['year']+10)), 10, 10)];
		$main[] = [$mod->Lang('title_default_year'),
					$mod->CreateInputText($id, 'fp_default_year',
						$this->GetProperty('default_year', '-1'), 10, 10),
					$mod->Lang('help_default_year')];
		$adv[] = [$mod->Lang('title_date_format'),
					$mod->CreateInputText($id, 'fp_date_format',
						$this->GetProperty('date_format', 'j F Y'), 25, 25),
					$mod->Lang('help_date_format')];
		$adv[] = [$mod->Lang('title_date_order'),
					$mod->CreateInputText($id, 'fp_date_order',
						$this->GetProperty('date_order', 'd-m-y'), 5, 5),
					$mod->Lang('help_date_order')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$today = getdate();

		$Days = [''=>''];
		for ($i=1; $i<32; $i++) {
			$Days[$i] = $i;
		}

		$Years = [''=>''];
		$sty = $this->GetProperty('start_year', ($today['year']-10));
		if ($sty <= 0) {
			$sty = $today['year'];
		}
		$ndy = $this->GetProperty('end_year', ($today['year']+10)) + 1;
		for ($i=$sty; $i<$ndy; $i++) {
			$Years[$i] = $i;
		}

		if ($this->HasValue()) {
			$user_order = $this->GetProperty('date_order', 'd-m-y');
			$arrUserOrder = explode("-", $user_order);

			$today['mday'] = $this->GetArrayValue(array_search('d', $arrUserOrder));
			$today['mon'] = $this->GetArrayValue(array_search('m', $arrUserOrder));
			$today['year'] = $this->GetArrayValue(array_search('y', $arrUserOrder));
		} elseif ($this->GetProperty('default_blank', 0)) {
			$today['mday']='';
			$today['mon']='';
			$today['year']='';
		} else {
			$i = $this->GetProperty('default_year', 0);
			if ($i) {
				$today['year'] = $i;
			}
		}

		$mod = $this->formdata->pwfmod;
		$js = $this->GetScript();

		$day = new \stdClass();
		$day->title = $mod->Lang('day');
		$tid = $this->GetInputId('_day');
		$tmp = '<label for="'.$tid.'">'.$day->title.'</label>';
		$day->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id, $this->formdata->current_prefix.$this->Id.'[]', $Days, -1,
			$today['mday'], 'id="'.$tid.'"'.$js);
		$day->input = $this->SetClass($tmp);

		$mon = new \stdClass();
		$tid = $this->GetInputId('_month');
		$mon->title = $mod->Lang('month');
		$tmp = '<label for="'.$tid.'">'.$mon->title.'</label>';
		$mon->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id, $this->formdata->current_prefix.$this->Id.'[]', $this->Months, -1,
			$today['mon'], 'id="'.$tid.'"'.$js);
		$mon->input = $this->SetClass($tmp);

		$yr = new \stdClass();
		$tid = $this->GetInputId('_year');
		$yr->title = $mod->Lang('year');
		$tmp = '<label for="'.$tid.'">'.$yr->title.'</label>';
		$yr->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id, $this->formdata->current_prefix.$this->Id.'[]', $Years, -1,
			$today['year'], 'id="'.$tid.'"'.$js);
		$yr->input = $this->SetClass($tmp);

		$order = ['d' => $day,'m' => $mon,'y' => $yr];
		$user_order = $this->GetProperty('date_order', 'd-m-y');
		$arrUserOrder = explode("-", $user_order);

		$ret = [];
		foreach ($arrUserOrder as $key) {
			$ret[] = $order[$key];
		}

		$this->MultiPopulate = TRUE;
		return $ret;
	}
}
