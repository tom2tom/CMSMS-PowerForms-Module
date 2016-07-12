<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2011 Robert Campbell <calguy1000@cmsmadesimple.org>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class sends a pre-defined message to a destination selected from addresses
//recorded as a property of the FrontEndUsers module 

namespace PWForms;

class EmailFEUProperty extends EmailBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->Type = 'EmailFEUProperty';
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('title_feu_property').': '.$this->GetOption('feu_property');
		$status = $this->TemplateStatus();
		if ($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	public function GetHumanReadableValue($as_string = TRUE)
	{
		$mod = $this->formdata->formsmodule;
		$prop = $this->GetOption('feu_property');
		$ret = FALSE;
		if ($prop) {
			$feu = $mod->GetModuleInstance('FrontEndUsers');
			$opts = $feu->GetSelectOptions($prop);
			if (array_key_exists($this->Value,$opts)) //TODO check logic
				$ret = $opts[$this->Value]; //TODO if FALSE
			else
				$ret = $this->GetOption('unspecified',$mod->Lang('unspecified'));
		} else
			$ret = $this->GetOption('unspecified',$mod->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if (!$feu)
			return array('main'=>array('<span style="color:red">'.$mod->Lang('error').'</span>',
			'',$mod->Lang('error_module_feu')));

		$defns = $feu->GetPropertyDefns();
		if (!is_array($defns))
			return array('main'=>array('<span style="color:red">'.$mod->Lang('error').'</span>',
				'',$mod->Lang('error_feudefns')));

		// check for dropdown or multiselect fields
		$opts = array();
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
		if (!count($opts))
			return array('main'=>array('<span style="color:red">'.$mod->Lang('error').'</span>',
				'',$mod->Lang('error_feudefns')));

		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id,TRUE);
		$waslast = array_pop($ret['main']); //keep the email to-type selector for last
		$keys = array_keys($opts);
		$main[] = array($mod->Lang('title_feu_property'),
				$mod->CreateInputDropdown($id,'opt_feu_property',array_flip($opts),-1,
					$this->GetOption('feu_property',$keys[0])),
				$mod->Lang('help_feu_property'));
		$main[] = $waslast;
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if (!$feu) return '';

		// get the property name and data
		$prop = $this->GetOption('feu_property');
		if (!$prop) return '';
		$defn = $feu->GetPropertyDefn($prop);
		if (!$defn) return '';

		switch ($defn['type']) {
		 case 4: // dropdown
		 case 5: // multiselect
		 case 7: // radio button group
			// get the property input field
			$choices = $feu->GetSelectOptions($prop);
			// rendered all as a dropdown field.
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			$res = $this->SetClass($tmp);
			break;
		 default:
			$res = '';
		}
		return $res;
	}

	public function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if (!$feu) return array(FALSE,'FrontEndUsers module not found'); //TODO translate

		// get the property name
		$prop = $this->GetOption('feu_property');

		// get the list of emails that match this value.
		$users = $feu->GetUsersInGroup(-1,'','','',$prop,$this->Value);
		if (!is_array($users) || count($users) == 0) {
			// no matching users is not an error.
			return array(TRUE,'');
		}

		$tplvars = array();
		$smarty_users = array();
		$destinations = array();
		$ucount = count($users);
		for ($i = 0; $i < $ucount; $i++)
		{
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
		return $this->SendForm($destinations,$this->GetOption('email_subject'),$tplvars);
	}
}
