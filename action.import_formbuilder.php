<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

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
				$finds[] = '\$fld_'.$row['old_id'];
				$repls[] = '\$fld_'.$row['new_id'];
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

		$sql = 'SELECT prop_id,value,longvalue FROM '.$pre.'module_pwf_formprops WHERE form_id=? AND name=\'submission_template\'';
		$row = $db->GetRow($sql, [$newfid]);
		if ($row) {
			$sval = $row['value'];
			$lval = $row['longvalue'];
			if ($sval) {
				$tpl = str_replace($finds, $repls, $sval);
			} elseif ($lval) {
				$tpl = str_replace($finds, $repls, $lval);
			}
			if ($sval || $lval) {
				$sql = 'UPDATE '.$pre.'module_pwf_formprops SET value=?,longvalue=? WHERE prop_id=?';
				$args = (strlen($tpl) <= PWForms::LENSHORTVAL) ?
						[$tpl,NULL,$row['prop_id']]:
						[NULL,$tpl,$row['prop_id']];
				$db->Execute($sql, $args);
			}
		}

		$sql = 'SELECT prop_id,value,longvalue FROM '.$pre.'module_pwf_fieldprops WHERE form_id=? AND name LIKE\'%template%\'';
		$rows = $db->GetArray($sql, [$newfid]);
		if ($rows) {
			$sql = 'UPDATE '.$pre.'module_pwf_fieldprops SET value=?,longvalue=? WHERE prop_id=?';
			foreach ($rows as &$row) {
				$sval = $row['value'];
				$lval = $row['longvalue'];
				if ($sval) {
					$tpl = str_replace($finds, $repls, $sval);
				} elseif ($lval) {
					$tpl = str_replace($finds, $repls, $lval);
				}
				if ($sval || $lval) {
					$args = (strlen($tpl) <= PWForms::LENSHORTVAL) ?
							[$tpl,NULL,$row['prop_id']]:
							[NULL,$tpl,$row['prop_id']];
					$db->Execute($sql, $args);
				}
			}
			unset($row);
		}
	}

	function Get_FieldOpts(&$db, $pre, $oldfid, $newfid, $oldf, $newf, $oldtype, &$passdowns, &$passbacks)
	{
		$sql = 'SELECT * FROM '.$pre.'module_fb_field_opt WHERE form_id=? AND field_id=? ORDER BY option_id';
		$data = $db->GetArray($sql, [$oldfid, $oldf]);
		if ($data) {
			$fbfields = array_keys($data[0]);
			//exclude some properties
			foreach ([
			'crypt',
			'modifiesOtherFields',
			'searchable',
			'sortable',
			'sortfield1',
			'sortfield2',
			'sortfield3',
			'sortfield4',
			'sortfield5',
			'HasDeleteOp',
			'HasUserAddOp',
			'HasUserDeleteOp',
			] as $one) {
				$p = array_search($one, $fbfields);
				if ($p !== FALSE) {
					unset($fbfields[$p]);
				}
				$p = array_search($one, $passdowns);
				if ($p !== FALSE) {
					unset($passdowns[$p]);
				}
			}
/*			$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_fieldprops');
			if (!$pfrow) {
				$db->Execute('INSERT INTO '.$pre.'module_pwf_fieldprops (prop_id) VALUES (-1)');
				$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_fieldprops');
				$db->Execute('DELETE FROM '.$pre.'module_pwf_fieldprops WHERE prop_id=-1');
			}
			$pwfields = array_keys($pfrow);
			unset($pwfields['prop_id']); //exclude auto-increment field

			$namers = implode(',', $pwfields);
			$fillers = str_repeat('?,', count($pwfields)-1);
			$sql = 'INSERT INTO '.$pre.'module_pwf_fieldprops ('.$namers.') VALUES ('.$fillers.'?)';
*/
			$pwfields = ['field_id','form_id','name','value','longvalue'];
			$pfrow = array_fill_keys($pwfields, NULL); //default values
			$sql = 'INSERT INTO '.$pre.'module_pwf_fieldprops (field_id,form_id,name,value,longvalue) VALUES (?,?,?,?,?)';
			//TODO support insert into $pre.'module_pwf_field' if relevant

			//some field-types simply repeat the same option-name (relying on save-order for any reconciliation!)
			//we are more careful!
			$sequence = in_array($oldtype, [
			'CheckboxGroupField',
			'DispositionDirector',
			'DispositionEmail',
			'DispositionEmailBasedFrontendFields',
			'DispositionFileDirector',
			'DispositionMultiselectFileDirector',
			'DispositionPageRedirector',
			'MultiselectField',
			'PulldownField',
			'RadioGroupField',
			]);
			if ($sequence) {
				$desc = '';
				$uses = array_count_values(array_column($data, 'name'));
			}
/* TODO
			$obfld = new PWForms\XXX();
			$populators = $obfld->AdminPopulate('FAKE');
			$hasmain = (isset($populators['main']) && count($populators['main']) > 0);
			$hasadv = (isset($populators['adv']) && count($populators['adv']) > 0);
			$hastbl = (isset($populators['table']) && count($populators['table']) > 0);
			//TODO get object names from xml, omit others in $data
*/
			foreach ($data as $fbrow) {
				extract($pfrow); //NULL default values
				extract($fbrow);
				if (!$name) {
					$name = $this->Lang('none2');
				}
				//existing option-value prevails
				if (isset($passdowns[$name])) {
					unset($passdowns[$name]);
				}
				if ($sequence) {
					if ($name != $desc) {
						$desc = $name;
						$indx = 1;
					}
					//not all field-properties are sequences (and some that are will be single-valued)
					if ($uses[$name] > 1) {
						$name .= $indx;
						$indx++;
					}
				}
				//rename some properties e.g. 'option_'* to 'indexed_'*
				if (strncmp($name, 'option_', 7) == 0) {
					$name = 'indexed_'.substr($name, 7);
				}
				$value = $value;
				$longvalue = $longvalue;
				if (strlen($value) > PWForms::LENSHORTVAL) {
					$longvalue = $value;
					$value = NULL;
				}
				$field_id = $newf;
				$form_id = $newfid;

				$args = [];
				foreach ($pwfields as $one) {
					$args[] = $$one;
				}
				$db->Execute($sql, $args);
//				$pid = $db->Insert_ID();
			}
			//TODO update $passbacks
			foreach ($passdowns as $nm=>$val) {
//				if ($val) {
				extract($pfrow); //NULL default values
				$name = ($nm) ? $nm:$this->Lang('none2');
				if ($name == 'alias') {
					$val = PWForms\Utils::MakeAlias($val, 24); //length conform to FieldBase::GetVariableName()
				}
				if (strlen($val) > PWForms::LENSHORTVAL) {
					$longvalue = $val;
				} else {
					$value = $val;
				}
				$field_id = $newf;
				$form_id = $newfid;
				$args = [];
				foreach ($pwfields as $one) {
					if ($one != 'prop_id') {
						$args[] = $$one;
					}
				}
				$db->Execute($sql, $args);
//				$pid = $db->Insert_ID();
//				}
			}
		}
	}

	function Get_Fields(&$db, $pre, $oldfid, $newfid)
	{
		$sql = 'SELECT * FROM '.$pre.'module_fb_field WHERE form_id=? ORDER BY order_by,field_id';
		$data = $db->GetArray($sql, [$oldfid]);
		if ($data) {
			$fbfields = array_keys($data[0]);
			$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_field');
			if (!$pfrow) {
				$db->Execute('INSERT INTO '.$pre.'module_pwf_field (field_id) VALUES (-1)');
				$pfrow = $db->GetRow('SELECT * FROM '.$pre.'module_pwf_field');
				$db->Execute('DELETE FROM '.$pre.'module_pwf_field WHERE field_id=-1');
			}
			$pwfields = array_keys($pfrow);
			$pfrow = array_fill_keys($pwfields, NULL); //default values

			$namers = implode(',', $pwfields);
			$fillers = str_repeat('?,', count($pwfields)-1);
			$sql = 'INSERT INTO '.$pre.'module_pwf_field ('.$namers.') VALUES ('.$fillers.'?)';
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
				$done = [];
				$args = [];
				foreach ($pwfields as $one) {
					$done[] = $one;
					if ($one != 'field_id') {
						$args[] = $$one;
					}
				}
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
				$back = []; //TODO missing keys to get from options
			Get_FieldOpts($db, $pre, $oldfid, $newfid, $oldf, $newf, $oldtype, $more, $back);
			//TODO handle passbacks
			}
		}
	}

	function Get_FormOpts(&$mod, &$db, $pre, $oldfid, $newfid, &$passdowns, &$passbacks)
	{
		$sql = 'SELECT * FROM '.$pre.'module_fb_form_attr WHERE form_id=? ORDER BY form_attr_id';
		$data = $db->GetArray($sql, [$oldfid]);
		if ($data) {
			//TODO support INSERT INTO $pre.'module_pwf_form' if relevant
			$sql = 'INSERT INTO '.$pre.'module_pwf_formprops (form_id,name,value,longvalue) VALUES (?,?,?,?)';
			foreach ($data as $row) {
				if (strpos($row['name'], 'captcha') !== FALSE) { //ignore redundant options
					continue;
				}
				if (strpos($row['name'], 'udt') !== FALSE && (!$row['value'] || $row['value'] == -1)) {
					continue;
				}
				$val = $row['value'];
				$longval = NULL;
				//CHECKME template arrangements used by newer FormBuilder
				switch ($row['name']) {
				 case 'form_template':
					if ($mod->oldtemplates) {
						$mod->SetTemplate('pwf_'.$newfid, $val);
					} else {
						MySetTemplate('form', $newfid, $val);
					}
					$name = $row['name'];
					$val = 'pwf_'.$newfid;
					break;
				 case 'submission_template':
					if ($mod->oldtemplates) {
						$mod->SetTemplate('pwf_sub_'.$newfid, $val);
					} else {
						MySetTemplate('submission', $newfid, $val);
					}
					$name = $row['name'];
					$val = 'pwf_sub_'.$newfid;
					break;
				 default:
					$name = $row['name'];
					if (strlen($val) > PWForms::LENSHORTVAL) {
						$longval = $val;
						$val = NULL;
					}
					break;
				}
				$db->Execute($sql, [$newfid, $name, $val, $longval]);
//				$ares = $db->Affected_Rows();
//				$newid = $db->Insert_ID();
			}
		}
		//TODO handle $passbacks
		if ($passdowns) {
			$this->Crash(); //TODO
		}
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
		$pwfields = array_keys($pfrow);
		$pfrow = array_fill_keys($pwfields, NULL); //default values

		$namers = implode(',', $pwfields);
		$fillers = str_repeat('?,', count($pwfields)-1);
		$sql = 'INSERT INTO '.$pre.'module_pwf_form ('.$namers.') VALUES ('.$fillers.'?)';
		$sql2 = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,1)';

		$funcs = new PWForms\FormOperations();
//		$renums = array(); //keys = FormBuilder id, values = PowerForms id
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
				$indx++;
			}
			$alias = $ta;

			$done = [];
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
			$back = []; //TODO missing keys to get from options
			Get_FormOpts($this, $db, $pre, $form_id, $newfid, $more, $back);
			//TODO handle passbacks
			Get_Fields($db, $pre, $form_id, $newfid);
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
