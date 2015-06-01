<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class allows sending an email to a destination selected from a pulldown

class pwfEmailDirector extends pwfEmailBase
{
	var $addressAdd;
	var $addressCount;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'EmailDirector';
		$this->addressAdd = FALSE;
		$this->addressCount = 0;
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
				$this->RemoveOptionElement('destination_address',$thisVal - $delcount); //TODO
				$this->RemoveOptionElement('destination_subject',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countAddresses()
	{
		$tmp = $this->GetOptionRef('destination_address');
		if(is_array($tmp))
			$this->addressCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->addressCount = 1;
		else
			$this->addressCount = 0;
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOption('destination_address');

		if(is_array($opt))
			$num = count($opt);
		elseif($opt)
			$num = 1;
		else
			$num = 0;

		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('destination_count',$num);
		$status = $this->TemplateStatus();
		if($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
			$ret = $this->GetOptionElement('destination_subject',($this->Value - 1)); //TODO index
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

		$ret = $this->PrePopulateAdminFormCommonEmail($module_id);
		$ret['main'][] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($module_id,'opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$ret['main'][] = array($mod->Lang('title_allow_subject_override'),
			$mod->CreateInputHidden($module_id,'opt_subject_override',0).
			$mod->CreateInputCheckbox($module_id,'opt_subject_override',1,
				$this->GetOption('subject_override',0)),
			$mod->Lang('help_allow_subject_override'));
//		$ret['main'][] = array($mod->Lang('title_director_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_subject'),
			$mod->Lang('title_destination_address'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for($i=0; $i<$num; $i++)
		{
			$dests[] = array(
			$mod->CreateInputText($module_id,'opt_destination_subject[]',
				$this->GetOptionElement('destination_subject',$i),40,128),
			$mod->CreateInputText($module_id,'opt_destination_address[]',
				$this->GetOptionElement('destination_address',$i),50,128),
			$mod->CreateInputCheckbox($module_id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		$ret['table'] = $dests;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		// remove the "email subject" field
		$this->RemoveAdminField($mainArray,
			$this->formdata->formsmodule->Lang('title_email_subject'));
	}

	function PostAdminSubmitCleanup(&$params)
	{
		$this->PostAdminSubmitCleanupEmail($params);
	}

	function AdminValidate()
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate();
		if(!ret)
			$messages[] = $msg;
	
		$mod = $this->formdata->formsmodule;
		list($rv,$msg) = $this->validateEmailAddr($this->GetOption('email_from_address'));
		if(!$rv)
		{
    	    $ret = FALSE;
            $messages[] = $msg;
		}
    	$dests = $this->GetOption('destination_address');
		$c = count($dests);
		if($c)
		{
		    for($i=0; $i<$c; $i++)
			{
				list($rv,$msg) = $this->validateEmailAddr($dests[$i]);
				if(!$rv)
				{
					$ret = FALSE;
					$messages[] = $msg;
				}
			}
		}
		else
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

		// why all this? Associative arrays are not guaranteed to preserve
		// order,except in "chronological" creation order.
		$sorted = array();
		if($this->GetOption('select_one'))
			$sorted[' '.$this->GetOption('select_one')] = '';
		else
			$sorted[' '.$mod->Lang('select_one')]='';

		$subjects = $this->GetOptionRef('destination_subject');

		if(count($subjects) > 1)
		{
			for($i=0; $i<count($subjects); $i++)
				$sorted[$subjects[$i]]=($i+1);
		}
		else
			$sorted[$subjects] = '1';

		return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$sorted,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function Validate()
	{
		if($this->Value)
		{
	  		$this->validated = TRUE;
  			$this->ValidationMessage = '';
		}
		else
		{
	  		$this->validated = FALSE;
  			$this->ValidationMessage = $this->formdata->formsmodule->Lang('must_specify_one_destination');
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function DisposeForm($returnid)
	{
		if($this->GetOption('subject_override',0) && $this->GetOption('email_subject'))
			$subject = $this->GetOption('email_subject');
		else
			$subject = $this->GetOptionElement('destination_subject',($this->Value - 1)); //TODO

		return $this->SendForm($this->GetOptionElement('destination_address',($this->Value - 1)),$subject);
	}

}

?>
