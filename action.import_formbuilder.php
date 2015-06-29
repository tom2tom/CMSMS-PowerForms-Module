<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

function Match_Browses(&$db,$pre)
{
	$sql = 'SELECT * FROM '.$pre.'module_pwf_trans ORDER BY isform,trans_id';
	$data = $db->GetAssoc($sql);
	if($data)
	{
		/*
		UPDATE form_id IN module_pwbr_browser module_pwbr_record
		UPDATE form_field IN module_pwbr_field
		*/
		$sql = 'UPDATE '.$pre.'module_pwbr_browser SET form_id=? WHERE form_id=?';
		$sql2 = 'UPDATE '.$pre.'module_pwbr_record SET form_id=? WHERE form_id=?';
		$sql3 = 'UPDATE '.$pre.'module_pwbr_field SET form_field=? WHERE form_field=?';
		foreach($data as &$row)
		{
			if($row['isform'])
			{
				$db->Execute($sql,array($row['new_id'],-$row['old_id']));
				$db->Execute($sql2,array($row['new_id'],-$row['old_id']));
			}
			else
				$db->Execute($sql3,array($row['new_id'],-$row['old_id']));
		}
		unset($row);
	}
}

/* REPLACEMENTS ...
	$fld_X					$fld_Y
	{$fb_form_header}		gone
	{$fb_form_footer}		gone
	{$fb_form_start}		gone
	{$fb_form_end}			gone
	{$fb_hidden}			gone
	$one->css_class			0
	$one->required			0
	$one->valid				0
	$has_captcha			0
	$captcha_error			0
	$fb_*					$*
	fbr_*					pwf_*
	fb_invalid				invalid_field
	FormBuilder				PowerForms
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
function Update_Templates(&$mod,&$db,$pre,$oldfid,$newfid)
{
	$finds = array(
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
		'{$TAB}',
		'$fb_version',
		'fb_invalid',
		'$fb_',
		'$fbr_id',
		'fbr_',
		'$in_formbrowser',
		'$sub_form_name',
		'$sub_url',
		'$sub_host',
		'$sub_source_ip',
		'class="error"',
		'class="fbr_helptext",
		'class="submit"'
	);
	$repls = array(
		'PowerForms',
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
		"\t",
		'$version',
		'invalid_field',
		'$',
		'$browser_id',
		'pwf_',
		'$in_browser',
		'$form_name',
		'$form_url',
		'$form_host',
		'$sub_source',
		'class="error_list"',
		'class="help_display"'
		'class="submit_actions"'
	);

	$sql = 'SELECT * FROM '.$pre.'module_pwf_trans WHERE NOT isform ORDER BY old_id';
	$data = $db->GetAssoc($sql);
	if($data)
	{
		foreach($data as &$row)
		{
			$finds[] = '\$fld_'.$row['old_id'];
			$repls[] = '\$fld_'.$row['new_id'];
		}
		unset($row);
	}

	$tpl = $mod->GetTemplate('pwf_'.$newfid);
	if($tpl)
	{
		$tpl = str_replace($finds,$repls,$tpl);
		$mod->SetTemplate('pwf_'.$newfid,$tpl);
	}
	$tpl = $mod->GetTemplate('pwf_sub_'.$newfid);
	if($tpl)
	{
		$tpl = str_replace($finds,$repls,$tpl);
		$mod->SetTemplate('pwf_sub_'.$newfid,$tpl);
	}

	$sql = 'SELECT option_id,value FROM '.$pre.'module_pwf_form_opt WHERE form_id=? AND name = \'submission_template\'';
	$row = $db->GetOne($sql,array($newfid));
	if($row)
	{
		$sql = 'UPDATE '.$pre.'module_pwf_form_opt SET value=? WHERE option_id=?';
		if($row['value'])
		{
			$tpl = str_replace($finds,$repls,$row['value']);
			$db->Execute($sql,array($tpl,$row['option_id']));
		}
	}

	$sql = 'SELECT option_id,value FROM '.$pre.'module_pwf_field_opt WHERE form_id=? AND name LIKE \'%template%\'';
	$rows = $db->GetArray($sql,array($newfid));
	if($rows)
	{
		$sql = 'UPDATE '.$pre.'module_pwf_field_opt SET value=? WHERE option_id=?';
		foreach($rows as &$row)
		{
			if($row['value'])
			{
				$tpl = str_replace($finds,$repls,$row['value']);
				$db->Execute($sql,array($tpl,$row['option_id']));
			}
		}
		unset($row);
	}
}

function Get_FieldOpts(&$db,$pre,$oldfid,$newfid,$oldf,$newf,&$fieldrow)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_field_opt WHERE form_id=? AND field_id=? ORDER BY option_id';
	$data = $db->GetArray($sql,array($oldfid,$oldf));
	if($data)
	{
		$extras = array();
		$extras['alias'] = pwfUtils::MakeAlias($fieldrow['name'],24); //length conform to pwfFieldBase::GetVariableName()
		if($fieldrow['hide_label']) $extras['hide_label'] = 1;
		if($fieldrow['required']) $extras['required'] = 1;
		if($fieldrow['validation_type']) $extras['validation_type'] = trim($fieldrow['validation_type']);
		//some field-types simply repeat the same option-name (relying on save-order for any reconciliation!)
		//we are more careful!
		$sequence = in_array($fieldrow['type'],array(
		 'CheckboxGroupField',
		 'DispositionDirector',
		 'DispositionEmail',
		 'DispositionEmailBasedFrontendFields',
		 'DispositionFileDirector',
		 'DispositionMultiselectFileDirector',
		 'DispositionPageRedirector'
		 'MultiselectField',
		 'PulldownField',
		 'RadioGroupField',
		));
		if($sequence)
			$desc = '';

		$sql = 'INSERT INTO '.$pre.'module_pwf_field_opt
(option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
		foreach($data as $row)
		{
			$oid = $db->GenID($pre.'module_pwf_field_opt_seq');
			$nm = $row['name'];
			if($sequence)
			{
				if($nm != $desc)
				{
					$desc = $nm;
					$indx = 1;
				}
				else
					$indx++;
				$nm .= $indx;
			}
			$db->Execute($sql,array($oid,$newf,$newfid,$nm,$row['value']));
			//existing option-value prevails over actions-table 'transfer'
			if(isset($extras[$row['name']]))
				$extras[$row['name']] = FALSE;
		}
		foreach($extras as $name=>$value)
		{
			if ($value)
			{
				$oid = $db->GenID($pre.'module_pwf_field_opt_seq');
				$db->Execute($sql,array($oid,$newf,$newfid,$name,$value));
			}
		}
	}
}

function Get_Fields(&$db,$pre,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_field WHERE form_id=? ORDER BY order_by,field_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$renames = array(
		 'ButtonField'=>'Button',
		 'CatalogerItemsField'=>'CatalogerItems',
		 'CCEmailAddressField'=>'EmailCCAddress',
		 'CheckboxExtendedField'=>'CheckboxExtended',
		 'CheckboxField'=>'Checkbox',
		 'CheckboxGroupField'=>'CheckboxGroup',
		 'CompanyDirectoryField'=>'CompanyDirectory',
		 'ComputedField'=>'Computed',
		 'CountryPickerField'=>'CountryPicker',
		 'DatePickerField'=>'DatePicker',
		 'DispositionDeliverToEmailAddressField'=>'EmailOne',
		 'DispositionDirector'=>'EmailDirector',
		 'DispositionEmail'=>'SystemEmail',
		 'DispositionEmailBasedFrontendFields'=>'CustomEmail',
		 'DispositionEmailConfirmation'=>'EmailConfirmation',
		 'DispositionEmailFromFEUProperty'=>'EmailFEUProperty',
		 'DispositionEmailSiteAdmin'=>'EmailSiteAdmin',
		 'DispositionFile'=>'SharedFile',
		 'DispositionFileDirector'=>'FileDirector',
		 'DispositionForm'=>'SubmitForm',
		 'DispositionFormBrowser'=>'FormBrowser',
		 'DispositionFromEmailAddressField'=>'UserEmail',
		 'DispositionMultiselectFileDirector'=>'MultiselectFileDirector',
		 'DispositionPageRedirector'=>'PageRedirector',
		 'DispositionUniqueFile'=>'UniqueFile',
		 'DispositionUserTag'=>'SubmissionTag',
		 'FieldsetEnd'=>'FieldsetEnd',
		 'FieldsetStart'=>'FieldsetStart',
		 'FileUploadField'=>'FileUpload',
		 'FromEmailAddressAgainField'=>'EmailAddressAgain',
		 'FromEmailAddressField'=>'EmailAddress',
		 'FromEmailNameField'=>'EmailSender',
		 'FromEmailSubjectField'=>'EmailSubject',
		 'HiddenField'=>'Hidden',
		 'LinkField'=>'Link',
		 'ModuleInterfaceField'=>'InputTemplate',
		 'MultiselectField'=>'Multiselect',
		 'OzStatePickerField'=>'OzStatePicker',
		 'PageBreakField'=>'PageBreak',
		 'PasswordAgainField'=>'PasswordAgain',
		 'PasswordField'=>'Password',
		 'ProvincePickerField'=>'ProvincePicker',
		 'PulldownField'=>'Pulldown',
		 'RadioGroupField'=>'RadioGroup',
		 'SiteAdminField'=>'EmailSiteAdmin',
		 'StatePickerField'=>'StatePicker',
		 'StaticTextField'=>'StaticText',
		 'SystemLinkField'=>'SystemLink',
		 'TextAreaField'=>'TextArea',
		 'TextField'=>'Text',
		 'TextFieldExpandable'=>'TextExpandable',
		 'TimePickerField'=>'TimePicker',
		 'UniqueIntegerField'=>'UniqueInteger',
		 'UserTagField'=>'InputTag',
		 'YearPullDownField'=>'YearPulldown'
		);
		$sql = 'INSERT INTO '.$pre.'module_pwf_field
(field_id,form_id,name,type,order_by) VALUES (?,?,?,?,?)';
		$sql2 = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,0)';
		foreach($data as $row)
		{
			$oldf = (int)$row['field_id'];
			$newf = $db->GenID($pre.'module_pwf_field_seq');
			$newt = (array_key_exists($row['type'],$renames)) ? $renames[$row['type']] : $row['type'];
			$db->Execute($sql,array($newf,$newfid,$row['name'],$newt,$row['order_by']));
			$db->Execute($sql2,array($oldf,$newf));
			Get_FieldOpts($db,$pre,$oldfid,$newfid,$oldf,$newf,$row);
		}
	}
}

function Get_Opts(&$mod,&$db,$pre,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_form_attr WHERE form_id=? ORDER BY form_attr_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pre.'module_pwf_form_opt
(option_id,form_id,name,value) VALUES (?,?,?,?)';
		foreach($data as $row)
		{
			if(strpos($row['name'],'captcha') !== FALSE) //ignore redundant options
				continue;
			if(strpos($row['name'],'udt') !== FALSE && ($row['value'] == FALSE || $row['value'] == -1))
				continue;
			if($row['name'] == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$newfid,$row['value']);
				$row['value'] = 'pwf_'.$newfid;
			}
			elseif($row['name'] == 'submission_template')
			{
				$mod->SetTemplate('pwf_sub_'.$newfid,$row['value']);
				$row['value'] = 'pwf_sub_'.$newfid;
			}
			$newopt = $db->GenID($pre.'module_pwf_form_opt_seq');
			$db->Execute($sql,array($newopt,$newfid,$row['name'],$row['value']));
		}
	}
}

if(isset($params['import']))
{
	$pre = cms_db_prefix();
	$db->Execute('DELETE FROM '.$pre.'module_pwf_trans');
	$sql = 'SELECT * FROM '.$pre.'module_fb_form ORDER BY form_id';
	$oldforms = $db->GetArray($sql);
	if($oldforms)
	{
		$funcs = new pwfFormOperations();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
		$renums = array();
		foreach($oldforms as $row)
		{
			$fid = $db->GenID($pre.'module_pwf_form_seq');
			$alias = $row['alias'];
			if($alias)
				$alias = pwfUtils::MakeAlias($alias,18); //maybe shorten
			else
				$alias = pwfUtils::MakeAlias($row['name'],18);
			$ta = $alias;
			$i = 1;
			while(!$funcs->NewID(FALSE,$alias))
			{
				$alias = $ta."[$i]";
				$i++;
			}
			$db->Execute($sql,array($fid,$row['name'],$alias));
			$renums[(int)$row['form_id']] = $fid;
		}
		$sql = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,1)';
		foreach($renums as $old=>$new)
		{
			$db->Execute($sql,array($old,$new));
			Get_Opts($this,$db,$pre,$old,$new);
			Get_Fields($db,$pre,$old,$new);
			Update_Templates($this,$db,$pre,$old,$new);
			//data may've already been imported by the browser module
			$rs = $db->SelectLimit('SELECT * FROM '.$pre.'module_pwbr_browser',1);
			if($rs)
			{
				if(!$rs->EOF)
					Match_Browses($db,$pre);
				$rs->Close();
			}
		}
		$this->Redirect($id,'defaultadmin');
	}
	else
		$message = $this->PrettyMessage('no_forms',FALSE);
}
elseif(isset($params['conform']))
{
	//relevant checks are done upstream (method.defaultadmin.php)
	$pre = cms_db_prefix();
	Match_Browses($db,$pre);
	$message = $this->PrettyMessage('browsers_updated');
}
else
	$message = $this->PrettyMessage('error',FALSE);

$this->Redirect($id,'defaultadmin','',array(
	'message'=>$message,'active_tab'=>'import'));

?>
