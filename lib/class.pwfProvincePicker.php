<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfProvincePicker extends pwfFieldBase
{
	var $Provinces;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'ProvincePicker';
		$this->Provinces = array(
        'Alberta'=>'AB','British Columbia'=>'BC','Manitoba'=>'MB',
		'New Brunswick'=>'NB','Newfoundland and Labrador'=>'NL',
		'Northwest Territories'=>'NT','Nova Scotia'=>'NS','Nunavut'=>'NU',
        'Ontario'=>'ON','Prince Edward Island'=>'PE','Quebec'=>'QC',
		'Saskatchewan'=>'SK','Yukon'=>'YT');
//		ksort($this->Provinces);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value,$this->Provinces);
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$choices = array_merge(array('No Default'=>''),$this->Provinces);
		$main = array(
			array($mod->Lang('title_select_default_province'),
            		$mod->CreateInputDropdown($id,'opt_default_province',
            		$choices,-1,$this->GetOption('default_province'))),
			array($mod->Lang('title_select_one_message'),
            		$mod->CreateInputText($id,'opt_select_one',
            		$this->GetOption('select_one',$mod->Lang('select_one'))))
		);
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		$choices = array_merge(array($this->GetOption('select_one',$mod->Lang('select_one'))=>''),$this->Provinces);

		if(!$this->HasValue() && $this->GetOption('default_province'))
			$this->SetValue($this->GetOption('default_province'));
		//TODO eliminate duplicate id tags 
		return $mod->CreateInputDropdown(
			$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
			$this->GetIdTag().$this->GetScript());
	}
}

?>
