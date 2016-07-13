<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Pulldown extends FieldBase
{
	var $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'Pulldown';
	}

	public function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	public function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	public function DoOptionAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	public function DoOptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemoveOptionElement('option_name',$indx);
				$this->RemoveOptionElement('option_value',$indx);
			}
		}
	}

	public function GetFieldStatus()
	{
		$opt = $this->GetOption('option_name');
		if (is_array($opt))
			$num = count($opt);
		elseif ($opt)
			$num = 1;
		else
			$num = 0;
		return $this->formdata->formsmodule->Lang('options',$num);
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		if ($this->HasValue())
			$ret = $this->GetOptionElement('option_value',$this->Value);
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if ($as_string)
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
		$main[] = array($mod->Lang('sort_options'),
			$mod->CreateInputDropdown($id,'opt_sort',
			  array($mod->Lang('yes')=>1,$mod->Lang('no')=>0),-1,
			  $this->GetOption('sort',0)));
		if ($this->optionAdd) {
			$this->AddOptionElement('option_name','');
			$this->AddOptionElement('option_value','');
			$this->optionAdd = FALSE;
		}
		$opt = $this->GetOptionRef('option_name');
		if ($opt) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_option_name'),
				$mod->Lang('title_option_value'),
				$mod->Lang('title_select')
				);
			foreach ($opt as $i=>&$one) {
				$dests[] = array(
				$mod->CreateInputText($id,'opt_option_name'.$i,$one,30,128),
				$mod->CreateInputText($id,'opt_option_value'.$i,$this->GetOptionElement('option_value',$i),30,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_pulldown_details'),$dests);
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('item')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetOptionRef('option_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetOptionElement('option_value',$i)) {
					$this->RemoveOptionElement('option_name',$i);
					$this->RemoveOptionElement('option_value',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$subjects = $this->GetOptionRef('option_name');
		if ($subjects) {
			$choices = array_flip($subjects);
			if (count($choices) > 1 && $this->GetOption('sort'))
				ksort($choices);
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one'))=>-1) + $choices;

			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

}

