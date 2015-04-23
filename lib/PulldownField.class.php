<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class fbPulldownField extends fbFieldBase
{
	var $optionCount;
	var $optionAdd;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'PulldownField';
		$this->DisplayInForm = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->ValidationTypes = array();
		$this->optionAdd = 0;
	}

	function array_sort_by_key($input)
	{
		if(!is_array($input)) return;
		$a1 = array();
		foreach($input as $k => $v)
		{
			$a1[$v] = $k;
		}
		asort($a1);
		$a2 = array();
		foreach($a1 as $k => $v)
		{
			$a2[$v] = $k;
		}
		return $a2;
	}

	function GetOptionAddButton()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('add_options');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('delete_options');
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
			if(substr($thisKey,0,9) == 'fbrp_sel_')
			{
				$this->RemoveOptionElement('option_name', $thisVal - $delcount);
				$this->RemoveOptionElement('option_value', $thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countItems()
	{
		$tmp = $this->GetOptionRef('option_name');
		if(is_array($tmp))
		{
			$this->optionCount = count($tmp);
		}
		elseif($tmp !== false)
		{
			$this->optionCount = 1;
		}
		else
		{
			$this->optionCount = 0;
		}
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');

		// why all this? Associative arrays are not guaranteed to preserve
		// order, except in "chronological" creation order.
		$sorted =array();
		$subjects = $this->GetOptionRef('option_name');
		if(count($subjects) > 1)
		{
			for($i=0;$i<count($subjects);$i++)
			{
				$sorted[$subjects[$i]]=($i+1);
			}
			if($this->GetOption('sort') == '1')
			{
				ksort($sorted);
			}
		}
		else
		{
			$sorted[$subjects] = '1';
		}

		if($this->GetOption('select_one','') != '')
		{
			$sorted = array(' '.$this->GetOption('select_one','')=>'') + $sorted;
		}
		else
		{
			$sorted = array(' '.$mod->Lang('select_one')=>'') + $sorted;
		}
		return $mod->CreateInputDropdown($id, 'fbrp__'.$this->Id, $sorted, -1, $this->Value,$js.$this->GetCSSIdTag());
	}

	function StatusInfo()
	{
		$mod = $this->form_ptr->module_ptr;
		$opt = $this->GetOption('option_name','');

		if(is_array($opt))
		{
			$num = count($opt);
		}
		elseif($opt != '')
		{
			$num = 1;
		}
		else
		{
          $num = 0;
        }
		$ret = $mod->Lang('options',$num);
        return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;

		$this->countItems();
		if($this->optionAdd > 0)
		{
			$this->optionCount += $this->optionAdd;
			$this->optionAdd = 0;
		}
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($formDescriptor, 'fbrp_opt_select_one',
			  $this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('sort_options'),
			$mod->CreateInputDropdown($formDescriptor,'fbrp_opt_sort',
			  array('Yes'=>1,'No'=>0),-1,
			  $this->GetOption('sort',0)));
//		$main[] = array($mod->Lang('title_pulldown_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_option_name'),
			$mod->Lang('title_option_value'),
			$mod->Lang('title_select')
			);
		$num = ($this->optionCount>1) ? $this->optionCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'fbrp_opt_option_name[]',$this->GetOptionElement('option_name',$i),30,128),
			$mod->CreateInputText($formDescriptor, 'fbrp_opt_option_value[]',$this->GetOptionElement('option_value',$i),30,128),
			$mod->CreateInputCheckbox($formDescriptor, 'fbrp_sel_'.$i, $i,-1,'style="margin-left:1em;"')
			);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->form_ptr->module_ptr;
		if($this->HasValue())
		{
			$ret = $this->GetOptionElement('option_value',($this->Value-1));
		}
		else
		{
			//$ret = $mod->Lang('unspecified');
			$ret = $this->form_ptr->GetAttr('unspecified',$mod->Lang('unspecified'));
		}
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}
}

?>
