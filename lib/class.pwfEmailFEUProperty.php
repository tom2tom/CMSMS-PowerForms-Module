<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2011 Robert Campbell <calguy1000@cmsmadesimple.org>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class sends a pre-defined message to a destination selected from addresses
//recorded as a property of the FrontEndUsers module 

class pwfEmailFEUProperty extends pwfEmailBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->NonRequirableField = TRUE;
		$this->Type = 'EmailFEUProperty';
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('title_feu_property').': '.$this->GetOption('feu_property');
		$status = $this->TemplateStatus();
		if($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	function GetHumanReadableValue($as_string = TRUE)
	{
		$mod = $this->formdata->formsmodule;
		$prop = $this->GetOption('feu_property');
		$ret = FALSE;
		if($prop)
		{
			$feu = $mod->GetModuleInstance('FrontEndUsers');
			$opts = $feu->GetSelectOptions($prop);
			if(array_key_exists($this->Value,$opts)) //TODO check logic
				$ret = $opts[$this->Value]; //TODO if FALSE
			else
				$ret = $this->GetOption('unspecified',$mod->Lang('TODO'));
		}
		else
			$ret = $this->GetOption('unspecified',$mod->Lang('TODO'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!$feu)
		{
			return array('main'=>array(array(
			'<span style="color:red">'.$mod->Lang('error').'</span>',
			$mod->Lang('error_module_feu'))));
		}

		$defns = $feu->GetPropertyDefns();
		if(!is_array($defns))
		{
			return array('main'=>array(array(
			'<span style="color:red">'.$mod->Lang('error').'</span>',
			$mod->Lang('error_feudefns'))));
		}

		// check for dropdown or multiselect fields
		$opts = array();
		foreach($defns as $key => $data)
		{
			switch($data['type'])
			{
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
		if(!count($opts))
		{
			// just act like a regular disposition...
			return array('main'=>array(array(
			'<span style="color:red">'.$mod->Lang('error').'</span>',
			$mod->Lang('error_feudefns'))));
		}
//TODO
		$ret = $this->PrePopulateAdminFormCommonEmail($id,TRUE);
		$main = $ret['main']; //assume it's there
		$waslast = array_pop($main); //keep the email to-type selector for last
		$keys = array_keys($opts);
		$main[] = array($mod->Lang('title_feu_property'),
				$mod->CreateInputDropdown($id,'opt_feu_property',
						   array_flip($opts),-1,
						   $this->GetOption('feu_property',$keys[0])),
				$mod->Lang('help_feu_property'));
		$main[] = $waslast;
		$ret['main'] = $main;
		return $ret;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!$feu) return FALSE;

		// get the property name and data
		$prop = $this->GetOption('feu_property');
		if(!$prop) return FALSE;
		$defn = $feu->GetPropertyDefn($prop);
		if(!$defn) return FALSE;

		// get the property input field
		$options = $feu->GetSelectOptions($prop);
		switch($defn['type'])
		{
		 case 4: // dropdown
		 case 5: // multiselect
		 case 7: // radio button group
			// rendered all as a dropdown field.
			$res = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$options,-1,$this->GetCSSIdTag());
			break;
		 default:
			$res = FALSE;
		}
		return $res;
	}

	function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!$feu) return array(FALSE,'FrontEndUsers module not found'); //TODO translate

		// get the property name
		$prop = $this->GetOption('feu_property');

		// get the list of emails that match this value.
		$users = $feu->GetUsersInGroup(-1,'','','',$prop,$this->Value);
		if(!is_array($users) || count($users) == 0)
		{
			// no matching users is not an error.
			return array(TRUE,'');
		}

		$smarty = cmsms()->GetSmarty();
		$smarty_users = array();
		$destinations = array();
		$ucount = count($users);
		for($i = 0; $i < $ucount; $i++)
		{
			$rec =& $users[$i];
			unset($rec['password']);
			if($feu->GetPreference('username_is_email'))
			{
				$rec['email'] = $rec['username'];
				$destinations[] = $rec['username'];
			}
			else
			{
				$rec['email'] = $feu->GetEmail($rec['id']);
				$destinations[] = $rec['email'];
			}
			$smarty_users[$rec['username']] = $rec;
			$smarty->assign('users',$smarty_users);

			if($ucount == 1)
			{
				$smarty->assign('user_info',$users[0]);
			}
		}
		// send the form
		return $this->SendForm($destinations,$this->GetOption('email_subject'));
	}
}

?>
