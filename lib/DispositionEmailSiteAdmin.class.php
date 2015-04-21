<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/

require_once(cms_join_path(dirname(__FILE__),'DispositionEmailBase.class.php'));

class fbDispositionEmailSiteAdmin extends fbDispositionEmailBase {

	var $addressCount;
	var $addressAdd;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'DispositionEmailSiteAdmin';
		$this->DisplayInForm = true;
		$this->IsDisposition = true;
		$this->HasAddOp = false;
		$this->HasDeleteOp = false;
		$this->ValidationTypes = array();
	}


	function GetFieldInput($id, &$params, $returnid)
	{
		$userops = cmsms()->GetUserOperations();
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');

		// why all this? Associative arrays are not guaranteed to preserve
		// order, except in "chronological" creation order.
		$sorted =array();
		if ($this->GetOption('select_one','') != '')
			{
			$sorted[' '.$this->GetOption('select_one','')]='';
			}
		else
			{
			$sorted[' '.$mod->Lang('select_one')]='';
			}

		if ($this->GetOption('restrict_to_group','0')=='1')
			{
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
			}
		else
			{
			$userlist = $userops->LoadUsers();
			}
		for($i=0;$i<count($userlist);$i++)
			{
			$name = array();
			if ($this->GetOption('show_userfirstname','0')=='1')
				{
				$name[] = $userlist[$i]->firstname;
				}
			if ($this->GetOption('show_userlastname','0')=='1')
				{
				$name[] = $userlist[$i]->lastname;
				}
			if ($this->GetOption('show_username','0')=='1')
				{
				$name[] = ' ('.$userlist[$i]->username.')';
				}
			$sname = implode(' ',$name);
			$sorted[$sname]=($i+1);
			}
		return $mod->CreateInputDropdown($id, 'fbrp__'.$this->Id, $sorted, -1, $this->Value, $js.$this->GetCSSIdTag());
	}

	function StatusInfo()
	{
		$ret = '';
		if ($this->GetOption('restrict_to_group','0')=='1')
			{
			$groupops = cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetOption('group'));
			if ($group && isset($group->name))
				{
				$mod = $this->form_ptr->module_ptr;
				$ret .= ', '.$mod->Lang('restricted_to_group',$group->name);
				}
			}
		$status = $this->TemplateStatus();
	    if ($status)
			{
			if ($ret) $ret.='<br />';
			$ret.=$status;
			}
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$groupops = cmsms()->GetGroupOperations();
		$groups = $groupops->LoadGroups();
		$mod = $this->form_ptr->module_ptr;

		$ret = $this->PrePopulateAdminFormBase($formDescriptor, true);
		$main = $ret['main']; //assume it's there
		$waslast = array_pop($main); //keep the email to-type selector for last
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($formDescriptor, 'fbrp_opt_select_one',
				$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_show_userfirstname'),
				$mod->CreateInputHidden($formDescriptor,'fbrp_opt_show_userfirstname','0').
				$mod->CreateInputCheckbox($formDescriptor, 'fbrp_opt_show_userfirstname', '1',
				$this->GetOption('show_userfirstname','1')));
		$main[] = array($mod->Lang('title_show_userlastname'),
				$mod->CreateInputHidden($formDescriptor,'fbrp_opt_show_userlastname','0').
				$mod->CreateInputCheckbox($formDescriptor, 'fbrp_opt_show_userlastname', '1',
				$this->GetOption('show_userlastname','1')));
		$main[] = array($mod->Lang('title_show_username'),
				$mod->CreateInputHidden($formDescriptor,'fbrp_opt_show_username','0').
				$mod->CreateInputCheckbox($formDescriptor, 'fbrp_opt_show_username', '1',
				$this->GetOption('show_username','0')));
		$main[] = $waslast;

		$items = array();
		foreach ($groups as $thisGroup)
			{
			$items[$thisGroup->name]=$thisGroup->id;
			}

		$main[] = array($mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($formDescriptor,'fbrp_opt_restrict_to_group','0').
				$mod->CreateInputCheckbox($formDescriptor, 'fbrp_opt_restrict_to_group', '1',
				$this->GetOption('restrict_to_group','0')).
				$mod->CreateInputDropdown($formDescriptor, 'fbrp_opt_group', $items, -1, $this->GetOption('group',''))
				);
		$ret['main'] = $main;
		return $ret;
	}

	function GetHumanReadableValue($as_string=true)
	{
		$userops = cmsms()->GetUserOperations();

		if ($this->GetOption('restrict_to_group','0')=='1')
			{
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
			}
		else
			{
			$userlist = $userops->LoadUsers();
			}
		if (isset($userlist[$this->Value - 1]))
			{
			$ret = $userlist[$this->Value - 1]->firstname . ' '. $userlist[$this->Value - 1]->lastname;
			}
		else
			{
			$mod = $this->form_ptr->module_ptr;
			$ret = $mod->Lang('unspecified');
			}
		if ($as_string)
			{
			return $ret;
			}
		else
			{
			return array($ret);
			}

	}

	function DisposeForm($returnid)
	{
		$userops = cmsms()->GetUserOperations();

		if ($this->GetOption('restrict_to_group','0')=='1')
			{
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
			}
		else
			{
			$userlist = $userops->LoadUsers();
			}
		$dest = array($userlist[$this->Value - 1]->email);
		return $this->SendForm($dest,$this->GetOption('email_subject'));
	}


	function AdminValidate()
    {
		list($ret,$message) = $this->validateEmailAddr($this->GetOption('email_from_address'));
        return array($ret,$message);
    }

	function Validate()
    {
         $result = true;
         $message = '';

         if ($this->Value == false)
            {
            $result = false;
						$mod = $this->form_ptr->module_ptr;
            $message .=
							$mod->Lang('must_specify_one_destination').'<br />';
            }
        return array($result,$message);
    }

}
?>
