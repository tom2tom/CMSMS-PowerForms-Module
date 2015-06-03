<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfStatePicker extends pwfFieldBase
{
	var $States;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'StatePicker';
		$this->States = array(
        'Alabama'=>'AL','Alaska'=>'AK','Arizona'=>'AZ','Arkansas'=>'AR',
        'California'=>'CA','Colorado'=>'CO','Connecticut'=>'CT','Delaware'=>'DE',
        'Florida'=>'FL','Georgia'=>'GA','Hawaii'=>'HI','Idaho'=>'ID',
        'Illinois'=>'IL','Indiana'=>'IN','Iowa'=>'IA',
        'Kansas'=>'KS','Kentucky'=>'KY','Louisiana'=>'LA','Maine'=>'ME',
        'Maryland'=>'MD','Massachusetts'=>'MA',
        'Michigan'=>'MI','Minnesota'=>'MN','Mississippi'=>'MS',
        'Missouri'=>'MO','Montana'=>'MT','Nebraska'=>'NE',
        'Nevada'=>'NV','New Hampshire'=>'NH','New Jersey'=>'NJ',
        'New Mexico'=>'NM','New York'=>'NY',
        'North Carolina'=>'NC','North Dakota'=>'ND','Ohio'=>'OH',
        'Oklahoma'=>'OK','Oregon'=>'OR',
        'Pennsylvania'=>'PA','Rhode Island'=>'RI','South Carolina'=>'SC',
        'South Dakota'=>'SD','Tennessee'=>'TN','Texas'=>'TX','Utah'=>'UT',
        'Vermont'=>'VT','Virginia'=>'VA','Washington'=>'WA',
        'District of Columbia'=>'DC','West Virginia'=>'WV','Wisconsin'=>'WI',
        'Wyoming'=>'WY');
//		ksort($this->States);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value,$this->States);
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$choices = array_merge(array('No Default'=>''),$this->States);
		$main = array(
			array($mod->Lang('title_select_default_state'),
            	$mod->CreateInputDropdown($id,'opt_default_state',
            		$choices,-1,$this->GetOption('default_state'))),
			array($mod->Lang('title_select_one_message'),
            	$mod->CreateInputText($id,'opt_select_one',
            		$this->GetOption('select_one',$mod->Lang('select_one'))))
		);
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		$choices = array_merge(array($this->GetOption('select_one',$mod->Lang('select_one'))=>''),$this->States);
		if(!$this->HasValue() && $this->GetOption('default_state'))
			$this->SetValue($this->GetOption('default_state'));
		//TODO eliminate duplicate id tags 
		return $mod->CreateInputDropdown(
			$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
			$this->GetIdTag().$this->GetScript());
	}
}

?>
