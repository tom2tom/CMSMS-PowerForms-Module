<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUtils
{
//	const MAILERMINVERSION = '1.73'; //minumum acceptable version of CMSMailer module
	/**
	GetForms:
	@orderby: forms-table field name, optional, default 'name'
	Returns: array of all content of the forms-table, sorted by @orderby
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

	//support for field-selection menu-item sorting
	private static function labelcmp($a,$b)
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
	Populates and caches full and abbreviated arrays of available field-types,
	from file 'Fields.manifest' plus any 'imported' field(s), for use in any
	add-field pulldown. Does nothing if the arrays are already cached.
	@mod: reference to PowerForms module object
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
		uksort($mod->field_types,array('pwfUtils','labelcmp'));

		$mod->std_field_types = array(
			$mod->Lang('field_type_Checkbox')=>'pwfCheckbox',
			$mod->Lang('field_type_Pulldown')=>'pwfPulldown',
			$mod->Lang('field_type_RadioGroup')=>'pwfRadioGroup',
			$mod->Lang('field_type_StaticText')=>'pwfStaticText',
			$mod->Lang('field_type_TextArea')=>'pwfTextArea',
			$mod->Lang('field_type_Text')=>'pwfText',
			$mod->Lang('field_type_SystemEmail')=>'pwfSystemEmail',
			$mod->Lang('field_type_SharedFile')=>'pwfSharedFile');
		uksort($mod->std_field_types,array('pwfUtils','labelcmp'));
	}

	/**
	Show_Field:
	Include @classname in the array of available fields (to be used in any add-field pulldown)
	@mod: reference to PowerForms module object
	@classname: name of class for the field to be added
	@sort: optional boolean, whether to sort ... , defalut TRUE
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
	FileClassName:
	@ilename: name of a field-class file, like 'class.pwfSomething.php'
	Returns: classname (the residual 'pwfSomething') after some checking
	*/
	public static function FileClassName($filename)
	{
		$shortname = str_replace(array('class.','.php'),array('',''),$filename);
		return self::MakeClassName($shortname);
	}

	/**
	MakeClassName:
	@type: 'core' part of a class name, with or without 'pwf' prefix
	Returns: a class name 'pwfSomething', possibly a (useless) default 'pwfField'
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

	/**
	MakeAlias:
	Generate an alias from @string
	@string: the source string
	@maxlen: optional maximum length for the created alias, defualt 48
	Returns: the alias string
	*/
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
	GetFormNameFromID:
	Interrogates the forms table to get the stored name for a form whose id is @form_id
	@form_id: form id number
	Returns: the name, or '' if record for the form is not found
	*/
	public static function GetFormNameFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$name = $db->GetOne($sql,array($form_id));
		if($name)
			return $name;
		return '';
	}

	/**
	GetFormAliasFromID:
	Interrogates the forms table to get the stored alias for form whose id is @form_id
	@form_id: form id number
	Returns: the alias, or '' if record for the form is not found
	*/
	public static function GetFormAliasFromID($form_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT alias FROM '.cms_db_prefix().'module_pwf_form WHERE form_id=?';
		$alias = $db->GetOne($sql,array($form_id));
		if($alias)
			return $alias;
		return '';
	}

	/**
	GetFormIDFromAlias:
	Interrogates forms table to get the stored id value for form whose alias is @form_alias
	@form_alias: form alias string
	Returns: the id, or -1 if record for the form is not found
	*/
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
	GetFormOption:
	Get the value of option @optname, in the Options array in @formdata
	@formdata: reference to pwfData form data object
	@optname: name of option to find
	@default: optional value to return if the requested option value doesn't exist, default ''
	Returns: value of form option, or @default
	*/
	public static function GetFormOption(&$formdata,$optname,$default='')
	{
		if(isset($formdata->Options[$optname]))
			return $formdata->Options[$optname];
		else
			return $default;
	}

	/**
	CreateSampleTemplate:
	@formdata: reference to pwfData form data object
	@htmlish: whether the template is to include html tags like <h1>, default FALSE
	@email:  whether the template is to be for an email-control, default TRUE
	@oneline: whether the template is to be ...  , default FALSE
	@header: whether the template is to be ...  , default FALSE
	@footer: whether the template is to be the end (of another template), default FALSE
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

	/**
	CreateTemplateAction:
	Setup to insert a defined (probably default) template into a html-control.
	For use when editing a form or field containing a template.
	@mod: reference to PowerForms module object
	@id: id given to the Powerforms module on execution
	@ctlName: name of the control, by convention like 'opt_'.field-opt-name,
		here, it may have appended suffix 'text'
	@$button_label: text for button label
	@template: template to be inserted into the control, upon button-click.
		This becomes a single-quoted js string, so any embedded single-quote
		must be escaped, and any js-unfriendly content must be resolved.
	@funcName: identifier for use when multiple buttons populate the same control, default ''
	Returns: 2-member array, 1st is a button, 2nd is js onclick-func for the button
	*/
	public static function CreateTemplateAction(&$mod,$id,$ctlName,$button_label,$template,$funcName=FALSE)
	{
		if(!$funcName)
			$funcName = $ctlName;
		$button = <<<EOS
<input type="button" class="cms_submit" value="{$button_label}" onclick="javascript:populate_{$funcName}(this.form)" />
EOS;
		$prompt = $mod->Lang('confirm');
		$func = <<<EOS
function populate_{$funcName}(formname) {
 if(confirm('{$prompt}')) {
  formname['{$id}{$ctlName}'].value='{$template}';
 }
}
EOS;
		return array($button,$func);
	}

	/**
	SampleTemplateActions:
	@formdata: reference to pwfData formdata object
	@id: The id given to the Powerforms module on execution
	@ctlData: array of parameters in which keys are names of affected form-control(s),
		values are arrays of parameters, any one or more of
		 'general_button'
		 'html_button'
		 'text_button'
		 'is_email'
		 'is_oneline'
		 'is_footer' (last, if used)
		 'is_header'
		e.g. for 3 controls:
		array
		  'opt_file_template' => array
			  'is_oneline' => true
		  'opt_file_header' => array
			  'is_oneline' => true
			  'is_header' => true
		  'opt_file_footer' => array
			  'is_oneline' => true
			  'is_footer' => true
	Returns: array($buttons,$funcs), where $funcs = array of scripts to be
	 activated by clicking the corresponding button in the $buttons array.
	 The scripts install a 'sample template' into the corresponding control.
	 For some combinations of options, pairs of buttons & scripts are created.
	*/
	public static function SampleTemplateActions(&$formdata,$id,$ctlData)
	{
		$mod = $formdata->formsmodule;
		$buttons = array();
		$funcs = array();
		foreach($ctlData as $ctlname=>$tpopts)
		{
			$gen_button = !empty($tpopts['general_button']);
			$html_button = !empty($tpopts['html_button']);
			$text_button = !empty($tpopts['text_button']);
			$is_email = !empty($vtpopts['is_email']);
			$is_oneline = !empty($tpopts['is_oneline']);
			$is_footer = !empty($tpopts['is_footer']);
			$is_header = !empty($tpopts['is_header']);

			if($html_button && $text_button)
			{
				$sample = self::CreateSampleTemplate($formdata,FALSE,
					$is_email,$is_oneline,$is_header,$is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				list($b,$f) = self::CreateTemplateAction($mod,$id,$ctlname,
					$mod->Lang('title_create_sample_template'),$sample,$ctlname.'_1');
				$buttons[] = $b;
				$funcs[] = $f;
			}

			if($html_button)
				$button_text = $mod->Lang('title_create_sample_html_template');
			elseif($is_header)
				$button_text = $mod->Lang('title_create_sample_header_template');
			elseif($is_footer)
				$button_text = $mod->Lang('title_create_sample_footer_template');
			else
				$button_text = $mod->Lang('title_create_sample_template');

			$sample = self::CreateSampleTemplate($formdata,$html_button || $gen_button,
				$is_email,$is_oneline,$is_header,$is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($b,$f) = self::CreateTemplateAction($mod,$id,$ctlname,$button_text,$sample);
			$buttons[] = $b;
			$funcs[] = $f;
		}
		return array($buttons,$funcs);
	}

	/**
	AddTemplateVariable:
	Adds a member to the $templateVariables array in @formdata (to be used for variables-help)
	@formdata: reference to pwfData object for form 
	@name: variable name (excluding '$')
	@langkey: lang-array key for variable description
	*/
	public static function AddTemplateVariable(&$formdata,$name,$langkey)
	{
		$formdata->templateVariables[$name] = $langkey;
	}

	/**
	FormFieldsHelp:
	Document contents of Fields array in @formdata, and append the contents of
	array @extras
	@formdata: reference to pwfData object for form 
	@$extras: optional array of items to be appended to the output, each member
		having key=id, value=name default = empty array
	Returns: xhtml string which generates a tabular help description
	*/
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
	SetupSubTemplateVarsHelp:
	Setup variables-help for a form's submission-template. Essentially, it sets
	smarty variable 'help_subtplvars' to the output from processing the template
	form_vars_help.tpl
	@formdata: reference to pwfData object for form 
	@mod: reference to current PowerBrowse module object
	@smarty: reference to smarty object
	*/
	public static function SetupSubTemplateVarsHelp(&$formdata,&$mod,&$smarty)
	{
		$smarty->assign('template_vars_title',$mod->Lang('title_template_variables'));
		$smarty->assign('variable_title',$mod->Lang('variable'));
		$smarty->assign('property_title',$mod->Lang('property'));

		$globalvars = array();
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
			$globalvars[] = $oneset;
		}
		foreach($formdata->templateVariables as $name=>$langkey)
		{
			$oneset = new stdClass();
			$oneset->name = '{$'.$name.'}';
			$oneset->title = $mod->Lang($langkey);
			$globalvars[] = $oneset;
		}
		$smarty->assign('globalvars',$globalvars);

		if($formdata->Fields)
		{
			$fieldvars = array();
			foreach($formdata->Fields as &$one)
			{
				if($one->DisplayInSubmission())
				{
					$oneset = new stdClass();
					$oneset->title = $one->GetName();
					$oneset->alias = $one->ForceAlias();
					$oneset->name = $one->GetVariableName();
					$oneset->id = $one->GetId();
					$oneset->escaped = str_replace("'","\\'",$oneset->title);
					$fieldvars[] = $oneset;
				}
			}
			unset($one);
			$smarty->assign('fieldvars',$fieldvars);
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

		$smarty->assign('help_subtplvars',$mod->ProcessTemplate('form_vars_help.tpl'));
	}

	/**
	SetupFormVars:
	Sets various smarty variables
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

	/**
	html_myentities_decode:
	Essentially, html_entity_decode() (with no encoding, flags ENT_COMPAT | ENT_XHTML)
	plus some other changes
	@val: string to be decoded
	Returns: decoded string
	*/
	public static function html_myentities_decode($val)
	{
		if($val == '')
			return '';

		$val = html_entity_decode($val,ENT_COMPAT|ENT_XHTML);
		$val = str_replace(
		array('&amp;','&#60;&#33;--','--&#62;','&gt;','&lt;','&quot;','&#39;','&#036;','&#33;'),
		array('&'    ,'<!--'        ,'-->'    ,'>'   ,'<'   ,'"'     ,"'"    ,'$'     ,'!'    ),
		$val);
		return $val;
	}

	/**
	Encrypt:
	@source: string to be encrypted
	@pass_phrase: en/de-crypt key
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	*/
	public static function Encrypt($source,$pass_phrase)
	{
		if(!$source)
			return '';
		elseif($pass_phrase && extension_loaded('mcrypt'))
		{
			$flag = (defined('MCRYPT_DEV_URANDOM')) ? MCRYPT_DEV_URANDOM : MCRYPT_RAND;
			$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),$flag);
			$encrypt = serialize($source);
			$key = hash('sha256', $pass_phrase); // $key is a 64-character hexadecimal string
			$mac = hash_hmac('sha256', $encrypt, substr($key,-32));
			$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$encrypt.$mac,MCRYPT_MODE_CBC,$iv);
			return base64_encode($passcrypt).'|'.base64_encode($iv);
		}
		else
			return base64_encode(serialize($source));
	}

	/**
	Decrypt:
	@source: string to be encrypted
	@pass_phrase: en/de-crypt key
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	*/
	public static function Decrypt($source,$pass_phrase)
	{
		if(!$source)
			return '';
		elseif($pass_phrase && extension_loaded('mcrypt'))
		{
			$decrypt = explode('|', $source.'|');
			$decoded = base64_decode($decrypt[0]);
			$iv = base64_decode($decrypt[1]);
			if(strlen($iv) !== mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC))
				return FALSE;
			$key = hash('sha256',$pass_phrase);
			$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$decoded,MCRYPT_MODE_CBC,$iv));
			$mac = substr($decrypted,-64);
			$decrypted = substr($decrypted,0,-64);
			$calcmac = hash_hmac('sha256',$decrypted,substr($key,-32));
			if($calcmac === $mac)
				return unserialize($decrypted);
			return FALSE;
		}
		else
			return unserialize(base64_decode($source));
	}

	/**
	CleanTables:
	Removes from the ip_log table all records older than 30-minutes before @time
	Removes from the record table all records older than 24-hours before @time
	Removes from the cache table all records older than 24-hours before @time
	@module: reference to PowerTools module object
	@time: timestamp, optional, default 0 (meaning current time)
	*/
	public static function CleanTables(&$module,$time=0)
	{
		if(!$time) $time = time();
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$limit = $db->DbTimeStamp($time-1800);
		$db->Execute('DELETE FROM '.$pre.'module_pwf_ip_log WHERE basetime<'.$limit);
		$limit = $db->DbTimeStamp($time-86400);
		$db->Execute('DELETE FROM '.$pre.'module_pwf_record WHERE submitted<'.$limit);
		$db->Execute('DELETE FROM '.$pre.'module_pwf_cache WHERE save_time<'.$limit);
	}

	/**
	GetUploadsPath:
	Returns: absolute filepath, or FALSE
	*/
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
