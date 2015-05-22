<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfMultiselectField extends pwfFieldBase
{
	var $optionCount;
	var $optionAdd;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'MultiselectField';
		$this->optionAdd = 0;
	}

	function GetOptionAddButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('add_options');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('delete_options');
	}

	function DoOptionAdd(&$params)
	{
		$this->optionAdd = 2;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('option_name',$thisVal - $delcount);
				$this->RemoveOptionElement('option_value',$thisVal - $delcount);
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
		elseif($tmp !== FALSE)
		{
			$this->optionCount = 1;
		}
		else
		{
			$this->optionCount = 0;
		}
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		// why all this? Associative arrays are not guaranteed to preserve
		// order,except in "chronological" creation order.
		$sorted = array();
		$subjects = $this->GetOptionRef('option_name');

		if(count($subjects) > 1)
		{
			for($i=0; $i<count($subjects); $i++)
			{
				$sorted[$subjects[$i]]=($i+1);
			}
		}
		else
		{
			$sorted[$subjects] = '1';
		}
		if($this->Value === FALSE)
		{
			$val = array();
		}
		elseif(!is_array($this->Value))
		{
			$val = array($this->Value);
		}
		else
		{
			$val = $this->Value;
		}
		return $mod->CreateInputSelectList($id,'pwfp_'.$this->Id.'[]',$sorted,$val,$this->GetOption('lines','3'),
         $js.$this->GetCSSIdTag());
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOption('option_name');
		if(is_array($opt))
			$num = count($opt);
		elseif($opt)
			$num = 1;
		else
			$num = 0;

		$mod = $this->formdata->formsmodule;
		return $mod->Lang('options',$num);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$this->countItems();
		if($this->optionAdd > 0)
		{
			$this->optionCount += $this->optionAdd;
			$this->optionAdd = 0;
		}
		$main = array();
		$main[] = array($mod->Lang('title_lines_to_show'),$mod->CreateInputText($module_id,'opt_lines',$this->GetOption('lines','3'),10,10));
//		$main[] = array($mod->Lang('title_multiselect_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_option_name'),
			$mod->Lang('title_option_value'),
			$mod->Lang('title_select')
			);
		$num = ($this->optionCount>1) ? $this->optionCount:1;
		for ($i=0; $i<$num; $i++)
		{
			$dests[] = array(
			$mod->CreateInputText($module_id,'opt_option_name[]',$this->GetOptionElement('option_name',$i),30,128),
			$mod->CreateInputText($module_id,'opt_option_value[]',$this->GetOptionElement('option_value',$i),30,128),
			$mod->CreateInputCheckbox($module_id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
		{
			if(is_array($this->Value))
			{
				$ret = array();
				$vals = $this->GetOptionRef('option_value');
				foreach($this->Value as $one)
					$ret[] = $vals[$one - 1]; //TODO off by one ?
				if($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$ret);
				else
					return $ret;
			}
			$ret = $this->Value;
		}
		else
		{
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if($as_string)
			return $ret;
		else
			return array($ret);
	}
}

?>
