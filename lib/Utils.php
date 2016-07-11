<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class Utils
{
	private static $cache = NULL; //cache object
/*MUTEX
	private static $mxtype = FALSE; //type of mutex in use - 'memcache' etc
	private static $instance = NULL; //'instance' object for mutex class, if needed
*/

	/**
	  GetCache:
	  @mod: reference to Booker-module object
	  @storage: optional cache-type name, one (or more, ','-separated) of
	  auto,yac,apc,apcu,wincache,xcache,redis,predis,file,database
	  default = 'auto' to try all of the above, in that order
	  @settings: optional array of general and cache-type-specific parameters,
	  (e.g. see default array in this func)
	  default empty
	  Returns: cache-object (after creating it if not already done) or NULL
	 */
	public static function GetCache(&$mod, $storage='auto', $settings=array())
	{
//		if (self::$cache == NULL && isset($_SESSION['bkrcache']))
//			self::$cache = $_SESSION['bkrcache'];
		if (self::$cache)
			return self::$cache;

		$path = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
		require($path . 'CacheInterface.php');
		require($path . 'CacheBase.php');

		$config = cmsms()->GetConfig();
		$url = (empty($_SERVER['HTTPS'])) ? $config['root_url'] : $config['ssl_url'];

		$basedir = $config['uploads_path'];
		if (is_dir($basedir)) {
			$rel = $mod->GetPreference('pref_uploadsdir');
			if ($rel) 	{
				$basedir .= DIRECTORY_SEPARATOR . $rel;
			}
		} else
			$basedir = '';

		$settings = array_merge(
			array(
/* 			'memcache' => array(
			  array('host'=>$url,'port'=>11211)
			  ),
			  'memcached' => array(
			  array('host'=>$url,'port'=>11211,'persist'=>1)
			  ),
*/
			'redis' => array(
				'host' => $url //TODO CHECKME
			),
			'predis' => array(
				'host' => $url
			),
			'file' => array(
				'path' => $basedir
			),
			'database' => array(
				'table' => cms_db_prefix() . 'module_bkr_cache'
			)
			), $settings);

		if ($storage) {
			$storage = strtolower($storage);
		} else
			$storage = 'auto';
		if (strpos($storage, 'auto') !== FALSE)
			$storage = 'yac,apc,apcu,wincache,xcache,redis,predis,file,database';

		$cache = NULL;
		$types = explode(',', $storage);
		foreach ($types as $one) {
			$one = trim($one);
			if (!isset($settings[$one]))
				$settings[$one] = array();
			if (empty($settings[$one]['namespace']))
				$settings[$one]['namespace'] = $mod->GetName();
			$class = 'MultiCache\Cache_'.$one;
			try {
				require($path.$one.'.php');
				$cache = new $class($settings[$one]);
				break;
			} catch (\Exception $e) {
				continue;
			}
//			$_SESSION['bkrcache'] = $cache;
			self::$cache = $cache;
			return self::$cache;
		}
		throw new Exception('No cache-driver is available');
	}

	public static function ClearCache()
	{
//		unset($_SESSION['bkrcache']);
		unset(self::$cache);
		self::$cache = NULL;
	}
	
	/* *
	GetMutex:
	@mod: reference to PowerForms module object
	@storage: optional cache-type name, one (or more, ','-separated) of
		auto,memcache,semaphore,file,database, default = 'auto'
	Returns: mutex-object or NULL
	*/
/*MUTEX
	public static function GetMutex(&$mod,$storage='auto')
	{
		$path = __DIR__.DIRECTORY_SEPARATOR.'mutex'.DIRECTORY_SEPARATOR;
		require($path.'interface.Mutex.php');

		$settings = array(
			'memcache'=>array(
				'instance'=>((self::$mxtype=='memcache')?self::$instance:NULL)
				),
			'shmop'=>array(
				),
			'semaphore'=>array(
				'instance'=>((self::$mxtype=='semaphore')?self::$instance:NULL)
				),
			'file'=>array(
				'updir'=>self::GetUploadsPath($mod)
				),
			'database'=>array(
				'table'=>cms_db_prefix().'module_pwf_flock'
				)
		);

		if (self::$mxtype) {
			$one = self::$mxtype;
			require($path.$one.'.php');
			$class = 'Mutex_'.$one;
			$mutex = new $class($settings[$one]);
			return $mutex;
		} else {
			if ($storage)
				$storage = strtolower($storage);
			else
				$storage = 'auto';
			if (strpos($storage,'auto') !== FALSE)
				$storage = 'memcache,semaphore,file,database';

			$types = explode(',',$storage);
			foreach ($types as $one) {
				$one = trim($one);
				$class = 'Mutex_'.$one;
				try {
					require($path.$one.'.php');
					$mutex = new $class($settings[$one]);
				} catch(Exception $e) {
					continue;
				}
				self::$mxtype = $one;
				if (isset($mutex->instance))
					self::$instance = &$mutex->instance;
				else
					self::$instance = NULL;
				return $mutex;
			}
			throw new Exception('Mutex not working');
		}
	}
*/
	/**
	SafeGet:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command
	@args: array of arguments for @sql
	@mode: optional type of get - 'one','row','col','assoc' or 'all', default 'all'
	Returns: boolean indicating successful completion
	*/
	public static function SafeGet($sql,$args,$mode='all')
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			switch ($mode) {
			 case 'one':
				$ret = $db->GetOne($sql,$args);
				break;
			 case 'row':
				$ret = $db->GetRow($sql,$args);
				break;
			 case 'col':
				$ret = $db->GetCol($sql,$args);
				break;
			 case 'assoc':
				$ret = $db->GetAssoc($sql,$args);
				break;
			 default:
				$ret = $db->GetAll($sql,$args);
				break;
			}
			if ($db->CompleteTrans())
				return $ret;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}

	/**
	SafeExec:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command, or array of them
	@args: array of arguments for @sql, or array of them
	Returns: boolean indicating successful completion
	*/
	public static function SafeExec($sql,$args)
	{
		$db = cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i=>$cmd)
					$db->Execute($cmd,$args[$i]);
			} else
				$db->Execute($sql,$args);
			if ($db->CompleteTrans())
				return TRUE;
			else {
				$nt--;
				usleep(50000);
			}
		}
		return FALSE;
	}

//	const MAILERMINVERSION = '1.73'; //minumum acceptable version of CMSMailer module
	/**
	GetForms:
	@orderby: forms-table field name, optional, default 'name'
	Returns: array of all content of the forms-table, sorted by @orderby
	*/
	public static function GetForms($orderby='name')
	{
		// DO NOT parameterise $orderby! ADODB would quote it, then the SQL is not valid
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
		if ($fa == $fb)
			return(strcmp($a,$b));
		elseif ($fa == '*')
			return 1;
		elseif ($fb == '*')
			return -1;
		elseif ($fa == '-') {
			if ($fb == '*')
				return -1;
			else
				return 1;
		} elseif ($fb == '-') {
			if ($fa == '*')
				return 1;
			else
				return -1;
		} else
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
		if ($mod->field_types)
			return; //already done
		$fp = __DIR__.DIRECTORY_SEPARATOR.'Fields.manifest';
		if (!file_exists($fp))
			return;

		$feu = $mod->GetModuleInstance('FrontEndUsers');
		$mail = $mod->GetModuleInstance('CMSMailer');
/*		if ($mail != FALSE) {
			if (version_compare($mail->GetVersion(),self::MAILERMINVERSION) < 0)
				$mail = FALSE;
		}
*/
		$imports = $mod->GetPreference('imported_fields');
		if ($imports)
			$imports = unserialize($imports);

		$mod->field_types = array();

		$rows = file($fp,FILE_SKIP_EMPTY_LINES); //flag doesn't work!!
		foreach ($rows as $oneline) {
			if ($oneline[0] == '#' || ($oneline[0] == '/' && $oneline[1] == '/'))
				continue;
			$classname = trim($oneline);
			if (!$classname)
				continue;
			if ($mail == FALSE && strpos($classname,'Email') !== FALSE)
				continue;
			if ($feu == FALSE && strpos($classname,'FEU') !== FALSE)
				continue;
			//TODO pre-req checks e.g. 'SubmitForm' needs cURL extension
			if ($imports && in_array($imports,$classname))
				self::Show_Field($mod,$classname,FALSE);
			else {
				$menukey = 'field_type_'.substr($classname,3);
				$mod->field_types[$mod->Lang($menukey)] = $classname;
			}
		}
		uksort($mod->field_types,array('Utils','labelcmp'));

		$mod->std_field_types = array(
			$mod->Lang('field_type_Checkbox')=>'Checkbox',
			$mod->Lang('field_type_Pulldown')=>'Pulldown',
			$mod->Lang('field_type_RadioGroup')=>'RadioGroup',
			$mod->Lang('field_type_StaticText')=>'StaticText',
			$mod->Lang('field_type_TextArea')=>'TextArea',
			$mod->Lang('field_type_Text')=>'Text',
			$mod->Lang('field_type_SystemEmail')=>'SystemEmail',
			$mod->Lang('field_type_SharedFile')=>'SharedFile');
		uksort($mod->std_field_types,array('Utils','labelcmp'));
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
		if ($mod->field_types) {
			$params = array();
			$formdata = $mod->GetFormData($params);
			$obfld = new $classname($formdata,$params);
			if ($obfield) {
				if (!($obfld->IsInput || $obfld->IsSortable)) //TODO check this
					$t = '-';
				elseif ($obfld->IsDisposition)
					$t = '*';
				else
					$t = '';
				$menulabel = $t.$obfld->mymodule->Lang($obfld->MenuKey);
				$mod->field_types[$menulabel] = $classname;
				if ($sort)
					uksort($mod->field_types,array('Utils','labelcmp'));
			}
		}
	}

	/**
	FileClassName:
	@ilename: name of a field-class file, like 'class.Something.php'
	Returns: classname (the residual 'Something') after some checking
	*/
	public static function FileClassName($filename)
	{
		$shortname = str_replace(array('class.','.php'),array('',''),$filename);
		return self::MakeClassName($shortname);
	}

	/**
	MakeClassName:
	@type: 'core' part of a class name, with or without 'pwf' prefix
	Returns: a class name 'Something', possibly a (useless) default 'Field'
	*/
	public static function MakeClassName($type)
	{
		// rudimentary security,cuz' $type could come from a form
		$type = preg_replace('~[\W]|\.\.~','_',$type); //TODO
		if ($type) {
			if (strpos($type,'pwf') !== 0)
				return $type;
			else
				return substr($type,3);
		}
		return 'Field';
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
		if (!$string)
			return '';
		$alias = strtolower(trim($string,"\t\n\r\0 _"));
		if (!$alias)
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
		if ($name)
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
		if ($alias)
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
		if ($fid)
			return (int)$fid;
		return -1;
	}

	/**
	GetFormOption:
	Get the value of option @optname, in the Options array in @formdata
	@formdata: reference to Data form data object
	@optname: name of option to find
	@default: optional value to return if the requested option value doesn't exist, default ''
	Returns: value of form option, or @default
	*/
	public static function GetFormOption(&$formdata,$optname,$default='')
	{
		if (isset($formdata->Options[$optname]))
			return $formdata->Options[$optname];
		else
			return $default;
	}

	/**
	CreateDefaultTemplate:
	@formdata: reference to Data form data object
	@htmlish: whether the template is to include html tags like <h1>, default FALSE
	@email:  whether the template is to begin with email-specific stuff, default TRUE
	@oneline: whether the template is to NOT begin with a 'thanks' line, (irrelevant if @email = TRUE) default FALSE
	@header: whether the template is to include fieldnames, (irrelevant if @oneline = FALSE), default FALSE
	@footer: whether the template is to be the end (of another template), default FALSE
	*/
	public static function CreateDefaultTemplate(&$formdata,
		$htmlish=FALSE,$email=TRUE,$oneline=FALSE,$header=FALSE,$footer=FALSE)
	{
		$mod = $formdata->formsmodule;
		$ret = '';

		if ($email) {
			if ($htmlish)
				$ret .= '<h3>'.$mod->Lang('email_default_template').'</h3>'.PHP_EOL;
			else
				$ret .= $mod->Lang('email_default_template').PHP_EOL;

			foreach (array(
			 'form_name' => 'title_form_name',
			 'form_url' =>'help_form_url',
			 'form_host' => 'help_server_name',
			 'sub_date' => 'help_submission_date',
			 'sub_source' => 'help_sub_source',
			 'version' => 'help_module_version') as $key=>$val)
			{
				if ($htmlish)
					$ret .= '<strong>'.$mod->Lang($val).'</strong>: {$'.$key.'}<br />';
				else
					$ret .= $mod->Lang($val).': {$'.$key.'}';
				$ret .= PHP_EOL;
			}

			if ($htmlish)
				$ret .= PHP_EOL.'<hr />'.PHP_EOL;
			else
				$ret .= PHP_EOL.'-------------------------------------------------'.PHP_EOL;
		} elseif (!$oneline) {
			if ($htmlish)
				$ret .= '<h4>'.$mod->Lang('thanks').'</h4>'.PHP_EOL;
			else
				$ret .= $mod->Lang('thanks').PHP_EOL;
		} elseif ($footer) {
			if ($htmlish)
				$ret .= '<hr />'.PHP_EOL.'<!--EOF-->'.PHP_EOL;
			else
				$ret .= '-------------------------------------------------'.PHP_EOL;
			 return $ret;
		}

		foreach ($formdata->Fields as &$one) {
			if ($one->DisplayInSubmission()) {
				$fldref = $one->ForceAlias();
	 			$ret .= '{if $'.$fldref.' != "" && $'.$fldref.' != "'.self::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified')).'"}';
				$fldref = '{$'.$fldref.'}';

				if ($htmlish)
					$ret .= '<strong>'.$one->GetName().'</strong>: '.$fldref.'<br />';
				elseif ($oneline && !$header)
					$ret .= $fldref."\t";
				elseif ($oneline && $header)
					$ret .= $one->GetName()."\t";
				else
					$ret .= $one->GetName().': '.$fldref;
				$ret .= '{/if}'.PHP_EOL;
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
		if (!$funcName)
			$funcName = $ctlName;
		$button = <<<EOS
<input type="button" class="cms_submit" value="{$button_label}" onclick="javascript:populate_{$funcName}(this.form)" />
EOS;
		$prompt = $mod->Lang('confirm');
		$func = <<<EOS
function populate_{$funcName}(formname) {
 if (confirm('{$prompt}')) {
  formname['{$id}{$ctlName}'].value='{$template}';
 }
}
EOS;
		return array($button,$func);
	}

	/**
	TemplateActions:
	@formdata: reference to Data formdata object
	@id: The id given to the Powerforms module on execution
	@ctlData: array of parameters in which key(s) are respective names of affected form-control(s),
		values are arrays of parameters, their key(s) being any one or more of
		 'general_button'
		 'html_button'
		 'text_button'
		 'is_email'
		 'is_oneline'
		 'is_footer' (last, if used)
		 'is_header'
		and their respective values being boolean
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
	public static function TemplateActions(&$formdata,$id,$ctlData)
	{
		$mod = $formdata->formsmodule;
		$buttons = array();
		$funcs = array();
		foreach ($ctlData as $ctlname=>$tpopts) {
			$gen_button = !empty($tpopts['general_button']);
			$html_button = !empty($tpopts['html_button']);
			$text_button = !empty($tpopts['text_button']);
			$is_email = !empty($vtpopts['is_email']);
			$is_oneline = !empty($tpopts['is_oneline']);
			$is_footer = !empty($tpopts['is_footer']);
			$is_header = !empty($tpopts['is_header']);

			$nl = PHP_EOL;
			$l = strlen($nl);
			$breaker = '';
			for ($i=0;$i<$l;$i++)
				$breaker .= (ord($nl[$i])==10) ? '\n':'\r';

			if ($html_button && $text_button) {
				$tplstr = self::CreateDefaultTemplate($formdata,FALSE,
					$is_email,$is_oneline,$is_header,$is_footer);
				//adjust the string for js
				$tplstr = str_replace(array("'",PHP_EOL),array("\\'",$breaker),$tplstr);
				list($b,$f) = self::CreateTemplateAction($mod,$id,$ctlname,
					$mod->Lang('title_create_sample_template'),$tplstr,$ctlname.'_1');
				$buttons[] = $b;
				$funcs[] = $f;
			}

			if ($html_button)
				$button_text = $mod->Lang('title_create_sample_html_template');
			elseif ($is_header)
				$button_text = $mod->Lang('title_create_sample_header_template');
			elseif ($is_footer)
				$button_text = $mod->Lang('title_create_sample_footer_template');
			else
				$button_text = $mod->Lang('title_create_sample_template');

			$tplstr = self::CreateDefaultTemplate($formdata,$html_button || $gen_button,
				$is_email,$is_oneline,$is_header,$is_footer);
			//adjust the string for js
			$tplstr = str_replace(array("'",PHP_EOL),array("\\'",$breaker),$tplstr);
			list($b,$f) = self::CreateTemplateAction($mod,$id,$ctlname,$button_text,$tplstr);
			$buttons[] = $b;
			$funcs[] = $f;
		}
		return array($buttons,$funcs);
	}

	/**
	AddTemplateVariable:
	Adds a member to the $templateVariables array in @formdata (to be used for variables-help)
	@formdata: reference to Data object for form
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
	@formdata: reference to Data object for form
	@$extras: optional array of items to be appended to the output, each member
		having key=id, value=name default = empty array
	Returns: xhtml string which generates a tabular help description
	*/
	public static function FormFieldsHelp(&$formdata,&$extras=array())
	{
		$rows = array();
		foreach ($formdata->Fields as &$one) {
			$oneset = new StdClass();
			$oneset->id = $one->GetId();
			$oneset->name = $one->GetName();
			$rows[] = $oneset;
		}
		unset($one);
		if ($extras) {
			foreach ($extras as $id=>$name) {
				$oneset = new StdClass();
				$oneset->id = $id;
				$oneset->name = $name;
				$rows[] = $oneset;
			}
		}

		$mod = $formdata->formsmodule;
		$tplvars = array(
			'title_variables' => $mod->Lang('title_variables_available'),
			'title_name' => $mod->Lang('title_php_variable'),
			'title_field' => $mod->Lang('title_form_field'),
			'rows' => $rows
		);

		return self::ProcessTemplate($mod,'varshelp.tpl',$tplvars);
	}

	/**
	SetupSubTemplateVarsHelp:
	Setup variables-help for a form's submission-template. Essentially, it sets
	smarty variable 'help_subtplvars' to the output from processing the template
	varshelp.tpl
	@formdata: reference to Data object for form
	@mod: reference to current PowerBrowse module object
	@tplvars: reference to template-variables array
	*/
	public static function SetupSubTemplateVarsHelp(&$formdata,&$mod,&$tplvars)
	{
		$tplvars = $tplvars + array(
		 'template_vars_title' => $mod->Lang('title_template_variables'),
		 'variable_title' => $mod->Lang('variable'),
		 'property_title' => $mod->Lang('property')
		);

		$globalvars = array();
		foreach (array(
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
		foreach ($formdata->templateVariables as $name=>$langkey) {
			$oneset = new stdClass();
			$oneset->name = '{$'.$name.'}';
			$oneset->title = $mod->Lang($langkey);
			$globalvars[] = $oneset;
		}
		$tplvars['globalvars'] = $globalvars;

		if ($formdata->Fields) {
			$fieldvars = array();
			foreach ($formdata->Fields as &$one) {
				if ($one->DisplayInSubmission()) {
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
			$tplvars['fieldvars'] = $fieldvars;
		}

/*		$obfields = array();
		foreach (array ('name','type','id','value','valuearray') as $name) {
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->title = $mod->Lang('title_field_'.$name);
			$obfields[] = $oneset;
		}
		$tplvars['obfields'] = $obfields;

//		$oneset->title = $mod->Lang('title_field_id2');
		$tplvars['help_field_values'] = $mod->Lang('help_field_values'));
		$tplvars['help_object_example'] = $mod->Lang('help_object_example'));
*/
		$tplvars['help_other_fields'] = $mod->Lang('help_other_fields');

		$tplvars['help_subtplvars'] = self::ProcessTemplate($mod,'varshelp.tpl',$tplvars);
	}

	/**
	SetupFormVars:
	Sets various smarty variables
	@formdata: reference to form data object
	@tplvars: reference to template-variables array
	@htmlemail: optional boolean, whether processing a form for html email, default FALSE
	*/
	public static function SetupFormVars(&$formdata,&$tplvars,$htmlemail=FALSE)
	{
		$mod = $formdata->formsmodule;
		// general variables
		$tplvars = $tplvars + array(
			'form_name' => $formdata->Name,
			'form_url' => (empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']),
			'form_host' => $_SERVER['SERVER_NAME'],
			'sub_date' => date('r'),
			'sub_source' => $_SERVER['REMOTE_ADDR'],
			'version' => $mod->GetVersion()
		);

		$unspec = self::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified'));

		foreach ($formdata->Fields as &$one) {
			$replVal = $unspec;
			$replVals = array();
			if ($one->DisplayInSubmission()) {
				$replVal = $one->GetHumanReadableValue();
				if ($htmlemail) {
					// allow <BR> as delimiter or in content
					$replVal = preg_replace(
						array('/<br(\s)*(\/)*>/i','/[\n\r]+/'),array('|BR|','|BR|'),
						$replVal);
					$replVal = htmlspecialchars($replVal);
					$replVal = str_replace('|BR|','<br />',$replVal);
				}
				if ($replVal == '')
					$replVal = $unspec;
			}

			$name = $one->GetVariableName();
//			$fldobj = $one->ExportObject();
			$tplvars[$name] = $replVal;
//			$tplvars[$name.'_obj'] = $fldobj;
			$alias = $one->ForceAlias();
			$tplvars[$alias] = $replVal;
//			$tplvars[$alias.'_obj'] = $fldobj;
			$id = $one->GetId();
			$tplvars['fld_'.$id] = $replVal;
//			$tplvars['fld_'.$id.'_obj'] = $fldobj;
		}
		unset ($one);
	}

	/**
	ProcessTemplate:
	@mod: reference to current PowerForms module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: string, processed template
	*/
	public static function ProcessTemplate(&$mod,$tplname,$tplvars,$cache=TRUE)
	{
		global $smarty;
		if ($mod->before20) {
			$smarty->assign($tplvars);
			return $mod->ProcessTemplate($tplname);
		} else {
			if ($cache) {
				$cache_id = md5('pwf'.$tplname.serialize(array_keys($tplvars)));
				$lang = CmsNlsOperations::get_current_language();
				$compile_id = md5('pwf'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),$cache_id,$compile_id,$smarty);
				if (!$tpl->isCached())
					$tpl->assign($tplvars);
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname),NULL,NULL,$smarty,$tplvars);
			}
			return $tpl->fetch();
		}
	}

	/**
	ProcessTemplateFromDatabase:
	@mod: reference to current PowerForms module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: nothing
	*/
	public static function ProcessTemplateFromDatabase(&$mod,$tplname,$tplvars,$cache=TRUE)
	{
		global $smarty;
		if ($mod->before20) {
			$smarty->assign($tplvars);
			echo $mod->ProcessTemplateFromDatabase($tplname);
		} else {
			//TODO handle old template if new one N/A
			if ($cache) {
				$cache_id = md5('pwf'.$tplname.serialize(array_keys($tplvars)));
				$lang = CmsNlsOperations::get_current_language();
				$compile_id = md5('pwf'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetTemplateResource($tplname),$cache_id,$compile_id,$smarty);
				if (!$tpl->isCached())
					$tpl->assign($tplvars);
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetTemplateResource($tplname),NULL,NULL,$smarty,$tplvars);
			}
			$tpl->display();
		}
	}

	/**
	ProcessTemplateFromData:
	@mod: reference to current PowerForms module object
	@data: string
	@tplvars: associative array of template variables
	No cacheing.
	Returns: string, processed template
	*/
	public static function ProcessTemplateFromData(&$mod,$data,$tplvars)
	{
		global $smarty;
		if ($mod->before20) {
			$smarty->assign($tplvars);
			return $mod->ProcessTemplateFromData($data);
		} else {
			$tpl = $smarty->CreateTemplate('eval:'.$data,NULL,NULL,$smarty,$tplvars);
			return $tpl->fetch();
		}
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
		if ($val == '')
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
	@mod: reference to PowerForms module object
	@source: string to be encrypted
	@pass_phrase: en/de-crypt key, if empty then the default will be used
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	*/
	public static function Encrypt(&$mod,$source,$pass_phrase='')
	{
		if (!$source)
			return '';
		if (!$pass_phrase) {
			$pass_phrase = self::Unfusc($mod->GetPreference('masterpass'));
		}
		if ($pass_phrase && $mod->havemcrypt) {
			$flag = (defined('MCRYPT_DEV_URANDOM')) ? MCRYPT_DEV_URANDOM : MCRYPT_RAND;
			$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC),$flag);
			$encrypt = serialize($source);
			$key = hash('sha256', $pass_phrase); // $key is a 64-character hexadecimal string
			$mac = hash_hmac('sha256', $encrypt, substr($key,-32));
			$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$encrypt.$mac,MCRYPT_MODE_CBC,$iv);
			return base64_encode($passcrypt).'|'.base64_encode($iv);
		} else
			return self::Fusc($pass_phrase.$source);
	}

	/**
	Decrypt:
	@mod: reference to PowerForms module object
	@source: string to be encrypted
	@pass_phrase: en/de-crypt key, if empty then the default will be used
	This function derived from work by Josh Hartman and others.
	Reference: http://www.warpconduit.net/2013/04/14/highly-secure-data-encryption-decryption-made-easy-with-php-mcrypt-rijndael-256-and-cbc
	*/
	public static function Decrypt(&$mod,$source,$pass_phrase='')
	{
		if (!$source)
			return '';
		if (!$pass_phrase) {
			$pass_phrase = self::Unfusc($mod->GetPreference('masterpass'));
		}
		if ($pass_phrase && $mod->havemcrypt) {
			$decrypt = explode('|', $source.'|');
			$decoded = base64_decode($decrypt[0]);
			$iv = base64_decode($decrypt[1]);
			if (strlen($iv) !== mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC))
				return FALSE;
			$key = hash('sha256',$pass_phrase);
			$decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128,substr($key,32),$decoded,MCRYPT_MODE_CBC,$iv));
			$mac = substr($decrypted,-64);
			$decrypted = substr($decrypted,0,-64);
			$calcmac = hash_hmac('sha256',$decrypted,substr($key,-32));
			if ($calcmac === $mac)
				return unserialize($decrypted);
			return FALSE;
		} else
			return substr(strlen($pass_phrase),self::Unfusc($source));
	}

	/**
	Fusc:
	@str: string or FALSE
	obfuscate @str
	*/
	public static function Fusc($str)
	{
		if ($str) {
			$s = substr(base64_encode(md5(microtime())),0,5);
			return $s.base64_encode($s.$str);
		}
		return '';
	}

	/**
	Unfusc:
	@str: string or FALSE
	de-obfuscate @str
	*/
	public static function Unfusc($str)
	{
		if ($str) {
			$s = base64_decode(substr($str,5));
			return substr($s,5);
		}
		return '';
	}

	/**
	CreateHierarchyPulldown:
	Get site-pages selector, with first item 'select one'
	@mod: reference to PowerTools module object
	@id: module identifier
	@name: control name
	@current: id of currently selected content object
	Returns: html string
	*/
	public static function CreateHierarchyPulldown(&$mod,$id,$name,$current)
	{
		$contentops = cmsms()->GetContentOperations();
		$name = $id.$name;
		$sel = $contentops->CreateHierarchyDropdown('',$current,$name);
		if ($sel) {
			$srch = array('<select name="'.$name.'" id="'.$name.'">',
						'<option value="-1">none</option>');
			$repl = array($srch[0].'<option value="0">'.$mod->Lang('select_one').'</option>','');
			return str_replace($srch,$repl,$sel);
		}
		return '';
	}

	/**
	CleanTables:
	Removes from the ip_log table all records older than 30-minutes before @time
	Removes from the record table all records older than 24-hours before @time
	Removes from the cache table all records older than 24-hours before @time
	@time: timestamp, optional, default 0 (meaning current time)
	*/
	public static function CleanTables($time=0)
	{
		if (!$time) $time = time();
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
	@mod: reference to current module object
	Returns: absolute filepath, or FALSE
	*/
	public static function GetUploadsPath(&$mod)
	{
		$config = cmsms()->GetConfig();
		$fp = $config['uploads_path'];
		if ($fp && is_dir($fp)) {
			$ud = $mod->GetPreference('uploads_dir');
			if ($ud) {
				$ud = $fp.DIRECTORY_SEPARATOR.$ud;
				if (is_dir($ud))
					return $ud;
			}
			return $fp;
		}
		return FALSE;
	}

}

