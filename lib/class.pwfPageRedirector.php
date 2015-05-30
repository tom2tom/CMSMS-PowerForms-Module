<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPageRedirector extends pwfFieldBase
{
	var $addressAdd;
	var $addressCount;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'PageRedirector';
		$this->addressAdd = FALSE;
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_destination');
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_destination');
	}

	function DoOptionAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('destination_page',$thisVal - $delcount); //TODO
				$this->RemoveOptionElement('destination_subject',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countAddresses()
	{
		$tmp = $this->GetOptionRef('destination_page');
		if(is_array($tmp))
			$this->addressCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->addressCount = 1;
		else
			$this->addressCount = 0;
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

		return $this->formdata->formsmodule->Lang('destination_count',$num);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
			$ret = $this->GetOptionElement('destination_page',($this->Value - 1)); //TODO index
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$this->countAddresses();
		if($this->addressAdd > 0)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = 0;
		}
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
		$contentops = cmsms()->GetContentOperations();
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for($i=0; $i<$num; $i++)
		{
			$dests[] = array(
			$mod->CreateInputText($module_id,'opt_destination_subject[]',$this->GetOptionElement('destination_subject',$i),30,128),
			$contentops->CreateHierarchyDropdown('',$this->GetOptionElement('destination_page',$i),$module_id.'opt_destination_page[]'),
			$mod->CreateInputHidden($module_id,'opt_sel_'.$i,0).
			$mod->CreateInputCheckbox($module_id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function AdminValidate()
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate();
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		if(!$this->GetOption('destination_page'))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('must_specify_one_destination');
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		$choices = array();
		$choices[' '.$this->GetOption('select_one',$mod->Lang('select_one'))] = '';

		$subjects = $this->GetOptionRef('destination_subject');

		if(count($subjects) > 1)
		{
			for($i=0; $i<count($subjects); $i++)
				$choices[$subjects[$i]] = ($i+1);
		}
		else
			$choices[$subjects] = '1';

		return $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function DisposeForm($returnid)
	{
		//TODO ensure all other dispositions are run before this
		$mod = $this->formdata->formsmodule;
		$mod->RedirectContent($this->GetOptionElement('destination_page',($this->Value - 1)));
		return array(TRUE,'');
	}

}

?>
