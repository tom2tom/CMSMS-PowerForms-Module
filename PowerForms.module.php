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
	var $std_field_types = FALSE; //subset : classes for use in 'fast-adder' simple mode
	var $disp_field_types = FALSE; //subset : disposition classes 
//	var $all_validation_types = FALSE; //accumulated validations NOT USED
	var $email_regex = FALSE; //used beyond email dispatchers

	function __construct()
	{
		parent::__construct();
		require_once cms_join_path(dirname(__FILE__),'lib','class.pwfData.php');
		$this->RegisterModulePlugin();
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
		return new pwfClearLogTask();
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

	function SupportsLazyLoading()
	{
		return TRUE;
	}

	function LazyLoadFrontend()
	{
		return TRUE;
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
		$this->SetParameterType(CLEAN_REGEXP.'/pwfp_.*/',CLEAN_STRING);
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
		if($params == NULL)
			return $fd;

		if(isset($params['form_id']))
			$fd->Id = (int)$params['form_id'];
		if(isset($params['form_alias']))
			$fd->Alias = trim($params['form_alias']);
		return $fd;
	}

	function CustomCreateInputType($id,$name,$value='',$size=10,$maxlength=255,$addttext='',$type='text')
	{
		$id = cms_htmlentities($id);
		$name = cms_htmlentities($name);
		$value = cms_htmlentities($value);
		$size = cms_htmlentities($size);
		$maxlength = cms_htmlentities($maxlength);

		$value = str_replace('"', '&quot;', $value);

		$text = '<input type="'.$type.'" name="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
		if($addttext != '')
			$text .= ' ' . $addttext;
		$text .= " />\n";
		return $text;
	}

	function CustomCreateInputSubmit($id,$name,$value='',$addttext='',$image='',$confirmtext='')
	{
		$id = cms_htmlentities($id);
		$name = cms_htmlentities($name);
		$image = cms_htmlentities($image);
		$text = '<input name="'.$id.$name.'" value="'.$value.'" type=';
		if($image != '')
		{
			$text .= '"image"';
			$config = cmsms()->GetConfig();
			$img = $config['root_url'] . '/' . $image;
			$text .= ' src="'.$img.'"';
		}
		else
			$text .= '"submit"';

		if($confirmtext != '')
			$text .= ' onclick="return confirm(\''.$confirmtext.'\');"';
		if($addttext != '')
			$text .= ' '.$addttext;
		$text .= ' />';
		return $text . "\n";
	}

	function RegisterField($classfilepath,$menulabel)
	{
		if(!$this->field_types)
			pwfUtils::Initialize($this);
		$basename = basename($classfilepath);
		$fp = cms_join_path($this->GetModulePath(),'lib',$basename);
		copy($classfilepath,$fp);
		require $fp;
		$classname = pwfUtils::FileClassName($basename);
		$params = array();
		$formdata = $this->GetFormData($params);
		$obfld = new $classname($formdata,$params);
		if(!($obfld->IsInput || $obfld->sortable)) //TODO check this
		{
			$menuname = '-'.$menulabel;
		}
		elseif($obfld->IsDisposition)
		{
			$menuname = '*'.$menulabel;
			$this->disp_field_types[$menuname] = $classname;
			uksort($this->disp_field_types,array('pwfUtils','fieldcmp'));
		}
		$this->field_types[$menuname] = $classname;
		uksort($this->field_types,array('pwfUtils','fieldcmp'));
	}
	
	function DeregisterField($classfilepath)
	{
		$basename = basename($classfilepath);
		$classname = pwfUtils::FileClassName($basename);
		$fp = cms_join_path($this->GetModulePath(),'lib',$basename);
		if(is_file($fp))
			unlink($fp);
		$menuname = array_search($classname,$this->field_types);
		if($menuname !== FALSE)
			unset($this->field_types[$menuname]);
		$menuname = array_search($classname,$this->disp_field_types);
		if($menuname !== FALSE)
			unset($this->field_types[$menuname]);
	}

}

?>
