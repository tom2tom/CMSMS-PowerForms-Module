<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUtils
{
//	const MAILERMINVERSION = '1.73'; //minumum acceptable version of CMSMailer module

	private static function fieldcmp($a,$b)
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
	Collect_Fields:
	@mod: reference to PowerForms module
	*/
	public static function Collect_Fields(&$mod)
	{
		if($mod->field_types)
			return; //already done
		$fp = dirname(__FILE__).DIRECTORY_SEPARATOR.'Fields.manifest';
		if(!file_exists($fp))
			return;

		$feu = $mod->GetModuleInstance('FrontEndUsers');
		$mail = $mod->GetModuleInstance('CMSMailer');
/*		if($mail != FALSE)
		{
			if(version_compare($mail->GetVersion(),self::MAILERMINVERSION) < 0)
				$mail = FALSE;
		}
*/
		$imports = $mod->GetPreference('imported_fields');
		if($imports)
			$imports = unserialize($imports);

		$mod->field_types = array();

		$rows = file($fp,FILE_SKIP_EMPTY_LINES); //flag doesn't work!!
		foreach($rows as $oneline)
		{
			if($oneline[0] == '#' || ($oneline[0] == '/' && $oneline[1] == '/'))
				continue;
			$classname = trim($oneline);
			if(!$classname)
				continue;
			if($mail == FALSE && strpos($classname,'Email') !== FALSE)
				continue;
			if($feu == FALSE && strpos($classname,'FEU') !== FALSE)
				continue;
			if($imports && in_array($imports,$classname))
				self::Show_Field($mod,$classname,FALSE);
			else
			{
				$menukey = 'field_type_'.substr($classname,3);
				$mod->field_types[$mod->Lang($menukey)] = $classname;
			}
		}
		uksort($mod->field_types,array('pwfUtils','fieldcmp'));

		$mod->std_field_types = array(
			$mod->Lang('field_type_Checkbox')=>'pwfCheckbox',
			$mod->Lang('field_type_Pulldown')=>'pwfPulldown',
			$mod->Lang('field_type_RadioGroup')=>'pwfRadioGroup',
			$mod->Lang('field_type_StaticText')=>'pwfStaticText',
			$mod->Lang('field_type_TextArea')=>'pwfTextArea',
			$mod->Lang('field_type_Text')=>'pwfText',
			$mod->Lang('field_type_SystemEmail')=>'pwfSystemEmail',
			$mod->Lang('field_type_WriteFile')=>'pwfWriteFile');
		uksort($mod->std_field_types,array('pwfUtils','fieldcmp'));
	}

	/**
	Show_Field:
	@mod: reference to PowerForms module
	@classname:
	Include @classname in the array of fields used in the field-adder pulldown
	*/
	public static function Show_Field(&$mod,$classname,$sort=TRUE)
	{
		if($mod->field_types)
		{
			$params = array();
			$formdata = $mod->GetFormData($params);
			$obfld = new $classname($formdata,$params);
			if($obfield)
			{
				if(!($obfld->IsInput || $obfld->IsSortable)) //TODO check this
					$t = '-';
				elseif($obfld->IsDisposition)
					$t = '*';
				else
					$t = '';
				$menulabel = $t.$obfld->mymodule->Lang($obfld->MenuKey);
				$mod->field_types[$menulabel] = $classname;
				if($sort)
					uksort($mod->field_types,array('pwfUtils','fieldcmp'));
			}
		}
	}

	/**
	GetForms:
	@orderby: forms-table field name,optional,default 'name'
	Returns: array of all content of the forms-table
	*/
	public static function GetForms($orderby='name')
	{
		// DO NOT parameterise $orderby! If ADODB quotes it,the SQL is not valid
		// instead,rudimentary security checks
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
		// rudimentary security,cuz' $type could come from a form
		$type = preg_replace('~[\W]|\.\.~','_',$type); //TODO
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
	@time: timestamp,optional,default = 0
	*/
	public static function CleanLog(&$module,$time = 0)
	{
		if(!$time) $time = time();
		$time -= 86400;
		$db = cmsms()->GetDb();
		$limit = $db->DbTimeStamp($time);
		$db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_ip_log WHERE sent_time<'.$limit);
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
		$fid = $db->GetOne($sql,array($form_alias));
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
		 'version' => 'help_module_version') as $name=>$langkey)
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
					$oneset->title = $one->GetName();
					$oneset->alias = $one->ForceAlias();
					$oneset->name = $one->GetVariableName();
					$oneset->id = $one->GetId();
					$oneset->escaped = str_replace("'","\\'",$oneset->title);
					$subfields[] = $oneset;
				}
			}
			unset($one);
			$smarty->assign('subfields',$subfields);
		}

/*		$obfields = array();
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
*/
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

	public static function GetFormOption(&$formdata,$optname,$default='')
	{
		if(isset($formdata->Options[$optname]))
			return $formdata->Options[$optname];
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
	@htmlish:  default FALSE
	@email:  default TRUE
	@oneline: default FALSE
	@header: default FALSE
	@footer: default FALSE
	*/
	public static function CreateSampleTemplate(&$formdata,
		$htmlish=FALSE,$email=TRUE,$oneline=FALSE,$header=FALSE,$footer=FALSE)
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
			 'version' => 'help_module_version') as $key=>$val)
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
				$fldref = $one->ForceAlias();
	 			$ret .= '{if $'.$fldref.' != "" && $'.$fldref.' != "'.self::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified')).'"}';
				$fldref = '{$'.$fldref.'}';

				if($htmlish)
					$ret .= '<strong>'.$one->GetName().'</strong>: '.$fldref.'<br />';
				elseif($oneline && !$header)
					$ret .= $fldref."\t";
				elseif($oneline && $header)
					$ret .= $one->GetName()."\t";
				else
					$ret .= $one->GetName().': '.$fldref;
				$ret .= "{/if}\n";
			}
		}
		unset ($one);
		return $ret;
	}

	//called only from AdminTemplateActions()
	//returns array, member[0] is js click-func for button member[1] 
	private static function CreateAction(&$mod,$id,$fieldName='opt_email_template',$button_text='',$suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix; //TODO check this alias still works
		$msg = $mod->Lang('confirm');
//TODO js goes to where ? |TEMPLATE| substitution where ?
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
	@module_id: The id given to the Powerforms module on execution
	@fieldStruct:
	*/
	public static function AdminTemplateActions(&$formdata,$module_id,$fieldStruct)
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
				$sample = self::CreateSampleTemplate($formdata,FALSE,$is_email,$is_oneline,$is_header,$is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				list($func,$btn) = self::CreateAction($mod,$module_id,$key,$mod->Lang('title_create_sample_template'),'text');
				$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'",$func);
				$buttons[] = $btn;
			}

			$sample = self::CreateSampleTemplate($formdata,$html_button || $gen_button,$is_email,$is_oneline,$is_header,$is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($func,$btn) = self::CreateAction($mod,$module_id,$key,$button_text);
			$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'",$func);
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

		$unspec = self::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified'));
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
//			$fldobj = $one->ExportObject();
			$smarty->assign($name,$replVal);
//			$smarty->assign_by_ref($name.'_obj',$fldobj);
			$alias = $one->ForceAlias();
			$smarty->assign($alias,$replVal);
//			$smarty->assign_by_ref($alias.'_obj',$fldobj);
			$id = $one->GetId();
			$smarty->assign('fld_'.$id,$replVal);
//			$smarty->assign_by_ref('fld_'.$id.'_obj',$fldobj);
		}
		unset ($one);

		// general variables
		$smarty->assign('form_name',$formdata->Name);
		$smarty->assign('form_url',(empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']));
		$smarty->assign('form_host',$_SERVER['SERVER_NAME']);
		$smarty->assign('sub_date',date('r'));
		$smarty->assign('sub_source',$_SERVER['REMOTE_ADDR']);
		$smarty->assign('version',$mod->GetVersion());
	}

	/**
	StoreResponse:
	Master response saver,used by various field-classes
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

		// Check if form has database fields,do init
/*redundant FormBrowser
		if(is_object($Disposer) &&
			$Disposer->GetFieldType() == 'FormBrowser')
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
				if($one->GetFieldType() == 'FormBrowser')
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
			list($res,$xml) = self::Crypt($xml,$Disposer);
			if(!$res)
				return array(FALSE,$xml);

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
			list($res,$xml) = self::Crypt($xml,$Disposer);
			if(!$res)
				return array(FALSE,$xml);

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
	   $sortfield2,$sortfield3,$sortfield4,$sortfield5,$feu_id,$xml)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		$secret_code = '';

		if($newrec)
		{
			// saving a new response
/* TODO save into browser-module table,or just send there via API
			$secret_code = substr(md5(session_id().'_'.time()),0,7);
			$response_id = $db->GenID($pre.'module_pwf_browse_seq');
			$sql = 'INSERT INTO '.$pre.
				'module_pwf_browse (browser_id,form_id,submitted,secret_code,index_key_1,index_key_2,index_key_3,index_key_4,index_key_5,feuid,response) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
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
		else if($approver)
		{
/* TODO save into browser-module table,or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set user_approved=? where browser_id=?';
			$res = $db->Execute($sql,array($db->DBTimeStamp(time()),$response_id));
			$mod = cms_utils::get_module('PowerForms');
			audit(-1,$mod->GetName(),$mod->Lang('user_approved_submission',array($response_id,$approver)));
*/
		}
		if(!$newrec)
		{
/* TODO save into browser-module table,or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set index_key_1=?,index_key_2=?,index_key_3=?,index_key_4=?,index_key_5=?,response=? where browser_id=?';
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
		$td = mcrypt_module_open ('tripledes','','ecb','');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td),MCRYPT_RAND);
		mcrypt_generic_init ($td,$key,$iv);
		$enc = base64_encode(mcrypt_generic ($td,$string));
		mcrypt_generic_deinit ($td);
		mcrypt_module_close ($td);
		return $enc;
	}
*/
/*TODO	public static function Decrypt($crypt,$key)
	{
		$crypt = base64_decode($crypt);
		$td = mcrypt_module_open ('tripledes','','ecb','');
		$key = substr(md5($key),0,24);
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td),MCRYPT_RAND);
		mcrypt_generic_init ($td,$key,$iv);
		$plain = mdecrypt_generic ($td,$crypt);
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

	public static function GetUploadsPath()
	{
		$config = cmsms()->GetConfig();
		$fp = $config['uploads_path'];
		if($fp && is_dir($fp))
		{
			$mod = cms_utils::get_module('PowerForms');
			$ud = $mod->GetPreference('uploads_dir');
			if($ud)
			{
				$ud = $fp.DIRECTORY_SEPARATOR.$ud;
				if(is_dir($ud))
					return $ud;
			}
			return $fp;
		}
		return FALSE;
	}

}

?>
