<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class SiteAdmin extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'SiteAdmin';
//		$this->ValidationType = 'notempty';
//		$this->ValidationTypes = [label=>'none', label=>'notempty'];
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'show_userfirstname' => 10,
		'show_userlastname' => 10,
		'show_username' => 10,
		'group' => 12, //TODO CHECKME type
		'select_label' => 12,
		'active_only' => 10,
		'restrict_to_group' => 10,
		];
	}

	public function GetSynopsis()
	{
		$ret = '';
		if ($this->GetProperty('restrict_to_group', 0)) {
			$groupops = \cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetProperty('group'));
			if ($group && isset($group->name)) {
				$mod = $this->formdata->pwfmod;
				$ret .= $mod->Lang('restricted_to_group', $group->name);
			}
		}
		return $ret;
	}

	public function buildList($select=FALSE)
	{
		$mod = $this->formdata->pwfmod;
		$userops = \cmsms()->GetUserOperations();
		if ($this->GetProperty('restrict_to_group', 0)) {
			$userlist = $userops->LoadUsersInGroup($this->GetProperty('group'));
		} else {
			$userlist = $userops->LoadUsers();
		}
		$c = count($userlist);
		if ($c) {
			$f = $this->GetProperty('show_userfirstname', 0);
			$l = $this->GetProperty('show_userlastname', 0);
			$u = $this->GetProperty('show_username', 0);
			$choices = [];
			if ($select) {
				$choices[' '.$this->GetProperty('select_label', $mod->Lang('select_one'))] = -1;
			}
			$indx = 1;
			for ($i=0; $i<$c; $i++) {
				$v = $userlist[$i];
				if ($v->active || !$this->GetProperty('active_only', 1)) {
					$parts = [];
					if ($f) {
						$parts[] = $v->firstname;
					}
					if ($l) {
						$parts[] = $v->lastname;
					}
					if ($u) {
						$parts[] = '('.$v->username.')';
					}
					$name = implode(' ', $parts);
					$choices[$name] = $indx;
					++$indx;
				}
			}
			return $choices;
		}
		return FALSE;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$admins = $this->buildList();
		if ($admins) {
			$ret = array_search($this->Value, $admins);
		} else {
			$ret = FALSE;
		}
		if (!$ret) {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->pwfmod->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		$choices = [];
		$groups = \cmsms()->GetGroupOperations()->LoadGroups();
		foreach ($groups as $one) {
			$choices[$one->name] = $one->id;
		}

		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_select_one_message'),
					$mod->CreateInputText($id, 'fp_select_label',
					$this->GetProperty('select_label', $mod->Lang('select_one')), 25, 128)];
		$main[] = [$mod->Lang('title_show_userfirstname'),
					$mod->CreateInputHidden($id, 'fp_show_userfirstname', 0).
					$mod->CreateInputCheckbox($id, 'fp_show_userfirstname', 1,
						$this->GetProperty('show_userfirstname', 1))];
		$main[] = [$mod->Lang('title_show_userlastname'),
					$mod->CreateInputHidden($id, 'fp_show_userlastname', 0).
					$mod->CreateInputCheckbox($id, 'fp_show_userlastname', 1,
						$this->GetProperty('show_userlastname', 1))];
		$main[] = [$mod->Lang('title_show_username'),
					$mod->CreateInputHidden($id, 'fp_show_username', 0).
					$mod->CreateInputCheckbox($id, 'fp_show_username', 1,
						$this->GetProperty('show_username', 0))];
		$main[] = [$mod->Lang('title_active_only'),
					$mod->CreateInputHidden($id, 'fp_active_only', 0).
					$mod->CreateInputCheckbox($id, 'fp_active_only', 1,
						$this->GetProperty('active_only', 1))];
		$main[] = [$mod->Lang('title_restrict_to_group'),
					$mod->CreateInputHidden($id, 'fp_restrict_to_group', 0).
					$mod->CreateInputCheckbox($id, 'fp_restrict_to_group', 1,
						$this->GetProperty('restrict_to_group', 0)).
					$mod->CreateInputDropdown($id, 'fp_group', $choices, -1,
						$this->GetProperty('group'))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$choices = $this->buildList(TRUE);
		if ($choices) {
			$mod = $this->formdata->pwfmod;
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Validate($id)
	{
		if ($this->Value) { //TODO CHECK anything will do?
			$val = TRUE;
			$this->ValidationMessage = '';
		} else {
			$val = FALSE;
			$this->ValidationMessage = $this->formdata->pwfmod->Lang('missing_type', $mod->Lang('admin'));
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
