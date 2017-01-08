<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class OzStatePicker extends FieldBase
{
	private $States;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'OzStatePicker';
		$this->States = array(
		'Australian Capital Territory'=>'ACT',
		'New South Wales'=>'NSW',
		'Northern Territory'=>'NT',
		'Queensland'=>'Qld',
		'South Australia'=>'SA',
		'Tasmania'=>'Tas',
		'Victoria'=>'Vic',
		'Western Australia'=>'WA'
		);
//		ksort($this->States);
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = array_search($this->Value, $this->States);
		if ($as_string) {
			return $ret;
		} else {
			return array($ret);
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE);
		$mod = $this->formdata->formsmodule;

		$choices = array_merge(array('No Default'=>''), $this->States);
		$main[] = array($mod->Lang('title_select_default_state'),
						$mod->CreateInputDropdown($id, 'fp_default_state', $choices, -1,
							$this->GetProperty('default_state')));
		$main[] = array($mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id, 'fp_select_one',
							$this->GetProperty('select_one', $mod->Lang('select_one'))));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;

		$choices = array_merge(array($this->GetProperty('select_one', $mod->Lang('select_one'))=>-1), $this->States);

		if (!$this->HasValue() && $this->GetProperty('default_state')) {
			$this->SetValue($this->GetProperty('default_state'));
		}

		$tmp = $mod->CreateInputDropdown(
			$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
