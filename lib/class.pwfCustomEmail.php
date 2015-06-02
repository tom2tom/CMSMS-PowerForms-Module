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

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$opt = $this->GetOptionRef('destination_address');
		if($opt)
			$ret = $mod->Lang('to').': '.count($opt).' '.$mod->Lang('fields');
		else
			$ret = $mod->Lang('no address TODO');
		$status = $this->TemplateStatus();
		if($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	function PrePopulateAdminForm($id)
	{
		$displayfields = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->DisplayInForm)
				$displayfields[$one->GetName()] = $one->GetId();
		}
		unset($one);
		$destfields = array();
		$opt = $this->GetOptionRef('destination_address');
		if($opt)
		{
			foreach($displayfields as $k=>$v)
			{
				if(in_array($v,$opt))
					$destfields[$k] = $v;
			}
		}
		$mod = $this->formdata->formsmodule;
		$choices = array($mod->Lang('select_one') => '') + $displayfields;

		$ret = $this->PrePopulateAdminFormCommonEmail($id,TRUE);
		$ret['main'] = array(
			   array($mod->Lang('title_subject_field'),
			   	$mod->CreateInputDropdown($id,'opt_email_subject',$choices,-1,$this->GetOption('email_subject'))),
			   array($mod->Lang('title_from_field'),
			   	$mod->CreateInputDropdown($id,'opt_email_from_name',$choices,-1,$this->GetOption('email_from_name',$mod->Lang('friendly_name')))),
			   array($mod->Lang('title_from_address_field'),
			   	$mod->CreateInputDropdown($id,'opt_email_from_address',$choices,-1,$this->GetOption('email_from_address'))),
			   array($mod->Lang('title_destination_field'),
			   	$mod->CreateInputSelectList($id,'opt_destination_address'.$i,$displayfields,$destfields,5)),
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

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
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
		$opt = $this->GetOption('email_from_address');
		if($opt)
		{
			if(!$this->validateEmailAddr($opt))
			{
				$ret = FALSE;
				$messages[] = $mod->Lang('invalid_TODO');
			}
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_address'));
		}
		$opt = $this->GetOptionRef('destination_address');
		if($opt)
		{
			list($rv,$msg) = $this->validateEmailAddr($opt);
			if(!$rv)
			{
				$ret = FALSE;
				$messages[] = $msg;
			}
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_destination_address'));
		}
		$msg = $this->TemplateStatus();
		if($msg)
		{
			$ret = FALSE;
			$messages[] = $msg;
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function Dispose($id,$returnid)
	{
		$dests = $this->GetOptionRef('destination_address'); //TODO in this case, field id's ?
		if($dests)
		{
			$formdata = $this->formdata;

			$senderfld = $this->GetOption('email_from_name'); //TODO confirm this is field_id?
			$fld = $formdata->Fields[$senderfld];
			$this->SetOption('email_from_name',$fld->GetHumanReadableValue());

			$fromfld = $this->GetOption('email_from_address');
			$fld = $formdata->Fields[$fromfld];
			$this->SetOption('email_from_address',$fld->GetHumanReadableValue());

			$addrs = array();
			foreach($dests as $field_id)
			{
				$fld = $formdata->Fields[$field_id];
				$value = $fld->GetHumanReadableValue();
				if(strpos($value,',') !== FALSE)
					$addrs = $addrs + explode(',',$value);
				else
					$addrs[] = $value;
			}

/*			$subjectfld = $this->GetOption('email_subject');
			$fld = $formdata->Fields[$subjectfld];
			$this->SetOption('email_subject',$fld->GetHumanReadableValue());

			$ret = $this->SendForm($addrs,$this->GetOption('email_subject'));

			$this->SetOption('email_subject',$subjectfld);
*/
			$fld = $formdata->Fields[$this->GetOption('email_subject')];
			$ret = $this->SendForm($addrs,$fld->GetHumanReadableValue()); //TODO check value(subject) is ok

			$this->SetOption('email_from_name',$senderfld);
			$this->SetOption('email_from_address',$fromfld);

			return $ret;
		}
		return array(FALSE,'errTODO');
	}

}

?>
