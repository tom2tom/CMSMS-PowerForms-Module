<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfOzStatePicker extends pwfFieldBase
{
	var $states;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'OzStatePicker';
		$this->states = array(
		'Australian Capital Territory'=>'ACT',
		'New South Wales'=>'NSW',
		'Northern Territory'=>'NT',
		'Queensland'=>'Qld',
		'South Australia'=>'SA',
		'Tasmania'=>'Tas',
		'Victoria'=>'Vic',
		'Western Australia'=>'WA'
		);
//		ksort($this->states);
	}

	function GetFieldStatus()
	{
		return '';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value,$this->states);
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$choices = array_merge(array('No Default'=>''),$this->states);
		$main = array(
			array($mod->Lang('title_select_default_state'),
            	$mod->CreateInputDropdown($module_id,'opt_default_state',
            		$choices,-1,$this->GetOption('default_state'))),
			array($mod->Lang('title_select_one_message'),
            	$mod->CreateInputText($module_id,'opt_select_one',
            		$this->GetOption('select_one',$mod->Lang('select_one'))))
		);
		return array('main'=>$main);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		$choices = array_merge(array($this->GetOption('select_one',$mod->Lang('select_one'))=>''),$this->states);

		if(!$this->HasValue() && $this->GetOption('default_state'))
			$this->SetValue($this->GetOption('default_state'));

		return $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

}

?>
