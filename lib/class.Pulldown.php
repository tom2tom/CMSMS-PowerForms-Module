<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Pulldown extends FieldBase
{
	private $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiComponent = TRUE;
		$this->Type = 'Pulldown';
	}

	public function GetOptionAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	public function GetOptionDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	public function OptionAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	public function OptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('indexed_name',$indx);
				$this->RemovePropIndexed('indexed_value',$indx);
			}
		}
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue())
			$ret = $this->GetPropIndexed('indexed_value',$this->Value);
		else
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetSynopsis()
	{
		$opt = $this->GetProperty('indexed_name');
		if (is_array($opt))
			$num = count($opt);
		elseif ($opt)
			$num = 1;
		else
			$num = 0;
		return $this->formdata->formsmodule->Lang('options',$num);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,'fp_select_one',
			  $this->GetProperty('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('sort_options'),
			$mod->CreateInputDropdown($id,'fp_sort',
			  array($mod->Lang('yes')=>1,$mod->Lang('no')=>0),-1,
			  $this->GetProperty('sort',0)));
		if ($this->optionAdd) {
			$this->AddPropIndexed('indexed_name','');
			$this->AddPropIndexed('indexed_value','');
			$this->optionAdd = FALSE;
		}
		$opt = $this->GetPropArray('indexed_name');
		if ($opt) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_indexed_name'),
				$mod->Lang('title_indexed_value'),
				$mod->Lang('title_select')
				);
			foreach ($opt as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = array(
				$mod->CreateInputText($id,'fp_indexed_name'.$arf,$one,30,128),
				$mod->CreateInputText($id,'fp_indexed_value'.$arf,$this->GetPropIndexed('indexed_value',$i),30,128),
				$mod->CreateInputCheckbox($id,'selected'.$arf,1,-1,'style="display:block;margin:auto;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_pulldown_details'),$dests);
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('member')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('indexed_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('indexed_value',$i)) {
					$this->RemovePropIndexed('indexed_name',$i);
					$this->RemovePropIndexed('indexed_value',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$subjects = $this->GetPropArray('indexed_name');
		if ($subjects) {
			$choices = array_flip($subjects);
			if (count($choices) > 1 && $this->GetProperty('sort'))
				ksort($choices);
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetProperty('select_one',$mod->Lang('select_one'))=>-1) + $choices;

			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}
}
