<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class DateTime extends FieldBase
{
	private $ShowDate = TRUE;
	private $ShowTime = TRUE;
	private $DateFormat = 'Y-m-d';
	private $TimeFormat = 'G:i:s';
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

	public function GetFieldStatus()
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
		list(main.adv) = $this->AdminPopulateCommon($id);
		//TODO tailor
		//checkbox date only
		//input date format
		//checkbox time only
		//input time format
		//checkbox + input for upper limit
		//checkbox + input for lower limit
		return array('main'=>$main,'adv'=>$adv);
	}

	public function AdminValidate($id)
	{
		//check date format
		//check time format
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
