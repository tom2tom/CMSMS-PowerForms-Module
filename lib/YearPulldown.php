<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) Tapio "Stikki" L�ytty <tapsa@blackmilk.fi> 
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class YearPulldown extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'YearPulldown';
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
			$ret = $this->Value;
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id,'opt_select_one',
						  $this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_year_end_message'),
						$mod->CreateInputText($id,'opt_year_start',
						  $this->GetOption('year_start',1900),25,128));
		$main[] = array($mod->Lang('sort_options'),
						$mod->CreateInputDropdown($id,'opt_sort',
						  array($mod->Lang('yes')=>1,$mod->Lang('no')=>0),-1,
						  $this->GetOption('sort',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		if($this->GetOption('year_start'))
			$count_from = $this->GetOption('year_start');
		else
			$count_from = 1900;

		$choices = array();
		for($i=date('Y'); $i>=$count_from; $i--)
			$choices[$i] = $i;

		if($this->GetOption('sort'))
			ksort($choices);

		$mod = $this->formdata->formsmodule;
		$choices = array($this->GetOption('select_one',$mod->Lang('select_one'))=>-1) + $choices;
		$tmp = $mod->CreateInputDropdown(
			$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}

}
