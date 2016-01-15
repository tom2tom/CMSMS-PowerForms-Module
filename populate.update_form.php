<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!empty($message))
	$tplvars['message'] = $message;

$tplvars['backtomod_nav'] = $this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top'));

$tplvars['form_start'] = $this->CreateFormStart($id,'update_form',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$form_id,
	'formdata'=>$params['formdata']));

$tab = $this->GetActiveTab($params);
$t = $this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('tab_form'),($tab == 'maintab'));
if($form_id > 0)
	$t .= $this->SetTabHeader('fieldstab',$this->Lang('tab_fields'),($tab == 'fieldstab'));
$t .=
	$this->SetTabHeader('designtab',$this->Lang('tab_design'),($tab == 'designtab')).
	$this->SetTabHeader('templatetab',$this->Lang('tab_templatelayout'),($tab == 'templatetab')).
	$this->SetTabHeader('udttab',$this->Lang('tab_udt'),($tab == 'udttab')).
	$this->SetTabHeader('submittab',$this->Lang('tab_submit'),($tab == 'submittab')).
	$this->EndTabHeaders().$this->StartTabContent();
$tplvars['tabs_start'] = $t;
if($form_id > 0)
	$tplvars['fieldstab_start'] = $this->StartTab('fieldstab');
$tplvars = $tplvars + array(
	'tabs_end' => $this->EndTabContent(),
	'maintab_start' => $this->StartTab('maintab'),
	'designtab_start' => $this->StartTab('designtab'),
	'templatetab_start' => $this->StartTab('templatetab'),
	'udttab_start' => $this->StartTab('udttab'),
	'submittab_start' => $this->StartTab('submittab'),
	'tab_end' => $this->EndTab(),
	'form_end' => $this->CreateFormEnd(),

	'title_form_name' => $this->Lang('title_form_name'),
	'input_form_name' => $this->CreateInputText($id,'form_name',$formdata->Name,50),
	'title_form_alias' => $this->Lang('title_form_alias'),
	'input_form_alias' => $this->CreateInputText($id,'form_alias',$formdata->Alias,50),
	'help_form_alias' => $this->Lang('help_form_alias'),
	'title_form_status' => $this->Lang('title_form_status'),

	'help_can_drag' => $this->Lang('help_can_drag'),
	'help_save_order' => $this->Lang('help_save_order'),

	'title_field_alias' => $this->Lang('title_field_alias_short'),
	'title_field_id' => $this->Lang('title_field_id'),
	'title_field_name' => $this->Lang('title_field_name'),
	'title_field_required_abbrev' => $this->Lang('title_field_required_abbrev'),
	'title_field_type' => $this->Lang('title_field_type'),

	'title_form_css_class' => $this->Lang('title_form_css_class'),
	'input_form_css_class' => $this->CreateInputText($id,'opt_css_class',
		pwfUtils::GetFormOption($formdata,'css_class','powerform'),50,50),

	'title_form_fields' => $this->Lang('title_form_fields'),
	'title_form_main' => $this->Lang('title_form_main'),

	'title_form_unspecified' => $this->Lang('title_form_unspecified'),
	'input_form_unspecified' =>	$this->CreateInputText($id,'opt_unspecified',
		pwfUtils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified')),30),

	'title_information' => $this->Lang('information'),
//	'title_order' => $this->Lang('order'),
	'title_form_dispositions' => $this->Lang('title_form_dispositions')
//	'title_submit_actions' => $this->Lang('title_submit_actions'),
//	'title_submit_labels' => $this->Lang('title_submit_labels'),
//	'security_key' => CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]
);

$theme = ($this->before20) ? cmsms()->variables['admintheme']:
	cms_utils::get_theme_object();
//script accumulators
$jsfuncs = array();
$jsloads = array();
$jsincs = array();
$baseurl = $this->GetModuleURLPath();

$tplvars['icon_info'] =
	$theme->DisplayImage('icons/system/info.gif',$this->Lang('help_help'),'','','systemicon tipper');

$tplvars['hidden'] = $this->CreateInputHidden($id,'active_tab');
$tplvars['save'] = $this->CreateInputSubmit($id,'submit',$this->Lang('save'));
$tplvars['apply'] = $this->CreateInputSubmit($id,'submit',$this->Lang('apply'),
	'title = "'.$this->Lang('save_and_continue').'" onclick="set_tab()"');

$icontrue = $theme->DisplayImage('icons/system/true.gif',$this->Lang('true'),'','','systemicon');
$iconfalse = $theme->DisplayImage('icons/system/false.gif',$this->Lang('false'),'','','systemicon');
$iconedit = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon');
$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$this->Lang('copy'),'','','systemicon');
$icondelete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$this->Lang('moveup'),'','','systemicon');
$icondown = $theme->DisplayImage('icons/system/arrow-d.gif',$this->Lang('movedn'),'','','systemicon');

$fields = array(); //non-disposition fields
$dispositions = array(); //disposition fields
$count = 1;
$dcount = 1;
$total = count($formdata->Fields);
$dtotal = 0;
if($total > 0)
{
	foreach($formdata->Fields as &$one)
	{
		if($one->IsDisposition())
			$dtotal++;
	}
	unset($one);
	$total -= $dtotal;
}

foreach($formdata->FieldOrders as $one)
{
	$one = $formdata->Fields[$one];
	$oneset = new stdClass();
	$fid = (int)$one->GetId();
	$oneset->id = $fid;
	$oneset->order = '<input type="hidden" name="'.$id.'orders[]" value="'.$fid.'" />';
	$this->CreateInputHidden($id,'orders[]',$fid);
	$oneset->name = $this->CreateLink($id,'update_field','',$one->GetName(),
		array('field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->alias = $one->ForceAlias();
	$oneset->type = $one->GetDisplayType();
	$oneset->field_status = $one->GetFieldStatus();
	$oneset->editlink = $this->CreateLink($id,'update_field','',
		$iconedit,
		array('field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->copylink = $this->CreateLink($id,'update_form','',
		$iconcopy,
		array('fieldcopy'=>1,'field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->deletelink = $this->CreateLink($id,'update_form','',
		$icondelete,
		array('fielddelete'=>1,'field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']),
		'','','',
		'onclick="delete_field(\''.$this->Lang('confirm_delete_field',htmlspecialchars($one->GetName())).'\');return FALSE;"');

	if($one->IsDisposition())
	{
		if($dcount > 1)
			$oneset->up = $this->CreateLink($id,'update_form','',
			$iconup,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'up'));
		else
			$oneset->up = '';
		if($dcount < $dtotal)
			$oneset->down = $this->CreateLink($id,'update_form','',
			$icondown,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'down'));
		else
			$oneset->down = '';

		$dispositions[] = $oneset;
		$dcount++;
	}
	else
	{
		if(!$one->DisplayInForm() || !$one->GetChangeRequirement())
			$oneset->required = '';
		elseif($one->GetRequired())
			$oneset->required = $this->CreateLink($id,'update_form','',
				$icontrue,
				array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'off'),
				'','','','class="true" onclick="update_field_required();return false;"');
		else
			$oneset->required = $this->CreateLink($id,'update_form','',
				$iconfalse,
				array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'on'),
				'','','','class="false" onclick="update_field_required();return false;"');

		if($count > 1)
			$oneset->up = $this->CreateLink($id,'update_form','',
			$iconup,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'up'));
		else
			$oneset->up = '';
		if($count < $total)
			$oneset->down = $this->CreateLink($id,'update_form','',
			$icondown,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'down'));
		else
			$oneset->down = '';

		$fields[] = $oneset;
		$count++;
	}
}

if($fields)
{
	$tplvars['fields'] = $fields;
}
else
{
	$tplvars = $tplvars + array(
		'nofields' => $this->Lang('no_fields'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('no_fields')
	);
}

if($dispositions)
{
	$tplvars = $tplvars + array(
		'dispositions' => $dispositions,
		'text_ready' => $this->Lang('title_ready')
	);
}
else
{
	$tplvars = $tplvars + array(
		'nodispositions' => $this->Lang('no_dispositions'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('help_not_ready')
	);
}

$t = $this->Lang('title_add_new_field');
$tplvars['title_fastadd'] = $t;
$tplvars['add_field_link'] =
	$this->CreateLink($id,'update_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'update_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE);

$t = $this->Lang('title_add_new_disposition');
$tplvars['title_fastadd2'] = $t;
$tplvars['add_disposition_link'] =
	$this->CreateLink($id,'update_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'update_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE);

$link = $this->CreateLink($id,'update_field',$returnid,'',
	array('field_id'=>-1,
	'form_id'=>$form_id,
	'formdata'=>$params['formdata']),'',TRUE,TRUE);
$link = str_replace('&amp;','&',$link);
$jsfuncs[] =<<<EOS
 function fast_add(field_type) {
 var type=field_type.options[field_type.selectedIndex].value;
 this.location='{$link}&{$id}field_type='+type;
 return TRUE;
}

EOS;

//CmsLayoutTemplate::get_designs() TODO
//CmsLayoutTemplate::set_designs()

pwfUtils::Collect_Fields($this);

$displays = array($this->Lang('select_type')=>''); //non-disposition fields
$dispositions = $displays; //non-disposition field

if($this->GetPreference('adder_fields','basic') == 'basic')
{
	foreach($this->std_field_types as $l=>$t)
	{
		$one = new $t($formdata,$params);
		if($one->IsDisposition())
		{
			if($one->IsInput)
				$displays[$l] = $t;
			else
				$dispositions[$l] = $t;
		}
		else
			$displays[$l] = $t;
	}
	unset($one);
	$tplvars['input_fastadd'] = $this->CreateInputDropdown($id,'field_type',
		$displays,-1,'','onchange="fast_add(this)"');
	$t = $this->Lang('title_switch_advanced');
	$tplvars['help_fastadd'] =
		$t.
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'fieldstab','set_field_level'=>'advanced'));
	$tplvars['input_fastadd2'] = $this->CreateInputDropdown($id,'disposition_type',
		$dispositions,-1,'','onchange="fast_add(this)"');
	$t = $this->Lang('title_switch_advanced2');
	$tplvars['help_fastadd2'] =
		$t.
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'submittab','set_field_level'=>'advanced'));
}
else
{
	foreach($this->field_types as $l=>$t)
	{
		$one = new $t($formdata,$params);
		if($one->IsDisposition())
		{
			if($one->IsInput)
				$displays[$l] = $t;
			else
				$dispositions[$l] = $t;
		}
		else
			$displays[$l] = $t;
	}
	unset($one);
	$tplvars['input_fastadd'] = $this->CreateInputDropdown($id,'field_type',
		$displays,-1,'','onchange="fast_add(this)"');
	$t = $this->Lang('title_switch_basic');
	$tplvars['help_fastadd'] =
		$t.
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'fieldstab','set_field_level'=>'basic'));
	$tplvars['input_fastadd2'] = $this->CreateInputDropdown($id,'disposition_type',
		$dispositions,-1,'','onchange="fast_add(this)"');
	$t = $this->Lang('title_switch_basic2');
	$tplvars['help_fastadd2'] =
		$t.
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'submittab','set_field_level'=>'basic'));
}

$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));

//no scope for !empty() checks for boolean attrs, so we add hidden 0 for checkboxes
$tplvars['title_inline_form'] = $this->Lang('title_inline_form');
$tplvars['input_inline_form'] =
	$this->CreateInputHidden($id,'opt_inline',0).
	$this->CreateInputCheckbox($id,'opt_inline',1,
		pwfUtils::GetFormOption($formdata,'inline',0)).'<br />'.
	$this->Lang('help_inline_form');

$tplvars['title_form_submit_button'] = $this->Lang('title_form_submit_button');
$tplvars['input_form_submit_button'] =
	$this->CreateInputText($id,'opt_submit_button_text',
		pwfUtils::GetFormOption($formdata,'submit_button_text',$this->Lang('button_submit')),35,35);

$tplvars['title_submit_button_safety'] = $this->Lang('title_submit_button_safety');
$tplvars['input_submit_button_safety'] =
	$this->CreateInputHidden($id,'opt_input_button_safety',0).
	$this->CreateInputCheckbox($id,'opt_input_button_safety',1,
		pwfUtils::GetFormOption($formdata,'input_button_safety',0)).'<br />'.
	$this->Lang('help_submit_safety');

$tplvars['title_form_prev_button'] = $this->Lang('title_form_prev_button');
$tplvars['input_form_prev_button'] =
	$this->CreateInputText($id,'opt_prev_button_text',
		pwfUtils::GetFormOption($formdata,'prev_button_text',$this->Lang('button_previous')),35,35);

$tplvars['title_form_next_button'] = $this->Lang('title_form_next_button');
$tplvars['input_form_next_button'] =
	$this->CreateInputText($id,'opt_next_button_text',
		pwfUtils::GetFormOption($formdata,'next_button_text',$this->Lang('button_continue')),35,35);

$usertagops = cmsms()->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = '';
foreach($usertags as $key => $value)
	$usertaglist[$value] = $key;

$tplvars['title_form_predisplay_udt'] = $this->Lang('title_form_predisplay_udt');
$tplvars['input_form_predisplay_udt'] =
	$this->CreateInputDropdown($id,'opt_predisplay_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'predisplay_udt'));

$tplvars['title_form_predisplay_each_udt'] = $this->Lang('title_form_predisplay_each_udt');
$tplvars['input_form_predisplay_each_udt'] =
	$this->CreateInputDropdown($id,'opt_predisplay_each_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'predisplay_each_udt'));

$tplvars['title_form_validate_udt'] = $this->Lang('title_form_validate_udt');
$tplvars['input_form_validate_udt'] =
	$this->CreateInputDropdown($id,'opt_validate_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'validate_udt'));

$tplvars['title_form_required_symbol'] = $this->Lang('title_form_required_symbol');
$tplvars['input_form_required_symbol'] =
	 $this->CreateInputText($id,'opt_required_field_symbol',
		pwfUtils::GetFormOption($formdata,'required_field_symbol','*'),5);

$tplvars['title_list_delimiter'] = $this->Lang('title_list_delimiter');
$tplvars['input_list_delimiter'] =
	$this->CreateInputText($id,'opt_list_delimiter',
		pwfUtils::GetFormOption($formdata,'list_delimiter',','),5);

$tplvars['title_submit_javascript'] = $this->Lang('title_submit_javascript');
$tplvars['input_submit_javascript'] =
	$this->CreateTextArea(FALSE,$id,pwfUtils::GetFormOption($formdata,'submit_javascript',''),
		'opt_submit_javascript','pwf_shortarea','submit_javascript',
		'','',50,8).
		'<br />'.$this->Lang('help_submit_javascript');

$tplvars['title_submit_limit'] = $this->Lang('title_submit_limit');
$tplvars['input_submit_limit'] =
	$this->CreateInputText($id,'opt_submit_limit',
		pwfUtils::GetFormOption($formdata,'submit_limit',$this->GetPreference('submit_limit')),3,5);

$templateList = array(''=>'',
	$this->Lang('default_template')=>'defaultform.tpl',
	$this->Lang('table_left_template')=>'tableform_lefttitles.tpl',
	$this->Lang('table_top_template')=>'tableform_toptitles.tpl');

$allForms = pwfUtils::GetForms();
foreach($allForms as $one)
{
	if($one['form_id'] != $form_id)
		$templateList[$this->Lang('form_template_name',$one['name'])] = $one['form_id'];
}

$thisLink = $this->CreateLink($id,'get_template',$returnid,'',array(),'',TRUE);
$tplvars['title_load_template'] = $this->Lang('title_load_template');
$tplvars['input_load_template'] = $this->CreateInputDropdown($id,'template_load',
	$templateList,-1,'','id="template_load" onchange="get_template(\''.$this->Lang('confirm_template').'\',\''.$thisLink.'\');"');

if($this->before20)
	$tpl = $this->GetTemplate('pwf::'.$form_id);
else
{
	$ob = CmsLayoutTemplate::load('pwf::'.$form_id);
	$tpl = $ob->get_content();
}
$tplvars['title_form_template'] = $this->Lang('title_form_template');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_form_template'] =
	$this->CreateSyntaxArea($id,$tpl,'opt_form_template',
	'pwf_tallarea','form_template','','',50,24,'','','style="height:30em;"');

$postsubmits = array($this->Lang('redirect_to_page')=>'redir',$this->Lang('display_text')=>'text');
$tplvars['title_submit_action'] = $this->Lang('title_submit_action');
$tplvars['input_submit_action'] =
	$this->CreateInputRadioGroup($id,'opt_submit_action',$postsubmits,
		pwfUtils::GetFormOption($formdata,'submit_action','text'),'','&nbsp;&nbsp;');

$tplvars['title_redirect_page'] = $this->Lang('title_redirect_page');
$tplvars['input_redirect_page'] =
	pwfUtils::CreateHierarchyPulldown($this,$id,'opt_redirect_page',
		pwfUtils::GetFormOption($formdata,'redirect_page',0));

if($this->before20)
	$tpl = $this->GetTemplate('pwf::sub_'.$form_id);
else
{
	$ob = CmsLayoutTemplate::load('pwf::sub_'.$form_id);
	$tpl = $ob->get_content();
}
if(!$tpl)
	$tpl = pwfUtils::CreateDefaultTemplate($formdata,TRUE,FALSE); //? generate default for CmsLayoutTemplateType
$tplvars['title_submit_template'] = $this->Lang('title_submit_response');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_submit_template'] =
	 $this->CreateSyntaxArea($id,$tpl,'opt_submission_template',
	 'pwf_tallarea','','','',50,15);
//setup to revert to 'sample' submission-template
$ctlData = array();
$ctlData['opt_submission_template']['general_button'] = TRUE;
list($buttons,$funcs) = pwfUtils::TemplateActions($formdata,$id,$ctlData);
$jsfuncs[] = $funcs[0];
$tplvars = $tplvars + array(
	'sample_submit_template' => $buttons[0],
	'help_submit_template' => $this->Lang('help_submit_template'),
	'title_variable' => $this->Lang('variable'),
	'title_property' => $this->Lang('property'),
	'title_description' => $this->Lang('description'),
	'title_tplvars' => $this->Lang('title_tpl_vars'),
	'help_tplvars' => $this->Lang('help_tpl_vars'),
	'help_fieldvars1' => $this->Lang('help_fieldvars1'),
	'help_fieldvars2' => $this->Lang('help_fieldvars2')
);

//help for form-template
$formvars = array();
foreach(array(
	'total_pages',
	'this_page',
	'title_page_x_of_y',
	'css_class',
	'form_name',
	'form_id',
	'actionid',
	'in_browser',
	'help_icon',
	'prev',
	'submit',
	'form_done',
	'submission_error',
	'show_submission_errors',
	'submission_error_list',
	'form_has_validation_errors',
	'form_validation_errors'
	) as $name)
{
	$oneset = new stdClass();
	$oneset->name = $name;
	$oneset->description = $this->Lang('desc_'.$name);
	$formvars[] = $oneset;
}
if($formdata->Fields)
{
	foreach($formdata->Fields as &$one)
	{
		if($one->DisplayInSubmission())
		{
			$oneset = new stdClass();
			$oneset->name = $one->GetVariableName().'} / {$fld_'.$one->GetId();
			$oneset->description = $this->Lang('field_named',$one->GetName());
			$formvars[] = $oneset;
		}
	}
	unset($one);
}
$tplvars['formvars'] = $formvars;

if($formdata->Fields)
{
	$fieldprops = array();
	foreach(array(
		'alias',
		'css_class',
		'display',
		'error',
		'helptext_id',
		'has_label',
		'helptext',
		'hide_name',
		'id',
		'input_id',
		'input',
		'label_parts',
		'multiple_parts',
		'name',
		'needs_div',
		'required',
		'required_symbol',
		'resources',
		'smarty_eval',
		'type',
		'valid',
		'values'
		) as $name)
	{
		$oneset = new stdClass();
		$oneset->name = $name;
		if($name != 'css_class')
			$oneset->description = $this->Lang('desc_'.$name);
		else
			$oneset->description = $this->Lang('desc_cssf_class'); //work around duplicate
		$fieldprops[] = $oneset;
	}
	$tplvars['fieldprops'] = $fieldprops;
}

//help for submission-template
pwfUtils::SetupSubTemplateVarsHelp($formdata,$this,$tplvars);

$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/jquery.tablednd.min.js"></script>
<script type="text/javascript" src="{$baseurl}/include/module.js"></script>
EOS;

if($jsloads)
{
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
$tplvars['jsfuncs'] = $jsfuncs;
$tplvars['jsincs'] = $jsincs;

?>
