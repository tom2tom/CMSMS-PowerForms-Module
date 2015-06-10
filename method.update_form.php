<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!empty($message))
	$smarty->assign('message',$message);

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top')));

$smarty->assign('form_start',$this->CreateFormStart($id,'update_form',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$form_id,
	'formdata'=>$params['formdata'])));

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
$smarty->assign('tabs_start',$t);
$smarty->assign('tabs_end',$this->EndTabContent());
$smarty->assign('maintab_start',$this->StartTab('maintab'));
if($form_id > 0)
	$smarty->assign('fieldstab_start',$this->StartTab('fieldstab'));
$smarty->assign('designtab_start',$this->StartTab('designtab'));
$smarty->assign('templatetab_start',$this->StartTab('templatetab'));
$smarty->assign('udttab_start',$this->StartTab('udttab'));
$smarty->assign('submittab_start',$this->StartTab('submittab'));
$smarty->assign('tab_end',$this->EndTab());
$smarty->assign('form_end',$this->CreateFormEnd());

$smarty->assign('title_form_name',$this->Lang('title_form_name'));
$smarty->assign('input_form_name',$this->CreateInputText($id,'form_name',$formdata->Name,50));
$smarty->assign('title_form_alias',$this->Lang('title_form_alias'));
$smarty->assign('input_form_alias',$this->CreateInputText($id,'form_alias',$formdata->Alias,50));
$smarty->assign('help_form_alias',$this->Lang('help_form_alias'));		
$smarty->assign('title_form_status',$this->Lang('title_form_status'));

$smarty->assign('help_can_drag',$this->Lang('help_can_drag'));
$smarty->assign('help_save_order',$this->Lang('help_save_order'));
$smarty->assign('help_submit_tab',$this->Lang('help_submit_tab'));

$smarty->assign('title_field_alias',$this->Lang('title_field_alias_short'));
$smarty->assign('title_field_id',$this->Lang('title_field_id'));
$smarty->assign('title_field_name',$this->Lang('title_field_name'));
$smarty->assign('title_field_required_abbrev',$this->Lang('title_field_required_abbrev'));
$smarty->assign('title_field_type',$this->Lang('title_field_type'));
$smarty->assign('title_field_type',$this->Lang('title_field_type'));
$smarty->assign('title_form_css_class',$this->Lang('title_form_css_class'));
$smarty->assign('input_form_css_class',
	$this->CreateInputText($id,'opt_css_class',
	pwfUtils::GetFormOption($formdata,'css_class','powerform'),50,50));
$smarty->assign('title_form_fields',$this->Lang('title_form_fields'));
$smarty->assign('title_form_main',$this->Lang('title_form_main'));
$smarty->assign('title_form_template',$this->Lang('title_form_template'));
$smarty->assign('title_form_unspecified',$this->Lang('title_form_unspecified'));
$smarty->assign('input_form_unspecified',
	$this->CreateInputText($id,'opt_unspecified',
	pwfUtils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified')),30));
$smarty->assign('title_form_vars',$this->Lang('title_form_vars'));
$smarty->assign('title_information',$this->Lang('information'));
$smarty->assign('title_inline_form',$this->Lang('title_inline_form'));
$smarty->assign('title_list_delimiter',$this->Lang('title_list_delimiter'));
//$smarty->assign('title_order',$this->Lang('order'));
$smarty->assign('title_redirect_page',$this->Lang('title_redirect_page'));
$smarty->assign('title_submit_actions',$this->Lang('title_submit_actions'));
$smarty->assign('title_submit_action',$this->Lang('title_submit_action'));
$smarty->assign('title_submit_javascript',$this->Lang('title_submit_javascript'));
$smarty->assign('title_submit_labels',$this->Lang('title_submit_labels'));
$smarty->assign('title_submit_template',$this->Lang('title_submit_response'));

$jsfuncs = array(); //script accumulator
$theme = $gCms->variables['admintheme'];

$smarty->assign('icon_info',
	$theme->DisplayImage('icons/system/info.gif',$this->Lang('help_help'),'','','systemicon tipper'));

$smarty->assign('hidden',$this->CreateInputHidden($id,'active_tab'));
$smarty->assign('save',$this->CreateInputSubmit($id,'submit',$this->Lang('save')));
$smarty->assign('apply',$this->CreateInputSubmit($id,'submit',$this->Lang('apply'),
		'title = "'.$this->Lang('save_and_continue').'" onclick="set_tab()"'));

$icontrue = $theme->DisplayImage('icons/system/true.gif',$this->Lang('true'),'','','systemicon');
$iconfalse = $theme->DisplayImage('icons/system/false.gif',$this->Lang('false'),'','','systemicon');
$iconedit = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon');
$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$this->Lang('copy'),'','','systemicon');
$icondelete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$this->Lang('moveup'),'','','systemicon');
$icondown = $theme->DisplayImage('icons/system/arrow-d.gif',$this->Lang('movedn'),'','','systemicon');

$fields = array();
$count = 1;
$last = count($formdata->Fields);

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

	if(!$one->DisplayInForm() || !$one->GetChangeRequirement())
		$oneset->disposition = '';
	elseif($one->GetRequired())
		$oneset->disposition = $this->CreateLink($id,'update_form','',
			$icontrue,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'off'),
			'','','','class="true" onclick="update_field_required();return false;"');
	else
		$oneset->disposition = $this->CreateLink($id,'update_form','',
			$iconfalse,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'on'),
			'','','','class="false" onclick="update_field_required();return false;"');

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
	if($count > 1)
		$oneset->up = $this->CreateLink($id,'update_form','',
		$iconup,
		array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'up'));
	else
		$oneset->up = '';
	if($count < $last)
		$oneset->down = $this->CreateLink($id,'update_form','',
		$icondown,
		array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'down'));
	else
		$oneset->down = '';

	$fields[] = $oneset;
	$count++;
}

if($fields)
{
	$smarty->assign('fields',$fields);
	if(pwfUtils::HasDisposition($formdata))
		$smarty->assign('text_ready',$this->Lang('title_ready'));
	else
	{
		$smarty->assign('text_ready','');
		$smarty->assign('text_notready',$this->Lang('title_not_ready'));
		$smarty->assign('help_notready',$this->Lang('help_not_ready'));
	}
}
else
{
	$smarty->assign('nofields',$this->Lang('no_fields'));
	$smarty->assign('text_ready','');
	$smarty->assign('text_notready',$this->Lang('title_not_ready'));
	$smarty->assign('help_notready',$this->Lang('no_fields'));
}

$t = $this->Lang('title_add_new_field');
$smarty->assign('add_field_link',
	$this->CreateLink($id,'update_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'update_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE));

$link = $this->CreateLink($id,'update_field',$returnid,'',
	array('field_id'=>-1,
	'form_id'=>$form_id,
	'formdata'=>$params['formdata']),'',TRUE,TRUE);
$link = str_replace('&amp;','&',$link);
$jsfuncs [] =<<<EOS
 function fast_add(field_type) {
 var type=field_type.options[field_type.selectedIndex].value;
 this.location='{$link}&{$id}field_type='+type;
 return TRUE;
}

EOS;

pwfUtils::Collect_Fields($this);

$smarty->assign('title_fastadd',$t);
if($this->GetPreference('adder_fields','basic') == 'basic')
{
	$smarty->assign('input_fastadd',$this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->std_field_types),-1,'','onchange="fast_add(this)"'));
	$smarty->assign('help_fastadd',
		$this->Lang('title_switch_advanced').
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'fieldstab','set_field_level'=>'advanced')));
}
else
{
	$smarty->assign('input_fastadd',$this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->field_types),-1,'','onchange="fast_add(this)"'));
	$smarty->assign('help_fastadd',
		$this->Lang('title_switch_basic').
		$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('formedit'=>1,'form_id'=>$form_id,'formdata'=>$params['formdata'],'active_tab'=>'fieldstab','set_field_level'=>'basic')));
}

$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

//no scope for !empty() checks for boolean attrs, so we add hidden 0 for checkboxes
$smarty->assign('input_inline_form',
	$this->CreateInputHidden($id,'opt_inline',0).
	$this->CreateInputCheckbox($id,'opt_inline',1,
		pwfUtils::GetFormOption($formdata,'inline',0)).
	$this->Lang('help_inline_form'));

$smarty->assign('title_form_submit_button',$this->Lang('title_form_submit_button'));
$smarty->assign('input_form_submit_button',
	$this->CreateInputText($id,'opt_submit_button_text',
		pwfUtils::GetFormOption($formdata,'submit_button_text',$this->Lang('button_submit')),35,35));

$smarty->assign('title_submit_button_safety',$this->Lang('title_submit_button_safety'));
$smarty->assign('input_submit_button_safety',
	$this->CreateInputHidden($id,'opt_input_button_safety',0).
	$this->CreateInputCheckbox($id,'opt_input_button_safety',1,
		pwfUtils::GetFormOption($formdata,'input_button_safety',0)).
	$this->Lang('help_submit_safety'));

$smarty->assign('title_form_prev_button',$this->Lang('title_form_prev_button'));
$smarty->assign('input_form_prev_button',
	$this->CreateInputText($id,'opt_prev_button_text',
		pwfUtils::GetFormOption($formdata,'prev_button_text',$this->Lang('button_previous')),35,35));

$smarty->assign('title_form_next_button',$this->Lang('title_form_next_button'));
$smarty->assign('input_form_next_button',
	$this->CreateInputText($id,'opt_next_button_text',
		pwfUtils::GetFormOption($formdata,'next_button_text',$this->Lang('button_continue')),35,35));

$smarty->assign('title_form_predisplay_udt',$this->Lang('title_form_predisplay_udt'));
$smarty->assign('title_form_predisplay_each_udt',$this->Lang('title_form_predisplay_each_udt'));

$usertagops = $gCms->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = '';
foreach($usertags as $key => $value)
	$usertaglist[$value] = $key;
$smarty->assign('input_form_predisplay_udt',
	$this->CreateInputDropdown($id,'opt_predisplay_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'predisplay_udt')));
$smarty->assign('input_form_predisplay_each_udt',
	$this->CreateInputDropdown($id,'opt_predisplay_each_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'predisplay_each_udt')));

$smarty->assign('title_form_validate_udt',$this->Lang('title_form_validate_udt'));
$usertagops = $gCms->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = '';
foreach($usertags as $key => $value)
	$usertaglist[$value] = $key;
$smarty->assign('input_form_validate_udt',
	$this->CreateInputDropdown($id,'opt_validate_udt',$usertaglist,-1,
		pwfUtils::GetFormOption($formdata,'validate_udt')));

$smarty->assign('title_form_required_symbol',$this->Lang('title_form_required_symbol'));
$smarty->assign('input_form_required_symbol',
	 $this->CreateInputText($id,'opt_required_field_symbol',
		pwfUtils::GetFormOption($formdata,'required_field_symbol','*'),5));
$smarty->assign('input_list_delimiter',
	$this->CreateInputText($id,'opt_list_delimiter',
		pwfUtils::GetFormOption($formdata,'list_delimiter',','),5));

$smarty->assign('input_submit_javascript',
	$this->CreateTextArea(FALSE,$id,
		pwfUtils::GetFormOption($formdata,'submit_javascript',''),'opt_submit_javascript','pwf_shortarea','submit_javascript',
		'','',50,8,'','').
		'<br />'.$this->Lang('help_submit_javascript'));

$smarty->assign('title_load_template',$this->Lang('title_load_template'));
$smarty->assign('security_key',CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);

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
$smarty->assign('input_load_template',$this->CreateInputDropdown($id,'template_load',
	$templateList,-1,'','id="template_load" onchange="get_template(\''.$this->Lang('confirm_template').'\',\''.$thisLink.'\');"'));

$smarty->assign('input_form_template',
	$this->CreateTextArea(FALSE,$id,$this->GetTemplate('pwf_'.$form_id),
		'opt_form_template','pwf_tallarea','form_template','','',50,15));

$postsubmits = array($this->Lang('redirect_to_page')=>'redir',$this->Lang('display_text')=>'text');
$smarty->assign('input_submit_action',
	$this->CreateInputRadioGroup($id,'opt_submit_action',$postsubmits,
		pwfUtils::GetFormOption($formdata,'submit_action','text'),'','&nbsp;&nbsp;'));

$contentops = $gCms->GetContentOperations();
$smarty->assign('input_redirect_page',
	$contentops->CreateHierarchyDropdown('',pwfUtils::GetFormOption($formdata,'redirect_page','0'),$id.'opt_redirect_page'));

$tpl = pwfUtils::GetFormOption($formdata,'submission_template');
if(!$tpl)
	$tpl = pwfUtils::CreateSampleTemplate($formdata,TRUE,FALSE);
$smarty->assign('input_submit_template',
	 $this->CreateTextArea(FALSE,$id,$tpl,
		'opt_submission_template','pwf_tallarea','','','',50,15));
//setup to revert to 'sample' submission-template
$ctlData = array();
$ctlData['opt_submission_template']['general_button'] = TRUE;
list($buttons,$funcs) = pwfUtils::SampleTemplateActions($formdata,$id,$ctlData);
$smarty->assign('sample_submit_template',$buttons[0]);
$jsfuncs[] = $funcs[0];
$smarty->assign('help_submit_template',$this->Lang('help_submit_template'));

$smarty->assign('title_variable',$this->Lang('variable'));
$smarty->assign('title_property',$this->Lang('property'));
$smarty->assign('title_description',$this->Lang('description'));
$smarty->assign('help_formvars',$this->Lang('help_form_vars'));
$smarty->assign('help_fieldvars1',$this->Lang('help_fieldvars1'));
$smarty->assign('help_fieldvars2',$this->Lang('help_fieldvars2'));

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
	'form_start',
	'form_end',
	'hidden',
	'help_icon',
	'jscript',
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
$smarty->assign('formvars',$formvars);

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
	$smarty->assign('fieldprops',$fieldprops);
}

//help for submission-template
pwfUtils::SetupSubTemplateVarsHelp($formdata,$this,$smarty);

$smarty->assign('incpath',$this->GetModuleURLPath().'/include/');
$smarty->assign('jsfuncs',$jsfuncs);

?>
