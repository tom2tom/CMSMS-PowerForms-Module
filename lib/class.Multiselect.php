<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Multiselect extends FieldBase
{
	private $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'Multiselect';
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	public function ComponentAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	public function ComponentDelete(&$params)
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
		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				$ret = array();
				$vals = $this->GetPropArray('indexed_value');
				foreach ($this->Value as $one)
					$ret[] = $vals[$one];
				if ($as_string)
					return implode($this->GetFormProperty('list_delimiter',','),$ret);
				else
					return $ret;
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetSynopsis()
	{
		$opt = $this->GetProperty('indexed_name');
		if (is_array($opt))
			$optionCount = count($opt);
		else
			$optionCount = 0;
		return $this->formdata->formsmodule->Lang('options',$optionCount);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_lines_to_show'),
						$mod->CreateInputText($id,'fp_lines',
							$this->GetProperty('lines','3'),10,10));
		if ($this->optionAdd) {
			$this->AddPropIndexed('indexed_name','');
			$this->AddPropIndexed('indexed_value','');
			$this->optionAdd = FALSE;
		}
		$names = $this->GetPropArray('indexed_name');
		if ($names) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_indexed_name'),
				$mod->Lang('title_indexed_value'),
				$mod->Lang('title_select')
				);
			foreach ($names as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = array(
				$mod->CreateInputText($id,'fp_indexed_name'.$arf,$one,30,128),
				$mod->CreateInputText($id,'fp_indexed_value'.$arf,$this->GetPropIndexed('indexed_value',$i),30,128),
				$mod->CreateInputCheckbox($id,'selected'.$arf,1,-1,'style="display:block;margin:auto;"')
				);
			}
			unset($one);
			$this->MultiComponent = TRUE;
			$this->HasDeleteOp = TRUE;
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests);
		} else {
			$this->MultiComponent = FALSE;
			$this->HasDeleteOp = FALSE;
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
		$choices = $this->GetPropArray('indexed_name');
		if ($choices) {
			$hidden = $this->formdata->formsmodule->CreateInputHidden(
				$id,$this->formdata->current_prefix.$this->Id,0);
			$choices = array_flip($choices);
			if (!($this->Value || is_numeric($this->Value)))
				$val = array();
			elseif (is_array($this->Value))
				$val = $this->Value;
			else
				$val = array($this->Value);

			$tmp = $this->formdata->formsmodule->CreateInputSelectList(
				$id,$this->formdata->current_prefix.$this->Id.'[]',$choices,$val,$this->GetProperty('lines',3),
			 	'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $hidden.$this->SetClass($tmp);
		 }
		 return '';
	}
}
