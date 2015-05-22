<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPageRedirectorField extends pwfFieldBase
{
	var $addressCount;
	var $addressAdd;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'PageRedirectorField';
		$this->addressAdd = 0;
	}

	function GetOptionAddButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('add_destination');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('delete_destination');
	}

	function DoOptionAdd(&$params)
	{
		$this->addressAdd = 1;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('destination_page',$thisVal - $delcount);
				$this->RemoveOptionElement('destination_subject',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countAddresses()
	{
		$tmp = $this->GetOptionRef('destination_page');
		if(is_array($tmp))
		{
			$this->addressCount = count($tmp);
		}
		elseif($tmp !== FALSE)
		{
			$this->addressCount = 1;
		}
		else
		{
			$this->addressCount = 0;
		}
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		// why all this? Associative arrays are not guaranteed to preserve
		// order,except in "chronological" creation order.
		$sorted =array();
		if($this->GetOption('select_one'))
			$sorted[' '.$this->GetOption('select_one')]='';
		else
			$sorted[' '.$mod->Lang('select_one')]='';

		$subjects = $this->GetOptionRef('destination_subject');

		if(count($subjects) > 1)
		{
			for($i=0; $i<count($subjects); $i++)
			{
				$sorted[$subjects[$i]] = ($i+1);
			}
		}
		else
		{
			$sorted[$subjects] = '1';
		}
		return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$sorted,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOption('destination_page');
		if(is_array($opt))
			$num = count($opt);
		elseif($opt)
			$num = 1;
		else
          $num = 0;
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('destination_count',$num);
	}

	function PrePopulateAdminForm($module_id)
	{
		global $id;
		$contentops = cmsms()->GetContentOperations();
		$mod = $this->formdata->formsmodule;

		$this->countAddresses();
		if($this->addressAdd > 0)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = 0;
		}
//		$ret = $this->PrePopulateAdminFormBase($module_id);
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($module_id,'opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
//		$main[] = array($mod->Lang('title_director_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_subject'),
			$mod->Lang('title_destination_page'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for ($i=0; $i<$num; $i++)
		{
			$dests[] = array(
			$mod->CreateInputText($module_id,'opt_destination_subject[]',$this->GetOptionElement('destination_subject',$i),30,128),
			$contentops->CreateHierarchyDropdown('',$this->GetOptionElement('destination_page',$i),$id.'opt_destination_page[]'),
			$mod->CreateInputCheckbox($module_id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function GetHumanReadableValue($as_string)
	{
		if($this->HasValue())
			$ret = $this->GetOptionElement('destination_page',($this->Value - 1)); //TODO index
		else

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function DisposeForm($returnid)
	{
		// If needed,make sure other dispositions get run 1st. See FormDispose($returnid) in action.default
		$mod = $this->formdata->formsmodule;
		$mod->RedirectContent($this->GetOptionElement('destination_page',($this->Value - 1)));
		return array(TRUE,'everything worked');
	}

	function AdminValidate()
	{
		$mod = $this->formdata->formsmodule;
		$opt = $this->GetOption('destination_page');
  		list($ret,$message) = $this->DoesFieldHaveName();
		if($ret)
		{
			list($ret,$message) = $this->DoesFieldNameExist();
		}
		if(count($opt) == 0)
		{
			$ret = FALSE;
			$message .= $mod->Lang('must_specify_one_destination').'<br />';
		}
	    return array($ret,$message);
	}
}

?>
