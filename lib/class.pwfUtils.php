<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUtils
{
//	const MAILERMINVERSION = '1.73'; //minumum acceptable version of CMSMailer module

	/**
	Initialize:
	@mod: reference to PowerForms module
	*/
	public static function Initialize(&$mod)
	{
		if($mod->field_types)
			return; //already done
	
		$feu = $mod->GetModuleInstance('FrontEndUsers');
		$mail = $mod->GetModuleInstance('CMSMailer');
/*		if($mail != FALSE)
		{
			if(version_compare($mail->GetVersion(),self::MAILERMINVERSION) < 0)
				$mail = FALSE;
		}
*/
		//this is used by several classes, not just email*
		//pretty much everything can be valid, provided there's an '@' in there! be concerned about typo's more than format!
		$mod->email_regex = '/.+@.+\..+/';

		$mod->field_types = array();
		$dir = opendir(dirname(__FILE__));
		while($filespec = readdir($dir))
		{
			if(!endswith($filespec,'.php')) continue;
			if(strpos($filespec,'Field') === FALSE && strpos($filespec,'Disposition') === FALSE)
				continue;
			if($mail == FALSE && strpos($filespec,'Disposition') !== FALSE && strpos($filespec,'Email') != FALSE)
				continue;
			if($feu == FALSE && strpos($filespec,'FEU') !== FALSE)
				continue;
			$shortname = substr($filespec,9,strpos($filespec,'.php',9) - 9);
			if(substr($shortname,-4) == 'Base')
				continue;
			if(substr($shortname,-10) == 'Operations')
				continue;

			$mod->field_types[$mod->Lang('field_type_'.$shortname)] = 'pwf'.$shortname;
		}
		uksort($mod->field_types,array('pwfUtils','fieldcmp'));

		foreach($mod->field_types as $tName=>$tType)
		{
			if(substr($tType,0,14) == 'pwfDisposition')
				$mod->disp_field_types[$tName]=$tType;
		}

		$mod->std_field_types = array(
			$mod->Lang('field_type_CheckboxField')=>'pwfCheckboxField',
//			$mod->Lang('field_type_CheckboxGroupField')=>'pwfCheckboxGroupField',
			$mod->Lang('field_type_DispositionEmail')=>'pwfDispositionEmail',
			$mod->Lang('field_type_DispositionFile')=>'pwfDispositionFile',
			$mod->Lang('field_type_PageBreakField')=>'pwfPageBreakField',
			$mod->Lang('field_type_PulldownField')=>'pwfPulldownField',
			$mod->Lang('field_type_RadioGroupField')=>'pwfRadioGroupField',
			$mod->Lang('field_type_StaticTextField')=>'pwfStaticTextField',
			$mod->Lang('field_type_TextAreaField')=>'pwfTextAreaField',
			$mod->Lang('field_type_TextField')=>'pwfTextField');
		uksort($mod->std_field_types,array('pwfUtils','fieldcmp'));
//		$mod->all_validation_types = array(); NEVER USED
	}

	private static function fieldcmp($a, $b)
	{
		$fa = $a[0];
		$fb = $b[0];
		if($fa == $fb)
			return(strcmp($a,$b));
		elseif($fa == '*')
			return 1;
		elseif($fb == '*')
			return -1;
		elseif($fa == '-')
		{
			if($fb == '*')
				return -1;
			else
				return 1;
		}
		elseif($fb == '-')
		{
			if($fa == '*')
				return 1;
			else
				return -1;
		}
		else
			return(strcmp($a,$b));
	}

	/**
	GetForms:
	@orderby: forms-table field name, optional, default 'name'
	Returns: array of all content of the forms-table
	*/
	public static function GetForms($orderby='name')
	{
		// DO NOT parameterise $orderby! If ADODB quotes it, the SQL is not valid
		// instead, rudimentary security checks
		$orderby = preg_replace('/\s/','',$orderby);
		$orderby = preg_replace('/[^\w\-.]/','_',$orderby);
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_form ORDER BY '.$orderby;
		$db = cmsms()->GetDb();
		return $db->GetArray($sql);
	}

	public static function FileClassName($filename)
	{
		$shortname = str_replace(array('class.','.php'),array('',''),$filename);
		return self::MakeClassName($shortname);
	}

	/**
	MakeClassName:
	@type: 'core' part of class name
	Returns: a namespaced class name
	*/
	public static function MakeClassName($type)
	{
		// rudimentary security, cuz' $type could come from a form
		$type = preg_replace('~[\W]|\.\.~', '_', $type); //TODO
		if(!$type)
			$type = 'Field';
		if(strpos($type,'pwf') === 0)
			return $type;
		else
			return 'pwf'.$type;
	}

	public static function MakeAlias($string,$maxlen=48)
	{
		if(!$string)
			return '';
		$alias = strtolower(trim($string,"\t\n\r\0 _"));
		if(!$alias)
			return '';
		$alias = preg_replace('/[^\w]+/','_',$alias);
		$parts = array_slice(explode('_',$alias),0,5);
		$alias = substr(implode('_',$parts),0,$maxlen);
		return trim($alias,'_');
	}

	/**
	CleanLog:
	@module: reference to PowerTools module object
	@time: timestamp, optional, default = 0
	*/
	public static function CleanLog(&$module,$time = 0)
	{
		if(!$time) $time = time();
		$time -= 86400;
		$db = cmsms()->GetDb();
		$limit = $db->DbTimeStamp($time);
		$db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_ip_log WHERE sent_time<'.$limit);
	}

	/**
	GetFieldById:
	@formdata: reference to form data object
	@$field_id:
	Returns: reference to first-found field-object whose id matches $field_id
	*/
	public static function &GetFieldById(&$formdata,$field_id)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetId() == $field_id)
				return $fld;
		}
		unset ($fld);
		$fld = FALSE; //need ref to this
		return $fld;
	}

	public static function GetFormNameFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$name = $db->GetOne($sql,array($form_id));
		if($name)
			return $name;
		return '';
	}

	public static function GetFormAliasFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT alias FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$alias = $db->GetOne($sql,array($form_id));
		if($alias)
			return $alias;
		return '';
	}

	public static function GetFormIDFromAlias($form_alias)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwf_form WHERE alias = ?';
		$fid = $db->GetOne($sql, array($form_alias));
		if($fid)
			return (int)$fid;
		return -1;
	}

	/**
	SetupVarsHelp:
	@mod: reference to current PowerBrowse module object
	@smarty: referenced to smarty object
	@fields: reference to array of form-fields e.g. pwfData::Fields() or empty array
	*/
	public static function SetupVarsHelp(&$mod,&$smarty,&$formfields)
	{
		$smarty->assign('template_vars_title',$mod->Lang('title_template_variables'));
		$smarty->assign('variable_title',$mod->Lang('variable'));
		$smarty->assign('attribute_title',$mod->Lang('attribute'));

		$sysfields = array();
		foreach(array(
		 'form_name' => 'title_form_name',
		 'form_url' =>'help_form_url',
		 'form_host' => 'help_server_name',
		 'sub_date' => 'help_submission_date',
		 'sub_source' => 'help_sub_source',
		 'version' => 'help_module_version',
		 'TAB' => 'help_tab') as $name=>$langkey)
		{
			$oneset = new stdClass();
			$oneset->name = '{$'.$name.'}';
			$oneset->title = $mod->Lang($langkey);
			$sysfields[] = $oneset;
		}
		$smarty->assign('sysfields',$sysfields);

		if($formfields)
		{
			$subfields = array();
			foreach($formfields as &$one)
			{
				if($one->DisplayInSubmission())
				{
					$oneset = new stdClass();
					$oneset->name = $one->GetVariableName();
					$oneset->id = $one->GetId();
					$oneset->alias = $one->GetAlias();
					$oneset->title = $one->GetName();
					$oneset->escaped = str_replace("'","\\'",$oneset->title);
					$subfields[] = $oneset;
				}
			}
			unset($one);
			$smarty->assign('subfields',$subfields);
		}

		$obfields = array();
		foreach(array ('name','type','id','value','valuearray') as $name)
		{
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->title = $mod->Lang('title_field_'.$name);
			$obfields[] = $oneset;
		}
		$smarty->assign('obfields',$obfields);

//		$oneset->title = $mod->Lang('title_field_id2');
		$smarty->assign('help_field_values',$mod->Lang('help_field_values'));
		$smarty->assign('help_object_example',$mod->Lang('help_object_example'));
		$smarty->assign('help_other_fields',$mod->Lang('help_other_fields'));

		$smarty->assign('help_vars',$mod->ProcessTemplate('form_vars_help.tpl'));
	}

	public static function AddTemplateVariable(&$formdata,$name,$def)
	{
		$key = '{$'.$name.'}';
		$formdata->templateVariables[$key] = $def;
	}

	public static function fieldValueTemplate(&$formdata,&$extras=array())
	{
		$mod = $formdata->formsmodule;
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('title_variables',$mod->Lang('title_variables_available'));
		$smarty->assign('title_name',$mod->Lang('title_php_variable'));
		$smarty->assign('title_field',$mod->Lang('title_form_field'));
		$rows = array();
		foreach($formdata->Fields as &$one)
		{
			$oneset = new StdClass();
			$oneset->id = $one->GetId();
			$oneset->name = $one->GetName();
			$rows[] = $oneset;
		}
		unset($one);
		if($extras)
		{
			foreach($extras as $id=>$name)
			{
				$oneset = new StdClass();
				$oneset->id = $id;
				$oneset->name = $name;
				$rows[] = $oneset;
			}
		}
		
		$smarty->assign('rows',$rows);
		return $mod->ProcessTemplate('form_vars_help.tpl');
	}

	public static function GetAttr(&$formdata,$attrname,$default='')
	{
		if(isset($formdata->Attrs[$attrname]))
			return $formdata->Attrs[$attrname];
		else
			return $default;
	}

	static public function HasDisposition(&$formdata)
	{
		foreach($formdata->Fields as &$one)
		{
			if($one->IsDisposition())
			{
				unset($one);
				return TRUE;
			}
		}
		unset($one);
		return FALSE;
	}

	/**
	CreateSampleTemplate:
	@formdata: reference to form data object
	@htmlish:=FALSE
	@email:=TRUE
	@oneline:=FALSE
	@header:=FALSE
	@footer:=FALSE
	*/
	public static function CreateSampleTemplate(&$formdata,$htmlish=FALSE,$email=TRUE,$oneline=FALSE,$header=FALSE,$footer=FALSE)
	{
		$mod = $formdata->formsmodule;
		$ret = '';

		if($email)
		{
			if($htmlish)
				$ret .= '<h1>'.$mod->Lang('email_default_template')."</h1>\n";
			else
				$ret .= $mod->Lang('email_default_template')."\n";

			foreach(array(
			 'form_name' => 'title_form_name',
			 'form_url' =>'help_form_url',
			 'form_host' => 'help_server_name',
			 'sub_date' => 'help_submission_date',
			 'sub_source' => 'help_sub_source',
			 'version' => 'help_module_version',
			 'TAB' => 'help_tab') as $key=>$val)
			{
				if($htmlish)
					$ret .= '<strong>'.$mod->Lang($val).'</strong>: {$'.$key.'}<br />';
				else
					$ret .= $mod->Lang($val).': {$'.$key.'}';
				$ret .= "\n";
			}

			if($htmlish)
				$ret .= "\n<hr />\n";
			else
				$ret .= "\n-------------------------------------------------\n";
		}
		elseif(!$oneline)
		{
			if($htmlish)
				$ret .= '<h2>';
			$ret .= $mod->Lang('thanks');
			if($htmlish)
				$ret .= "</h2>\n";
		}
		elseif($footer)
		{
			 $ret .= "------------------------------------------\n<!--EOF-->\n";
			 return $ret;
		}

		foreach($formdata->Fields as &$one)
		{
			if($one->DisplayInSubmission())
			{
				if($one->GetAlias() != '')
					$fldref = $one->GetAlias();
				else
					$fldref = 'fld_'. $one->GetId();

	 			$ret .= '{if $'.$fldref.' != "" && $'.$fldref.' != "'.self::GetAttr($formdata,'unspecified',$mod->Lang('unspecified')).'"}';
				$fldref = '{$'.$fldref.'}';

				if($htmlish)
					$ret .= '<strong>'.$one->GetName().'</strong>: '.$fldref.'<br />';
				elseif($oneline && !$header)
					$ret .= $fldref. '{$TAB}';
				elseif($oneline && $header)
					$ret .= $one->GetName().'{$TAB}';
				else
					$ret .= $one->GetName().': '.$fldref;
				$ret .= "{/if}\n";
			}
		}
		unset ($one);
		return $ret;
	}

	//called only from AdminTemplateActions()
	private static function CreateAction(&$mod, $id, $fieldName='opt_email_template', $button_text='', $suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix;
		$msg = $mod->Lang('confirm');
//TODO js goes to where ?
		$jsfunc = <<<EOS
function populate_{$fldAlias}(formname) {
 if(confirm ('{$msg}')) {
  formname['{$id}pwfp_{$fieldName}'].value=|TEMPLATE|;
 }
}

EOS;
		$btn = <<<EOS
<input type="button" class="cms_submit" value="{$button_text}" onclick="javascript:populate_{$fldAlias}(this.form)" />

EOS;
		return (array($jsfunc,$btn));
	}

	/**
	AdminTemplateActions:
	@formdata: reference to form data object
	@formDescriptor
	@fieldStruct
	*/
	public static function AdminTemplateActions(&$formdata,$formDescriptor,$fieldStruct)
	{
		$mod = $formdata->formsmodule;
		$funcs = array();
		$buttons = array();
		foreach($fieldStruct as $key=>$val)
		{
			$gen_button = !empty($val['general_button']);
			$html_button = !empty($val['html_button']);
			$text_button = !empty($val['text_button']);
			$is_email = !empty($val['is_email']);
			$is_footer = !empty($val['is_footer']);
			$is_header = !empty($val['is_header']);
			$is_oneline = !empty($val['is_oneline']);

			if($html_button)
				$button_text = $mod->Lang('title_create_sample_html_template');
			elseif($is_header)
				$button_text = $mod->Lang('title_create_sample_header_template');
			elseif($is_footer)
				$button_text = $mod->Lang('title_create_sample_footer_template');
			else
				$button_text = $mod->Lang('title_create_sample_template');

			if($html_button && $text_button)
			{
				$sample = self::CreateSampleTemplate($formdata,FALSE, $is_email, $is_oneline, $is_header, $is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				list($func,$btn) = self::CreateAction($mod, $formDescriptor, $key, $mod->Lang('title_create_sample_template'),'text');
				$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
				$buttons[] = $btn;
			}

			$sample = self::CreateSampleTemplate($formdata,$html_button || $gen_button, $is_email, $is_oneline, $is_header, $is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($func,$btn) = self::CreateAction($mod, $formDescriptor, $key, $button_text);
			$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
			$buttons[]= $btn;
		}
		return array($funcs,$buttons);
	}

	/**
	SetFinishedFormSmarty:
	@formdata: reference to form data object
	@htmlemail:=FALSE
	*/
	public static function SetFinishedFormSmarty(&$formdata,$htmlemail=FALSE)
	{
		$mod = $formdata->formsmodule;

		$unspec = self::GetAttr($formdata,'unspecified',$mod->Lang('unspecified'));
		$smarty = cmsms()->GetSmarty();

		$formInfo = array();

		foreach($formdata->Fields as &$one)
		{
			$replVal = $unspec;
			$replVals = array();
			if($one->DisplayInSubmission())
			{
				$replVal = $one->GetHumanReadableValue();
				if($htmlemail)
				{
					// allow <BR> as delimiter or in content
					$replVal = preg_replace(
						array('/<br(\s)*(\/)*>/i','/[\n\r]+/'),array('|BR|','|BR|'),
						$replVal);
					$replVal = htmlspecialchars($replVal);
					$replVal = str_replace('|BR|','<br />',$replVal);
				}
				if($replVal == '')
					$replVal = $unspec;
			}

			$name = $one->GetVariableName();
			$fldobj = $one->ExportObject();
			$smarty->assign($name,$replVal);
			$smarty->assign($name.'_obj',$fldobj);
			$id = $one->GetId();
			$smarty->assign('fld_'.$id,$replVal);
			$smarty->assign('fld_'.$id.'_obj',$fldobj);
			$alias = $one->GetAlias();
			if($alias != '')
			{
				$smarty->assign($alias,$replVal);
				$smarty->assign($alias.'_obj',$fldobj);
			}
		}
		unset ($one);

		// general variables
		$smarty->assign('form_name',$formdata->Name);
		$smarty->assign('form_url',(empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']));
		$smarty->assign('form_host',$_SERVER['SERVER_NAME']);
		$smarty->assign('sub_date',date('r'));
		$smarty->assign('sub_source',$_SERVER['REMOTE_ADDR']);
		$smarty->assign('version',$mod->GetVersion());
		$smarty->assign('TAB',"\t");
	}

	/**
	StoreResponse:
	Master response saver, used by various field-classes
	@response_id:=-1
	@approver:=''
	@Disposer:=NULL
	*/
/*	public static function StoreResponse(&$formdata,$response_id=-1,$approver='',&$Disposer=NULL)
	{
		$mod = $formdata->formsmodule;
		$db = cmsms()->GetDb();
		$newrec = FALSE;
		$crypt = FALSE;
		$hash_fields = FALSE;
		$sort_fields = array();

		// Check if form has database fields, do init
/*redundant FormBrowser
		if(is_object($Disposer) &&
			$Disposer->GetFieldType() == 'DispositionFormBrowser')
		{
			$crypt = ($Disposer->GetOption('crypt','0') == '1');
			$hash_fields = ($Disposer->GetOption('hash_sort','0') == '1');
			for ($i=0; $i<5; $i++)
				$sort_fields[$i] = $Disposer->getSortFieldVal($i+1);
		}
* /
		// If new field
		if($response_id == -1)
		{
			if(is_object($Disposer) && $Disposer->GetOption('feu_bind','0') == '1')
			{
				$feu = $mod->GetModuleInstance('FrontEndUsers');
				if($feu == FALSE)
				{
					debug_display("FAILED to instatiate FEU!");
					return;
				}
				$feu_id = $feu->LoggedInId();
			}
			else
			{
				$feu_id = -1;
			}
//TODO			$response_id = $db->GenID(cms_db_prefix(). 'module_pwf_browse_seq');
			foreach($formdata->Fields as &$one)
			{
				// set the response_id to be the attribute of the formbrowser disposition
				if($one->GetFieldType() == 'DispositionFormBrowser')
					$one->SetValue($response_id);
			}
			unset ($one);
			$newrec = TRUE;
		}
		else
		{
//TODO		$feu_id = $mod->getFEUIDFromResponseID($response_id);
		}

		// convert to XML
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<response form_id=\"".$formdata->Id."\">\n";
		foreach($formdata->Fields as &$one)
			$xml .= $one->ExportXML(TRUE);
		unset($one);
		$xml .= "</response>\n";

		// add
		if(!$crypt)
		{
			$output = self::StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		elseif(!$hash_fields)
		{
			list($res, $xml) = self::Crypt($xml,$Disposer);
			if(!$res)
				return array(FALSE, $xml);

			$output = self::StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		else
		{
			list($res, $xml) = self::Crypt($xml,$Disposer);
			if(!$res)
				return array(FALSE, $xml);

			$output = self::StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?self::getHashedSortFieldVal($sort_fields[0]):'',
				isset($sort_fields[1])?self::getHashedSortFieldVal($sort_fields[1]):'',
				isset($sort_fields[2])?self::getHashedSortFieldVal($sort_fields[2]):'',
				isset($sort_fields[3])?self::getHashedSortFieldVal($sort_fields[3]):'',
				isset($sort_fields[4])?self::getHashedSortFieldVal($sort_fields[4]):'',
				$feu_id,
				$xml);
		}
		return $output;
	}
*/
	// Insert parsed XML data to database
	private static function StoreResponseXML($response_id=-1,$newrec=FALSE,$approver='',$sortfield1,
	   $sortfield2,$sortfield3,$sortfield4,$sortfield5, $feu_id,$xml)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		$secret_code = '';

		if($newrec)
		{
			// saving a new response
/* TODO save into browser-module table, or just send there via API
			$secret_code = substr(md5(session_id().'_'.time()),0,7);
			$response_id = $db->GenID($pre.'module_pwf_browse_seq');
			$sql = 'INSERT INTO '.$pre.
				'module_pwf_browse (browser_id, form_id, submitted, secret_code, index_key_1, index_key_2, index_key_3, index_key_4, index_key_5, feuid, response) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
				array($response_id,
					$formdata->Id,
					$db->DBTimeStamp(time()),
					$secret_code,
					$sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,
					$feu_id,
					$xml
				));
*/
		}
		else if($approver != '')
		{
/* TODO save into browser-module table, or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set user_approved=? where browser_id=?';
			$res = $db->Execute($sql,array($db->DBTimeStamp(time()),$response_id));
			$mod = cms_utils::get_module('PowerForms');
			audit(-1, $mod->GetName(), $mod->Lang('user_approved_submission',array($response_id,$approver)));
*/
		}
		if(!$newrec)
		{
/* TODO save into browser-module table, or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set index_key_1=?, index_key_2=?, index_key_3=?, index_key_4=?, index_key_5=?, response=? where browser_id=?';
			$res = $db->Execute($sql,
				array($sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,$xml,$response_id));
*/
		}
		return array($response_id,$secret_code);
	}

/*	private static function getHashedSortFieldVal($val)
	{
		if(strlen($val) > 4)
			$val = substr($val,0,4). md5(substr($val,4));
		return $val;
	}

	private static function Crypt($string,$dispositionField)
	{
		if($dispositionField->GetOption('crypt_lib') == 'openssl')
		{
			$openssl = $this->GetModuleInstance('OpenSSL');
			if($openssl === FALSE)
			{
				return array(FALSE,$this->Lang('title_install_openssl'));
			}
			$openssl->Reset();
			if(!$openssl->load_certificate($dispositionField->GetOption('crypt_cert')))
			{
				return array(FALSE,$openssl->openssl_errors());
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
			$enc = self::Encrypt($string,$key);
		}
		return array(TRUE,$enc);
	}

	private static function Encrypt($string,$key)
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
*/
/*TODO	public static function Decrypt($crypt,$key)
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
*/

	public static function GetFileLock()
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		
		$sql = 'INSERT INTO '.$pre.'module_pwf_flock (flock_id,flock) VALUES (1,'.$db->sysTimeStamp.')';
		if($db->Execute($sql))
			return TRUE;
/*		$sql = 'SELECT flock_id FROM '.$pre.
TODO				'module_pwf_flock WHERE flock < '.$db->sysTimeStamp + 15;
		if($db->GetOne($sql))
			$db->Execute('DELETE FROM '.$pre.'module_pwf_flock');
*/
		return FALSE;
	}

	public static function ClearFileLock()
	{
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.cms_db_prefix().'module_pwf_flock';
		$db->Execute($sql);
	}

	public static function unmy_htmlentities($val)
	{
		if($val == '')
			return '';

		$val = html_entity_decode($val);
		$val = str_replace(
		array('&amp;','&#60;&#33;--','--&#62;','&gt;','&lt;','&quot;','&#39;','&#036;','&#33;'),
		array('&'    ,'<!--'        ,'-->'    ,'>'   ,'<'   ,'"'     ,"'"    ,'$'     ,'!'    ),
		$val);
		return $val;
	}

}

?>
