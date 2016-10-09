<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class OzStatePicker extends FieldBase
{
	var $states;

	public function __construct(&$formdata,&$params)
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

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value,$this->states);
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$choices = array_merge(array('No Default'=>''),$this->states);
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_select_default_state'),
						$mod->CreateInputDropdown($id,'opt_default_state',$choices,-1,
					  		$this->GetOption('default_state')));
		$main[] = array($mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id,'opt_select_one',
							$this->GetOption('select_one',$mod->Lang('select_one'))));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		$choices = array_merge(array($this->GetOption('select_one',$mod->Lang('select_one'))=>-1),$this->states);

		if (!$this->HasValue() && $this->GetOption('default_state'))
			$this->SetValue($this->GetOption('default_state'));

		$tmp = $mod->CreateInputDropdown(
			$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
