<?php
/*
This is CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <@>
Derived in part from FormBuilder module, copyright (C) 2005-2012, Samuel Goldstein <sjg@cmsmodules.com>
This project's forge-page is: http://dev.cmsmadesimple.org/projects/powerforms

This module is free software. You can redistribute it and/or modify it under
the terms of the GNU Affero General Public License as published by the Free
Software Foundation, either version 3 of that License, or (at your option)
any later version.

This module is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.
Read the License online: http://www.gnu.org/licenses/licenses.html#AGPL
*/

class PWForms extends CMSModule
{
	const ASYNCSPACE = 'PWFm';
	const LENSHORTVAL = 64;

	public $before20;
	public $oldtemplates;
	//these are populated when first used
	public $field_types = FALSE; //array of all usable field classnames
	public $std_field_types = FALSE; //subset of $field_types, classes for use in 'fast-adder' simple mode
	//this regex is used by several field-types, not just email*
	//pretty much everything is valid, provided there's an '@' in there!
	//(we're concerned more about typo's than format!)
	public $email_regex = '/.+@.+\..+/';

	public function __construct()
	{
		parent::__construct();
		global $CMS_VERSION;
		$this->before20 = (version_compare($CMS_VERSION, '2.0') < 0);
		$this->oldtemplates = $this->before20 || TRUE; //TODO

//		spl_autoload_register([$this, 'cmsms_spacedload']);

		$this->RegisterModulePlugin(TRUE);
	}

/*	public function __destruct()
	{
		spl_autoload_unregister([$this, 'cmsms_spacedload']);
		if (function_exists('parent::__destruct')) {
			parent::__destruct();
		}
	}
*/
	/* namespace autoloader - CMSMS default autoloader doesn't do spacing */
/*	private function cmsms_spacedload($class)
	{
		$prefix = get_class().'\\'; //our namespace prefix
		$o = ($class[0] != '\\') ? 0:1;
		$p = strpos($class, $prefix, $o);
		if ($p === 0 || ($p == 1 && $o == 1)) {
			// directory for the namespace
			$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		} else {
			$p = strpos($class, '\\', 1);
			if ($p === FALSE) {
				return;
			}
			$prefix = substr($class, $o, $p-$o);
			$bp = dirname(__DIR__).DIRECTORY_SEPARATOR.$prefix.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
		}
		// relative class name
		$len = strlen($prefix) + $o;
		$relative_class = trim(substr($class, $len), '\\');

		if (($p = strrpos($relative_class, '\\', -1)) !== FALSE) {
			$relative_dir = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
			$bp .= substr($relative_dir, 0, $p+1);
			$base = substr($relative_dir, $p+1);
		} else {
			$base = $relative_class;
		}

		$fp = $bp.'class.'.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
			return;
		}
		$fp = $bp.$base.'.php';
		if (file_exists($fp)) {
			include $fp;
		}
	}
*/
	public function AllowAutoInstall()
	{
		return FALSE;
	}

	public function AllowAutoUpgrade()
	{
		return FALSE;
	}

	//for 1.11+
	public function AllowSmartyCaching()
	{
		return FALSE;
	}

	public function GetName()
	{
		return 'PWForms';
	}

	public function GetFriendlyName()
	{
		return $this->Lang('friendly_name');
	}

	public function GetHelp()
	{
		return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'modhelp.htm');
	}

	public function GetVersion()
	{
		return '0.7';
	}

	public function GetAuthor()
	{
		return 'tomphantoo';
	}

	public function GetAuthorEmail()
	{
		return 'tpgww@onepost.net';
	}

	public function GetChangeLog()
	{
		return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'changelog.htm');
	}

	public function GetDependencies()
	{
		return [];
	}

	public function GetEventDescription($eventname)
	{
		return $this->Lang('event_info_'.$eventname);
	}

	public function GetEventHelp($eventname)
	{
		return $this->Lang('event_help_'.$eventname);
	}

	public function MinimumCMSVersion()
	{
		return '1.11'; //need smarty 3
	}

/*	public function MaximumCMSVersion()
	{
	}
*/
	public function InstallPostMessage()
	{
		return $this->Lang('post_install');
	}

	public function UninstallPreMessage()
	{
		return $this->Lang('confirm_uninstall');
	}

	public function UninstallPostMessage()
	{
		return $this->Lang('post_uninstall');
	}

	public function IsPluginModule()
	{
		return TRUE;
	}

	public function HasCapability($capability, $params = [])
	{
		switch ($capability) {
			case 'plugin':
			case 'tasks':
				return TRUE;
		}
		return FALSE;
	}

	public function HasAdmin()
	{
		return TRUE;
	}

	public function LazyLoadAdmin()
	{
		return TRUE;
	}

	public function GetAdminSection()
	{
		return 'extensions';
	}

	public function GetAdminDescription()
	{
		return $this->Lang('admin_desc');
	}

	public function VisibleToAdminUser()
	{
		return self::_CheckAccess();
	}

	public function GetHeaderHTML()
	{
		return '<link rel="stylesheet" type="text/css" id="adminstyler" href="'.$this->GetModuleURLPath().'/css/admin.css" />';
	}

/*	public function AdminStyle()
	{
	}
*/
	public function SuppressAdminOutput(&$request)
	{
		if (isset($_SERVER['QUERY_STRING'])) {
			foreach ([
			'require_field',
			'delete_field',
			'get_template',
			'populate_template',
			'export_form',
			] as $name) {
				if (strpos($_SERVER['QUERY_STRING'], $name) !== FALSE) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

/*	public function SupportsLazyLoading()
	{
		return TRUE;
	}
*/
	public function LazyLoadFrontend()
	{
		return TRUE;
	}

	//setup for pre-1.10
	public function SetParameters()
	{
		self::InitializeAdmin();
		self::InitializeFrontend();
	}

	//partial setup for pre-1.10, backend setup for 1.10+
	public function InitializeFrontend()
	{
		$this->RestrictUnknownParams();
		$this->SetParameterType('captcha_input', CLEAN_STRING);
		$this->SetParameterType('form', CLEAN_STRING);
		$this->SetParameterType('form_id', CLEAN_INT);
//		$this->SetParameterType('field_id',CLEAN_INT);
//		$this->SetParameterType('browser_id',CLEAN_INT);
//		$this->SetParameterType('in_admin',CLEAN_INT);
//		$this->SetParameterType('in_browser',CLEAN_INT);
		$this->SetParameterType('preload', CLEAN_NONE);
		$this->SetParameterType('resume', CLEAN_STRING);
		$this->SetParameterType(CLEAN_REGEXP.'/pwfp_\d{3}_.*/', CLEAN_STRING); //or NONE?
		$this->SetParameterType(CLEAN_REGEXP.'/value_.*/', CLEAN_STRING); //or NONE?
	}

	/**
	Partial setup for 1.10+
	*/
	public function InitializeAdmin()
	{
		//document only the parameters relevant for external use
		$this->CreateParameter('form', '', $this->Lang('param_form_alias'), FALSE);
		$this->CreateParameter('value_*', '', $this->Lang('param_passed_from_tag'));
	}

	public function DoAction($action, $id, $params, $returnid=-1)
	{
		switch ($action) {
		 case 'default':
			$action = 'show_form';
			break;
		}
		parent::DoAction($action, $id, $params, $returnid);
	}

	public function get_tasks()
	{
		if ($this->before20) {
			return array(
			 new pwfClearcacheTask(),
			 new pwfClearTablesTask(),
			);
		} else {
			return [
			 new PWForms\ClearcacheTask(),
			 new PWForms\ClearTablesTask(),
			];
		}
	}

// ~~~~~~~~~~~~~~~~~~~~~ NON-CMSModule METHODS ~~~~~~~~~~~~~~~~~~~~~

	public function _CheckAccess($permission='')
	{
		switch ($permission) {
		 case 'ModifyPFForms':
			if ($this->CheckPermission('ModifyPFForms')) {
				return TRUE;
			}
			$desc = '"'.$this->Lang('perm_modify').'"';
			break;
		 case 'ModifyPFSettings':
			if ($this->CheckPermission('ModifyPFSettings')) {
				return TRUE;
			}
			$desc = '"'.$this->Lang('perm_admin').'"';
			break;
		 default:
			if ($this->CheckPermission('ModifyPFForms')
			|| $this->CheckPermission('ModifyPFSettings')) {
				return TRUE;
			}
			$desc = $this->Lang('perm_any');
		}
		echo '<p class="error_message">'.$this->Lang('you_need_permission', $desc).'</p>';
		return FALSE;
	}

	public function _GetActiveTab(&$params)
	{
		if (!empty($params['active_tab'])) {
			return $params['active_tab'];
		} else {
			return 'maintab';
		}
	}

	/*
	Returns a pair of object-name-prefixes, like 'pwfp_NNN_', for constructing
	objects to be 'submitted' by a form, without being dropped (see
	InitializeFrontend()), and with some bot-avoidance attributes
	Submitted data will be accepted if the parameter-keys of that data include
	either the 'current-period' prefix or the 'previous-period' one
	*/
	public function _GetTokens()
	{
		$now = time();
		$base = floor($now / (84600 * 1800)) * 1800; //start of current 30-mins
		$day = date('j', $now);
		return [
			'pwfp_'.$this->_Hash($base+$day).'_',
			'pwfp_'.$this->_Hash($base-1800+$day-1).'_'
		];
	}

	private function _Hash($num)
	{
		//djb2a hash : see http://www.cse.yorku.ca/~oz/hash.html
		$n = ''.$num;
		$l = strlen($n);
		$hash = 5381;
		for ($i = 0; $i < $l; $i++) {
			$hash = ($hash + ($hash << 5)) ^ $n[$i]; //aka $hash = $hash*33 ^ $n[$i]
		}
		return substr($hash, -3);
	}

	//$success may be boolean or 'warn'
	public function _PrettyMessage($text, $success=TRUE, $key=TRUE)
	{
		$msg = ($key) ? $this->Lang($text) : $text;
		if ($success === 'warn') {
			return "<div class=\"pagewarning\"><p class=\"pagemessage\">$msg</p></div>";
		} elseif ($success) {
			return $this->ShowMessage($msg);
		} else {
			$msg = $this->ShowErrors($msg);
			//strip the link
			$pos = strpos($msg, '<a href=');
			$part1 = ($pos !== FALSE) ? substr($msg, 0, $pos) : '';
			$pos = strpos($msg, '</a>', $pos);
			$part2 = ($pos !== FALSE) ? substr($msg, $pos+4) : $msg;
			$msg = $part1.$part2;
			return $msg;
		}
	}

	public function RegisterField($classfilepath)
	{
		$basename = basename($classfilepath);
		$fp = cms_join_path($this->GetModulePath(), 'lib', $basename);
		@copy($classfilepath, $fp);

		$classname = PWForms\Utils::FileClassName($basename);
		//cache field data to be ready for restarts
		$imports = $this->GetPreference('imported_fields');
		if ($imports) {
			$imports = unserialize($imports);
			$imports[] = $classname;
		} else {
			$imports = [$classname];
		}
		$this->SetPreference('imported_fields', serialize($imports));
		if ($this->field_types) {
			PWForms\Utils::Show_Field($this, $classname);
		}
	}

	public function DeregisterField($classfilepath)
	{
		$basename = basename($classfilepath);
		$classname = PWForms\Utils::FileClassName($basename);
		$fp = cms_join_path($this->GetModulePath(), 'lib', $basename);
		if (is_file($fp)) {
			unlink($fp);
		}
		if ($this->field_types) {
			$menuname = array_search($classname, $this->field_types);
			if ($menuname !== FALSE) {
				unset($this->field_types[$menuname]);
			}
		}
		//uncache this data
		$imports = $this->GetPreference('imported_fields');
		if ($imports) {
			$imports = unserialize($imports);
			$key = array_search($classname, $imports);
			if ($key !== FALSE) {
				unset($imports[$key]);
			}
			if ($imports) {
				$this->SetPreference('imported_fields', serialize($imports));
			} else {
				$this->SetPreference('imported_fields', FALSE);
			}
		}

		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		$sql = 'SELECT field_id FROM '.$pre.'module_pwf_field WHERE type=?';
		$classname = substr($classname, 3); //strip 'pwf' namespace
		$ids = $db->GetCol($sql, [$classname]);
		if ($ids) {
			$join = implode(',', $ids);
			$sql = 'DELETE FROM '.$pre.'module_pwf_field WHERE field_id IN('.$join.')';
			$db->Execute($sql);
		}
	}
}
