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

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;

		$ret = $this->PrePopulateAdminFormCommonEmail($id,TRUE);
		$waslast = array_pop($ret['main']); //keep the email to-type selector for later
		$ret['main'][] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id,'opt_select_one',
				$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$ret['main'][] = array($mod->Lang('title_show_userfirstname'),
				$mod->CreateInputHidden($id,'opt_show_userfirstname',0).
				$mod->CreateInputCheckbox($id,'opt_show_userfirstname',1,
				$this->GetOption('show_userfirstname',1)));
		$ret['main'][] = array($mod->Lang('title_show_userlastname'),
				$mod->CreateInputHidden($id,'opt_show_userlastname',0).
				$mod->CreateInputCheckbox($id,'opt_show_userlastname',1,
				$this->GetOption('show_userlastname',1)));
		$ret['main'][] = array($mod->Lang('title_show_username'),
				$mod->CreateInputHidden($id,'opt_show_username',0).
				$mod->CreateInputCheckbox($id,'opt_show_username',1,
				$this->GetOption('show_username',0)));
		$ret['main'][] = $waslast;

		$choices = array();
		$groupops = cmsms()->GetGroupOperations();
		$groups = $groupops->LoadGroups();
		foreach($groups as $one)
			$choices[$one->name] = $one->id;

		$ret['main'][] = array($mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($id,'opt_restrict_to_group',0).
				$mod->CreateInputCheckbox($id,'opt_restrict_to_group',1,
				$this->GetOption('restrict_to_group',0)).
				$mod->CreateInputDropdown($id,'opt_group',$choices,-1,$this->GetOption('group'))
				);
		return $ret;
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
    	$dest = $this->GetOption('email_from_address');
		if($dest)
		{
			list($rv,$msg) = $this->validateEmailAddr($dest);
			if(!$rv)
			{
				$ret = FALSE;
				$messages[] = $msg;
			}
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('must_specify_TODO');
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function GetFieldInput($id,&$params)
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
			$js = $this->GetOption('javascript');
			return $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->Lang('TODO');
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
			$this->ValidationMessage = $this->formdata->formsmodule->Lang('must_specify_one_destination'); //TODO person
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
