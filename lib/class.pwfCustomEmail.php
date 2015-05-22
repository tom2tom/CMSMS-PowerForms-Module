<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class allows sending an email with subject, sender-name, sender-email
//and receiver(s) taken from (specified) other fields

class pwfCustomEmail extends pwfEmailBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'CustomEmail';
	}

	// Get all fields
	private function GetFieldList($selectone = FALSE)
	{
		$mod = $this->formdata->formsmodule;
		$ret = array();
		if($selectone)
			$ret[$mod->Lang('select_one')] = '';

		foreach($this->formdata->Fields as &$one)
		{
			if($one->DisplayInForm)
				$ret[$one->GetName()] = $one->GetId();
		}
		unset($one);
		return $ret;
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOptionRef('destination_address');
		if(!is_array($opt))
			$opt = array($opt);

		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('to').': '.count($opt).' '.$mod->Lang('fields');
		$status = $this->TemplateStatus();
		if($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$destadd_all = $this->GetFieldList();
		$destadd_tmp = $this->GetOptionRef('destination_address');
		if(!is_array($destadd_tmp))
			$destadd_tmp = array($destadd_tmp);

		$destadd_sel = array();
		foreach($destadd_all as $k=>$v)
		{
			if(in_array($v,$destadd_tmp))
				$destadd_sel[$k] = $v;
		}

		$ret = $this->PrePopulateAdminFormCommonEmail($module_id,TRUE);
		$ret['main'] = array(
			   array($mod->Lang('title_subject_field'),
			   	$mod->CreateInputDropdown($module_id,'opt_email_subject',$this->GetFieldList(TRUE),-1,$this->GetOption('email_subject'))),
			   array($mod->Lang('title_from_field'),
			   	$mod->CreateInputDropdown($module_id,'opt_email_from_name',$this->GetFieldList(TRUE),-1,$this->GetOption('email_from_name',$mod->Lang('friendly_name')))),
			   array($mod->Lang('title_from_address_field'),
			   	$mod->CreateInputDropdown($module_id,'opt_email_from_address',$this->GetFieldList(TRUE),-1,$this->GetOption('email_from_address'))),
			   array($mod->Lang('title_destination_field'),
			   	$mod->CreateInputSelectList($module_id,'opt_destination_address[]',$destadd_all,$destadd_sel,5)),
			   array_pop($tmp) //keep only the default to-type selector
			  );

		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function PostAdminSubmitCleanup(&$params)
	{
		$this->PostAdminSubmitCleanupEmail($params);
	}

	function AdminValidate()
	{
		$mod = $this->formdata->formsmodule;
		$subject = $this->GetOption('email_subject');
		$name = $this->GetOption('email_from_name');
		$from = $this->GetOption('email_from_address');
    	$dest = $this->GetOptionRef('destination_address');


TODO  $validate = $this->validateEmailAddr($email);


  		list($ret,$message) = $this->DoesFieldHaveName();

		if($ret)
			list($ret,$message) = $this->DoesFieldNameExist();
		if(!$subject)
		{
			$ret = FALSE;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_subject')).'<br />';
		}
		if(!$name)
		{
			$ret = FALSE;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_name')).'<br />';
		}
		if(!$from)
		{
			$ret = FALSE;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_address')).'<br />';
		}
		if(!$dest)
		{
			$ret = FALSE;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_destination_address')).'<br />';
		}
        return array($ret,$message);
	}
	
	function DisposeForm($returnid)
	{
		$mod = $this->formdata->formsmodule;
		$formdata = $this->formdata;

		$fld = pwfUtils::GetFieldByID($formdata,$this->GetOption('email_subject'));
		$this->SetOption('email_subject',$fld->GetHumanReadableValue());

		$fld = pwfUtils::GetFieldByID($formdata,$this->GetOption('email_from_name'));
		$this->SetOption('email_from_name',$fld->GetHumanReadableValue());

		$fld = pwfUtils::GetFieldByID($formdata,$this->GetOption('email_from_address'));
		$this->SetOption('email_from_address',$fld->GetHumanReadableValue());

		$addarr = array();
		$dests = $this->GetOptionRef('destination_address'); //in this case, field id's ?
		if(!is_array($dests))
			$dests = array($dests);
		foreach($dests as $one)
		{
			$fld = pwfUtils::GetFieldByID($formdata,$one);
			$value = $fld->GetHumanReadableValue();
			if(strpos($value,',') !== FALSE)
				$addarr = $addarr + explode(',',$value);
			else
				$addarr[] = $value;
		}

		return $this->SendForm($addarr,$this->GetOption('email_subject'));
	}

}

?>
