<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

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
			$choices = array();
			$choices[' '.$this->GetOption('select_one',$mod->Lang('select_one'))]='';
			for($i=0; $i<$c; $i++)
			{
				$parts = array();
				if($this->GetOption('show_userfirstname',0))
					$parts[] = $userlist[$i]->firstname;
				if($this->GetOption('show_userlastname',0))
					$parts[] = $userlist[$i]->lastname;
				if($this->GetOption('show_username',0))
					$parts[] = ' ('.$userlist[$i]->username.')';
				$name = implode(' ',$parts);
				$choices[$name] = ($i+1);
			}
			$js = $this->GetOption('javascript');
			return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
		}
		else
		{
			return $mod->Lang('TODO');
		}
	}

	function PrePopulateAdminForm($module_id)
	{
		$groupops = cmsms()->GetGroupOperations();
		$groups = $groupops->LoadGroups();
		$mod = $this->formdata->formsmodule;

		$ret = $this->PrePopulateAdminFormBase($module_id,TRUE); //TODO
		$main = $ret['main']; //assume it's there
		$waslast = array_pop($main); //keep the email to-type selector for last
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($module_id,'opt_select_one',
				$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_show_userfirstname'),
				$mod->CreateInputHidden($module_id,'opt_show_userfirstname','0').
				$mod->CreateInputCheckbox($module_id,'opt_show_userfirstname','1',
				$this->GetOption('show_userfirstname','1')));
		$main[] = array($mod->Lang('title_show_userlastname'),
				$mod->CreateInputHidden($module_id,'opt_show_userlastname','0').
				$mod->CreateInputCheckbox($module_id,'opt_show_userlastname','1',
				$this->GetOption('show_userlastname','1')));
		$main[] = array($mod->Lang('title_show_username'),
				$mod->CreateInputHidden($module_id,'opt_show_username','0').
				$mod->CreateInputCheckbox($module_id,'opt_show_username','1',
				$this->GetOption('show_username','0')));
		$main[] = $waslast;

		$items = array();
		foreach($groups as $thisGroup)
		{
			$items[$thisGroup->name]=$thisGroup->id;
		}

		$main[] = array($mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($module_id,'opt_restrict_to_group','0').
				$mod->CreateInputCheckbox($module_id,'opt_restrict_to_group','1',
				$this->GetOption('restrict_to_group','0')).
				$mod->CreateInputDropdown($module_id,'opt_group',$items,-1,$this->GetOption('group'))
				);
		$ret['main'] = $main;
		return $ret;
	}

	function AdminValidate()
	{
		return $this->validateEmailAddr($this->GetOption('email_from_address'));
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
			$this->ValidationMessage = $this->formdata->formsmodule->Lang('must_specify_one_destination'); //TODO person
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function DisposeForm($returnid)
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
