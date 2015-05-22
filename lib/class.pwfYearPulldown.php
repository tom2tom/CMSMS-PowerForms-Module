<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) Tapio "Stikki" Löytty <tapsa@blackmilk.fi> 
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfYearPulldown extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'YearPulldown';
	}

	function GetHumanReadableValue($as_string=TRUE)
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

	function GetFieldInput($id,&$params)
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
		$choices = array($this->GetOption('select_one',$mod->Lang('select_one'))=>'') + $choices;

		$js = $this->GetOption('javascript');
		return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
					$mod->CreateInputText($module_id,'opt_select_one',
						  $this->GetOption('select_one',$mod->Lang('select_one')),25,128));

		$main[] = array($mod->Lang('title_year_end_message'),
					$mod->CreateInputText($module_id,'opt_year_start',
						  $this->GetOption('year_start',1900),25,128));

		$main[] = array($mod->Lang('sort_options'),
					$mod->CreateInputDropdown($module_id,'opt_sort',
						  array($mod->Lang('yes')=>1,$mod->Lang('no')=>0),-1,
						  $this->GetOption('sort',0)));

		return array('main'=>$main);
	}
}

?>
