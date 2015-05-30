<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfRadioGroup extends pwfFieldBase
{
	var $optionAdd;
	var $optionCount;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->HasMultipleFormComponents = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'RadioGroup';
		$this->optionAdd = FALSE;
		$this->optionCount = 0;
	}

	function countBoxes()
	{
		$tmp = $this->GetOptionRef('button_name');
		if(is_array($tmp))
			$this->optionCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->optionCount = 1;
		else
			$this->optionCount = 0;
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$names = $this->GetOptionRef('button_name');
		$is_set = $this->GetOptionRef('button_is_set');
		$js = $this->GetOption('javascript');

		$fieldDisp = array();
		foreach($names as $i=>&$one)
		{
			$label = '';
			$thisBox = new stdClass();
			if($one)
			{
				$thisBox->name = '<label for="'.$this->GetCSSId('_'.$i).'">'.$one.'</label>';
				$thisBox->title = $one;
			}

			if($this->Value !== FALSE)
				$check_val = $this->FindArrayValue($i); //TODO
			elseif(isset($is_set[$i]) && $is_set[$i] == 'y')
				$check_val = TRUE;
			else
				$check_val = FALSE;

			$thisBox->input = '<input type="radio" name="'.$id.$this->formdata->current_prefix.$this->Id.'" value="'.$i.'"';
			if($check_val)
				$thisBox->input .= ' checked="checked"';
			$thisBox->input .= $js.$this->GetCSSIdTag('_'.$i).' />';
			$fieldDisp[] = $thisBox;
		}
		unset($one);
		return $fieldDisp;
	}

	function GetFieldStatus()
	{
		$this->countBoxes();
		$ret = $this->formdata->formsmodule->Lang('options',$this->optionCount);
		if($this->ValidationType)
			$ret .= ','.array_search($this->ValidationType,$this->ValidationTypes);
		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
		   $ret = $this->GetOptionElement('button_checked',($this->Value - 1)); //TODO index
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function DoOptionAdd(&$params)
	{
		$this->optionAdd = 1;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('button_name',$thisVal - $delcount);
				$this->RemoveOptionElement('button_checked',$thisVal - $delcount);
				$this->RemoveOptionElement('button_is_set',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');

		$this->countBoxes();
		if($this->optionAdd > 0)
		{
			$this->optionCount += $this->optionAdd;
			$this->optionAdd = 0;
		}
//		$main = array();
//		$main = array($mod->Lang('title_radiogroup_details'),$boxes);
		$boxes = array();
		$boxes = array(
			$mod->Lang('title_radio_label'),
			$mod->Lang('title_checked_value'),
			$mod->Lang('title_default_set'),
			$mod->Lang('title_select')
			);
		$num = ($this->optionCount>1) ? $this->optionCount:1;
		for ($i=0; $i<$num; $i++)
		{
			$boxes = array(
			  $mod->CreateInputText($module_id,'opt_button_name[]',$this->GetOptionElement('button_name',$i),25,128),
			  $mod->CreateInputText($module_id,'opt_button_checked[]',$this->GetOptionElement('button_checked',$i),25,128),
			  $mod->CreateInputDropdown($module_id,'opt_button_is_set[]',$yesNo,-1,$this->GetOptionElement('button_is_set',$i)),
			  $mod->CreateInputCheckbox($module_id,'sel_'.$i,$i,-1,'style="margin-left:1em;"')
			 );
		}

		return array('table'=>$boxes);
	}

	function PostAdminSubmitCleanup(&$params)
	{
		$names = $this->GetOptionRef('button_name');
		$checked = $this->GetOptionRef('button_checked');
		for ($i=0; $i<count($names); $i++)
		{
			if($names[$i] == '' && $checked[$i] == '')
			{
				$this->RemoveOptionElement('button_name',$i);
				$this->RemoveOptionElement('button_checked',$i);
				$this->RemoveOptionElement('button_is_set',$i);
				$i--;
			}
		}
		$this->countBoxes();
	}
}

?>
