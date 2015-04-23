<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This class Copyright (C) 2011 Robert Campbell <calguy1000@cmsmadesimple.org> */

require_once(cms_join_path(dirname(__FILE__),'DispositionEmailBase.class.php'));

class pwfDispositionEmailFromFEUProperty extends pwfDispositionEmailBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr,$params);
		$this->Type = 'DispositionEmailFromFEUProperty';
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = true;
		$this->NonRequirableField = true;
	}

	function StatusInfo()
	{
		$mod = $this->form_ptr->module_ptr;
		$ret = $mod->Lang('title_feu_property').': '.$this->GetOption('feu_property');
		$status = $this->TemplateStatus();
		if($status) $ret.='<br />'.$status;
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;

		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!is_object($feu))
		{
			// just act like a regular disposition...
			return array('main'=>array(array($mod->Lang('error_emailfromfeuprop'),$mod->Lang('error_nofeu'))));
		}

		$defns = $feu->GetPropertyDefns();
		if(!is_array($defns))
		{
			// just act like a regular disposition...
			return array('main'=>array(array($mod->Lang('error_emailfromfeuprop'),$mod->Lang('error_nofeudefns'))));
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
			return array('main'=>array(array($mod->Lang('error_emailfromfeuprop'),$mod->Lang('error_nofeudefns'))));
		}

		$ret = $this->PrePopulateAdminFormBase($formDescriptor, true);
		$main = $ret['main']; //assume it's there
		$waslast = array_pop($main); //keep the email to-type selector for last
		$keys = array_keys($opts);
		$main[] = array($mod->Lang('title_feu_property'),
				$mod->CreateInputDropdown($formDescriptor,'pwfp_opt_feu_property',
						   array_flip($opts),-1,
						   $this->GetOption('feu_property',$keys[0])),
				$mod->Lang('info_feu_property'));
		$main[] = $waslast;
		$ret['main'] = $main;
		return $ret;
	}


	function GetFieldInput($id,&$params,$returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!$feu) return FALSE;

		// get the proeprty name and data
		$prop = $this->GetOption('feu_property');
		if(!$prop) return FALSE;
		$defn = $feu->GetPropertyDefn($prop);
		if(!$defn) return FALSE;

		// get the property input field.
		$options = $feu->GetSelectOptions($prop);
		switch($defn['type'])
		{
		 case 4: // dropdown
		 case 5: // multiselect
		 case 7: // radio button group
			// rendered all as a dropdown field.
			$res = $mod->CreateInputDropdown($id,'pwfp__'.$this->Id,$options,-1,$this->GetCSSIdTag());
			break;
		 default:
			$res = FALSE;
		}
		return $res;
	}

	function GetHumanReadableValue($as_string = true)
	{
		$mod = $this->form_ptr->module_ptr;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		$prop = $this->GetOption('feu_property');

		$opts = array_flip($feu->GetSelectOptions($opts));
		if(isset($opts[$this->Value])) return $opts[$this->Value];
	}

	function DisposeForm($returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		if(!$feu) return array(false,'FrontEndUsers module not found');

		// get the property name
		$prop = $this->GetOption('feu_property');

		// get the list of emails that match this value.
		$users = $feu->GetUsersInGroup(-1,'','','',$prop,$this->Value);
		if(!is_array($users) || count($users) == 0)
		{
			// no matching users is not an error.
			return array(true,'');
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
