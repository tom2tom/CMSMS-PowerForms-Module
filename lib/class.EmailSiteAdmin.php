<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for email to a specified admin user

namespace PWForms;

class EmailSiteAdmin extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = TRUE;
//		$this->IsInput = TRUE; no need to preserve input value
		$this->Required = TRUE;
		$this->Type = 'EmailSiteAdmin';
	}

	public function GetSynopsis()
	{
		$ret = '';
		if ($this->GetProperty('restrict_to_group', 0)) {
			$groupops = \cmsms()->GetGroupOperations();
			$group = $groupops->LoadGroupByID($this->GetProperty('group'));
			if ($group && isset($group->name)) {
				$mod = $this->formdata->formsmodule;
				$ret .= ','.$mod->Lang('restricted_to_group', $group->name);
			}
		}
		$status = $this->TemplateStatus();
		if ($status) {
			if ($ret) {
				$ret .= '<br />';
			}
			$ret .= $status;
		}
		return $ret;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$userops = \cmsms()->GetUserOperations();
		if ($this->GetProperty('restrict_to_group', 0)) {
			$userlist = $userops->LoadUsersInGroup($this->GetProperty('group'));
		} else {
			$userlist = $userops->LoadUsers();
		}

		if (isset($userlist[$this->Value - 1])) {
			$ret = $userlist[$this->Value - 1]->firstname . ' '. $userlist[$this->Value - 1]->lastname;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
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

		$mod = $this->formdata->formsmodule;

		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, FALSE, TRUE);
		$waslast = array_pop($main); //keep the email to-type selector for later
		$main[] = [$mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id, 'fp_select_one',
				$this->GetProperty('select_one', $mod->Lang('select_one')), 25, 128)];
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
		$main[] = $waslast;
		$main[] = [$mod->Lang('title_restrict_to_group'),
				$mod->CreateInputHidden($id, 'fp_restrict_to_group', 0).
				$mod->CreateInputCheckbox($id, 'fp_restrict_to_group', 1,
					$this->GetProperty('restrict_to_group', 0)).
				$mod->CreateInputDropdown($id, 'fp_group', $choices, -1, $this->GetProperty('group'))];
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
	}

	public function AdminValidate($id)
	{
		$messages = [];
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}

		$addr = $this->GetProperty('email_from_address');
		if ($addr) {
			list($rv, $msg) = $this->validateEmailAddr($addr);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$mod = $this->formdata->formsmodule;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('source'));
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
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
			$choices[' '.$this->GetProperty('select_one', $mod->Lang('select_one'))]=-1;
			for ($i=0; $i<$c; $i++) {
				$parts = [];
				$v = $userlist[$i];
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
				$choices[$name] = $i+1;
			}
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		} else {
			return $mod->Lang('no_admins');
		}
	}

	public function Validate($id)
	{
		if ($this->Value) {
			$val = TRUE;
			$this->ValidationMessage = '';
		} else {
			$val = FALSE;
			$mod = $this->formdata->formsmodule;
			$this->ValidationMessage = $mod->Lang('missing_type', $mod->Lang('admin'));
		}
		$this->SetStatus('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	public function Dispose($id, $returnid)
	{
		if ($this->HasValue()) {
			$userops = \cmsms()->GetUserOperations();

			if ($this->GetProperty('restrict_to_group', 0)) {
				$userlist = $userops->LoadUsersInGroup($this->GetProperty('group'));
			} else {
				$userlist = $userops->LoadUsers();
			}

			$dest = [$userlist[$this->Value - 1]->email];
			return $this->SendForm($dest, $this->GetProperty('email_subject'));
		} else {
			return [TRUE,''];
		}
	}
}
