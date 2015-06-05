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
	@orderby: forms-table field name,optional, default 'name'
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
		if($type)
		{
			if(strncmp($type,'pwf',3) == 0)
				return $type;
			else
				return 'pwf'.$type;
		}
		return 'pwfField';
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

	//interrogates forms table to get name value for form whose id is $form_id, '' if not found
	public static function GetFormNameFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$name = $db->GetOne($sql,array($form_id));
		if($name)
			return $name;
		return '';
	}

	//interrogates forms table to get alias value for form whose id is $form_id, '' if not found
	public static function GetFormAliasFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT alias FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$alias = $db->GetOne($sql,array($form_id));
		if($alias)
			return $alias;
		return '';
	}

	//interrogates forms table to get id value for form whose alias is $form_alias, -1 if not found
	public static function GetFormIDFromAlias($form_alias)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwf_form WHERE alias = ?';
		$fid = $db->GetOne($sql,array($form_alias));
		if($fid)
			return (int)$fid;
		return -1;
	}

	//returns value of (loaded/cached) form option, or $default
	public static function GetFormOption(&$formdata,$optname,$default='')
	{
		if(isset($formdata->Options[$optname]))
			return $formdata->Options[$optname];
		else
			return $default;
	}

	//walk all form fields, return TRUE if a disposition field is found
	//used in method.update_form
	public static function HasDisposition(&$formdata)
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
	@id: The id given to the Powerforms module on execution
	@fieldStruct: array of parameters ...
	Returns: array($funcs,$buttons) $funcs = ... $buttons = ...
	*/
	public static function AdminTemplateActions(&$formdata,$id,$fieldStruct)
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
				list($func,$btn) = self::CreateAction($mod,$id,$key,$mod->Lang('title_create_sample_template'),'text');
				$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'",$func);
				$buttons[] = $btn;
			}

			$sample = self::CreateSampleTemplate($formdata,$html_button || $gen_button,$is_email,$is_oneline,$is_header,$is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($func,$btn) = self::CreateAction($mod,$id,$key,$button_text);
			$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'",$func);
			$buttons[] = $btn;
		}
		return array($funcs,$buttons);
	}

	//adds a member to $formdata->templateVariables[]
	//used by EmailConfirmation field to set url variable
	public static function AddTemplateVariable(&$formdata,$name,$def)
	{
		$key = '{$'.$name.'}';
		$formdata->templateVariables[$key] = $def;
	}

	//returns xhtml string which generates a tabular help description
	public static function FormFieldsHelp(&$formdata,&$extras=array())
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

	/**
	SetupFormVarsHelp:
	@mod: reference to current PowerBrowse module object
	@smarty: referenced to smarty object
	@fields: reference to array of form-fields e.g. pwfData::Fields() or empty array
	*/
	public static function SetupFormVarsHelp(&$mod,&$smarty,&$formfields)
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

	/**
	SetupFormVars:
	@formdata: reference to form data object
	@htmlemail: optional boolean, whether processing a form for html email, default FALSE
	*/
	public static function SetupFormVars(&$formdata,$htmlemail=FALSE)
	{
		$mod = $formdata->formsmodule;
		$smarty = cmsms()->GetSmarty();
		// general variables
		$smarty->assign('form_name',$formdata->Name);
		$smarty->assign('form_url',(empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']));
		$smarty->assign('form_host',$_SERVER['SERVER_NAME']);
		$smarty->assign('sub_date',date('r'));
		$smarty->assign('sub_source',$_SERVER['REMOTE_ADDR']);
		$smarty->assign('version',$mod->GetVersion());

		$unspec = self::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified'));

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
	}

	//used by action.validate TODO
	public static function CheckResponse($form_id,$response_id,$code)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT secret_code FROM '.cms_db_prefix().'module_pwf_browse WHERE form_id=? AND browser_id=?'; //TODO
		if($result = $db->GetOne($sql,array($form_id,$response_id)))
		{
			if($result == $code)
				return TRUE;
		}
		return FALSE;
	}

/*	private static function Encrypt($string,$key)
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
TODO
	public static function Decrypt($crypt,$key)
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

/* see mutex for this stuff
	//used by several file-related field-types
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
*/
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

	/**
	CleanLog:
	@module: reference to PowerTools module object
	@time: timestamp,optional,default = 0
	Removes from table records older than 30-minutes
	*/
	public static function CleanLog(&$module,$time = 0)
	{
		if(!$time) $time = time();
		$time -= 900;
		$db = cmsms()->GetDb();
		$limit = $db->DbTimeStamp($time);
		$db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_ip_log WHERE basetime<'.$limit);
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
