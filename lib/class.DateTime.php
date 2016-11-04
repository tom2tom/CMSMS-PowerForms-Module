<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
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

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'DateTime';
		$this->ValidationType = 'none';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_before')=>'before',
			$mod->Lang('validation_after')=>'after',
			$mod->Lang('validation_between')=>'between'
		);
	}

	public function GetSynopsis()
	{
		//TODO report date/time/date+time, format(s)
		$ret = '';
		if ($this->ShowDate) {
		}
		if ($this->ShowTime) {
		}
		return $ret;
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = '';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
/*
$lang['help_dateformat'] = 'A string including format characters recognised by PHP\'s date() function. For reference, please check the <a href="http://www.php.net/manual/function.date.php">php manual</a>.<br />Remember to escape any characters you don\'t want interpreted as format codes!';
$lang['help_timeformat'] = 'See advice for date format.';
$lang['title_date_only'] = 'Show date, not time';
$lang['title_dateformat'] = 'Template for formatting displayed dates';
$lang['title_high_limit'] = 'Upper-limit for the value';
$lang['title_low_limit'] = 'Lower-limit for the value';
$lang['title_time_only'] = 'Show time, not date';
$lang['title_timeformat'] = 'Template for formatting displayed times';
*/
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_date_only'),
				$mod->CreateInputHidden($id,'fp_date_only',0).
				$mod->CreateInputCheckbox($id,'fp_date_only',1,
					$this->GetProperty('date_only',0)));
		$main[] = array($mod->Lang('title_time_only'),
				$mod->CreateInputHidden($id,'fp_time_only',0).
				$mod->CreateInputCheckbox($id,'fp_time_only',1,
					$this->GetProperty('time_only',0)));

		$adv[] = array($mod->Lang('title_dateformat'),
				$mod->CreateInputText($id,'fp_date_format',
					$mod->GetPreference('date_format'),10,12),
					$mod->Lang('help_dateformat'));
		$adv[] = array($mod->Lang('title_timeformat'),
				$mod->CreateInputText($id,'fp_time_format',
					$mod->GetPreference('time_format'),10,12),
				$mod->Lang('help_timeformat'));
		$v = $this->GetProperty('low_limit',0);
		if ($v) {
			$vs = $this->GetProperty('low_value','');
//TODO convert stored stamp for display
		} else {
			$vs = '';
		}
		$adv[] = array($mod->Lang('title_low_limit'),
				$mod->CreateInputHidden($id,'fp_low_limit',0).
				$mod->CreateInputCheckbox($id,'fp_low_limit',1,$v)
				.'&nbsp;'.
				$mod->CreateInputText($id,'fp_low_value',$vs,20));
		$v = $this->GetProperty('high_limit',0);
		if ($v) {
			$vs = $this->GetProperty('high_value','');
//TODO convert stored stamp for display
		} else {
			$vs = '';
		}
		$adv[] = array($mod->Lang('title_high_limit'),
				$mod->CreateInputHidden($id,'fp_high_limit',0).
				$mod->CreateInputCheckbox($id,'fp_high_limit',1,$v)
				.'&nbsp;'.
				$mod->CreateInputText($id,'fp_high_value',$vs,20));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function AdminValidate($id)
	{
		//check date format
		//check time format
		//check low value
		//check high value
		//$this->ValidationType = func(lower-limit,upper-limit)
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$l1 = strlen($this->DateFormat);
		$l2 = strlen($this->TimeFormat);
		if ($this->ShowDate && $this->ShowTime) {
			$ln = $l1 + $l2 + 2;
		} elseif ($this->ShowDate) {
			$ln = $l1 + 2;
		} else {
			$ln = $l2 + 2;
		}
		//TODO format value
		$val = $this->Value;
		//TODO actual or fake watermark
		$tmp = $mod->CreateInputText($id,$this->formdata->current_prefix.$this->Id,
				$val,$ln,$ln,$this->GetScript());
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		$this->valid = TRUE;
		$this->ValidationMessage = '';
		if ($this->ValidationType != 'none') {
			$key = FALSE;
			$lvl = error_reporting(0);
			$dt = new \DateTime($this->Value,NULL); //TODO current timezone ok?
			error_reporting($lvl);
			if ($dt) {
				$st = $dt->getTimestamp();
				switch ($this->ValidationType) {
				 case 'before':
					if ($st > $this->HighLimit) {
						$key = 'TODO';
					}
					break;
				 case 'after':
					if ($st < $this->LowLimit) {
						$key = 'TODO';
					}
					break;
				 case 'between':
					if ($st < $this->LowLimit || $st > $this->HighLimit) {
						$key = 'TODO';
					}
					break;
				}
			} else {
				$key = 'TODO';
			}
			if ($key) {
				$this->valid = FALSE;
				$this->ValidationMessage = $this->formdata->formsmodule->Lang($key);
			}
		}
		return array($this->valid,$this->ValidationMessage);
	}
}
