<?php
#------------------------------------------------------------------------
# This is CMS Made Simple module: PowerForms
# Portions copyright (C) 2012-2015 Tom Phane <@>
# Derived from FormBuilder module copyright (C) 2005-2012, Samuel Goldstein <sjg@cmsmodules.com>
# This project's forge-page is: http://dev.cmsmadesimple.org/projects/powerforms
#
# This module is free software; you can redistribute it and/or modify it under
# the terms of the GNU Affero General Public License as published by the Free
# Software Foundation; either version 3 of the License, or (at your option)
# any later version.
#
# This module is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License (www.gnu.org/licenses/licenses.html#AGPL)
# for more details
#-----------------------------------------------------------------------
define('MailerReqVersion', '1.73'); //minumum acceptable version of CMSMailer module

class FormBuilder extends CMSModule
{
	var $field_types;
	var $disp_field_types;
	var $std_field_types;
	var $all_validation_types;
	var $module_id; //TODO stupid
	var $email_regex; //used beyond email dispatchers
	var $email_regex_relaxed;
	var $dbHandle;

	function __construct()
	{
		parent::__construct();

		$this->RegisterModulePlugin();

		$this->module_id = '';
		$this->email_regex = "/^([\w\d\.\-\_])+\@([\w\d\.\-\_]+)\.(\w+)$/i";
		$this->email_regex_relaxed = "/^([\w\d\.\-\_])+\@([\w\d\.\-\_])+$/i";
		$this->dbHandle = cmsms()->GetDb();

//		require_once (cms_join_path(dirname(__FILE__),'lib','Form.class.php')); //TODO as needed
//		require_once (cms_join_path(dirname(__FILE__),'lib','FieldBase.class.php'));
//		$path = cms_join_path(dirname(__FILE__),'lib');
//		set_include_path(get_include_path().PATH_SEPARATOR.$path);
	}

	function initialize()
	{
		$dir = opendir(cms_join_path(dirname(__FILE__),'lib'));
		$this->field_types = array();
		$feu = $this->GetModuleInstance('FrontEndUsers');
		$mail = $this->GetModuleInstance('CMSMailer');
		if($mail != false)
		{
			if(version_compare($mail->GetVersion(),MailerReqVersion) < 0) $mail = false;
		}

		while($filespec = readdir($dir))
		{
		  if(!endswith($filespec,'.php')) continue;
			if(strpos($filespec,'Field') === false && strpos($filespec,'Disposition') === false)
			{
				continue;
			}
			if($mail == false && strpos($filespec,'Disposition') !== false && strpos($filespec,'Email') != false)
			{
				continue;
			}
			if($feu == false && strpos($filespec,'FEU') !== false)
			{
				continue;
			}
			$shortname = substr($filespec,0,strpos($filespec,'.'));
			if(substr($shortname,-4) == 'Base')
			{
				continue;
			}
			$this->field_types[$this->Lang('field_type_'.$shortname)] = $shortname;
		}

		foreach($this->field_types as $tName=>$tType)
		{
			if(substr($tType,0,11) == 'Disposition')
			{
				$this->disp_field_types[$tName]=$tType;
			}
		}
		$this->all_validation_types = array();
		ksort($this->field_types);
		$this->std_field_types = array(
			$this->Lang('field_type_TextField')=>'TextField',
			$this->Lang('field_type_TextAreaField')=>'TextAreaField',
			$this->Lang('field_type_CheckboxField')=>'CheckboxField',
			$this->Lang('field_type_CheckboxGroupField')=>'CheckboxGroupField',
			$this->Lang('field_type_PulldownField')=>'PulldownField',
			$this->Lang('field_type_RadioGroupField')=>'RadioGroupField',
			$this->Lang('field_type_DispositionEmail')=>'DispositionEmail',
			$this->Lang('field_type_DispositionFile')=>'DispositionFile',
			$this->Lang('field_type_PageBreakField')=>'PageBreakField',
			$this->Lang('field_type_StaticTextField')=>'StaticTextField');
		ksort($this->std_field_types);
	}

	function AllowAutoInstall()
	{
		return FALSE;
	}

	function AllowAutoUpgrade()
	{
		return FALSE;
	}

	function GetName()
	{
		return 'PowerForms';
	}

	function GetFriendlyName()
	{
		return $this->Lang('friendlyname');
	}

	function GetVersion()
	{
		return '0.8';
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
		return $this->Lang('admindesc');
	}

	function GetChangeLog()
	{
		$fn = cms_join_path(dirname(__FILE__),'changelog.inc');
		return ''.@file_get_contents($fn);
	}

	function IsPluginModule()
	{
		return true;
	}

	function SupportsLazyLoading()
	{
		return true;
	}

	function HasAdmin()
	{
		return true;
	}

	function VisibleToAdminUser()
	{
		return $this->CheckPermission('ModifyForms');
	}

	function LazyLoadAdmin()
	{
		return false;
	}

	function GetHeaderHTML()
	{
		$config = cmsms()->GetConfig();
		$incpath = $this->GetModuleURLPath().'/include/';
		$pref = '<script type="text/javascript" src="'.$incpath;
		$suff = '"></script>'."\n";

		if(version_compare($GLOBALS['CMS_VERSION'],'1.9') >= 0)
		{
			$ret = '';
		}
		else
		{
		    $ret = $pref.'jquery-1.4.2.min.js'.$suff;
		}
//		$ret .= $pref.'jquery.tablednd.min.js'.$suff;
//		$ret .= $pref.'fb_jquery_functions.js'.$suff;
//		$ret .= $pref.'fb_jquery.js'.$suff;
		$ret .= '<link rel="stylesheet" type="text/css" href="'.$incpath.'admin.css" />'."\n";

		return $ret;
	}

	/**
	For pre-1.10
	*/
	function SetParameters()
	{
		$this->InitializeAdmin();
		$this->InitializeFrontend();
	}

	function LazyLoadFrontend()
	{
		return true;
	}

	/**
	Partial setup for 1.10+
	*/
	function InitializeFrontend()
	{
		$this->RestrictUnknownParams();
		$this->SetParameterType(CLEAN_REGEXP.'/pwfp_.*/',CLEAN_STRING);
		$this->SetParameterType('form_id',CLEAN_INT);
		$this->SetParameterType('form',CLEAN_STRING);
		$this->SetParameterType('field_id',CLEAN_INT);
		$this->SetParameterType(CLEAN_REGEXP.'/value_.*/',CLEAN_STRING);
		$this->SetParameterType('response_id',CLEAN_INT);
	}

	/**
	Partial setup for 1.10+
	*/
	function InitializeAdmin()
	{
		$this->CreateParameter('pwfp_*','null',$this->Lang('formbuilder_params_general'));
		$this->CreateParameter('form_id','null',$this->Lang('formbuilder_params_form_id'));
		$this->CreateParameter('form','null',$this->Lang('formbuilder_params_form_name'));
		$this->CreateParameter('field_id','null',$this->Lang('formbuilder_params_field_id'));
		$this->CreateParameter('value_*','null',$this->Lang('formbuilder_params_passed_from_tag'));
		$this->CreateParameter('response_id','null',$this->Lang('formbuilder_params_response_id'));
	}

	function DoAction($name,$id,$params,$returnid='')
	{
		$this->module_id = $id;
		parent::DoAction($name,$id,$params,$returnid);
	}

	function GetDependencies()
	{
	}

	// may be too stringent, but better safe than sorry.
	function MinimumCMSVersion()
	{
		return '1.9';
	}

	function MaximumCMSVersion()
	{
		return '1.19.99';
	}

	function InstallPostMessage()
	{
		return $this->Lang('post_install');
	}

	function CheckAccess($permission='ModifyForms')
	{
		if(!$this->CheckPermission($permission))
		{
			echo '<p class="error">'.$this->Lang('you_need_permission',$this->Lang('perm_modify')).'</p>';
			return false;
		}
		return true;
	}

	/*
	DO NOT allow parameters to be used for passing the order_by! It is not escaped before
	database access. If we let ADODB quote it, the SQL is not valid (not that MySQL cares,
	but Postgres does).
	*/

	function GetForms($order_by='name')
	{
		$db = $this->dbHandle;
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_fb_form ORDER BY '.$order_by;
		$result = array();
		$rs = $db->Execute($sql);
		if($rs && $rs->RecordCount() > 0)
		{
			$result = $rs->GetArray();
		}
		return $result;
	}

	function GetFormIDFromAlias($form_alias)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT form_id from '.cms_db_prefix().'module_fb_form WHERE alias = ?';
		$rs = $db->Execute($sql, array($form_alias));
		if($rs && $rs->RecordCount() > 0)
		{
			$result = $rs->FetchRow();
			return $result['form_id'];
		}
		return -1;
	}

	function GetFormNameFromID($form_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT name from '.cms_db_prefix().'module_fb_form WHERE form_id = ?';
		$rs = $db->Execute($sql, array($form_id));
		if($rs && $rs->RecordCount() > 0)
		{
			$result = $rs->FetchRow();
		}
		return $result['name'];
	}

	function GetFormByID($form_id,$loadDeep=false)
	{
		$params = array('form_id'=>$form_id);
		return new pwfUtils($this, $params, $loadDeep);
	}

	function GetFormByParams(&$params,$loadDeep=false)
	{
		return new pwfUtils($this, $params, $loadDeep);
	}

	function GetHelp($lang = 'en_US')
	{
		return $this->Lang('help');
	}

	function GetResponse($form_id,$response_id,$field_list=array(),$dateFmt='d F y')
	{
		$names = array();
		$values = array();
		$db = $this->dbHandle;
		$Field = $this->GetFormBrowserField($form_id);
		if($Field == false)
		{
			// error handling goes here
			echo($this->Lang('error_no_browser_field'));
		}

		$dbresult = $db->Execute('SELECT * FROM '.cms_db_prefix().
					'module_fb_formbrowser WHERE fbr_id=?', array($response_id));

		$oneset = new stdClass();
		if($dbresult && $row = $dbresult->FetchRow())
		{
			$oneset->id = $row['fbr_id'];
			$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
			$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
			$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
			$oneset->user_approved_date = (empty($row['user_approved'])?'':$db->UnixTimeStamp($row['user_approved']));
			$oneset->admin_approved_date = (empty($row['admin_approved'])?'':$db->UnixTimeStamp($row['admin_approved']));
			$oneset->submitted_date = $db->UnixTimeStamp($row['submitted']);
			$oneset->xml = $row['response'];
			$oneset->fields = array();
			$oneset->names = array();
			$oneset->fieldsbyalias = array();
		}

		$populate_names = true;
		$this->HandleResponseFromXML($Field, $oneset);
		list($fnames, $aliases, $vals) = $this->ParseResponseXML($oneset->xml);

		foreach($fnames as $id=>$name)
		{
			if(isset($field_list[$id]) && $field_list[$id] > -1)
			{
				$oneset->values[$field_list[$id]]=$vals[$id];
				$oneset->names[$field_list[$id]]=$fnames[$id];
			}
			if(isset($aliases[$id]))
			{
				$oneset->fieldsbyalias[$aliases[$id]] = $vals[$id];
			}
		}
		return $oneset;
	}

	function ParseResponseXML($xmlstr,$human_readable_values = true)
	{
		$names = array();
		$aliases = array();
		$vals = array();
		$xml = new SimpleXMLElement($xmlstr);
		foreach($xml->field as $xmlfield)
		{
			if($human_readable_values)
			{
				if(isset($xmlfield['display_in_submission']) && $xmlfield['display_in_submission'] == '1')
				{
					$id = (int)$xmlfield['id'];
					$names[$id] = ((string)$xmlfield->field_name);
					$vals[$id] = ((string)$xmlfield->human_readable_value);
					if(isset($xmlfield->options))
					{
						foreach($xmlfield->options->option as $to)
						{
							if($to['name'] == 'field_alias')
							{
								$aliases[$id]=((string)$to);
							}
						}
					}
				}
			}
			else
			{
				$id = (int)$xmlfield['id'];
				$arrTypes = $xmlfield->xpath('options/value');
				if(count($arrTypes) > 1)
				{
					$vals[$id] = array();
					foreach($arrTypes as $tv)
					{
						$vals[$id][] = (string)$tv;
					}
				}
				else
				{
					$vals[$id] = (string)$xmlfield->options->value;
				}
			}
		}
		return array($names, $aliases, $vals);
	}

	// New function as part of fix for Bug 5702 from Mike Hughesdon.
	function ParseResponseXMLType($xmlstr)
	{
		$types = array();
		$xml = new SimpleXMLElement($xmlstr);
		foreach($xml->field as $xmlfield)
		{
			$id = (int)$xmlfield['id'];
			$types['pwfp__'.$id] = (string)$xmlfield['type'];
		}
		return $types;
	}

	function GetFormBrowserField($form_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT * FROM ' . cms_db_prefix().'module_fb_field WHERE form_id=? and type=?';
		$rs = $this->dbHandle->Execute($sql, array($form_id,'DispositionFormBrowser'));
		$result = array();
		if(!$rs || $rs->RecordCount() == 0)
		{
			return false;
		}
		$thisRes = $rs->GetArray();
		$params = array();
		$funcs = new pwfUtils($this, $params, false);

		$className = $funcs->MakeClassName($thisRes[0]['type'], '');
		// create the field object
		$field = $funcs->NewField($thisRes[0]);
		return $field;
	}

	function HandleResponseFromXML(&$Field, &$responseObj)
	{
		$crypt = $Field->GetOption('crypt','0');
		if($crypt == '1')
		{
			$cryptlib = $Field->GetOption('crypt_lib');
			$keyfile = $Field->GetOption('keyfile');
			if($cryptlib == 'openssl')
			{
				$openssl = $this->GetModuleInstance('OpenSSL');
				$pkey = $Field->GetOption('private_key');
				$openssl->Reset();
				$openssl->load_private_keyfile($pkey,$keyfile);
			}
			else
			{
				if(file_exists($keyfile))
		    	{
			        $keyfile = file_get_contents($keyfile);
		 	     }
			}
		}

		if($crypt == '1')
		{
			if($cryptlib == 'openssl')
			{
				$responseObj->xml = $openssl->decrypt_from_payload($responseObj->xml);
				if($responseObj->xml == false)
				{
					debug_display($openssl->openssl_errors());
				}
			}
			else
			{
				$responseObj->xml = $this->fbdecrypt($responseObj->xml,$keyfile);
			}
		}
	}

	function GetSortedResponses($form_id, $start_point,$number=100,$admin_approved=false,$user_approved=false,$field_list=array(), $dateFmt='d F y', &$params)
	{
		$db = $this->dbHandle;
		$names = array();
		$values = array();
		$sql = 'FROM '.cms_db_prefix().'module_fb_formbrowser WHERE form_id=?';
		$sqlparms = array($form_id);
		if($user_approved)
		{
			$sql .= ' AND user_approved IS NOT NULL';
		}
		if($admin_approved)
		{
			$sql .= ' AND admin_approved IS NOT NULL';
		}
		if(!empty($params['pwfp_response_search']) && (is_array($params['pwfp_response_search'])))
		{
			$sql .= ' AND fbr_id IN ('. implode(',', $params['pwfp_response_search']) .')';
		}
		if(isset($params['filter_field']) && substr($params['filter_field'],0,5) =='index')
		{
			$idxfld = intval(substr($params['filter_field'],5));
			$sql .= ' AND index_key_'.$idxfld.'=?';
			$sqlparms[] = $params['filter_value'];
		}
		if(!isset($params['pwfp_sort_field']) || $params['pwfp_sort_field']=='submitdate' || empty($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'a')
			{
				$sql .= ' ORDER BY submitted';
			}
			else
			{
				$sql .= ' ORDER BY submitted DESC';
			}
		}
		else if(isset($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'].' DESC';
			}
			else
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'];
			}
		}

		$dbcount = $db->Execute('SELECT COUNT(*) AS num '.$sql,$sqlparms);

		$records = 0;
		if($dbcount && $row = $dbcount->FetchRow())
		{
			$records = $row['num'];
		}

		if($number > -1)
		{
			$dbresult = $db->SelectLimit('SELECT * '.$sql, $number, $start_point, $sqlparms);
		}
		else
		{
			$dbresult = $db->Execute('SELECT * '.$sql, $sqlparms);
		}

		while($dbresult && $row = $dbresult->FetchRow())
		{
			$oneset = new stdClass();
			$oneset->id = $row['fbr_id'];
			$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
			$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
			$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
			$oneset->user_approved_date = (empty($row['user_approved'])?'':$db->UnixTimeStamp($row['user_approved']));
			$oneset->admin_approved_date = (empty($row['admin_approved'])?'':$db->UnixTimeStamp($row['admin_approved']));
			$oneset->submitted_date = $db->UnixTimeStamp($row['submitted']);

			$oneset->xml = $row['response'];
			$oneset->fields = array();
			$oneset->fieldsbyalias = array();
			$values[] = $oneset;
		}
		$Field = $this->GetFormBrowserField($form_id);
		if($Field == false)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$populate_names = true;
		$mapfields = (count($field_list) > 0);
		for ($i=0;$i<count($values);$i++)
		{
			$this->HandleResponseFromXML($Field, $values[$i]);
			list($fnames, $aliases, $vals) = $this->ParseResponseXML($values[$i]->xml);
			foreach($fnames as $id=>$name)
			{
				if($mapfields)
				{
					if(isset($field_list[$id]) && $field_list[$id] > -1)
					{
						if($populate_names)
						{
							$names[$field_list[$id]] = $name;
						}
						$values[$i]->fields[$field_list[$id]]=$vals[$id];
					}
					if(isset($aliases[$id]))
					{
						$values[$i]->fieldsbyalias[$aliases[$id]] = $vals[$id];
					}
				}
				else
				{
					if($populate_names)
					{
						$names[$id] = $name;
					}
					$values[$i]->fields[$id]=$vals[$id];
					if(isset($aliases[$id]))
					{
						$values[$i]->fieldsbyalias[$aliases[$id]] = $vals[$id];
					}
				}
			}
			$populate_names = false;
		}
		return array($records, $names, $values);
	}

	// writes all records into a flat file.
	function WriteSortedResponsesToFile($form_id,$filespec,$striptags=true,$dateFmt='d F y',&$params)
	{
		$db = $this->dbHandle;
		$names = array();
		$values = array();
		$sql = 'FROM '.cms_db_prefix().'module_fb_formbrowser WHERE form_id=?';

		if(!isset($params['pwfp_sort_field']) || $params['pwfp_sort_field']=='submitdate' || empty($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY submitted DESC';
			}
			else
			{
				$sql .= ' ORDER BY submitted';
			}
		}
		else if(isset($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'].' DESC';
			}
			else
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'];
			}
		}

		$Field = $this->GetFormBrowserField($form_id);
		if($Field == false)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$fh = fopen($filespec, 'w+');
		if($fh === false)
		{
			return false;
		}

		$dbresult = $db->Execute('SELECT * '.$sql, array($form_id));

		$populate_names = true;
		while ($dbresult && $row = $dbresult->FetchRow())
		{
			$oneset = new stdClass();
			$oneset->id = $row['fbr_id'];
			$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
			$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
			$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
			$oneset->xml = $row['response'];
			$this->HandleResponseFromXML($Field, $oneset);
			list($fnames, $aliases, $vals) = $this->ParseResponseXML($oneset->xml);
			if($populate_names)
			{
				if($striptags)
	         	{
					foreach($fnames as $id=>$name)
					{
		            	$fnames[$i] = strip_tags($fnames[$i]);
	            	}
	         	}
				fputs ($fh, $this->Lang('title_submit_date')."\t".
					$this->Lang('title_approval_date')."\t".
					$this->Lang('title_user_approved')."\t".
					implode("\t",$fnames)."\n");
				$populate_names = false;
			}
			fputs ($fh,$oneset->submitted . "\t");
			fputs ($fh,$oneset->admin_approved . "\t");
			fputs ($fh,$oneset->user_approved . "\t");
			foreach($vals as $tv)
			{
				if($striptags)
	            {
					$tv = strip_tags($tv);
	            }
				fputs ($fh,preg_replace('/[\n\t\r]/',' ',$tv));
				fputs ($fh,"\t");
			}
			fputs($fh,"\n");
		}
		fclose($fh);
		return true;
	}

	function GetSortableFields($form_id)
	{
		$parm = array('form_id'=>$form_id);
		$funcs = new pwfUtils($this, $parm, true);
		$Field = $funcs->GetFormBrowserField();
		if($Field != false)
		{
			return $Field->getSortFieldList();
		}
		// error handling goes here
		return array();
	}

	function GetFEUIDFromResponseID($response_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT feuid FROM '.cms_db_prefix().'module_fb_formbrowser WHERE fbr_id=?';
		if($result = $db->GetRow($sql, array($response_id)))
		{
			return $result['feuid'];
		}
		return -1;
	}

	function GetResponseIDFromFEUID($feu_id,$form_id=-1)
	{
		$db = $this->dbHandle;

		// Fix for Bug 5422. Adapted from Mike Hughesdon's code.
		$sql = 'SELECT fbr_id FROM '.cms_db_prefix().'module_fb_formbrowser WHERE feuid=?';
		if($form_id != -1)
		{
			$sql .= ' AND form_id = '.$form_id.' ORDER BY submitted DESC';
		}

		if($result = $db->GetRow($sql, array($feu_id)))
		{
			return $result['fbr_id'];
		}
		return false;
 	}

	function field_sorter_asc($a,$b)
	{
		return strcasecmp($a->fields[$a->sf], $b->fields[$b->sf]);
	}

	function field_sorter_desc($a,$b)
	{
		return strcasecmp($b->fields[$b->sf], $a->fields[$a->sf]);
	}

	// For a given form, returns an array of response objects
	function ListResponses($form_id,$sort_order='submitted')
	{
		$db = $this->dbHandle;
		$ret = array();
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_fb_resp WHERE form_id=? ORDER BY ?';
		$dbresult = $db->Execute($query, array($form_id,$sort_order));
		while ($dbresult && $row = $dbresult->FetchRow())
		{
			$oneset = new stdClass();
			$oneset->id = $result['resp_id'];
			$oneset->user_approved = $db->UnixTimeStamp($result['user_approved']);
			$oneset->admin_approved = $db->UnixTimeStamp($result['admin_approved']);
			$oneset->submitted = $db->UnixTimeStamp($result['submitted']);
			$ret[] = $oneset;
		}
		return $ret;
	}

	function def(&$var)
	{
		if(!isset($var))
		{
			return false;
		}
		else if(is_null($var))
		{
			return false;
		}
		else if(!is_array($var) && empty($var))
		{
			return false;
		}
		else if(is_array($var) && count($var) == 0)
		{
			return false;
		}
		return true;
	}

	function ClearFileLock()
	{
		$db = $this->dbHandle;
		$sql = 'DELETE FROM '.cms_db_prefix().'module_fb_flock';
		$rs = $db->Execute($sql);
	}

	function GetFileLock()
	{
		$db = $this->dbHandle;
		$sql = 'INSERT INTO '.cms_db_prefix().'module_fb_flock (flock_id, flock) values (1,'.$db->sysTimeStamp.')';
		$rs = $db->Execute($sql);
		if($rs)
		{
			return true;
		}
		$sql = 'SELECT flock_id FROM '.cms_db_prefix().
				"module_fb_flock WHERE flock + interval 15 second < ".$db->sysTimeStamp;
		$rs = $db->Execute($sql);
		if($rs && $rs->RecordCount() > 0)
		{
			$this->ClearFileLock();
			return false;
		}
		return false;
	}

	function ReturnFileLock()
	{
		$this->ClearFileLock();
	}

	function GetEventDescription($eventname)
	{
		return $this->Lang('event_info_'.$eventname);
	}

	function GetEventHelp($eventname)
	{
		return $this->Lang('event_help_'.$eventname);
	}

	function CreatePageDropdown($id,$name,$current='',$addtext='',$markdefault=true)
	{
		// we get here (hopefully) when the template is changed in the dropdown.
		$db = $this->dbHandle;
		$defaultid = '';
		if($markdefault)
		{
			$contentops = cmsms()->GetContentOperations();
			$defaultid = $contentops->GetDefaultPageID();
		}

		// get a list of the pages used by this template
		$mypages = array();

		$q = 'SELECT content_id,content_name FROM '.cms_db_prefix().
			'content WHERE type = ? AND active = 1';
		$dbresult = $db->Execute($q, array('content'));
		while($row = $dbresult->FetchRow())
		{
			if($defaultid != '' && $row['content_id'] == $defaultid)
			{
				// use a star instead of a word here so I don't have to
				// worry about translation stuff
				$mypages[$row['content_name'].' (*)'] = $row['content_id'];
			}
			else
			{
				$mypages[$row['content_name']] = $row['content_id'];
			}
		}
		return $this->CreateInputDropdown($id,'pwfp_'.$name,$mypages,-1,$current,$addtext);
	}

	function SuppressAdminOutput(&$request)
	{
		if(isset($_SERVER['QUERY_STRING']))
		{
			if(strpos($_SERVER['QUERY_STRING'],'export_form') !== false)
				return true;
			if(strpos($_SERVER['QUERY_STRING'],'get_template') !== false)
				return true;
		}
		return false;
	}

	function crypt($string, $dispositionField)
	{
		if($dispositionField->GetOption('crypt_lib') == 'openssl')
		{
			$openssl = $this->GetModuleInstance('OpenSSL');
			if($openssl === FALSE)
			{
				return array(false,$this->Lang('title_install_openssl'));
			}
			$openssl->Reset();
			if(!$openssl->load_certificate($dispositionField->GetOption('crypt_cert')))
			{
				return array(false,$openssl->openssl_errors());
			}
			$enc = $openssl->encrypt_to_payload($string);
		}
		else
		{
			$kf = $dispositionField->GetOption('keyfile');
			if(file_exists($kf))
			{
				$key = file_get_contents($kf);
			}
			else
			{
				$key = $kf;
			}
			$enc = $this->fbencrypt($string,$key);
		}
		return array(true,$enc);
	}

	function getHashedSortFieldVal($val)
	{
		if(strlen($val) > 4)
		{
			$val = substr($val,0,4). md5(substr($val,4));
		}
		return $val;
	}

	function GetActiveTab(&$params)
	{
		if(!empty($params['fbr_atab']))
		{
		    return $params['fbr_atab'];
		}
		else
		{
			return 'maintab';
		}
	}

	function fbencrypt($string,$key)
	{
		$key = substr(md5($key),0,24);
		$td = mcrypt_module_open ('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
		mcrypt_generic_init ($td, $key, $iv);
		$enc = base64_encode(mcrypt_generic ($td, $string));
		mcrypt_generic_deinit ($td);
		mcrypt_module_close ($td);
		return $enc;
	}

	function fbdecrypt($crypt,$key)
	{
		$crypt = base64_decode($crypt);
		$td = mcrypt_module_open ('tripledes', '', 'ecb', '');
		$key = substr(md5($key),0,24);
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
		mcrypt_generic_init ($td, $key, $iv);
		$plain = mdecrypt_generic ($td, $crypt);
		mcrypt_generic_deinit ($td);
		mcrypt_module_close ($td);
		return $plain;
	}

	function fbCreateInputText($id,$name,$value='',$size='10',$maxlength='255',$addttext='',$type='text')
	{
		$value = cms_htmlentities($value);
		$id = cms_htmlentities($id);
		$name = cms_htmlentities($name);
		$size = cms_htmlentities($size);
		$maxlength = cms_htmlentities($maxlength);

		$value = str_replace('"', '&quot;', $value);

		$text = '<input type="'.$type.'" name="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
		if($addttext != '')
		{
			$text .= ' ' . $addttext;
		}
		$text .= " />\n";
		return $text;
	}

	function fbCreateInputSubmit($id,$name,$value='',$addttext='',$image='',$confirmtext='')
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
		{
			$text .= '"submit"';
		}
		if($confirmtext != '')
		{
			$text .= ' onclick="return confirm(\''.$confirmtext.'\');"';
		}
		if($addttext != '')
		{
			$text .= ' '.$addttext;
		}
		$text .= ' />';
		return $text . "\n";
	}

	function DeleteFromSearchIndex(&$params)
	{
		$funcs = new pwfUtils($this,$params,true);

		// find browsers keyed to this
		$browsers = $funcs->GetFormBrowsersForForm();
		if(count($browsers) < 1)
		{
			return;
		}

		$module = $this->GetModuleInstance('Search');
	    if($module != FALSE)
	    {
			foreach($browsers as $thisBrowser)
			{
				$module->DeleteWords('FormBrowser', $params['response_id'], 'sub_'.$thisBrowser);
			}
	    }
	}

/*	function RegisterTinyMCEPlugin()
	{
		$plugin = "
tinymce.create('tinymce.plugins.formpicker', {
 createControl: function(n,cm) {
  switch (n) {
   case 'formpicker':
    var c = cm.createMenuButton('formpicker', {
     title : '" . $this->GetFriendlyName() . "',
     image : '" . $this->GetModuleURLPath() . "/images/info-small.gif',
     icons : false
    });
    c.onRenderMenu.add(function(c, m) {
";

    	$forms = $this->GetForms();
		foreach($forms as $form)
		{
	        $plugin .= "
     m.add({
      title : '" . $form['name'] . "',
      onclick : function() {
       tinyMCE.activeEditor.execCommand('mceInsertContent', false, '&#123;FormBuilder form=\"".$form["alias"]."\"&#125;');
      }
     });
";
		}

	    $plugin .= "
    });
    return c; //return menu-button instance
  }
  return null;
 }
});
";
		return array(array('formpicker', $plugin, $this->GetFriendlyName()));
	}
*/
}

?>
