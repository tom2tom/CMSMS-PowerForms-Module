<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCheckboxGroup extends pwfFieldBase
{
	var $boxAdd = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->HasMultipleFormComponents = TRUE;
		$this->IsInput = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'CheckboxGroup';
	}

	// Get add-button label
	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_checkboxes');
	}

	// Get delete-button label
	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_checkboxes');
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
			if(strncmp($key,'opt_chkb',8) == 0)
			{
				$this->RemoveOptionElement('box_name',$val);
				$this->RemoveOptionElement('box_checked',$val);
				$this->RemoveOptionElement('box_unchecked',$val);
				$this->RemoveOptionElement('box_is_set',$val);
			}
		}
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOptionRef('box_name');
		if($opt)
	        $boxCount = count($opt);
		else
			$boxCount = 0;
		return $this->formdata->formsmodule->Lang('boxes',$boxCount);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$names = $this->GetOptionRef('box_name');
		if($names)
		{
			$checked = $this->GetOptionRef('box_checked');
			$unchecked = $this->GetOptionRef('box_unchecked');

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
				return $ret;
			}
		}
		return ''; //TODO upspecified
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_dont_submit_unchecked'),
			$mod->CreateInputHidden($id,'opt_no_empty',0).
			$mod->CreateInputCheckbox($id,'opt_no_empty',1,
				$this->GetOption('no_empty',0)),
			$mod->Lang('help_dont_submit_unchecked'));
//		$main[] = array($mod->Lang('title_checkbox_details'),$boxes);
		$boxes = array();
		if($this->boxAdd)
		{
			$this->AddOptionElement('box_name','');
			$this->AddOptionElement('box_checked','');
			$this->AddOptionElement('box_is_set','y');
			$this->boxAdd = FALSE;
		}
		$names = $this->GetOptionRef('box_name');
		if($names)
		{
			$boxes[] = array(
				$mod->Lang('title_checkbox_label'),
				$mod->Lang('title_checked_value'),
				$mod->Lang('title_unchecked_value'),
				$mod->Lang('title_default_set'),
				$mod->Lang('title_select')
				);
			$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');
			foreach($names as $i=>&$one)
			{
				$boxes[] = array(
					$mod->CreateInputText($id,'opt_box_name'.$i,$one,30,128),
					$mod->CreateInputText($id,'opt_box_checked'.$i,$this->GetOptionElement('box_checked',$i),20,128),
					$mod->CreateInputText($id,'opt_box_unchecked'.$i,$this->GetOptionElement('box_unchecked',$i),20,128),
					$mod->CreateInputDropdown($id,'opt_box_is_set'.$i,$yesNo,-1,$this->GetOptionElement('box_is_set',$i)),
					$mod->CreateInputCheckbox($id,'opt_chkb'.$i,$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
		}
		
		$adv = array(
			array($mod->Lang('title_field_includelabels'),
					$mod->CreateInputHidden($id,'opt_include_labels',0).
					$mod->CreateInputCheckbox($id,'opt_include_labels',1,
						$this->GetOption('include_labels',0)),
					$mod->Lang('help_field_includelabels'))
		);

		return array('main'=>$main,'table'=>$boxes,'adv'=>$adv);
	}

	function PostAdminSubmitCleanup(&$params)
	{
		$names = $this->GetOptionRef('box_name');
		if($names)
		{
			$checked = $this->GetOptionRef('box_checked');
			foreach($names as $i=>&$one)
			{
				if($one == '' && empty($checked[$i]))
				{
					$this->RemoveOptionElement('box_name',$i); //should be ok in loop
					$this->RemoveOptionElement('box_checked',$i);
					$this->RemoveOptionElement('box_unchecked',$i);
					$this->RemoveOptionElement('box_is_set',$i);
				}
			}
			unset($one);
		}
	}

	function Populate($id,&$params)
	{
		$names = $this->GetOptionRef('box_name');
		if($names)
		{
			$is_set = $this->GetOptionRef('box_is_set');
			$mod = $this->formdata->formsmodule;
			$js = $this->GetScript();
			$ret = array();
			foreach($names as $i=>&$one)
			{
				$oneset = new stdClass();
				if($one)
				{
					$oneset->title = $one;
					$oneset->name = '<label for="'.$this->GetInputId('_'.$i).'">'.$one.'</label>';
				}
				else
				{
					$oneset->title = '';
					$oneset->name = '';
				}

				if($this->Value !== FALSE)
					$check_val = $this->FindArrayValue($i); //TODO
				elseif(isset($is_set[$i]) && $is_set[$i] == 'y')
					$check_val = TRUE;
				else
					$check_val = FALSE;
				$tmp = $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.$this->Id.'[]',$i,
					(($check_val)?$i:-1),$js);
				$oneset->input = preg_replace('/id="\S+"/','id="'.$this->GetInputId('_'.$i).'"',$tmp);
				$ret[] = $oneset;
			}
			unset($one);
			return $ret;
		}
		return '';
	}

}

?>
