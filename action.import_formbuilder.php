<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

if (!$this->_CheckAccess('ModifyPFForms')) {
	exit;
}

if (!function_exists('Match_Browses')) {
	function Match_Browses(&$db, $pre)
	{
		$sql = 'SELECT * FROM '.$pre.'module_pwf_trans ORDER BY isform,trans_id';
		$data = $db->GetAssoc($sql);
		if ($data) {
/*
			UPDATE form_id IN module_pwbr_browser module_pwbr_record
			UPDATE form_field IN module_pwbr_field
*/
			$sql = 'UPDATE '.$pre.'module_pwbr_browser SET form_id=? WHERE form_id=?';
			$sql2 = 'UPDATE '.$pre.'module_pwbr_record SET form_id=? WHERE form_id=?';
			$sql3 = 'UPDATE '.$pre.'module_pwbr_field SET form_field=? WHERE form_field=?';
			foreach ($data as &$row) {
				if ($row['isform']) {
					$db->Execute($sql, [$row['new_id'], -$row['old_id']]);
					$db->Execute($sql2, [$row['new_id'], -$row['old_id']]);
				} else {
					$db->Execute($sql3, [$row['new_id'], -$row['old_id']]);
				}
			}
			unset($row);
		}
	}

//for CMSMS 2+
	function MySetTemplate($type, $id, $val)
	{
		static $editors = NULL;
		if ($editors === NULL) {
			$editors = [];
			global $db;
			$pre = cms_db_prefix();
			$sql = <<<EOS
SELECT G.group_id
FROM {$pre}groups G
JOIN {$pre}group_perms GP ON G.group_id = GP.group_id
JOIN {$pre}permissions P on GP.permission_id = P.permission_id
WHERE G.active=1 AND P.permission_name='ModifyPFSettings'
EOS;
			$all = $db->GetCol($sql);
			if ($all) {
				foreach ($all as $id) {
					$editors[] = -$id;
				}
			}
			$sql = <<<EOS
SELECT DISTINCT U.user_id
FROM {$pre}users U
JOIN {$pre}user_groups UG ON U.user_id = UG.user_id
JOIN {$pre}group_perms GP ON GP.group_id = UG.group_id
JOIN {$pre}permissions P ON P.permission_id = GP.permission_id
JOIN {$pre}groups GR ON GR.group_id = UG.group_id
WHERE U.admin_access=1 AND U.active=1 AND GR.active=1 AND
P.permission_name='ModifyPFSettings'
EOS;
			$all = $db->GetCol($sql);
			if ($all) {
				foreach ($all as $id) {
					$editors[] = $id;
				}
			}
		}
		$tpl = new CmsLayoutTemplate();
		$tpl->set_type($type);
		$pref = ($type == 'form') ? 'pwf_':'pwf_sub_';
		$tpl->set_name($pref.$id);
		$tpl->set_owner(1); //original admin user
		$tpl->set_additional_editors($editors); // !too bad if permissions change? or handle that event ?
		$tpl->set_content($val);
		$tpl->save();
 }

/* REPLACEMENTS ...
	$fld_X					$fld_Y
	{$fb_form_header}		gone
	{$fb_form_footer}		gone
	{$fb_form_start}		gone
	{$fb_form_end}			gone
	{$fb_hidden}			gone
	{$title_captcha}		gone
	{$graphic_captcha}		gone
	{$input_captcha}		gone
	$one->css_class			0
	$one->required			0
	$one->valid				0
	$has_captcha			0
	$captcha_error			0
	$fb_*					$*
	fbr_*					pwf_*
	fb_invalid				invalid_field
	fbht					help_toggle
	field_helptext			helptext
	FormBuilder				PWForms
	$in_formbrowser			$in_browser
	$fbr_id					$browser_id
	$sub_form_name			$form_name
	$sub_url				$form_url
	$sub_host				$form_host
	$sub_source_ip			$sub_source
	$fb_version				$version
	{$TAB}					"\t"
	class="error"			class="error_list"
	class="submit"			class="submit_actions"
	class="fbr_helptext"	class="help_display"

	$old_alias				$new_alias NEEDS manual update
	{if ... $has_captcha .... {/if} NEEDS manual removal
	e.g.
	{if !empty($captcha_error)}
		<div class="error_message">{$captcha_error}</div>
	{/if}
	or
	{if !empty($has_captcha)}
		<div class="captcha">{$graphic_captcha}{$title_captcha}<br />{$input_captcha}<br /></div>
	{/if}
*/
	function Update_Templates(&$mod, &$db, $pre, $oldfid, $newfid)
	{
		$finds = [
		'FormBuilder',
		'$one->css_class',
		'$one->required',
		'$one->valid',
		'$has_captcha',
		'$captcha_error',
		'{$fb_form_header}',
		'{$fb_form_footer}',
		'{$fb_form_start}',
		'{$fb_form_end}',
		'{$fb_hidden}',
		'{$title_captcha}',
		'{$graphic_captcha}',
		'{$input_captcha}',
		'{$TAB}',
		'$fb_version',
		'fb_invalid',
		'$fb_',
		'$fbr_id',
		'fbr_',
		'fbht',
		'field_helptext',
		'$in_formbrowser',
		'$sub_form_name',
		'$sub_url',
		'$sub_host',
		'$sub_source_ip',
		'class="error"',
		'class="fbr_helptext"',
		'class="submit"'
		];
		$repls = [
		'PWForms',
		'0',
		'0',
		'0',
		'0',
		'0',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		"\t",
		'$version',
		'invalid_field',
		'$',
		'$browser_id',
		'pwf_',
		'help_toggle',
		'helptext',
		'$in_browser',
		'$form_name',
		'$form_url',
		'$form_host',
		'$sub_source',
		'class="error_list"',
		'class="help_display"',
		'class="submit_actions"'
		];

		$sql = 'SELECT * FROM '.$pre.'module_pwf_trans WHERE NOT isform ORDER BY old_id';
		$data = $db->GetAssoc($sql);
		if ($data) {
			foreach ($data as &$row) {
				$finds[] = '$fld_'.$row['old_id'];
				$repls[] = '$fld_'.$row['new_id'];
			}
			unset($row);
		}

		if ($mod->oldtemplates) {
			$tpl = $mod->GetTemplate('pwf_'.$newfid);
		} else {
			//CHECKME try/catch?
			$ob = CmsLayoutTemplate::load('pwf_'.$newfid);
			$tpl = $ob->get_content();
		}

		if ($tpl) {
			$tpl = str_replace($finds, $repls, $tpl);
			if ($mod->oldtemplates) {
				$mod->SetTemplate('pwf_'.$newfid, $tpl);
			} else {
				$ob->set_content($tpl);
				$ob->save();
			}
		}

		if ($mod->oldtemplates) {
			$tpl = $mod->GetTemplate('pwf_sub_'.$newfid);
		} else {
			$ob = CmsLayoutTemplate::load('pwf_sub_'.$newfid);
			$tpl = $ob->get_content();
		}
		if ($tpl) {
			$tpl = str_replace($finds, $repls, $tpl);
			if ($mod->oldtemplates) {
				$mod->SetTemplate('pwf_sub_'.$newfid, $tpl);
			} else {
				$ob->set_content($tpl);
				$ob->save();
			}
		}
	}

	function Get_FieldOpts(&$db, $pre, $oldfid, $newfid, $oldf, $newf, $type, &$includes, &$passdowns, &$passbacks)
	{
		$props = [];
		$data = [];
		if ($passdowns) {
			foreach ($passdowns as $name=>$value) {
				$data[] = ['name'=>$name, 'value'=>$value];
			}
		}

		$sql = 'SELECT name,value FROM '.$pre.'module_fb_field_opt WHERE form_id=? AND field_id=? ORDER BY option_id';
		$data = array_merge($data, $db->GetArray($sql, [$oldfid, $oldf]));

		if ($data) {
			/* Some fb-field-types simply repeat the same option-name
			(relying on save-order for any reconciliation!)
			We are more careful!
			NB need each field-type that involves GetPropArray(), MultiComponent
			and/or MultiChoice properties not sufficient (e.g. CustomEmail!)
			*/
			$multi = in_array($type, [
			'CheckboxGroup',
			'CustomEmail',
			'EmailDirector',
			'FileDirector',
			'Multiselect',
			'MultiselectFileDirector',
			'PageRedirector',
			'Pulldown',
			'RadioGroup',
			//'SubmitForm' stores actual suffixes, for field_id's ?
			'SystemEmail',
			'TextExpandable',
			]);
			if ($multi) {
				$multers = [];
				foreach ($includes as $key => $one) {
					$len = strlen($key);
					if ($key[$len-1] == '*') { //'*' suffix
						$key = substr($key, 0, $len-1);
						$multers[$key] = 1;
					}
				}
			}

			$renames = [
			'hide_label'=>'HideLabel',
			'required'=>'Required',
			'smarty_eval'=>'SmartyEval',
			'validation_type'=>'ValidationType',
			'select_one'=>'select_label',
			'field_logic'=>'resources',
			'file_header'=>'header_template',
			'file_footer'=>'footer_template',
			];

			$finds = NULL; //populate if/when needed
			$repls = [];

			foreach ($data as $fbrow) {
				extract($fbrow);
				$value = $value; //for DEBUGGER display ?

				$check = TRUE;
				//rename some properties
				if ($multi) {
					if (strncmp($name, 'option_', 7) == 0) {
						$name = 'indexed_'.substr($name, 7);
					}
 					if (isset($multers[$name])) {
						$i = $includes[$name.'*'];
						$name .= $multers[$name]++;
						$includes[$name] = $i;
						$check = FALSE;
					}
				}
				if (array_key_exists($name, $renames)) {
					$name = $renames[$name];
				}

				if ($check && !array_key_exists($name, $includes)) {
					if ($name == 'field_alias') {
						if ($value) {
							$passbacks['alias'] = $value;
						}
					}
					//CHECKME other passbacks ?
					continue;
				}

				//revalue some properties
				switch ($includes[$name]) {
				 case 0:
				 case 10:
					$value = ($value) ? 1:0;
					break;
				 case 1:
				 case 11:
					$value += 0;
					break;
				 case 3:
				 case 13:
					if ($finds === NULL) {
						$sql = 'SELECT * FROM '.$pre.'module_pwf_trans WHERE NOT isform ORDER BY old_id';
						$trans = $db->GetAssoc($sql);
						if ($trans) {
							foreach ($trans as &$row) {
								$finds[] = '$fld_'.$row['old_id'];
								$repls[] = '$fld_'.$row['new_id'];
							}
							unset($row);
						} else {
							$finds = [];
						}
					}
					$value = str_replace($finds, $repls, $value);
					break;
				 case 4:
				 case 14:
					if ($value === NULL || is_scalar($value)) {
						if (is_numeric($value)) {
							$value += 0;
						}
					} else {
						$value = serialize($value);
					}
					break;
				}
				$props[$name] = $value;
			}
			//supplementary property
			if ($type == 'Text') {
				$props['size'] = min($props['length'], 50);
			}
			ksort($props);
		}

		$value = serialize($props);
		$sql = 'UPDATE '.$pre.'module_pwf_field SET props=? WHERE field_id=?';
		$db->Execute ($sql, [$value, $newf]);
	}

	function Get_Fields(&$db, $pre, $oldfid, $newfid, &$formdata)
	{
		$sql = 'SELECT * FROM '.$pre.'module_fb_field WHERE form_id=? ORDER BY field_id,order_by';
		$data = $db->GetArray($sql, [$oldfid]);
		if ($data) {
			$fbfields = array_keys($data[0]);
			$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_field');
			if (!$pfrow) {
				$db->Execute('INSERT INTO '.$pre.'module_pwf_field (field_id) VALUES (-1)');
				$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_field');
				$db->Execute('DELETE FROM '.$pre.'module_pwf_field WHERE field_id=-1');
			}
			unset($pfrow['field_id']);  //ignore auto-inc field, keeping props is ok
			$pwfields = array_keys($pfrow);
			$pfrow = array_fill_keys($pwfields, NULL); //default values

			$bp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'class.';

			$sql2 = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,0)';
			//these are used after type has been cleaned up and some duplicates done
			$renames = [
			'DeliverToEmailAddress'=>'EmailOne',
			'Director'=>'EmailDirector',
			'Email'=>'SystemEmail',
			'EmailBasedFrontends'=>'CustomEmail',
			'EmailFromFEUProperty'=>'EmailFEUProperty',
			'File'=>'SharedFile',
			'Form'=>'SubmitForm',
			'FromEmailAddress'=>'EmailAddress',
			'FromEmailAddressAgain'=>'EmailAddressAgain',
			'FromEmailName'=>'EmailSender',
			'FromEmailSubject'=>'EmailSubject',
			'ModuleInterface'=>'ByTemplate',
			'setEnd'=>'FieldsetEnd',
			'setStart'=>'FieldsetStart',
			'SiteAdmin'=>'EmailSiteAdmin',
			'UserTag'=>'SubmissionTag',
			];

			foreach ($data as $fbrow) {
				extract($pfrow); //default values
				extract($fbrow);
				$form_id = $newfid;
				$oldf = (int)$field_id;
				$oldtype = $type;
				$type = str_replace(
				['Field', 'DispositionFromEmailAddress', 'DispositionUserTag', 'Disposition'],
				['', 'UserEmail', 'SubmissionTag', ''], $type);
				if (array_key_exists($type, $renames)) {
					$type = $renames[$type];
				}
				//GetMutables() needs data from a loaded field
				//check existence, to prevent un-recoverable fatal error upon attempted open
				$fp = $bp.$type.'.php';
				if (file_exists($fp)) {
					include_once $fp;
					$classPath = 'PWForms\\'.$type;
				} else {
					$classPath = 'PWForms\\FieldBase'; //revert to default
				}
				$t = [];
				$obfld = new $classPath($formdata, $t);
				PWForms\FieldOperations::Load($obfld);
				$includes = $obfld->GetMutables(FALSE, FALSE); //get 'base' identifiers too

				unset($includes['field_id']);
				unset($includes['Id']);
				$includes['form_id'] = 1;

				$done = [];
				$args = [];
				foreach ($pwfields as $one) {
					if (array_key_exists($one, $includes) && $includes[$one] < 10) {
						$done[] = $one;
						switch ($includes[$one]) {
						 case 0:
							$args[] = ($$one) ? 1:0;
							break;
						 case 1:
							$args[] = $$one + 0;
							break;
//						 case 3: nothing special for templates
//							break;
						 case 4:
							if ($$one === NULL || is_scalar($$one)) {
								if (is_numeric($$one)) {
									$$one += 0;
								}
								$args[] = $$one;
							} else {
								$args[] = serialize($$one);
							}
							break;
						 default:
							$args[] = $$one;
						}
						unset($includes[$one]);
					}
				}

				$namers = implode(',', $done);
				$fillers = str_repeat('?,', count($done)-1);
				$sql = 'INSERT INTO '.$pre.'module_pwf_field ('.$namers.') VALUES ('.$fillers.'?)';
				$db->Execute($sql, $args);
				$newf = $db->Insert_ID();
				$db->Execute($sql2, [$oldf, $newf]);

				$more = [];
				$xopts = array_diff($fbfields, $done);
				if ($xopts) {
					foreach ($xopts as $one) {
						$more[$one] = $$one;
					}
				}
				unset($more['field_id']); //ignore auto-inc field

				$back = []; //for missing parameters (maybe) in options
				Get_FieldOpts($db, $pre, $oldfid, $newfid, $oldf, $newf, $type, $includes, $more, $back);
				if ($back) {
					foreach ($back as $name => $value) {
						$sql3 = 'UPDATE '.$pre.'module_pwf_field SET '.$name.'=? WHERE field_id='.$newf;
						$db->Execute($sql3,[$value]);
					}
				}
			}
		}
	}

	function Get_FormOpts(&$mod, &$db, $pre, $oldfid, $newfid, &$formincludes, &$passdowns, &$passbacks)
	{
		$data = [];
		if ($passdowns) {
			foreach ($passdowns as $name=>$value) {
				if (!$name) {
					$name = '<'.$this->Lang('none2').'>';
				}
				$data[$name] = $value;
			}
		}
		$sql = 'SELECT * FROM '.$pre.'module_fb_form_attr WHERE form_id=? ORDER BY name';
		$data = array_merge ($data, $db->GetArray($sql, [$oldfid]));

		$props = [];
		if ($data) {
			foreach ($data as $row) {
				$name = $row['name'];

				if (!array_key_exists($name, $formincludes)) {
	//TODO process passback if relevant
					continue;
				}

				$value = $row['value'];
				//revalue some properties
				switch ($name) {
				 case 'predisplay_each_udt':
				 case 'predisplay_udt':
				 case 'validate_udt':
					if ($value == -1) {
						$value = '';
					}
				}
				switch ($formincludes[$name]) {
				 case 0:
				 case 10:
					$value = ($value) ? 1:0;
					break;
				 case 1:
				 case 11:
					$value += 0;
					break;
				 case 3:
				 case 13:
//TODO CHECK template arrangements used by newer FormBuilder
					if ($name == 'form_template') {
						if ($mod->oldtemplates) {
							$mod->SetTemplate('pwf_'.$newfid, $value);
						} else {
							MySetTemplate('form', $newfid, $value);
						}
						$value = 'pwf_'.$newfid;
					} elseif ($name == 'submission_template') {
						if ($mod->oldtemplates) {
							$mod->SetTemplate('pwf_sub_'.$newfid, $value);
						} else {
							MySetTemplate('submission', $newfid, $value);
						}
						$value = 'pwf_sub_'.$newfid;
					}
					break;
				 case 4:
				 case 14:
					if ($value === NULL || is_scalar($value)) {
						if (is_numeric($value)) {
							$value += 0;
						}
					} else {
						$value = serialize($value);
					}
					break;
				 default:
				//TODO populate $passbacks as needed
					break;
				}
				$props[$name] = $value;
			}
			ksort($props);
		}

		$value = serialize($props);
		$sql = 'UPDATE '.$pre.'module_pwf_form SET props=? WHERE form_id=?';
		$db->Execute ($sql, [$value, $newfid]);
	}
} // !function_exists

if (isset($params['import'])) {
	$pre = cms_db_prefix();
	$db->Execute('DELETE FROM '.$pre.'module_pwf_trans');
	$sql = 'SELECT * FROM '.$pre.'module_fb_form ORDER BY form_id';
	$oldforms = $db->GetArray($sql);
	if ($oldforms) {
		if (!$this->oldtemplates) {
			$types = CmsLayoutTemplateType::load_all_by_originator('FormBuilder');
			if ($types) {
				foreach ($types as $type) {
					$templates = $type->get_template_list();
					if ($templates) {
						$this->Crash();
/* as of 0.8.x at least, FormBuilder doesn't do new-style templates
						foreach ($templates as $tpl) {
							switch ($type) {
					default:
								$txt = $tpl->get_content();
								//TODO set type,id, migrate contents
								MySetTemplate($mytype,$myid,$txt);
							}
						}
*/
					}
				}
			}
		}

		$fbfields = array_keys($oldforms[0]);
		$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_form');
		if (!$pfrow) {
			$db->Execute('INSERT INTO '.$pre.'module_pwf_form (form_id) VALUES (-1)');
			$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_form');
			$db->Execute('DELETE FROM '.$pre.'module_pwf_form WHERE form_id=-1');
		}
		unset($pfrow['form_id']); //ignore auto-inc field
		$pwfields = array_keys($pfrow);
		$pfrow = array_fill_keys($pwfields, NULL); //default values

		$namers = implode(',', $pwfields);
		$fillers = str_repeat('?,', count($pwfields)-1);
		$sql = 'INSERT INTO '.$pre.'module_pwf_form ('.$namers.') VALUES ('.$fillers.'?)';
		$sql2 = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,1)';

		$formdata = new PWForms\FormData($this);
		$formincludes = $formdata->GetMutables(FALSE);
		$funcs = new PWForms\FormOperations();
//		$renums = []; //keys = FormBuilder id, values = PWForms id

		foreach ($oldforms as $fbrow) {
			extract($pfrow); //default values
			extract($fbrow);
			if (!$name) {
				$name = $this->Lang('none2');
			}
			if (!$alias) {
				$alias = $name;
			}
			$alias = PWForms\Utils::MakeAlias($alias, 18); //maybe shorten
			$ta = $alias;
			$indx = 1;
			while (!$funcs->NewID(FALSE, $ta)) {
				$ta = $alias."[$indx]";
				++$indx;
			}
			$alias = $ta;

			$done = ['form_id'];
			$args = [];
			foreach ($pwfields as $one) {
				$done[] = $one;
				if ($one != 'form_id') {
					$args[] = $$one;
				}
			}
			$db->Execute($sql, $args);
			$newfid = $db->Insert_ID();
//			$renums[$form_id] = $newfid;
			$db->Execute($sql2, [$form_id, $newfid]);

			$more = [];
			$xopts = array_diff($fbfields, $done);
			if ($xopts) {
				foreach ($xopts as $one) {
					$more[$one] = $$one;
				}
			}
			$back = []; //for missing parameters (maybe) in options
			Get_FormOpts($this, $db, $pre, $form_id, $newfid, $formincludes, $more, $back);
			if ($back) {
				foreach ($back as $name => $value) {
					$sql3 = 'UPDATE '.$pre.'module_pwf_form SET '.$name.'=? WHERE form_id='.$newfid;
					$db->Execute($sql3, [$value]);
				}
			}
			Get_Fields($db, $pre, $form_id, $newfid, $formdata);
			Update_Templates($this, $db, $pre, $form_id, $newfid);
			//data may've already been imported by the browser module
			$rs = $db->SelectLimit('SELECT * FROM '.$pre.'module_pwbr_browser', 1);
			if ($rs) {
				if (!$rs->EOF) {
					Match_Browses($db, $pre);
				}
				$rs->Close();
			}
		}
		$message = $this->_PrettyMessage('adjust_templates', 'warn');
	} else {
		$message = $this->_PrettyMessage('no_forms', FALSE);
	}
} elseif (isset($params['conform'])) {
	//relevant checks are done upstream (method.defaultadmin.php)
	$pre = cms_db_prefix();
	Match_Browses($db, $pre);
	$message = $this->_PrettyMessage('browsers_updated');
} else {
	$message = $this->_PrettyMessage('error', FALSE);
}

$this->Redirect($id, 'defaultadmin', '', [
	'message'=>$message, 'active_tab'=>'import']);
