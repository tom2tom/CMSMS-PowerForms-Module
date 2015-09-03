<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfRadioGroup extends pwfFieldBase
{
	var $optionAdd = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'RadioGroup';
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	function DoOptionAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		if(isset($params['selected']))
		{
			foreach($params['selected'] as $indx)
			{
				$this->RemoveOptionElement('button_name',$indx);
				$this->RemoveOptionElement('button_checked',$indx);
				$this->RemoveOptionElement('button_is_set',$indx);
			}
		}
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOptionRef('button_name');
		if($opt)
			$optionCount = count($opt);
		else
			$optionCount = 0;
		$ret = $this->formdata->formsmodule->Lang('options',$optionCount);
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

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_radio_separator'),
						$mod->CreateInputText($id,'opt_radio_separator',
							$this->GetOption('radio_separator','&nbsp;&nbsp'),15,25),
						$mod->Lang('help_radio_separator'));
		if($this->optionAdd)
		{
			$this->AddOptionElement('button_name','');
			$this->AddOptionElement('button_checked','');
			$this->AddOptionElement('button_is_set','y');
			$this->optionAdd = FALSE;
		}
		$names = $this->GetOptionRef('button_name');
		if($names)
		{
			$boxes = array();
			$boxes[] = array(
				$mod->Lang('title_radio_label'),
				$mod->Lang('title_checked_value'),
				$mod->Lang('title_default_set'),
				$mod->Lang('title_select')
				);
			$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');
			foreach($names as $i=>&$one)
			{
				$boxes[] = array(
					$mod->CreateInputText($id,'opt_button_name'.$i,$one,25,128),
					$mod->CreateInputText($id,'opt_button_checked'.$i,$this->GetOptionElement('button_checked',$i),25,128),
					$mod->CreateInputDropdown($id,'opt_button_is_set'.$i,$yesNo,-1,$this->GetOptionElement('button_is_set',$i)),
					$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				 );
			}
			unset($one);
//			$main[] = array($mod->Lang('title_radiogroup_details'),$boxes);
			return array('main'=>$main,'adv'=>$adv,'table'=>$boxes);
		}
		else
		{
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('item')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetOptionRef('button_name');
		if($names)
		{
			foreach($names as $i=>&$one)
			{
				if(!($one || $this->GetOptionElement('button_checked',$i)))
				{
					$this->RemoveOptionElement('button_name',$i); //should be ok in loop
					$this->RemoveOptionElement('button_checked',$i);
					$this->RemoveOptionElement('button_is_set',$i);
				}
			}
			unset($one);
		}
	}

	function Populate($id,&$params)
	{
		$names = $this->GetOptionRef('button_name');
		if($names)
		{
			$ret = array();
			$mod = $this->formdata->formsmodule;
			$sep = $this->GetOption('radio_separator','&nbsp;&nbsp');
			$cnt = count($names);
			$b = 1;
			foreach($names as $i=>&$one)
			{
				$oneset = new stdClass();
				if($one)
				{
					$oneset->title = $one;
					if($b == $cnt) //last button
						$sep = '';
					$tmp = '<label for="'.$this->GetInputId('_'.$i).'">'.$one.'</label>'.$sep;
					$oneset->name = $this->SetClass($tmp);
				}
				else
				{
					$oneset->title = '';
					$oneset->name = '';
				}

 				$tmp = '<input type="radio" id="'.$this->GetInputId('_'.$i).'" name="'.
					$id.$this->formdata->current_prefix.$this->Id.'[]" value="'.$i.'"';
				if(property_exists($this,'Value'))
					$checked = $this->FindArrayValue($i); //TODO
				elseif($this->GetOptionElement('button_is_set',$i) == 'y')
					$checked = TRUE;
				else
					$checked = FALSE;
				if($checked)
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

?>
