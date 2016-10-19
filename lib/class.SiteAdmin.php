<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SiteAdmin extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'SiteAdmin';
	}

	public function buildList($select=FALSE)
	{
		$mod = $this->formdata->formsmodule;
		$userops = \cmsms()->GetUserOperations();
		if ($this->GetProperty('restrict_to_group',0))
			$userlist = $userops->LoadUsersInGroup($this->GetProperty('group'));
		else
			$userlist = $userops->LoadUsers();
		$c = count($userlist);
		if ($c) {
			$f = $this->GetProperty('show_userfirstname',0);
			$l = $this->GetProperty('show_userlastname',0);
			$u = $this->GetProperty('show_username',0);
			$choices = array();
			if ($select)
				$choices[' '.$this->GetProperty('select_one',$mod->Lang('select_one'))] = -1;
			$indx = 1;
			for ($i=0; $i<$c; $i++)
			{
				$v = $userlist[$i];
				if ($v->active || !$this->GetProperty('active_only',1)) {
					$parts = array();
					if ($f) $parts[] = $v->firstname;
					if ($l) $parts[] = $v->lastname;
					if ($u) $parts[] = '('.$v->username.')';
					$name = implode(' ',$parts);
					$choices[$name] = $indx;
					$indx++;
				}
			}
			return $choices;
		}
		return FALSE;
	}

	public function GetFieldStatus()
	{
		$ret = '';
		if ($this->GetProperty('restrict_to_group',0)) {
			$groupops = \cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetProperty('group'));
			if ($group && isset($group->name)) {
				$mod = $this->formdata->formsmodule;
				$ret .= $mod->Lang('restricted_to_group',$group->name);
			}
		}
		return $ret;
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$admins = $this->buildList();
		if ($admins)
			$ret = array_search($this->Value,$admins);
		else
			$ret = FALSE;
		if (!$ret)
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$choices = array();
		$groups = \cmsms()->GetGroupOperations()->LoadGroups();
		foreach ($groups as $one)
			$choices[$one->name] = $one->id;

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_select_one_message'),
					$mod->CreateInputText($id,'pdt_select_one',
					$this->GetProperty('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_show_userfirstname'),
					$mod->CreateInputHidden($id,'pdt_show_userfirstname',0).
					$mod->CreateInputCheckbox($id,'pdt_show_userfirstname',1,
						$this->GetProperty('show_userfirstname',1)));
		$main[] = array($mod->Lang('title_show_userlastname'),
					$mod->CreateInputHidden($id,'pdt_show_userlastname',0).
					$mod->CreateInputCheckbox($id,'pdt_show_userlastname',1,
						$this->GetProperty('show_userlastname',1)));
		$main[] = array($mod->Lang('title_show_username'),
					$mod->CreateInputHidden($id,'pdt_show_username',0).
					$mod->CreateInputCheckbox($id,'pdt_show_username',1,
						$this->GetProperty('show_username',0)));
		$main[] = array($mod->Lang('title_active_only'),
					$mod->CreateInputHidden($id,'pdt_active_only',0).
					$mod->CreateInputCheckbox($id,'pdt_active_only',1,
						$this->GetProperty('active_only',1)));
		$main[] = array($mod->Lang('title_restrict_to_group'),
					$mod->CreateInputHidden($id,'pdt_restrict_to_group',0).
					$mod->CreateInputCheckbox($id,'pdt_restrict_to_group',1,
						$this->GetProperty('restrict_to_group',0)).
					$mod->CreateInputDropdown($id,'pdt_group',$choices,-1,
						$this->GetProperty('group')));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$choices = $this->buildList(TRUE);
		if ($choices) {
			$mod = $this->formdata->formsmodule;
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Validate($id)
	{
		if (property_exists($this,'Value') && $this->Value) {
			$this->valid = TRUE;
			$this->ValidationMessage = '';
		} else {
			$this->valid = FALSE;
			$mod = $this->formdata->formsmodule;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('admin'));
		}
		return array($this->valid,$this->ValidationMessage);
	}
}
