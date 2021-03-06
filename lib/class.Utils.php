<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Utils
{
	/**
	  GetCache:
	  Returns: cache-object or NULL
	 */
	public static function GetCache()
	{
		$funcs = new \Async\Cache();
		return $funcs->Get();
	}

	/**
	SafeGet:
	Execute SQL command(s) with minimal chance of data-race
	@sql: SQL command
	@args: array of arguments for @sql
	@mode: optional type of get - 'one','row','col','assoc' or 'all', default 'all'
	Returns: boolean indicating successful completion
	*/
	public static function SafeGet($sql, $args, $mode='all')
	{
		$db = \cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			switch ($mode) {
			 case 'one':
				$ret = $db->GetOne($sql, $args);
				break;
			 case 'row':
				$ret = $db->GetRow($sql, $args);
				break;
			 case 'col':
				$ret = $db->GetCol($sql, $args);
				break;
			 case 'assoc':
				$ret = $db->GetAssoc($sql, $args);
				break;
			 default:
				$ret = $db->GetArray($sql, $args);
				break;
			}
			if ($db->CompleteTrans()) {
				return $ret;
			} else {
				--$nt;
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
	public static function SafeExec($sql, $args)
	{
		$db = \cmsms()->GetDb();
		$nt = 10;
		while ($nt > 0) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
			$db->StartTrans();
			if (is_array($sql)) {
				foreach ($sql as $i=>$cmd) {
					$db->Execute($cmd, $args[$i]);
				}
			} else {
				$db->Execute($sql, $args);
			}
			if ($db->CompleteTrans()) {
				return TRUE;
			} else {
				--$nt;
				usleep(50000);
			}
		}
		return FALSE;
	}

//	const MAILERMINVERSION = '1.73'; //minimum acceptable version of CMSMailer module
	/**
	GetForms:
	@orderby: forms-table field name, optional, default 'name'
	Returns: array of all content of the forms-table, sorted by @orderby
	*/
	public static function GetForms($orderby='name')
	{
		// DO NOT parameterise $orderby! ADODB would quote it, then the SQL is not valid
		// instead,rudimentary security checks
		$orderby = preg_replace('/\s/', '', $orderby);
		$orderby = preg_replace('/[^\w\-.]/', '_', $orderby);
		$pre = \cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_form ORDER BY '.$orderby;
		$db = \cmsms()->GetDb();
		return $db->GetArray($sql);
	}

/*	public static function mb_asort(&$array)
	{
		if (extension_loaded('intl')) {
			collator_asort(collator_create(NULL),$array); //OR 'root' OR specific locale
		} else {
			array_multisort(array_map(function($str)
			{
				return preg_replace(
				'~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i',
				'$1'.chr(255).'$2',
				htmlentities($str,ENT_QUOTES,'UTF-8'));
			},$array),$array);
		}
	}

	public static function mb_strcmp($a,$b)
	{
		if (extension_loaded('intl')) {
			static $coll = NULL;
			if ($coll == NULL)
				$coll = new \collator(NULL); //OR 'root' OR specific locale
			return $coll->compare($a,$b);
		} else {
//$converted = preg_replace('~[^\w\s]+~','',iconv('UTF-8','ASCII//TRANSLIT',$str));
//$converted = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i','$1'.chr(255).'$2',htmlentities($str,ENT_QUOTES,'UTF-8'));
		}
	}
	setlocale (LC_COLLATE,'en_US'); or whatever
	return strcoll($str1,$str2);
*/
	//comparer for field-selection menu-item sorting, dipositions last, non-inputs 2nd-last
	private static function labelcmp($a, $b)
	{
		$fa = $a[0];
		$fb = $b[0];
		if ($fa == $fb) {
			return(strcmp($a, $b)); //TODO mb_ comparison
		} elseif ($fa == '*') { //disposition field-prefix
			return 1;
		} elseif ($fb == '*') {
			return -1;
		} elseif ($fa == '-') { //non-input field-prefix
			if ($fb == '*') {
				return -1;
			} else {
				return 1;
			}
		} elseif ($fb == '-') {
			if ($fa == '*') {
				return 1;
			} else {
				return -1;
			}
		} else {
			return(strcmp($a, $b)); //TODO mb_ comparison
		}
	}

	/**
	Collect_Fields:
	Populates and caches full and abbreviated arrays of available field-types,
	from file 'Fields.manifest' plus any 'imported' field(s), for use in any
	add-field pulldown. Does nothing if the arrays are already cached.
	@mod: reference to PWForms module object
	*/
	public static function Collect_Fields(&$mod)
	{
		if ($mod->field_types) {
			return;
		} //already done

		$menu = [];
		foreach ([
			'Checkbox',
			'Pulldown',
			'RadioGroup',
			'StaticText',
			'TextArea',
			'Text',
			'SystemEmail',
			'SharedFile'] as $classname) {
			$menukey = 'fieldlabel_'.$classname;
			//TODO dynamically add prefix '*' for dispositions, '-' for non-inputs??
			$menu[$classname] = $mod->Lang($menukey);
		}
		uasort($menu, ['self', 'labelcmp']);
		$mod->std_field_types = array_flip($menu);

		$fp = __DIR__.DIRECTORY_SEPARATOR.'Fields.manifest';
		if (file_exists($fp)) {
			if ($mod->before20) {
				$mail = \cms_utils::get_module('CMSMailer');
/*				if ($mail) {
					if (version_compare($mail->GetVersion(),self::MAILERMINVERSION) < 0)
						$mail = FALSE;
				}
*/
			} else {
				$mail = TRUE;
			}
			$feu = \cms_utils::get_module('FrontEndUsers');

			$menu = [];
			$rows = file($fp, FILE_SKIP_EMPTY_LINES); //flag doesn't work!!
			foreach ($rows as $oneline) {
				if ($oneline[0] == '#' || ($oneline[0] == '/' && $oneline[1] == '/')) {
					continue;
				}
				$classname = trim($oneline);
				if (!$classname) {
					continue;
				}
				if (!$mail && strpos($classname, 'Email') !== FALSE) {
					continue;
				}
				if (!$feu && strpos($classname, 'FEU') !== FALSE) { //DEPRECATED feu-related classes to be $imports members
					continue;
				}
				//TODO pre-req checks e.g. 'SubmitForm' needs cURL extension
				$menukey = 'fieldlabel_'.$classname;
				//TODO dynamically add prefix '*' for dispositions, '-' for non-inputs? c.f. self::Show_Field()
				$menu[$classname] = $mod->Lang($menukey);
			}
		} else {
			$menu += ['_' => $mod->Lang('missing_type', $mod->Lang('TODO'))];
		}

		$imports = $mod->GetPreference('imported_fields');
		if ($imports) {
			$imports = unserialize($imports);
			$bp = __DIR__.DIRECTORY_SEPARATOR.'class.';
			foreach ($imports as $classname) {
				$fp = $bp.$classname.'.php';
				if (is_file($fp)) {
					include_once $fp;
					$classpath = 'PWForms\\'.$classname;
					$formdata = new FormData($mod);
					$params = [];
					$obfld = new $classpath($formdata, $params);
					if ($obfld) {
						$menu[$classname] = $obfld->GetDisplayType();
					}
				}
			}
		}

		uasort($menu, ['self', 'labelcmp']);
		$mod->field_types = array_flip($menu);
	}

	/**
	Show_Field:
	Include @classname in the array of available fields (to be used in any add-field pulldown)
	@mod: reference to PWForms module object
	@classname: name of class for the field to be added
	@sort: optional boolean, whether to sort ... , default TRUE
	*/
	public static function Show_Field(&$mod, $classname, $sort=TRUE)
	{
		if ($mod->field_types) {
			$formdata = new PWForms\Formdata($mod);
			$classpath = 'PWForms\\'.$classname;
			$params = [];
			$obfld = new $classpath($formdata, $params);
			if ($obfld) {
				if (!$obfld->IsInput) { //TODO check this
					$p = '-';
				} elseif ($obfld->IsDisposition) {
					$p = '*';
				} else {
					$p = '';
				}
				$menulabel = $p.$obfld->mymodule->Lang($obfld->MenuKey);
				$mod->field_types[$menulabel] = $classname;
				if ($sort) {
					uksort($mod->field_types, ['self', 'labelcmp']); //TODO mb-compatible sort $coll = new Collator('fr_FR');
				}
			}
		}
	}

	/**
	GetFieldIDFromAlias:
	Interrogates fields table to get the stored id value for field whose alias is @alias
	@alias: field alias string
	Returns: the id, or -1 if record for the field is not found
	*/
	public static function GetFieldIDFromAlias($alias)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'SELECT field_id FROM '.$pre.'module_pwf_field WHERE alias = ?';
		$fid = $db->GetOne($sql, [$alias]);
		if ($fid) {
			return (int)$fid;
		}
		return -1;
	}

	/**
	FileClassName:
	@ilename: name of a field-class file, like 'class.Something.php'
	Returns: classname (the residual 'Something') after some checking
	*/
	public static function FileClassName($filename)
	{
		$shortname = str_replace(['class.', '.php'], ['', ''], $filename);
		return self::MakeClassName($shortname);
	}

	/**
	MakeClassName:
	@type: 'core' part of a class name, with or without 'pwf' prefix
	Returns: a class name 'Something', possibly a (useless) default 'Field'
	*/
	public static function MakeClassName($type)
	{
		// rudimentary security, cuz' $type could come from a form
		$type = preg_replace('~[\W]|\.\.~', '_', $type); //TODO
		if ($type) {
			return $type;
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
	public static function MakeAlias($string, $maxlen=48)
	{
		if (!$string) {
			return '';
		}
		$alias = strtolower(trim($string, "\t\n\r\0 _"));
		if (!$alias) {
			return '';
		}
		$alias = preg_replace('/[^\w]+/', '_', $alias);
		$parts = array_slice(explode('_', $alias), 0, 5);
		$alias = substr(implode('_', $parts), 0, $maxlen);
		return trim($alias, '_');
	}

	/**
	GetFormNameFromID:
	Interrogates the forms table to get the stored name for a form whose id is @form_id
	@form_id: form id number
	Returns: the name, or '' if record for the form is not found
	*/
	public static function GetFormNameFromID($form_id)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'SELECT name FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$name = $db->GetOne($sql, [$form_id]);
		if ($name) {
			return $name;
		}
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
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'SELECT alias FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$alias = $db->GetOne($sql, [$form_id]);
		if ($alias) {
			return $alias;
		}
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
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'SELECT form_id FROM '.$pre.'module_pwf_form WHERE alias = ?';
		$fid = $db->GetOne($sql, [$form_alias]);
		if ($fid) {
			return (int)$fid;
		}
		return -1;
	}

	/**
	GetFormProperty:
	Get the value of @formdata->XtraProps[@propname]
	@formdata: reference to FormData-class object
	@propname: name/key of wanted property
	@default: optional value to return if the requested property doesn't exist, default ''
	Returns: value of form property if it exists, or else @default
	*/
	public static function GetFormProperty(&$formdata, $propname, $default='')
	{
		if (isset($formdata->XtraProps[$propname])) {
			return $formdata->XtraProps[$propname];
		} else {
			return $default;
		}
	}

	/**
	CreateDefaultTemplate:
	@formdata: reference to FormData-class object
	@htmlish: whether the template is to include html tags like <h1>, default FALSE
	@email:  whether the template is to begin with email-specific stuff, default TRUE
	@oneline: whether the template is to NOT begin with a 'thanks' line, (irrelevant if @email = TRUE) default FALSE
	@header: whether the template is to include fieldnames, (irrelevant if @oneline = FALSE), default FALSE
	@footer: whether the template is to be the end (of another template), default FALSE
	*/
	public static function CreateDefaultTemplate(&$formdata,
		$htmlish=FALSE, $email=TRUE, $oneline=FALSE, $header=FALSE, $footer=FALSE)
	{
		$mod = $formdata->pwfmod;
		$ret = '';

		if ($email) {
			if ($htmlish) {
				$ret .= '<h3>'.$mod->Lang('email_default_template').'</h3>'.PHP_EOL;
			} else {
				$ret .= $mod->Lang('email_default_template').PHP_EOL;
			}

			foreach ([
			 'form_name' => 'title_form_name',
			 'form_url' =>'help_form_url',
			 'form_host' => 'help_server_name',
			 'sub_date' => 'help_submission_date',
			 'sub_source' => 'help_sub_source',
			 'version' => 'help_module_version'] as $key=>$val) {
				if ($htmlish) {
					$ret .= '<strong>'.$mod->Lang($val).'</strong>: {$'.$key.'}<br />';
				} else {
					$ret .= $mod->Lang($val).': {$'.$key.'}';
				}
				$ret .= PHP_EOL;
			}

			if ($htmlish) {
				$ret .= '<hr />'.PHP_EOL;
			} else {
				$ret .= '-------------------------------------------------'.PHP_EOL;
			}
		} elseif (!$oneline) {
			if ($htmlish) {
				$ret .= '<h4>'.$mod->Lang('thanks').'</h4>'.PHP_EOL;
			} else {
				$ret .= $mod->Lang('thanks').PHP_EOL;
			}
		} elseif ($footer) {
			if ($htmlish) {
				$ret .= '<hr />'.PHP_EOL.'<!--EOF-->'.PHP_EOL;
			} else {
				$ret .= '-------------------------------------------------'.PHP_EOL;
			}
			return $ret;
		}

		$unspec = self::GetFormProperty($formdata, 'unspecified', $mod->Lang('unspecified'));
//TODO support field-sequences
		foreach ($formdata->Fields as &$one) {
			if ($one && $one->DisplayInSubmission()) {
				$fldref = $one->ForceAlias();
				$ret .= '{if $'.$fldref.' && $'.$fldref.' != "'.$unspec.'"}';
				$fldref = '{$'.$fldref.'}';

				if ($htmlish) {
					$ret .= '<strong>'.$one->GetName().'</strong>: '.$fldref.'<br />';
				} elseif ($oneline) {
					if ($header) {
						$ret .= $one->GetName().PHP_EOL;
					} else {
						$ret .= $fldref.PHP_EOL;
					}
				} else {
					$ret .= $one->GetName().':';
					if ($email) {
						$ret .= ' ';
					} else {
						$ret .= PHP_EOL;
					}
					$ret .= $fldref.PHP_EOL;
				}
				$ret .= '{/if}'.PHP_EOL;
			}
		}
		unset($one);
		return $ret;
	}

	/**
	SetTemplateScript:
	@mod: reference to PWForms module object
	@id: module id
	@params: associative array of URL parameters, including (at least) 'type'=>'whatever'
	Returns: string js function
	*/
	public static function SetTemplateScript(&$mod, $id, $params)
	{
		$prompt = $mod->Lang('confirm_template');
		$msg = $mod->Lang('err_server');
		$u = $mod->create_url($id, 'populate_template', '', $params + ['datakey'=>'']);
		$offs = strpos($u, '?mact=');
		$u = str_replace('&amp;', '&', substr($u, $offs+1));
		return <<<EOS
function populate_template(elid,extra) {
 if (confirm('$prompt')) {
  var msg = '$msg',
   append = $('input[name={$id}datakey').val();
  if(typeof extra !== 'undefined') {
   Object.keys(extra).forEach(function(key) {
    append += '&$id'+key+'='+extra[key];
   });
  }
  $.ajax({
   type: 'POST',
   url: 'moduleinterface.php',
   data: '$u'+append,
   dataType: 'text',
   success: function(data,status) {
    if (status=='success') {
     $('#'+elid).val(data);
    } else {
     alert(msg);
    }
   },
   error: function() {
    alert(msg);
   }
  });
 }
}
EOS;
	}

	/**
	SetTemplateButton:
	Setup to insert a defined (probably default) template into a html-control.
	For use when editing a form or field containing a template.
	@ctlName: 'raw' name of the control, by convention like field-prop-name
	@label: text for button-label
	Returns: string XHTML button (un-named, no applied js)
	*/
	public static function SetTemplateButton($ctlName, $label)
	{
		return <<<EOS
<input type="button" class="cms_submit" id="get_{$ctlName}" value="{$label}" />
EOS;
	}

	/**
	AddTemplateVariable:
	Adds a member to the $templateVariables array in @formdata (to be used for variables-help)
	@formdata: reference to FormData-class object
	@name: variable name (excluding '$')
	@langkey: lang-array key for variable description
	*/
	public static function AddTemplateVariable(&$formdata, $name, $langkey)
	{
		$formdata->templateVariables[$name] = $langkey;
	}

	/**
	FormFieldsHelp:
	Document contents of Fields array in @formdata, and append the contents of
	array @extras
	@formdata: reference to FormData-class object
	@$extras: optional array of items to be appended to the output, each member
		having key=id, value=name default = empty array
	Returns: xhtml string which generates a tabular help description
	*/
	public static function FormFieldsHelp(&$formdata, &$extras=[])
	{
		$rows = [];
		foreach ($formdata->Fields as &$one) {
			$oneset = new \stdClass();
			$oneset->id = $one->GetId();
			$oneset->name = $one->GetName();
			$rows[] = $oneset;
		}
		unset($one);
		if ($extras) {
			foreach ($extras as $id=>$name) {
				$oneset = new \stdClass();
				$oneset->id = $id;
				$oneset->name = $name;
				$rows[] = $oneset;
			}
		}

		$mod = $formdata->pwfmod;
		$tplvars = [
			'title_variables' => $mod->Lang('title_variables_available'),
			'title_name' => $mod->Lang('title_php_variable'),
			'title_field' => $mod->Lang('title_form_field'),
			'rows' => $rows
		];

		return self::ProcessTemplate($mod, 'varshelp.tpl', $tplvars);
	}

	/**
	SetupSubTemplateVarsHelp:
	Setup variables-help for a form's submission-template. Essentially, it sets
	smarty variable 'help_subtplvars' to the output from processing the template
	varshelp.tpl
	@formdata: reference to FormData-class object
	@mod: reference to current PowerBrowse module object
	@tplvars: reference to template-variables array
	*/
	public static function SetupSubTemplateVarsHelp(&$formdata, &$mod, &$tplvars)
	{
		$tplvars += [
		 'template_vars_title' => $mod->Lang('title_template_variables'),
		 'variable_title' => $mod->Lang('variable'),
		 'property_title' => $mod->Lang('property')
		];

		$globalvars = [];
		foreach ([
		 'form_name' => 'title_form_name',
		 'form_url' =>'help_form_url',
		 'form_host' => 'help_server_name',
		 'sub_date' => 'help_submission_date',
		 'sub_source' => 'help_sub_source',
		 'version' => 'help_module_version'] as $name=>$langkey) {
			$oneset = new \stdClass();
			$oneset->name = '{$'.$name.'}';
			$oneset->title = $mod->Lang($langkey);
			$globalvars[] = $oneset;
		}
		foreach ($formdata->templateVariables as $name=>$langkey) {
			$oneset = new \stdClass();
			$oneset->name = '{$'.$name.'}';
			$oneset->title = $mod->Lang($langkey);
			$globalvars[] = $oneset;
		}
		$tplvars['globalvars'] = $globalvars;

		if ($formdata->Fields) {
			$fieldvars = [];
			foreach ($formdata->Fields as &$one) {
				if ($one && $one->DisplayInSubmission()) {
					$oneset = new \stdClass();
					$oneset->name = $one->GetAlias(); //NOT ForceAlias()!
					if (!$oneset->name) {
						$oneset->name = $one->GetVariableName();
					}
					$oneset->id = $one->GetId();
					$oneset->title = $one->GetName();
					$oneset->escaped = str_replace("'", "\\'", $oneset->title);
					$fieldvars[] = $oneset;
				}
			}
			unset($one);
			$tplvars['fieldvars'] = $fieldvars;
		}

/*		$obflds = array();
		foreach (array ('name','type','id','value','valuearray') as $name) {
			$oneset = new \stdClass();
			$oneset->name = $name;
			$oneset->title = $mod->Lang('title_field_'.$name);
			$obflds[] = $oneset;
		}
		$tplvars['obfields'] = $obflds;

//		$oneset->title = $mod->Lang('title_field_id2');
		$tplvars['help_field_values'] = $mod->Lang('help_field_values'));
		$tplvars['help_object_example'] = $mod->Lang('help_object_example'));
*/
		$tplvars['help_other_fields'] = $mod->Lang('help_other_fields');

		$tplvars['help_subtplvars'] = self::ProcessTemplate($mod, 'varshelp.tpl', $tplvars);
	}

	/**
	SetupFormVars:
	Sets various smarty variables
	@formdata: reference to form data object
	@tplvars: reference to template-variables array
	@htmlemail: optional boolean, whether processing a form for html email, default FALSE
	*/
	public static function SetupFormVars(&$formdata, &$tplvars, $htmlemail=FALSE)
	{
		$mod = $formdata->pwfmod;
		// general variables
		$tplvars += [
			'form_name' => $formdata->Name,
			'form_url' => (empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']),
			'form_host' => $_SERVER['SERVER_NAME'],
			'sub_date' => date('r'),
			'sub_source' => $_SERVER['REMOTE_ADDR'],
			'version' => $mod->GetVersion()
		];

		$unspec = self::GetFormProperty($formdata, 'unspecified', $mod->Lang('unspecified'));

		foreach ($formdata->Fields as &$one) {
			$replVal = $unspec;
			$replVals = [];
			if ($one->DisplayInSubmission()) {
				$replVal = $one->DisplayableValue();
				if ($htmlemail) {
					// allow <BR> as delimiter or in content
					$replVal = preg_replace(
						['/<br(\s)*(\/)*>/i', '/[\n\r]+/'], ['|BR|', '|BR|'],
						$replVal);
					$replVal = htmlspecialchars($replVal);
					$replVal = str_replace('|BR|', '<br />', $replVal);
				}
				if ($replVal == '') {
					$replVal = $unspec;
				}
			}

			$name = $one->GetVariableName();
			$tplvars[$name] = $replVal;
			$alias = $one->ForceAlias();
			$tplvars[$alias] = $replVal;
			$id = $one->GetId();
			$tplvars['fld_'.$id] = $replVal;
		}
		unset($one);
	}

	/**
	ProcessTemplate:
	@mod: reference to current PWForms module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: string, processed template
	*/
	public static function ProcessTemplate(&$mod, $tplname, $tplvars, $cache=TRUE)
	{
		if ($mod->before20) {
			global $smarty;
		} else {
			$smarty = $mod->GetActionTemplateObject();
			if (!$smarty) {
				global $smarty;
			}
		}
		$smarty->assign($tplvars);
		if ($mod->oldtemplates) {
			$smarty->assign($tplvars);
			return $mod->ProcessTemplate($tplname);
		} else {
			if ($cache) {
				$cache_id = md5('pwf'.$tplname.serialize(array_keys($tplvars)));
				$lang = \CmsNlsOperations::get_current_language();
				$compile_id = md5('pwf'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname), $cache_id, $compile_id, $smarty);
				if (!$tpl->isCached()) {
					$tpl->assign($tplvars);
				}
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetFileResource($tplname), NULL, NULL, $smarty, $tplvars);
			}
			return $tpl->fetch();
		}
	}

	/**
	ProcessTemplateFromDatabase:
	@mod: reference to current PWForms module object
	@tplname: template identifier
	@tplvars: associative array of template variables
	@cache: optional boolean, default TRUE
	Returns: nothing
	*/
	public static function ProcessTemplateFromDatabase(&$mod, $tplname, $tplvars, $cache=TRUE)
	{
		if ($mod->before20) {
			global $smarty;
		} else {
			$smarty = $mod->GetActionTemplateObject();
			if (!$smarty) {
				global $smarty;
			}
		}
		$smarty->assign($tplvars);
		if ($mod->oldtemplates) {
			echo $mod->ProcessTemplateFromDatabase($tplname);
		} else {
			if ($cache) {
				$cache_id = md5('pwf'.$tplname.serialize(array_keys($tplvars)));
				$lang = \CmsNlsOperations::get_current_language();
				$compile_id = md5('pwf'.$tplname.$lang);
				$tpl = $smarty->CreateTemplate($mod->GetTemplateResource($tplname), $cache_id, $compile_id, $smarty);
				if (!$tpl->isCached()) {
					$tpl->assign($tplvars);
				}
			} else {
				$tpl = $smarty->CreateTemplate($mod->GetTemplateResource($tplname), NULL, NULL, $smarty, $tplvars);
			}
			$tpl->display();
		}
	}

	/**
	ProcessTemplateFromData:
	@mod: reference to current PWForms module object
	@data: string
	@tplvars: associative array of template variables
	No cacheing.
	Returns: string, processed template
	*/
	public static function ProcessTemplateFromData(&$mod, $data, $tplvars)
	{
		if ($mod->before20) {
			global $smarty;
		} else {
			$smarty = $mod->GetActionTemplateObject();
			if (!$smarty) {
				global $smarty;
			}
		}
		$smarty->assign($tplvars);
		if ($mod->oldtemplates) {
			return $mod->ProcessTemplateFromData($data);
		} else {
			$tpl = $smarty->CreateTemplate('eval:'.$data, NULL, NULL, $smarty, $tplvars);
			return $tpl->fetch();
		}
	}

	/**
	MergeJS:
	@jsincs: string or array of js 'include' directives
	@jsfuncs: string or array of js methods
	@jsloads: string or array of js onload-methods
	@$merged: reference to variable to be populated with the merged js string
	*/
	public static function MergeJS($jsincs, $jsfuncs, $jsloads, &$merged)
	{
		if (is_array($jsincs)) {
			$all = $jsincs;
		} elseif ($jsincs) {
			$all = [$jsincs];
		} else {
			$all = [];
		}
		if ($jsfuncs || $jsloads) {
			$all[] =<<<'EOS'
<script type="text/javascript">
//<![CDATA[
EOS;
			if (is_array($jsfuncs)) {
				$all = array_merge($all, $jsfuncs);
			} elseif ($jsfuncs) {
				$all[] = $jsfuncs;
			}
			if ($jsloads) {
				$all[] =<<<'EOS'
$(document).ready(function() {
EOS;
				if (is_array($jsloads)) {
					$all = array_merge($all, $jsloads);
				} else {
					$all[] = $jsloads;
				}
				$all[] =<<<EOS
});
EOS;
			}
			$all[] =<<<'EOS'
//]]>
</script>
EOS;
		}
		$merged = implode(PHP_EOL, $all);
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
		if ($val == '') {
			return '';
		}

		$val = html_entity_decode($val, ENT_COMPAT|ENT_XHTML);
		$val = str_replace(
		['&amp;', '&#60;&#33;--', '--&#62;', '&gt;', '&lt;', '&quot;', '&#39;', '&#036;', '&#33;'],
		['&', '<!--', '-->', '>', '<', '"', "'", '$', '!'	],
		$val);
		return $val;
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
	public static function CreateHierarchyPulldown(&$mod, $id, $name, $current)
	{
		$contentops = \cmsms()->GetContentOperations();
		$name = $id.$name;
		$sel = $contentops->CreateHierarchyDropdown('', $current, $name);
		if ($sel) {
			$srch = ['<select name="'.$name.'" id="'.$name.'">',
						'<option value="-1">none</option>'];
			$repl = [$srch[0].'<option value="0">'.$mod->Lang('select_one').'</option>',''];
			return str_replace($srch, $repl, $sel);
		}
		return '';
	}

	/**
	CleanTables:
	Removes from the ip_log table all records older than 30-minutes before @time
	Removes from the record table all records older than 24-hours before @time
	@time: timestamp, optional, default 0 (meaning current time)
	*/
	public static function CleanTables($time=0)
	{
		if (!$time) {
			$time = time();
		}
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$limit = $time-1800;
		$db->Execute('DELETE FROM '.$pre.'module_pwf_ip_log WHERE basetime<'.$limit);
		$limit = $time-86400; //ignore DST
		$db->Execute('DELETE FROM '.$pre.'module_pwf_session WHERE submitted<'.$limit);
	}

	/**
	GetUploadURL:
	@mod: reference to current module object
	@file: name, or relative path, of uploaded or to-be-uploaded file
	Returns: URL for @file in uploads dir
	*/
	public static function GetUploadURL(&$mod, $file)
	{
		$config = \cmsms()->GetConfig();
		$rooturl = (empty($_SERVER['HTTPS'])) ? $config['uploads_url']:$config['ssl_uploads_url'];
		$ud = $mod->GetPreference('uploads_dir');
		$lp = ($ud) ? '/'.str_replace('\\', '/', $ud) : '';
		$url = $rooturl.$lp.'/'.str_replace('\\', '/', $file);
		return $url;
	}

	/**
	GetUploadsPath:
	@mod: reference to current module object
	Returns: absolute filepath, or FALSE
	*/
	public static function GetUploadsPath(&$mod)
	{
		$config = \cmsms()->GetConfig();
		$fp = $config['uploads_path'];
		if ($fp && is_dir($fp)) {
			$ud = $mod->GetPreference('uploads_dir');
			if ($ud) {
				$ud = $fp.DIRECTORY_SEPARATOR.$ud;
				if (is_dir($ud)) {
					return $ud;
				}
			}
			return $fp;
		}
		return FALSE;
	}

	/**
	DeleteUploadFile:
	@mod: reference to current module object
	@file: filename
	@except: form enumerator default FALSE
	*/
	public static function DeleteUploadFile(&$mod, $file, $except=FALSE)
	{
		if ($except) {
			$db = \cmsms()->GetDb();
			$pre = \cms_db_prefix();
			$sql = 'SELECT props FROM '.$pre.'module_pwf_form WHERE form_id!=?';
			$props = $db->GetCol($sql, [$except]);
			if ($props) {
				foreach ($props as &$one) {
					$t = unserialize($one);
					if ($t && isset($t['css_file']) && $t['css_file'] == $file) {
						unset($one);
						return;
					}
				}
				unset($one);
			}
		}

		$fp = self::GetUploadsPath($mod);
		if ($fp) {
			$fp = $fp.DIRECTORY_SEPARATOR.$file;
			if (is_file($fp)) {
				@unlink($fp);
			}
		}
	}
}
