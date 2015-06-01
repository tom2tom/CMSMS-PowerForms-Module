<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCheckboxGroup extends pwfFieldBase
{
	var $boxAdd;
	var $boxCount;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->HasMultipleFormComponents = TRUE;
		$this->IsInput = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'CheckboxGroup';
		$this->boxAdd = FALSE;
		$this->boxCount = 0;
	}

	// Count how many boxes we have
	function countBoxes()
	{
		$tmp = $this->GetOptionRef('box_name');
		if(is_array($tmp))
	        $this->boxCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->boxCount = 1;
		else
			$this->boxCount = 0;
	}

	// Get add button label
	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_checkboxes');
	}

	// Get delete button label
	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_checkboxes');
	}

	function GetFieldStatus()
	{
		$this->countBoxes();
		return $this->formdata->formsmodule->Lang('boxes',$this->boxCount);
	}
//TODO cleanup
	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$names = $this->GetOptionRef('box_name');
		if(!is_array($names))
			$names = array($names);
		$is_set = $this->GetOptionRef('box_is_set');
		if(!is_array($is_set))
			$is_set = array($is_set);
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
			$thisBox->input = $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.$this->Id.'[]',$i,
				(($check_val !== FALSE)?$i:-1),$js.$this->GetCSSIdTag('_'.$i));

			$fieldDisp[] = $thisBox;
		}
		unset($one);
		return $fieldDisp;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		// Init names
		$names = $this->GetOptionRef('box_name');
		if(!is_array($names))
			$names = array($names);
		// Init checked boxes
		$checked = $this->GetOptionRef('box_checked');
		if(!is_array($checked))
			$checked = array($checked);
		// Init unchecked boxes
		$unchecked = $this->GetOptionRef('box_unchecked');
		if(!is_array($unchecked))
			$unchecked = array($unchecked);

		$ret = array();
		foreach($names as $i=>&$one)
		{
			if($this->FindArrayValue($i) === FALSE) //TODO sequence
			{
				if(!$this->GetOption('no_empty',0) &&
					isset($unchecked[$i]) && trim($unchecked[$i]))
					$ret[$one] = $unchecked[$i];
			}
			elseif(isset($checked[$i]) && trim($checked[$i]))
				$ret[$one] = $checked[$i];
		}
		unset($one);

		if($as_string)
		{
			// Check if we should include labels
			if($this->GetOption('include_labels',0))
			{
				$output = '';
				foreach($ret as $key=>$value)
					$output .= $key.': '.$value.$this->GetFormOption('list_delimiter',',');

				$output = substr($output,0,strlen($output)-1);
				return $output;
			}
			return join($this->GetFormOption('list_delimiter',','),$ret);
		}
		else
		{
			return $rRet;
		}
	}

	// Add action
	function DoOptionAdd(&$params)
	{
		$this->boxAdd = TRUE;
	}

	// Delete action
	function DoOptionDelete(&$params)
	{
		foreach($params as $key=>$val)
		{
			if(substr($key,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('box_name',$val);
				$this->RemoveOptionElement('box_checked',$val);
				$this->RemoveOptionElement('box_unchecked',$val);
				$this->RemoveOptionElement('box_is_set',$val);
			}
		}
	}

	// Populate tabs
	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_dont_submit_unchecked'),
			$mod->CreateInputHidden($id,'opt_no_empty','0').
			$mod->CreateInputCheckbox($id,'opt_no_empty','1',$this->GetOption('no_empty','0')),
			$mod->Lang('help_dont_submit_unchecked'));
//		$main[] = array($mod->Lang('title_checkbox_details'),$boxes);
		$boxes = array();
		$boxes[] = array(
			$mod->Lang('title_checkbox_label'),
			$mod->Lang('title_checked_value'),
			$mod->Lang('title_unchecked_value'),
			$mod->Lang('title_default_set'),
			$mod->Lang('title_select')
			);

		$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');
		$names = $this->GetOptionRef('box_name');
		foreach($names as $i=>&$one)
		{
			$boxes[] = array(
				$mod->CreateInputText($id,'opt_box_name[]',$one,30,128),
				$mod->CreateInputText($id,'opt_box_checked[]',$this->GetOptionElement('box_checked',$i),20,128),
				$mod->CreateInputText($id,'opt_box_unchecked[]',$this->GetOptionElement('box_unchecked',$i),20,128),
				$mod->CreateInputDropdown($id,'opt_box_is_set[]',$yesNo,-1,$this->GetOptionElement('box_is_set',$i)),
				$mod->CreateInputCheckbox($id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		unset($one);
		if($this->boxAdd)
		{
			$i++;
			$boxes[] = array(
				$mod->CreateInputText($id,'opt_box_name[]','',30,128),
				$mod->CreateInputText($id,'opt_box_checked[]','',20,128),
				$mod->CreateInputText($id,'opt_box_unchecked[]','',20,128),
				$mod->CreateInputDropdown($id,'opt_box_is_set[]',$yesNo,-1,'n'),
				$mod->CreateInputCheckbox($id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
			$this->boxAdd = FALSE;
		}
		
		$adv = array(
			array($mod->Lang('title_field_includelabels'),
					$mod->CreateInputHidden($id,'opt_include_labels','0').
					$mod->CreateInputCheckbox($id,'opt_include_labels','1',$this->GetOption('include_labels','0')),
					$mod->Lang('help_field_includelabels'))
		);

		return array('main'=>$main,'table'=>$boxes,'adv'=>$adv);
	}

	function PostAdminSubmitCleanup(&$params)
	{
		$names = $this->GetOptionRef('box_name');
		$checked = $this->GetOptionRef('box_checked');
		foreach($names as $i=>&$one)
		{
			if($one == '' && $checked[$i] == '')
			{
				$this->RemoveOptionElement('box_name',$i); //should be safe in loop
				$this->RemoveOptionElement('box_checked',$i);
				$this->RemoveOptionElement('box_unchecked',$i);
				$this->RemoveOptionElement('box_is_set',$i);
			}
		}
		unset($one);
		$this->countBoxes();
	}

}

?>
