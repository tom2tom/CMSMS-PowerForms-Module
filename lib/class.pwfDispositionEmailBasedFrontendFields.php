<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//require_once(cms_join_path(dirname(__FILE__),'class.pwfDispositionEmailBase.php'));

class pwfDispositionEmailBasedFrontendFields extends pwfDispositionEmailBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionEmailBasedFrontendFields';
		$this->DisplayInForm = false;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->IsDisposition = true;
		$this->HideLabel = 1;
		$this->ValidationTypes = array();
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;

		$opt = $this->GetOptionRef('destination_address','');
		if(!is_array($opt))
		{
			$opt = array($opt);
		}

		$ret= $mod->Lang('to').': '.count($opt).' '.$mod->Lang('fields');
		$status = $this->TemplateStatus();
		if($status) $ret.='<br />'.$status;

		return $ret;
	}

    // send emails
	function DisposeForm($returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$form = $this->formdata;

		$tmp = $form->GetFieldByID($this->GetOption('email_subject'));
		$this->SetOption('email_subject',$tmp->GetHumanReadableValue());

		$tmp = $form->GetFieldByID($this->GetOption('email_from_name'));
		$this->SetOption('email_from_name',$tmp->GetHumanReadableValue());

		$tmp = $form->GetFieldByID($this->GetOption('email_from_address'));
		$this->SetOption('email_from_address',$tmp->GetHumanReadableValue());

		$addarr = array();
		$address = $this->GetOptionRef('destination_address');

		if(!is_array($address))
		{
			$address = array($address);
		}

		foreach($address as $item)
		{
			$tmp = $form->GetFieldByID($item);
			$value = $tmp->GetHumanReadableValue();

			if(strpos($value,',') !== false)
			{
				$arr = explode(',',$value);
			}
			else
			{
				$arr = array($value);
			}

			foreach($arr as $email)
			{
				$validate = $this->validateEmailAddr($email);

				if($validate[0])
				{
					$addarr[] = $email;
				}
			}
		}

		return $this->SendForm($addarr,$this->GetOption('email_subject'));
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$destadd_all = $this->GetFieldList();
		$destadd_tmp = $this->GetOptionRef('destination_address');
		if(!is_array($destadd_tmp)) {
			$destadd_tmp = array($destadd_tmp);
		}

		$destadd_sel = array();
		foreach($destadd_all as $k=>$v)
		{
			if(in_array($v,$destadd_tmp))
			{
				$destadd_sel[$k] = $v;
			}
		}

		$ret = $this->PrePopulateAdminFormBase($formDescriptor, true);
		$ret['main'] = array(
			   array($mod->Lang('title_subject_field'),
			   	$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_email_subject',$this->GetFieldList(true),-1,$this->GetOption('email_subject',''))),
			   array($mod->Lang('title_from_field'),
			   	$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_email_from_name',$this->GetFieldList(true),-1,$this->GetOption('email_from_name',$mod->Lang('friendly_name')))),
			   array($mod->Lang('title_from_address_field'),
			   	$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_email_from_address',$this->GetFieldList(true),-1,$this->GetOption('email_from_address',''))),
			   array($mod->Lang('title_destination_field'),
			   	$mod->CreateInputSelectList($formDescriptor, 'pwfp_opt_destination_address[]',$destadd_all,$destadd_sel,5)),
			   array_pop($tmp) //keep only the default to-type selector
			  );

		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$this->HiddenDispositionFields($mainArray, $advArray);
	}

	// Validate admin side
	function AdminValidate()
	{
		$mod = $this->formdata->pwfmodule;
		$subject = $this->GetOption('email_subject','');
		$name = $this->GetOption('email_from_name','');
		$from = $this->GetOption('email_from_address','');
    	$dest = $this->GetOptionRef('destination_address');

  		list($ret, $message) = $this->DoesFieldHaveName();

		if($ret)
		{
			list($ret, $message) = $this->DoesFieldNameExist();
		}

		if($subject == false || count($subject) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_subject')).'<br />';
		}

		if($name == false || count($name) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_name')).'<br />';
		}

		if($from == false || count($from) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_address')).'<br />';
		}

		if($dest == false || count($dest) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('no_field_assigned',$mod->Lang('title_destination_address')).'<br />';
		}

        return array($ret,$message);
  }

	// Get all fields
	private function GetFieldList($selectone = false)
	{
		$mod = $this->formdata->pwfmodule;
		$form = $this->formdata;
		$fields = $form->GetFields();
		$ret = array();

		if($selectone)
		{
			$ret[$mod->Lang('select_one')] = '';
		}

		foreach($fields as $thisField)
		{
			if($thisField->DisplayInForm)
			{
				$ret[$thisField->GetName()] = $thisField->GetId();
			}
		}

		return $ret;
	}
}

?>
