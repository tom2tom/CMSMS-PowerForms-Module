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

	// get all fields
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
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate();
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		if(!$this->GetOption('email_subject'))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_subject'));
		}
		if(!$this->GetOption('email_from_name'))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_name'));
		}
		if($this->GetOption('email_from_address'))
		{
			//TODO validate address
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_address'));
		}
		if($this->GetOptionRef('destination_address'))
		{
			//TODO validate address(es)
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_destination_address'));
		}
		//TODO message-body field?
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function DisposeForm($returnid)
	{
		$formdata = $this->formdata;

		$senderfld = $this->GetOption('email_from_name'); //TODO confirm this is field_id?
		$fld = $formdata->Fields[$senderfld];
		$this->SetOption('email_from_name',$fld->GetHumanReadableValue());

		$fromfld = $this->GetOption('email_from_address');
		$fld = $formdata->Fields[$fromfld];
		$this->SetOption('email_from_address',$fld->GetHumanReadableValue());

		$addarr = array();
		$dests = $this->GetOptionRef('destination_address'); //TODO in this case, field id's ?
		if(!is_array($dests))
			$dests = array($dests);
		foreach($dests as $field_id)
		{
			$fld = $formdata->Fields[$field_id];
			$value = $fld->GetHumanReadableValue();
			if(strpos($value,',') !== FALSE)
				$addarr = $addarr + explode(',',$value);
			else
				$addarr[] = $value;
		}

/*		$subjectfld = $this->GetOption('email_subject');
		$fld = $formdata->Fields[$subjectfld];
		$this->SetOption('email_subject',$fld->GetHumanReadableValue());

		$ret = $this->SendForm($addarr,$this->GetOption('email_subject'));

		$this->SetOption('email_subject',$subjectfld);
*/
		$fld = $formdata->Fields[$this->GetOption('email_subject')];
		$ret = $this->SendForm($addarr,$fld->GetHumanReadableValue()); //TODO check is ok, message content?

		$this->SetOption('email_from_name',$senderfld);
		$this->SetOption('email_from_address',$fromfld);

		return $ret;
	}

}

?>
