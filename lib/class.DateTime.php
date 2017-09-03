<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class DateTime extends FieldBase
{
	public $IsTimeStamp = TRUE;
	public $ShowDate = TRUE;
	public $ShowTime = TRUE;
	private $DateFormat = 'Y-m-d';
	private $TimeFormat = 'H:i:s';
	private $LowLimit = 0;
	private $HighLimit = 0;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'DateTime';
		$this->ValidationType = 'none';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_before')=>'before',
			$mod->Lang('validation_after')=>'after',
			$mod->Lang('validation_between')=>'between'
		];
	}

	private function CurrentFormat()
	{
		if ($this->ShowDate && $this->ShowTime) {
			$fmt = $this->DateFormat.' '.$this->TimeFormat;
		} elseif ($this->ShowDate) {
			$fmt = $this->DateFormat;
		} else {
			$fmt = $this->TimeFormat;
		}
		return trim($fmt);
	}

	public function SetProperty($propName, $propValue)
	{
		if (!is_int($propValue)) {
			$dt = new \DateTime('@0', NULL);
			$lvl = error_reporting(0);
			$res = $dt->modify($propValue);
			error_reporting($lvl);
			if ($res) {
				$propValue = $dt->getTimestamp();
			}
		}
		parent::SetProperty($propName, $propValue);
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->Value) {
			$dt = new \DateTime('@'.$this->Value, NULL);
			$fmt = $this->CurrentFormat();
			$ret = $dt->format($fmt);
		} else {
			$ret = $this->formdata->formsmodule->Lang('none2');
		}
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetSynopsis()
	{
		$dt = new \DateTime('@0', NULL);
		$fmt = $this->CurrentFormat();

		if ($this->GetProperty('low_limit')) {
			$val1 = $this->GetProperty('low_value');
			$dt->setTimestamp($val1);
			$val1 = $dt->format($fmt);
		} else {
			$val1 = FALSE;
		}
		if ($this->GetProperty('high_limit')) {
			$val2 = $this->GetProperty('high_value');
			$dt->setTimestamp($val2);
			$val2 = $dt->format($fmt);
		} else {
			$val2 = FALSE;
		}
		if ($val1 && $val2) {
			$ret = $this->formdata->formsmodule->Lang('validation_between').' '.$val1.','.$val2.', ';
		} elseif ($val1) {
			$ret = '>= '.$val1.', ';
		} elseif ($val2) {
			$ret = '<= '.$val2.', ';
		} else {
			$ret = '';
		}
		$ret .= 'as '.$fmt; //TODO lang
		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;

		$main[] = [$mod->Lang('title_date_only'),
				$mod->CreateInputHidden($id, 'fp_date_only', 0).
				$mod->CreateInputCheckbox($id, 'fp_date_only', 1,
					$this->GetProperty('date_only', 0))];
		$main[] = [$mod->Lang('title_time_only'),
				$mod->CreateInputHidden($id, 'fp_time_only', 0).
				$mod->CreateInputCheckbox($id, 'fp_time_only', 1,
					$this->GetProperty('time_only', 0))];

		$adv[] = [$mod->Lang('title_dateformat'),
				$mod->CreateInputText($id, 'fp_date_format',
					$this->GetProperty('date_format',
						$mod->GetPreference('date_format')), 10, 12),
					$mod->Lang('help_dateformat')];
		$adv[] = [$mod->Lang('title_timeformat'),
				$mod->CreateInputText($id, 'fp_time_format',
				$this->GetProperty('time_format',
					$mod->GetPreference('time_format')), 10, 12),
				$mod->Lang('help_timeformat')];
		$v = $this->GetProperty('low_limit', 0);
		if ($v) {
			$vs = $this->GetProperty('low_value', '');
			if ($vs) {
				$dt = new \DateTime('@'.$vs, NULL);
				$fmt = trim(GetPreference('date_format').' '.GetPreference('time_format'));
				$vs = $dt->format($fmt);
			}
		} else {
			$vs = '';
		}
		$adv[] = [$mod->Lang('title_low_limit'),
				$mod->CreateInputHidden($id, 'fp_low_limit', 0).
				$mod->CreateInputCheckbox($id, 'fp_low_limit', 1, $v)
				.'&nbsp;'.
				$mod->CreateInputText($id, 'fp_low_value', $vs, 20)];
		$v = $this->GetProperty('high_limit', 0);
		if ($v) {
			$vs = $this->GetProperty('high_value', '');
			if ($vs) {
				if (isset($dt)) {
					$dt->setTimestamp($vs);
				} else {
					$dt = new \DateTime('@'.$vs, NULL);
					$fmt = trim(GetPreference('date_format').' '.GetPreference('time_format'));
				}
				$vs = $dt->format($fmt);
			}
		} else {
			$vs = '';
		}
		$adv[] = [$mod->Lang('title_high_limit'),
				$mod->CreateInputHidden($id, 'fp_high_limit', 0).
				$mod->CreateInputCheckbox($id, 'fp_high_limit', 1, $v)
				.'&nbsp;'.
				$mod->CreateInputText($id, 'fp_high_value', $vs, 20)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function AdminValidate($id)
	{
		$ret = TRUE;
		$messages = [];
		$dt = new \DateTime('@'.time(), NULL);

		if (!$this->GetProperty('time_only')) {
			$fmt = $this->GetProperty('date_format');
			$lvl = error_reporting(0);
			$res = $dt->format($fmt);
			error_reporting($lvl);
			if (!$res) {
				$ret = FALSE;
				$messages[] = $this->formdata->formsmodule->Lang('err_format');
			}
		}
		if (!$this->GetProperty('date_only')) {
			$fmt = $this->GetProperty('time_format');
			$lvl = error_reporting(0);
			$res = $dt->format($fmt);
			error_reporting($lvl);
			if (!$res) {
				$ret = FALSE;
				$messages[] = $this->formdata->formsmodule->Lang('err_format');
			}
		}
		if ($this->GetProperty('low_limit')) {
			$val1 = $this->GetProperty('low_value');
		} else {
			$val1 = FALSE;
		}
		if ($this->GetProperty('high_limit')) {
			$val2 = $this->GetProperty('high_value');
		} else {
			$val2 = FALSE;
		}
		if ($val1 && $val2) {
			if ($val2 > $val1) {
				$this->ValidationType = 'between';
			} else {
				$ret = FALSE;
				$messages[] = $this->formdata->formsmodule->Lang('err_values');
			}
		} elseif ($val1) {
			$this->ValidationType = 'after';
		} elseif ($val2) {
			$this->ValidationType = 'before';
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		$baseurl = $mod->GetModuleURLPath();
		$this->formdata->Jscript->jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.watermark.min.js"></script>
EOS;
		$dt = new \DateTime('@'.time(), NULL);
		$fmt = $this->CurrentFormat();
		$example = $dt->format($fmt);
		$xl1 = strlen($example)+1;
		$example = $mod->Lang('tip_example', $example);
		$xl2 = strlen($example);

		if ($this->Value) {
			$dt->setTimestamp($this->Value);
			$val = $dt->format($fmt);
		} else {
			$val = '';
		}

		$t = $this->GetInputId();
		$this->formdata->Jscript->jsloads[] = <<<EOS
 setTimeout(function() {
  $('#{$t}').watermark();
 },10);
EOS;
		$tmp = $mod->CreateInputText($id, $this->formdata->current_prefix.$this->Id,
				$val, $xl1, $xl2, 'title="'.$example.'"'.$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$t.'"', $tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
		}
		$val = TRUE;
		$this->ValidationMessage = '';
		if ($this->ValidationType != 'none') {
			$msg = FALSE;
			$lvl = error_reporting(0);
			$dt = new \DateTime($this->Value, NULL);
			error_reporting($lvl);
			if ($dt) {
				$st = $dt->getTimestamp();
				switch ($this->ValidationType) {
				 case 'before':
					if ($st > $this->HighLimit) {
						$dt->setTimestamp($this->HighLimit);
						$fmt = $this->CurrentFormat();
						$t = $dt->format($fmt);
						$msg = $this->formdata->formsmodule->Lang('when_before', $t);
					}
					break;
				 case 'after':
					if ($st < $this->LowLimit) {
						$dt->setTimestamp($this->LowLimit);
						$fmt = $this->CurrentFormat();
						$t = $dt->format($fmt);
						$msg = $this->formdata->formsmodule->Lang('when_after', $t);
					}
					break;
				 case 'between':
					if ($st < $this->LowLimit || $st > $this->HighLimit) {
						$dt->setTimestamp($this->LowLimit);
						$fmt = $this->CurrentFormat();
						$t = $dt->format($fmt);
						$dt->setTimestamp($this->HighLimit);
						$t2 = $dt->format($fmt);
						$msg = $this->formdata->formsmodule->Lang('when_between', $t, $t2);
					}
					break;
				}
			} else {
				$msg = $this->formdata->formsmodule->Lang('err_format');
			}
			if ($msg) {
				$val = FALSE;
				$this->ValidationMessage = $msg;
			}
		}
		$this->SetStatus('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
