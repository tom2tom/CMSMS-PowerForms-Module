<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$formdata = $this->GetFormData($params); //TODO LOAD FORM IF EXISTS

if(!empty($message))
	$smarty->assign('message',$message);

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin','',$this->Lang('back_top'),array()));

$smarty->assign('formstart',$this->CreateFormStart($id,'update_form',$returnid));

$tab = $this->GetActiveTab($params);
$smarty->assign('tabs_start',$this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('tab_main'),($tab == 'maintab')).
	$this->SetTabHeader('fieldstab',$this->Lang('tab_fields'),($tab == 'fieldstab')).
	$this->SetTabHeader('designtab',$this->Lang('tab_design'),($tab == 'designtab')).
	$this->SetTabHeader('templatetab',$this->Lang('tab_templatelayout'),($tab == 'templatetab')).
	$this->SetTabHeader('udttab',$this->Lang('tab_udt'),($tab == 'udttab')).
	$this->SetTabHeader('submittab',$this->Lang('tab_submit'),($tab == 'submittab')).
	$this->SetTabHeader('submittpltab',$this->Lang('tab_submissiontemplate'),($tab == 'submittpltab')).
	$this->EndTabHeaders().$this->StartTabContent());

$smarty->assign('tabs_end',$this->EndTabContent());
$smarty->assign('maintab_start',$this->StartTab('maintab'));
$smarty->assign('fieldstab_start',$this->StartTab('fieldstab'));
$smarty->assign('designtab_start',$this->StartTab('designtab'));
$smarty->assign('templatetab_start',$this->StartTab('templatetab'));
$smarty->assign('udttab_start',$this->StartTab('udttab'));
$smarty->assign('submittab_start',$this->StartTab('submittab'));
$smarty->assign('submittemplatetab_start',$this->StartTab('submittpltab'));
$smarty->assign('tab_end',$this->EndTab());
$smarty->assign('form_end',$this->CreateFormEnd());

$smarty->assign('title_form_name',$this->Lang('title_form_name'));
$smarty->assign('input_form_name',
	$this->CreateInputText($id,'form_name',$formdata->Name,50));
$smarty->assign('title_form_alias',$this->Lang('title_form_alias'));
$smarty->assign('input_form_alias',
$this->CreateInputText($id,'form_alias',$formdata->Alias,50));
$smarty->assign('help_form_alias',$this->Lang('help_form_alias'));		
$smarty->assign('title_form_status',$this->Lang('title_form_status'));
$smarty->assign('text_ready',$this->Lang('status_ready'));
$smarty->assign('text_notready','<strong>'.$this->Lang('status_not_ready').'</strong>'.
	' - '.$this->Lang('help_not_ready'));

$smarty->assign('title_form_css_class',$this->Lang('title_form_css_class'));
$smarty->assign('input_form_css_class',
	 $this->CreateInputText($id,'forma_css_class',
		pwfUtils::GetAttr($formdata,'css_class','powerform'),50,50));
$smarty->assign('title_form_unspecified',$this->Lang('title_form_unspecified'));
$smarty->assign('input_form_unspecified',
	$this->CreateInputText($id,'forma_unspecified',
		pwfUtils::GetAttr($formdata,'unspecified',$this->Lang('unspecified')),30));

$smarty->assign('title_form_fields',$this->Lang('title_form_fields'));
$smarty->assign('title_form_main',$this->Lang('title_form_main'));
$smarty->assign('title_field_id',$this->Lang('title_field_id'));
$smarty->assign('title_field_alias',$this->Lang('title_field_alias_short'));
$smarty->assign('title_field_name',$this->Lang('title_field_name'));
$smarty->assign('title_field_type',$this->Lang('title_field_type'));
$smarty->assign('title_field_type',$this->Lang('title_field_type'));
$smarty->assign('title_form_template',$this->Lang('title_form_template'));
$smarty->assign('title_form_vars',$this->Lang('title_form_vars'));
$smarty->assign('title_list_delimiter',$this->Lang('title_list_delimiter'));
$smarty->assign('title_redirect_page',$this->Lang('title_redirect_page'));
$smarty->assign('title_submit_action',$this->Lang('title_submit_action'));
$smarty->assign('title_submit_template',$this->Lang('title_submit_response'));
$smarty->assign('help_can_drag',$this->Lang('help_can_drag'));
$smarty->assign('help_save_order',$this->Lang('help_save_order'));
$smarty->assign('title_inline_form',$this->Lang('title_inline_form'));
$smarty->assign('title_submit_actions',$this->Lang('title_submit_actions'));
$smarty->assign('title_submit_labels',$this->Lang('title_submit_labels'));
$smarty->assign('title_submit_javascript',$this->Lang('title_submit_javascript'));
$smarty->assign('help_submit_tab',$this->Lang('help_submit_tab'));
$smarty->assign('title_submit_template_help',$this->Lang('help_response_template'));

$theme = $gCms->variables['admintheme'];

$smarty->assign('icon_info',
	$theme->DisplayImage('icons/system/info.gif',$this->Lang('info'),'','','systemicon'));
$submitActions = array($this->Lang('display_text')=>'text',
	 $this->Lang('redirect_to_page')=>'redir');
$smarty->assign('input_submit_action',
$this->CreateInputRadioGroup($id,'forma_submit_action',$submitActions,
	pwfUtils::GetAttr($formdata,'submit_action','text'),'','&nbsp;&nbsp;'));

$captcha = $this->getModuleInstance('Captcha');
if($captcha == null)
{
	 $smarty->assign('title_install_captcha',$this->Lang('help_captcha_not_installed'));
	 $smarty->assign('captcha_installed',0);
}
else
{
	 $smarty->assign('title_use_captcha',$this->Lang('title_use_captcha'));
	 $smarty->assign('captcha_installed',1);

	 $smarty->assign('input_use_captcha',$this->CreateInputHidden($id,'forma_use_captcha','0').
			$this->CreateInputCheckbox($id,'forma_use_captcha','1',
			pwfUtils::GetAttr($formdata,'use_captcha','0')).
			$this->Lang('help_use_captcha'));
}
$smarty->assign('title_information',$this->Lang('information'));
$smarty->assign('title_order',$this->Lang('order'));
$smarty->assign('title_field_required_abbrev',$this->Lang('title_field_required_abbrev'));
$smarty->assign('hasdisposition',pwfUtils::HasDisposition($formdata));
$maxOrder = 1;
if($form_id > 0)
{
	$smarty->assign('hidden',
		$this->CreateInputHidden($id,'form_id',$form_id),
		$this->CreateInputHidden($id,'form_op',$this->Lang('updated')).
		$this->CreateInputHidden($id,'sort_order').
		$this->CreateInputHidden($id,'active_tab'));
	$smarty->assign('adding',0);
	$smarty->assign('save',$this->CreateInputSubmit($id,'submit',$this->Lang('save')));
	$smarty->assign('apply',$this->CreateInputSubmit($id,'submit',$this->Lang('apply'),
			'title = "'.$this->Lang('save_and_continue').'" onclick="set_tab()"'));
	$fieldList = array();
	$jsfuncs = array();
	$count = 1;
	$last = count($formdata->Fields);
	
	$icontrue = $theme->DisplayImage('icons/system/true.gif',$this->Lang('true'),'','','systemicon');
	$iconfalse = $theme->DisplayImage('icons/system/false.gif',$this->Lang('false'),'','','systemicon');
	$iconedit = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon');
	$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$this->Lang('copy'),'','','systemicon');
	$icondelete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
	$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$this->Lang('moveup'),'','','systemicon');
	$icondown = $theme->DisplayImage('icons/system/arrow-d.gif',$this->Lang('movedn'),'','','systemicon');

	foreach($formdata->Fields as &$one)
	{
		$oneset = new stdClass();
		$oneset->name = $this->CreateLink($id,'add_edit_field','',$one->GetName(),array('field_id'=>$one->GetId(),'form_id'=>$form_id));
		$oneset->id = $this->CreateLink($id,'add_edit_field','',$one->GetId(),array('field_id'=>$one->GetId(),'form_id'=>$form_id));
		$oneset->type = $one->GetDisplayType();
		$oneset->alias = $one->GetAlias();

		if(!$one->DisplayInForm() || $one->IsNonRequirableField())
		{
			$oneset->disposition = '';
			$no_avail = $this->Lang('not_available');
		}
		else if($one->IsRequired())
		{
			$oneset->disposition = $this->CreateLink($id,'update_form','',
				$icontrue,array('form_id'=>$form_id,'active'=>'off','field_id'=>$one->GetId()),'','','',
				'class="true" onclick="update_field_required();return false;"');
		}
		else
		{
			$oneset->disposition = $this->CreateLink($id,'update_form','',
				$iconfalse,array('form_id'=>$form_id,'active'=>'on','field_id'=>$one->GetId()),'','','',
				'class="false" onclick="update_field_required();return false;"');
		}

		$oneset->field_status = $one->StatusInfo();
		$oneset->editlink = $this->CreateLink($id,'add_edit_field','',
			$iconedit,
			array('field_id'=>$one->GetId(),'form_id'=>$form_id));
		$oneset->copylink = $this->CreateLink($id,'update_form','',
			$iconcopy,
			array('fieldcopy'=>1,'field_id'=>$one->GetId(),'form_id'=>$form_id));
		$oneset->deletelink = $this->CreateLink($id,'update_form','',
			$icondelete,
			array('fielddelete'=>1,'field_id'=>$one->GetId(),'form_id'=>$form_id),'','','',
			'onclick="delete_field(\''.$this->Lang('confirm_delete_field',htmlspecialchars($one->GetName())).'\'); return false;"');

		if($count > 1)
			$oneset->up = $this->CreateLink($id,'update_form','',$iconup,array('form_id'=>$form_id,'dir'=>'up','field_id'=>$one->GetId()));
		else
			$oneset->up = '';
		if($count < $last)
			$oneset->down=$this->CreateLink($id,'update_form','',$icondown,array('form_id'=>$form_id,'dir'=>'down','field_id'=>$one->GetId()));
		else
			$oneset->down = '';

		$count++;
		if($one->GetOrder() >= $maxOrder)
			$maxOrder = $one->GetOrder() + 1;

		$fieldList[] = $oneset;
	}
	unset ($one);

	if($fieldList)
		$smarty->assign('fields',$fieldList);
	else
		$smarty->assign('nofields',$this->Lang('no_fields'));
	
	$smarty->assign('add_field_link',
		$this->CreateLink($id,'add_edit_field',$returnid,
			$theme->DisplayImage('icons/system/newobject.gif',$this->Lang('title_add_new_field'),'','','systemicon'),
			array('form_id'=>$form_id,'order_by'=>$maxOrder),'',false).' '.
			$this->CreateLink($id,'add_edit_field',$returnid,$this->Lang('title_add_new_field'),array('form_id'=>$form_id,'order_by'=>$maxOrder),'',false));

	$smarty->assign('fastadd',1);
	$smarty->assign('title_fastadd',$this->Lang('title_fastadd'));
	$link = $this->CreateLink($id,'add_edit_field',$returnid,'',
		array('form_id'=>$form_id,'order_by'=>$maxOrder),'',true,true);
	$link = str_replace('&amp;','&',$link);
	$typeFunc = <<<EOS
function fast_add(field_type)
{
var type=field_type.options[field_type.selectedIndex].value;
this.location='{$link}&{$id}field_type='+type;
return true;
}
EOS;
	$jsfuncs [] = $typeFunc; //TODO handle duplicates
	pwfUtils::Initialize($this);
	if($this->GetPreference('adder_fields','basic') == 'basic')
	{
		$smarty->assign('input_fastadd',$this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->std_field_types),-1,'','onchange="fast_add(this)"').
			$this->Lang('title_switch_advanced').
			$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_advanced_link'),
			array('formedit'=>1,'form_id'=>$form_id,'set_field_level'=>'advanced')));
	}
	else
	{
		$smarty->assign('input_fastadd',$this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->field_types),-1,'','onchange="fast_add(this)"').
			$this->Lang('title_switch_basic').
			$this->CreateLink($id,'update_form',$returnid,$this->Lang('title_switch_basic_link'),
			array('formedit'=>1,'form_id'=>$form_id,'set_field_level'=>'basic')));
	}
}
else //new form being added
{
	$smarty->assign('hidden',
		$this->CreateInputHidden($id,'form_id',$form_id),
		$this->CreateInputHidden($id,'form_op',$this->Lang('added')).
		$this->CreateInputHidden($id,'sort_order').
		$this->CreateInputHidden($id,'active_tab'));
	$smarty->assign('adding',1);
	$smarty->assign('save','');
	$smarty->assign('apply',
			$this->CreateInputSubmit($id,'submit',$this->Lang('add')));
}

$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

//$smarty->assign('link_notready','<strong>'.$this->Lang('title_not_ready1').'</strong> '.
//	$this->Lang('title_not_ready2')
//	.' '.
//	$this->CreateLink($id,'add_edit_field',$returnid,$this->Lang('title_not_ready_link'),
//	array('form_id'=>$form_id,'order_by'=>$maxOrder,'dispose_only'=>1),
//	'',false,false,'class="pwf_link"').' '.
//	$this->Lang('title_not_ready3'));
$smarty->assign('input_inline_form',$this->CreateInputHidden($id,'forma_inline','0').
	$this->CreateInputCheckbox($id,'forma_inline','1',pwfUtils::GetAttr($formdata,'inline','0')).
		$this->Lang('help_inline_form'));

$smarty->assign('title_form_submit_button',$this->Lang('title_form_submit_button'));
$smarty->assign('input_form_submit_button',
	$this->CreateInputText($id,'forma_submit_button_text',
		pwfUtils::GetAttr($formdata,'submit_button_text',$this->Lang('button_submit')),35,35));
$smarty->assign('title_submit_button_safety',$this->Lang('help_add_safety'));
$smarty->assign('input_submit_button_safety',
	$this->CreateInputHidden($id,'forma_input_button_safety','0').
	$this->CreateInputCheckbox($id,'forma_input_button_safety','1',pwfUtils::GetAttr($formdata,'input_button_safety','0')).
	$this->Lang('title_submit_button_safety'));
$smarty->assign('title_form_prev_button',$this->Lang('title_form_prev_button'));
$smarty->assign('input_form_prev_button',
	$this->CreateInputText($id,'forma_prev_button_text',
		pwfUtils::GetAttr($formdata,'prev_button_text',$this->Lang('button_previous')),35,35));

$smarty->assign('input_title_user_captcha',
	$this->CreateInputText($id,'forma_title_user_captcha',
		pwfUtils::GetAttr($formdata,'title_user_captcha',$this->Lang('title_user_captcha')),50,80));
$smarty->assign('title_title_user_captcha',$this->Lang('title_title_user_captcha'));

$smarty->assign('input_title_user_captcha_error',
	$this->CreateInputText($id,'forma_captcha_wrong',
		pwfUtils::GetAttr($formdata,'captcha_wrong',$this->Lang('wrong_captcha')),50,80));
$smarty->assign('title_user_captcha_error',$this->Lang('title_user_captcha_error'));

$smarty->assign('title_form_next_button',$this->Lang('title_form_next_button'));
$smarty->assign('input_form_next_button',
	$this->CreateInputText($id,'forma_next_button_text',
		pwfUtils::GetAttr($formdata,'next_button_text',$this->Lang('button_continue')),35,35));
$smarty->assign('title_form_predisplay_udt',$this->Lang('title_form_predisplay_udt'));
$smarty->assign('title_form_predisplay_each_udt',$this->Lang('title_form_predisplay_each_udt'));

$usertagops = $gCms->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = -1;
foreach($usertags as $key => $value)
	$usertaglist[$value] = $key;
$smarty->assign('input_form_predisplay_udt',
	$this->CreateInputDropdown($id,'forma_predisplay_udt',$usertaglist,-1,
		pwfUtils::GetAttr($formdata,'predisplay_udt',-1)));
$smarty->assign('input_form_predisplay_each_udt',
	$this->CreateInputDropdown($id,'forma_predisplay_each_udt',$usertaglist,-1,
		pwfUtils::GetAttr($formdata,'predisplay_each_udt',-1)));

$smarty->assign('title_form_validate_udt',$this->Lang('title_form_validate_udt'));
$usertagops = $gCms->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = -1;
foreach($usertags as $key => $value)
	$usertaglist[$value] = $key;
$smarty->assign('input_form_validate_udt',
	$this->CreateInputDropdown($id,'forma_validate_udt',$usertaglist,-1,
		pwfUtils::GetAttr($formdata,'validate_udt',-1)));

$smarty->assign('title_form_required_symbol',$this->Lang('title_form_required_symbol'));
$smarty->assign('input_form_required_symbol',
	 $this->CreateInputText($id,'forma_required_field_symbol',
		pwfUtils::GetAttr($formdata,'required_field_symbol','*'),5));
$smarty->assign('input_list_delimiter',
	$this->CreateInputText($id,'forma_list_delimiter',
		pwfUtils::GetAttr($formdata,'list_delimiter',','),5));

$contentops = $gCms->GetContentOperations();
$smarty->assign('input_redirect_page',$contentops->CreateHierarchyDropdown('',pwfUtils::GetAttr($formdata,'redirect_page','0'),$id.'forma_redirect_page'));

$tplstr = ''.@file_get_contents(cms_join_path(dirname(dirname(__FILE__)),'templates','RenderFormDefault.tpl'));
$smarty->assign('input_form_template',
	$this->CreateTextArea(false,$id,
		pwfUtils::GetAttr($formdata,'form_template',$tplstr),
		'forma_form_template',
		'pwf_tallarea',
		'form_template',
		'','',80,15));

$smarty->assign('input_submit_javascript',
	$this->CreateTextArea(false,$id,
		pwfUtils::GetAttr($formdata,'submit_javascript',''),'forma_submit_javascript','pwf_shortarea','submit_javascript',
		'','',80,15,'','').
		'<br />'.$this->Lang('help_submit_javascript'));

$attr_name = 'submission_template';
$smarty->assign('input_submit_template',
	 $this->CreateTextArea(false,$id,
		pwfUtils::GetAttr($formdata,$attr_name,pwfUtils::CreateSampleTemplate($formdata,true,false)),
		'forma_'.$attr_name,
		'pwf_tallarea',
		'','','',80,15));

$smarty->assign('title_load_template',$this->Lang('title_load_template'));
$smarty->assign('security_key',CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);

$templateList = array(''=>'',
	$this->Lang('default_template')=>'RenderFormDefault.tpl',
	$this->Lang('table_left_template')=>'RenderFormTableTitleLeft.tpl',
	$this->Lang('table_top_template')=>'RenderFormTableTitleTop.tpl');

$allForms = pwfUtils::GetForms();
foreach($allForms as $one)
{
	if($one['form_id'] != $form_id)
		$templateList[$this->Lang('form_template_name',$one['name'])] = $one['form_id'];
}

$thisLink = $this->CreateLink($id,'get_template',$returnid,'',array(),'',true);
$smarty->assign('input_load_template',$this->CreateInputDropdown($id,'template_load',
	$templateList,-1,'','id="template_load" onchange="get_template(\''.$this->Lang('confirm_template').'\',\''.$thisLink.'\');"'));

$globalfields = array();
foreach(array(
	'total_pages',
	'this_page',
	'title_page_x_of_y',
	'css_class',
	'form_name',
	'form_id',
	'in_formbrowser',
	'in_admin',
	'browser_id',
	'hidden',
	'prev',
	'submit'
	) as $name)
{
	$oneset = new stdClass();
	$oneset->name = $name;
	$oneset->description = $this->Lang('desc_'.$name);
	$globalfields[] = $oneset;
}
$smarty->assign('globalfields',$globalfields);

$attrs = array();
foreach(array(
	'alias',
	'css_class',
	'display',
	'error',
	'field_helptext_id',
	'has_label',
	'helptext',
	'hide_name',
	'id',
	'input_id',
	'input',
	'label_parts',
	'logic',
	'multiple_parts',
	'name',
	'needs_div',
	'required_symbol',
	'required',
	'smarty_eval',
	'type',
	'valid',
	'values'
	) as $name)
{
	$oneset = new stdClass();
	$oneset->name = $name;
	$oneset->description = $this->Lang('desc_'.$name);
	$attrs[] = $oneset;
}
$smarty->assign('attrs',$attrs);

$smarty->assign('variable',$this->Lang('variable'));
$smarty->assign('attribute',$this->Lang('attribute'));
$smarty->assign('description',$this->Lang('description'));
$smarty->assign('help_globals',$this->Lang('help_globals'));
$smarty->assign('help_attrs1',$this->Lang('help_attrs1'));
$smarty->assign('help_attrs2',$this->Lang('help_attrs2'));

pwfUtils::SetupVarsHelp($formdata,$smarty);

$parms = array();
$parms[$attr_name]['general_button'] = true;
list ($popfuncs,$buttons) = pwfUtils::AdminTemplateActions($formdata,$id,$parms);

$smarty->assign('incpath',$this->GetModuleURLPath().'/include/');
$smarty->assign('jsfuncs',array_merge($jsfuncs,$popfuncs));
$smarty->assign('buttons',$buttons);

?>