<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSiteAdmin extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'SiteAdmin';
	}

	function buildList()
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
			$choices[' '.$this->GetOption('select_one',$mod->Lang('select_one'))] = '';
			$ind = 1;
			for($i=0; $i<$c; $i++)
			{
				$v = $userlist[$i];
				if($v->active || !$this->GetOption('active_only',1))
				{
					$parts = array();
					if($f) $parts[] = $v->firstname;
					if($l) $parts[] = $v->lastname;
					if($u) $parts[] = '('.$v->username.')';
					$name = implode(' ',$parts);
					$choices[$name] = $ind;
					$ind++;
				}
			}
			return $choices;
		}
		else
		{
			return $mod->Lang('TODO');
		}
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
				$ret .= $mod->Lang('restricted_to_group',$group->name);
			}
		}
        return $ret;
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

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$sorted = $this->buildList();
		return $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$sorted,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($id)
	{
		$groupops = cmsms()->GetGroupOperations();
		$groups = $groupops->LoadGroups();
		$mod = $this->formdata->formsmodule;

		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id,'opt_select_one',
				$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_show_userfirstname'),
				$mod->CreateInputHidden($id,'opt_show_userfirstname','0').
				$mod->CreateInputCheckbox($id,'opt_show_userfirstname','1',
				$this->GetOption('show_userfirstname','1')));
		$main[] = array($mod->Lang('title_show_userlastname'),
				$mod->CreateInputHidden($id,'opt_show_userlastname','0').
				$mod->CreateInputCheckbox($id,'opt_show_userlastname','1',
				$this->GetOption('show_userlastname','1')));
		$main[] = array($mod->Lang('title_show_username'),
				$mod->CreateInputHidden($id,'opt_show_username','0').
				$mod->CreateInputCheckbox($id,'opt_show_username','1',
				$this->GetOption('show_username','0')));
		$main[] = array($mod->Lang('title_active_only'),
				$mod->CreateInputHidden($id,'opt_active_only','0').
				$mod->CreateInputCheckbox($id,'opt_active_only','1',
				$this->GetOption('active_only','1')));

		$items = array();
		foreach($groups as $thisGroup)
		{
			$items[$thisGroup->name]=$thisGroup->id;
		}

		$main[] = array($mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($id,'opt_restrict_to_group',0).
				$mod->CreateInputCheckbox($id,'opt_restrict_to_group',1,
				$this->GetOption('restrict_to_group',0)).
				$mod->CreateInputDropdown($id,'opt_group',$items,-1,$this->GetOption('group'))
				);

		return array('main'=>$main);
	}

	function Validate($id)
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
