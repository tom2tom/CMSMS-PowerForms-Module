<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

/*
 This class sends a pre-defined message to a destination selected from addresses
 recorded as a property of the FrontEndUsers module
 DEPRECATED - should be applied dynamically by FrontEndUsers module
*/
namespace PWForms;

class EmailFEUProperty extends EmailBase
{
	const MODNAME = 'FrontEndUsers'; //initiator/owner module name
	public $MenuKey = 'field_label'; //owner-module lang key for this field's menu label, used by PWForms
	public $mymodule; //used also by PWForms, do not rename

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'EmailFEUProperty';
		$this->mymodule = \cms_utils::get_module(self::MODNAME);
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + ['feu_property' => 12];
	}

	public function GetSynopsis()
	{
		if ($this->mymodule) {
			$ret = $this->formdata->pwfmod->Lang('title_feu_property').': '.$this->GetProperty('feu_property');
			$status = $this->TemplateStatus();
			if ($status) {
				$ret .= '<br />'.$status;
			}
			return $ret;
		}
		return $this->formdata->pwfmod->Lang('missing_module', self::MODNAME);
	}

	public function DisplayableValue($as_string = TRUE)
	{
		$mod = $this->formdata->pwfmod;
		$prop = $this->GetProperty('feu_property');
		$ret = FALSE;
		if ($prop) {
			$feu = \cms_utils::get_module('FrontEndUsers');
			$opts = $feu->GetSelectOptions($prop);
			if (array_key_exists($this->Value, $opts)) { //TODO check logic
				$ret = $opts[$this->Value];  //TODO if FALSE
			} else {
				$ret = $this->GetProperty('unspecified', $mod->Lang('unspecified'));
			}
		} else {
			$ret = $this->GetProperty('unspecified', $mod->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetDisplayType()
	{
		return $this->mymodule->Lang($this->MenuKey);
	}

	public function AdminPopulate($id)
	{
		$feu = $this->mymodule;
		if (!$feu) {
			return ['main'=>[$this->GetErrorMessage('err_module', self::MODNAME)]];
		}
		$defns = $feu->GetPropertyDefns();
		if (!is_array($defns)) {
			return ['main'=>[$this->GetErrorMessage('err_feudefns')]];
		}
		// check for dropdown or multiselect fields
		$opts = [];
		foreach ($defns as $key => $data) {
			switch ($data['type']) {
			 case 4: //dropdown
			 case 5: //multiselect
			 case 7: //radiobuttons
				$opts[$data['name']] = $data['prompt'];
				break;
			 default:
				// ignore other field types
				break;
			}
		}
		if (!$opts) {
			return ['main'=>[$this->GetErrorMessage('err_feudefns')]];
		}
		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, FALSE, TRUE);
		$waslast = array_pop($ret['main']); //keep the email to-type selector for last
		$keys = array_keys($opts);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_feu_property'),
				$mod->CreateInputDropdown($id, 'fp_feu_property', array_flip($opts), -1,
					$this->GetProperty('feu_property', $keys[0])),
				$mod->Lang('help_feu_property')];
		$main[] = $waslast;
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$feu = $this->mymodule;
		if (!$feu) {
			return $mod->Lang('err_module', self::MODNAME);
		}

		// get the property name and data
		$prop = $this->GetProperty('feu_property');
		if (!$prop) {
			return '';
		}
		$defn = $feu->GetPropertyDefn($prop);
		if (!$defn) {
			return '';
		}

		switch ($defn['type']) {
		 case 4: // dropdown
		 case 5: // multiselect
		 case 7: // radio button group
			// get the property input field
			$choices = $feu->GetSelectOptions($prop);
			// rendered all as a dropdown field.
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			$res = $this->SetClass($tmp);
			break;
		 default:
			$res = '';
		}
		return $res;
	}

	public function Dispose($id, $returnid)
	{
		$feu = $this->mymodule;
		if (!$feu) {
			return [FALSE,$this->formdata->pwfmod->Lang('err_module', self::MODNAME)];
		}

		// get the property name
		$prop = $this->GetProperty('feu_property');

		// get the list of emails that match this value.
		$users = $feu->GetUsersInGroup(-1, '', '', '', $prop, $this->Value);
		if (!is_array($users) || count($users) == 0) {
			// no matching users is not an error.
			return [TRUE,''];
		}

		$tplvars = [];
		$smarty_users = [];
		$destinations = [];
		$ucount = count($users);
		for ($i = 0; $i < $ucount; $i++) {
			$rec =& $users[$i];
			unset($rec['password']);
			if ($feu->GetPreference('username_is_email')) {
				$rec['email'] = $rec['username'];
				$destinations[] = $rec['username'];
			} else {
				$rec['email'] = $feu->GetEmail($rec['id']);
				$destinations[] = $rec['email'];
			}
			$smarty_users[$rec['username']] = $rec;
			$tplvars['users'] = $smarty_users;

			if ($ucount == 1) {
				$tplvars['user_info'] = $users[0];
			}
		}
		// send email(s)
		return $this->SendForm($destinations, $this->GetProperty('email_subject'), $tplvars);
	}
}
