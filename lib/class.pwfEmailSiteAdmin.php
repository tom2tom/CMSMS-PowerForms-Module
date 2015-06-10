<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for email to a specified admin user

class pwfEmailSiteAdmin extends pwfEmailBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Required = TRUE;
		$this->Type = 'EmailSiteAdmin';
		$this->ValidationType = 'email';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_email_address')=>'email');
	}

	function GetFieldStatus()
	{
		$ret = '';
		if($this->GetOption('restrict_to_group',0))
		{
			$groupops = cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetOption('group'));
			if($group && isset($group->name))
			{
				$mod = $this->formdata->formsmodule;
				$ret .= ','.$mod->Lang('restricted_to_group',$group->name);
			}
		}
		$status = $this->TemplateStatus();
	    if($status)
		{
			if($ret)
				$ret .= '<br />';
			$ret .= $status;
		}
		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$userops = cmsms()->GetUserOperations();
		if($this->GetOption('restrict_to_group',0))
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
		else
			$userlist = $userops->LoadUsers();

		if(isset($userlist[$this->Value - 1]))
			$ret = $userlist[$this->Value - 1]->firstname . ' '. $userlist[$this->Value - 1]->lastname;
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		$choices = array();
		$groups = cmsms()->GetGroupOperations()->LoadGroups();
		foreach($groups as $one)
			$choices[$one->name] = $one->id;

		$mod = $this->formdata->formsmodule;

		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id,TRUE);
		$waslast = array_pop($main); //keep the email to-type selector for later
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id,'opt_select_one',
				$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_show_userfirstname'),
				$mod->CreateInputHidden($id,'opt_show_userfirstname',0).
				$mod->CreateInputCheckbox($id,'opt_show_userfirstname',1,
					$this->GetOption('show_userfirstname',1)));
		$main[] = array($mod->Lang('title_show_userlastname'),
				$mod->CreateInputHidden($id,'opt_show_userlastname',0).
				$mod->CreateInputCheckbox($id,'opt_show_userlastname',1,
					$this->GetOption('show_userlastname',1)));
		$main[] = array($mod->Lang('title_show_username'),
				$mod->CreateInputHidden($id,'opt_show_username',0).
				$mod->CreateInputCheckbox($id,'opt_show_username',1,
					$this->GetOption('show_username',0)));
		$main[] = $waslast;
		$main[] = array($mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($id,'opt_restrict_to_group',0).
				$mod->CreateInputCheckbox($id,'opt_restrict_to_group',1,
					$this->GetOption('restrict_to_group',0)).
				$mod->CreateInputDropdown($id,'opt_group',$choices,-1,$this->GetOption('group')));
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;

    	$addr = $this->GetOption('email_from_address');
		if($addr)
		{
			list($rv,$msg) = $this->validateEmailAddr($addr);
			if(!$rv)
			{
				$ret = FALSE;
				$messages[] = $msg;
			}
		}
		else
		{
			$ret = FALSE;
			$mod = $this->formdata->formsmodule;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('source'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$userops = cmsms()->GetUserOperations();
		if($this->GetOption('restrict_to_group',0))
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
		else
			$userlist = $userops->LoadUsers();
		$c = count($userlist);
		if($c)
		{
			$f = $this->GetOption('show_userfirstname',0);
			$l = $this->GetOption('show_userlastname',0);
			$u = $this->GetOption('show_username',0);
			$choices = array();
			$choices[' '.$this->GetOption('select_one',$mod->Lang('select_one'))]='';
			for($i=0; $i<$c; $i++)
			{
				$parts = array();
				$v = $userlist[$i];
				if($f) $parts[] = $v->firstname;
				if($l) $parts[] = $v->lastname;
				if($u) $parts[] = '('.$v->username.')';
				$name = implode(' ',$parts);
				$choices[$name] = $i+1;
			}
			return $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
		}
		else
		{
			return $mod->Lang('no_admins');
		}
	}

	function Validate($id)
	{
		if($this->Value)
		{
			$this->validated = TRUE;
			$this->ValidationMessage = '';
		}
		else
		{
			$this->validated = FALSE;
			$mod = $this->formdata->formsmodule;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('admin'));
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function Dispose($id,$returnid)
	{
		if($this->HasValue())
		{
			$userops = cmsms()->GetUserOperations();

			if($this->GetOption('restrict_to_group',0))
				$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
			else
				$userlist = $userops->LoadUsers();

			$dest = array($userlist[$this->Value - 1]->email);
			return $this->SendForm($dest,$this->GetOption('email_subject'));
		}
		else
			return array(TRUE,'');
	}

}

?>
