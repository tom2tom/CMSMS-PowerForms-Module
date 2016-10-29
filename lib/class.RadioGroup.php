<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class RadioGroup extends FieldBase
{
	private $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'RadioGroup';
		$this->ValidationTypes = array();
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
			foreach ($params['selected'] as $indx) {
				$this->RemovePropIndexed('button_name',$indx);
				$this->RemovePropIndexed('button_checked',$indx);
				$this->RemovePropIndexed('button_is_set',$indx);
			}
		}
	}

	public function GetFieldStatus()
	{
		$opt = $this->GetPropArray('button_name');
		if ($opt)
			$optionCount = count($opt);
		else
			$optionCount = 0;
		$ret = $this->formdata->formsmodule->Lang('options',$optionCount);
		return $ret;
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue())
		   $ret = $this->GetPropIndexed('button_checked',($this->Value - 1)); //TODO index
		else
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_radio_separator'),
						$mod->CreateInputText($id,'pdt_radio_separator',
							$this->GetProperty('radio_separator','&nbsp;&nbsp;'),15,25),
						$mod->Lang('help_radio_separator'));
		if ($this->optionAdd) {
			$this->AddPropIndexed('button_name','');
			$this->AddPropIndexed('button_checked','');
			$this->AddPropIndexed('button_is_set','y');
			$this->optionAdd = FALSE;
		}
		$names = $this->GetPropArray('button_name');
		if ($names) {
			$boxes = array();
			$boxes[] = array(
				$mod->Lang('title_radio_label'),
				$mod->Lang('title_checked_value'),
				$mod->Lang('title_default_set'),
				$mod->Lang('title_select')
				);
			$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');
			foreach ($names as $i=>&$one) {
				$boxes[] = array(
					$mod->CreateInputText($id,'pdt_button_name'.$i,$one,25,128),
					$mod->CreateInputText($id,'pdt_button_checked'.$i,$this->GetPropIndexed('button_checked',$i),25,128),
					$mod->CreateInputDropdown($id,'pdt_button_is_set'.$i,$yesNo,-1,$this->GetPropIndexed('button_is_set',$i)),
					$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				 );
			}
			unset($one);
//			$main[] = array($mod->Lang('title_radiogroup_details'),$boxes);
			return array('main'=>$main,'adv'=>$adv,'table'=>$boxes);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('member')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('button_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!($one || $this->GetPropIndexed('button_checked',$i))) {
					$this->RemovePropIndexed('button_name',$i); //should be ok in loop
					$this->RemovePropIndexed('button_checked',$i);
					$this->RemovePropIndexed('button_is_set',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$names = $this->GetPropArray('button_name');
		if ($names) {
			$ret = array();
			$mod = $this->formdata->formsmodule;
			$sep = $this->GetProperty('radio_separator','&nbsp;&nbsp;');
			$cnt = count($names);
			$b = 1;
			foreach ($names as $i=>&$one) {
				$oneset = new \stdClass();
				if ($one) {
					$oneset->title = $one;
					if ($b == $cnt) //last button
						$sep = '';
					$tmp = '<label for="'.$this->GetInputId('_'.$i).'">'.$one.'</label>'.$sep;
					$oneset->name = $this->SetClass($tmp);
				} else {
					$oneset->title = '';
					$oneset->name = '';
				}

 				$tmp = '<input type="radio" id="'.$this->GetInputId('_'.$i).'" name="'.
					$id.$this->formdata->current_prefix.$this->Id.'[]" value="'.$i.'"';

				if (($this->Value || is_numeric($this->Value)) &&
					$this->GetPropIndexed('button_checked',$i) == $this->Value) {
					$checked = TRUE;
				} elseif ($this->GetPropIndexed('button_is_set',$i) == 'y') {
					$checked = TRUE;
				} else {
					$checked = FALSE;
				}
				if ($checked)
					$tmp .= ' checked="checked"';
				$tmp .= $this->GetScript().' />';
				$oneset->input = $this->SetClass($tmp);
				$ret[] = $oneset;
				$b++;
			}
			unset($one);
			$this->MultiPopulate = TRUE;
			return $ret;
		}
		$this->MultiPopulate = FALSE;
		return '';
	}
}
