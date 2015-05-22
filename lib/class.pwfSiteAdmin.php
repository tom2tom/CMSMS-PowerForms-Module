<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSiteAdminField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'SiteAdminField';
	}

	function buildList()
	{
		$userops = cmsms()->GetUserOperations();
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		// why all this? Associative arrays are not guaranteed to preserve
		// order,except in "chronological" creation order.
		$sorted =array();
		if($this->GetOption('select_one'))
			$sorted[' '.$this->GetOption('select_one')] = '';
		else
			$sorted[' '.$mod->Lang('select_one')] = '';

		if($this->GetOption('restrict_to_group','0') == '1')
			$userlist = $userops->LoadUsersInGroup($this->GetOption('group'));
		else
			$userlist = $userops->LoadUsers();

		$ind = 1;
		for($i=0; $i<count($userlist); $i++)
		{
			$name = array();
			if($this->GetOption('show_userfirstname','0') == '1')
			{
				$name[] = $userlist[$i]->firstname;
			}
			if($this->GetOption('show_userlastname','0') == '1')
			{
				$name[] = $userlist[$i]->lastname;
			}
			if($this->GetOption('show_username','0') == '1')
			{
				$name[] = ' ('.$userlist[$i]->username.')';
			}
			$sname = implode(' ',$name);
			if($userlist[$i]->active || $this->GetOption('active_only','1')=='0')
			{
				$sorted[$sname]=$ind;
				$ind += 1;
			}
		}
		return $sorted;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$sorted = $this->buildList();
		return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$sorted,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function GetFieldStatus()
	{
		$ret = '';
		if($this->GetOption('restrict_to_group','0')=='1')
		{
			$groupops = cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetOption('group'));
			if($group && isset($group->name))
			{
				$mod = $this->formdata->formsmodule;
				$ret .= $mod->Lang('restricted_to_group',$group->name);
			}
		}
        return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$groupops = cmsms()->GetGroupOperations();
		$groups = $groupops->LoadGroups();
		$mod = $this->formdata->formsmodule;

		$main = array();
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
		$main[] = array($mod->Lang('title_active_only'),
				$mod->CreateInputHidden($module_id,'opt_active_only','0').
				$mod->CreateInputCheckbox($module_id,'opt_active_only','1',
				$this->GetOption('active_only','1')));

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

		return array('main'=>$main);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$userlist = array_flip($this->buildList());
		if(isset($userlist[$this->Value]))
			$ret = $userlist[$this->Value];
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function Validate()
	{
		$result = TRUE;
		$message = '';

		if($this->Value == FALSE)
		{
			$result = FALSE;
			$mod = $this->formdata->formsmodule;
			$message .= $mod->Lang('must_specify_one_admin').'<br />';
		}
		return array($result,$message);
	}

}

?>
