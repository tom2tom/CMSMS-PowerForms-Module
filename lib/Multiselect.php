<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class Multiselect extends FieldBase
{
	var $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'Multiselect';
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
			$optionCount = count($opt);
		else
			$optionCount = 0;
		return $this->formdata->formsmodule->Lang('options',$optionCount);
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				$ret = array();
				$vals = $this->GetOptionRef('option_value');
				foreach ($this->Value as $one)
					$ret[] = $vals[$one];
				if ($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$ret);
				else
					return $ret;
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_lines_to_show'),
						$mod->CreateInputText($id,'opt_lines',
							$this->GetOption('lines','3'),10,10));
		if ($this->optionAdd) {
			$this->AddOptionElement('option_name','');
			$this->AddOptionElement('option_value','');
			$this->optionAdd = FALSE;
		}
		$names = $this->GetOptionRef('option_name');
		if ($names) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_option_name'),
				$mod->Lang('title_option_value'),
				$mod->Lang('title_select')
				);
			foreach ($names as $i=>&$one) {
				$dests[] = array(
				$mod->CreateInputText($id,'opt_option_name'.$i,$one,30,128),
				$mod->CreateInputText($id,'opt_option_value'.$i,$this->GetOptionElement('option_value',$i),30,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_multiselect_details'),$dests);
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
		$choices = $this->GetOptionRef('option_name');
		if ($choices) {
			$choices = array_flip($choices);
			if (!property_exists($this,'Value'))
				$val = array();
			elseif (!is_array($this->Value))
				$val = array($this->Value);
			else
				$val = $this->Value;

			$tmp = $this->formdata->formsmodule->CreateInputSelectList(
				$id,$this->formdata->current_prefix.$this->Id.'[]',$choices,$val,$this->GetOption('lines',3),
			 	'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		 }
		 return '';
	}

}
