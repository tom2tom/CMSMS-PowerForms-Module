<?php
#------------------------------------------------------------------------
# This is CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <@>
# Derived in part from FormBuilder module, copyright (C) 2005-2012, Samuel Goldstein <sjg@cmsmodules.com>
# This project's forge-page is: http://dev.cmsmadesimple.org/projects/powerforms
#
# This module is free software. You can redistribute it and/or modify it under
# the terms of the GNU Affero General Public License as published by the Free
# Software Foundation, either version 3 of that License, or (at your option)
# any later version.
#
# This module is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License (www.gnu.org/licenses/licenses.html#AGPL)
# for more details.
#-----------------------------------------------------------------------

class PowerForms extends CMSModule
{
	//these are populated when first used
	var $field_types = FALSE; //array of all usable field classnames
	var $std_field_types = FALSE; //subset of $field_types, classes for use in 'fast-adder' simple mode
	//this regex is used by several field-types, not just email*
	//pretty much everything is valid, provided there's an '@' in there!
	//(we're concerned more about typo's than format!)
	var $email_regex = '/.+@.+\..+/';
	var $mutex = NULL; //object for serialising access, setup @ 1st use
	var $cache = NULL; //object for cacheing of formdata objects, setup @ 1st use

	function __construct()
	{
		parent::__construct();
		require_once cms_join_path(dirname(__FILE__),'lib','class.pwfData.php');
		$this->RegisterModulePlugin(TRUE);
	}

	function AllowAutoInstall()
	{
		return FALSE;
	}

	function AllowAutoUpgrade()
	{
		return FALSE;
	}

	function InstallPostMessage()
	{
		return $this->Lang('post_install');
	}

	function UninstallPreMessage()
	{
		return $this->Lang('confirm_uninstall');
	}

	function UninstallPostMessage()
	{
		return $this->Lang('post_uninstall');
	}

	function GetName()
	{
		return 'PowerForms';
	}

	function GetFriendlyName()
	{
		return $this->Lang('friendly_name');
	}

	function GetHelp()
	{
		return $this->Lang('help_module');
	}

	function GetVersion()
	{
		return '0.7';
	}

	function GetAuthor()
	{
		return 'tomphantoo';
	}

	function GetAuthorEmail()
	{
		return 'tpgww@onepost.net';
	}

	function GetAdminDescription()
	{
		return $this->Lang('admin_desc');
	}

	function GetChangeLog()
	{
		$fn = cms_join_path(dirname(__FILE__),'include','changelog.inc');
		return ''.@file_get_contents($fn);
	}

	function GetDependencies()
	{
		return array();
	}

	function GetEventDescription($eventname)
	{
		return $this->Lang('event_info_'.$eventname);
	}

	function GetEventHelp($eventname)
	{
		return $this->Lang('event_help_'.$eventname);
	}

	function get_tasks()
	{
		return new pwfClearTablesTask();
	}

	function MinimumCMSVersion()
	{
		return '1.10'; //need class autoloading
	}

	function MaximumCMSVersion()
	{
		return '1.19.99';
	}

	function IsPluginModule()
	{
		return TRUE;
	}

	function HasAdmin()
	{
		return TRUE;
	}

	function LazyLoadAdmin()
	{
		return FALSE;
	}

	function GetAdminSection()
	{
		return 'extensions';
	}

	function VisibleToAdminUser()
	{
		return $this->CheckAccess();
	}

/*	see AdminStyle()
	function GetHeaderHTML()
	{
		return '<link rel="stylesheet" type="text/css" href="'.
			$this->GetModuleURLPath().'/css/admin.css" />';
	}
*/
	function AdminStyle()
	{
		$fn = cms_join_path(dirname(__FILE__),'css','admin.css');
		return ''.@file_get_contents($fn);
	}

	function SuppressAdminOutput(&$request)
	{
		if(isset($_SERVER['QUERY_STRING']))
		{
			if(strpos($_SERVER['QUERY_STRING'],'export_form') !== FALSE)
				return TRUE;
			if(strpos($_SERVER['QUERY_STRING'],'get_template') !== FALSE)
				return TRUE;
		}
		return FALSE;
	}

/*	function SupportsLazyLoading()
	{
		return TRUE;
	}
*/
	function LazyLoadFrontend()
	{
		return FALSE;
	}

	//setup for pre-1.10
	function SetParameters()
	{
		$this->InitializeAdmin();
		$this->InitializeFrontend();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	function InitializeFrontend()
	{
		$this->RestrictUnknownParams();
		$this->SetParameterType('form',CLEAN_STRING);
		$this->SetParameterType('form_id',CLEAN_INT);
		$this->SetParameterType('field_id',CLEAN_INT);
		$this->SetParameterType('response_id',CLEAN_INT);
		$this->SetParameterType('captcha_input',CLEAN_STRING);
		$this->SetParameterType(CLEAN_REGEXP.'/pwfp_\d{3}_.*/',CLEAN_STRING);
		$this->SetParameterType(CLEAN_REGEXP.'/value_.*/',CLEAN_STRING);
	}

	/**
	Partial setup for 1.10+
	*/
	function InitializeAdmin()
	{
		//document only the parameters relevant for external (page-tag) usage
		$this->CreateParameter('form','',$this->Lang('param_form_alias'),FALSE);
//		$this->CreateParameter('form_id',-1,$this->Lang('param_form_id'));
//		$this->CreateParameter('field_id',-1,$this->Lang('param_field_id'));
//		$this->CreateParameter('response_id',-1,$this->Lang('param_response_id'));
//		$this->CreateParameter('pwfp_*','',$this->Lang('param_general'));
		$this->CreateParameter('value_*','',$this->Lang('param_passed_from_tag'));
	}

// ~~~~~~~~~~~~~~~~~~~~~ NON-CMSModule METHODS ~~~~~~~~~~~~~~~~~~~~~

	function CheckAccess($permission='')
	{
		switch($permission)
		{
		 case 'ModifyPFForms':
 			if($this->CheckPermission('ModifyPFForms'))
				return TRUE;
			$desc = '"'.$this->Lang('perm_modify').'"';
			break;
		 case 'ModifyPFSettings':
			if($this->CheckPermission('ModifyPFSettings'))
				return TRUE;
			$desc = '"'.$this->Lang('perm_admin').'"';
			break;
		 default:
			if($this->CheckPermission('ModifyPFForms')
			|| $this->CheckPermission('ModifyPFSettings'))
				return TRUE;
			$desc = $this->Lang('perm_any');
		}
		echo '<p class="error">'.$this->Lang('you_need_permission',$desc).'</p>';
		return FALSE;
	}

	function GetActiveTab(&$params)
	{
		if(!empty($params['active_tab']))
		    return $params['active_tab'];
		else
			return 'maintab';
	}

	function PrettyMessage($text,$success=TRUE,$faillink=FALSE,$key=TRUE)
	{
		$base = ($key) ? $this->Lang($text) : $text;
		if ($success)
			return $this->ShowMessage($base);
		else
		{
			$msg = $this->ShowErrors($base);
			if ($faillink == FALSE)
			{
				//strip the link
				$pos = strpos($msg,'<a href=');
				$part1 = ($pos !== FALSE) ? substr($msg,0,$pos) : '';
				$pos = strpos($msg,'</a>',$pos);
				$part2 = ($pos !== FALSE) ? substr($msg,$pos+4) : $msg;
				$msg = $part1.$part2;
			}
			return $msg;
		}
	}

	function &GetFormData(&$params=NULL)
	{
		$fd = new pwfData();

		$fd->formsmodule =& $this;
		list($fd->current_prefix,$fd->prior_prefix) = $this->GetTokens();

		if($params == NULL)
			return $fd;

		if(isset($params['form_id']))
			$fd->Id = (int)$params['form_id'];
		if(isset($params['form_alias']))
			$fd->Alias = trim($params['form_alias']);
		return $fd;
	}

	/*
	Returns a pair of object-name-prefixes, like 'pwfp_NNN_', for constructing
	objects to be 'submitted' by a form, without being dropped (see
	InitializeFrontend()), and with some bot-avoidance attributes
	Submitted data will be accepted if the parameter-keys of that data include
	either the 'current-period' prefix or the 'previous-period' one
	*/
	function GetTokens()
	{
		$now = time();
		$base = floor($now / (84600 * 1800)) * 1800; //start of current 30-mins
		$day = date('j',$now);
		return array(
			'pwfp_'.$this->Hash($base+$day).'_',
			'pwfp_'.$this->Hash($base-1800+$day-1).'_'
		);
	}

	private function Hash($num)
	{
		//djb2 hash : see http://www.cse.yorku.ca/~oz/hash.html
		$n = ''.$num;
		$l = strlen($n);
		$hash = 5381;
		for($i = 0; $i < $l; $i++)
			$hash = $hash * 33 + $n[$i];
		return substr($hash,-3);
	}

	function RegisterField($classfilepath)
	{
		$basename = basename($classfilepath);
		$fp = cms_join_path($this->GetModulePath(),'lib',$basename);
		copy($classfilepath,$fp);
		
		$classname = pwfUtils::FileClassName($basename);
		//cache field data to be ready for restarts
		$imports = $this->GetPreference('imported_fields');
		if($imports)
		{
			$imports = unserialize($imports);
			$imports[] = $classname;
		}
		else
		{
			$imports = array($classname);
		}
		$this->SetPreference('imported_fields',serialize($imports));
		if($this->field_types)
			pwfUtils::Show_Field($this,$classname);
	}
	
	function DeregisterField($classfilepath)
	{
		$basename = basename($classfilepath);
		$classname = pwfUtils::FileClassName($basename);
		$fp = cms_join_path($this->GetModulePath(),'lib',$basename);
		if(is_file($fp))
			unlink($fp);
		if($this->field_types)
		{
			$menuname = array_search($classname,$this->field_types);
			if($menuname !== FALSE)
				unset($this->field_types[$menuname]);
		}
		//uncache this data
		$imports = $this->GetPreference('imported_fields');
		if($imports)
		{
			$imports = unserialize($imports);
			$key = array_search($classname,$imports);
			if($key !== FALSE)
				unset($imports[$key]);
			if($imports)
				$this->SetPreference('imported_fields',serialize($imports));
			else
				$this->SetPreference('imported_fields',FALSE);
		}
	}

}

?>
