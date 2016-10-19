<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!empty($message))
	$tplvars['message'] = $message;

$tplvars['backtomod_nav'] = $this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top'));

$tplvars['form_start'] = $this->CreateFormStart($id,'open_form',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$form_id,
	'formdata'=>$params['formdata']));

$tab = $this->_GetActiveTab($params);
$t = $this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('tab_form'),($tab == 'maintab'));
if ($form_id > 0)
	$t .= $this->SetTabHeader('fieldstab',$this->Lang('tab_fields'),($tab == 'fieldstab'));
$t .=
	$this->SetTabHeader('designtab',$this->Lang('tab_design'),($tab == 'designtab')).
	$this->SetTabHeader('templatetab',$this->Lang('tab_templatelayout'),($tab == 'templatetab')).
	$this->SetTabHeader('udttab',$this->Lang('tab_udt'),($tab == 'udttab')).
	$this->SetTabHeader('submittab',$this->Lang('tab_submit'),($tab == 'submittab')).
	$this->SetTabHeader('externtab',$this->Lang('tab_external'),($tab == 'externtab')).
	$this->EndTabHeaders().$this->StartTabContent();
$tplvars['tabs_start'] = $t;
if ($form_id > 0)
	$tplvars['fieldstab_start'] = $this->StartTab('fieldstab');
$tplvars = $tplvars + array(
	'tabs_end' => $this->EndTabContent(),
	'maintab_start' => $this->StartTab('maintab'),
	'designtab_start' => $this->StartTab('designtab'),
	'templatetab_start' => $this->StartTab('templatetab'),
	'udttab_start' => $this->StartTab('udttab'),
	'submittab_start' => $this->StartTab('submittab'),
	'externtab_start' => $this->StartTab('externtab'),
	'tab_end' => $this->EndTab(),
	'form_end' => $this->CreateFormEnd(),

	'title_form_name' => $this->Lang('title_form_name'),
	'input_form_name' => $this->CreateInputText($id,'form_Name',$formdata->Name,50), //NB object name = 'form_'.property-name
	'title_form_alias' => $this->Lang('title_form_alias'),
	'input_form_alias' => $this->CreateInputText($id,'form_Alias',$formdata->Alias,50), //ditto
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
	'input_form_css_class' => $this->CreateInputText($id,'pdt_css_class',
		PWForms\Utils::GetFormOption($formdata,'css_class','powerform'),50,50),

	'title_form_fields' => $this->Lang('title_form_fields'),
	'title_form_main' => $this->Lang('title_form_main'),

	'title_form_unspecified' => $this->Lang('title_form_unspecified'),
	'input_form_unspecified' =>	$this->CreateInputText($id,'pdt_unspecified',
		PWForms\Utils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified')),30),

	'title_information' => $this->Lang('information'),
//	'title_order' => $this->Lang('order'),
	'title_form_dispositions' => $this->Lang('title_form_dispositions'),
	'title_form_externals' => $this->Lang('title_form_externals')
//	'title_submit_actions' => $this->Lang('title_submit_actions'),
//	'title_submit_labels' => $this->Lang('title_submit_labels'),
//	'security_key' => CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]
);

$theme = ($this->before20) ? cmsms()->variables['admintheme']:
	cms_utils::get_theme_object();
//script accumulators
$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

$hidden = array();

$tplvars['icon_info'] =
	$theme->DisplayImage('icons/system/info.gif',$this->Lang('help_help'),'','','systemicon tipper');

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
if ($total > 0) {
	foreach ($formdata->Fields as &$one) {
		if ($one->IsDisposition())
			$dtotal++;
	}
	unset($one);
	$total -= $dtotal;
}

foreach ($formdata->FieldOrders as $one) {
	$one = $formdata->Fields[$one];
	$oneset = new stdClass();
	$fid = (int)$one->GetId();
	$oneset->id = $fid;
	$t = $one->GetName();
	if (!$t)
		$t = $this->Lang('none');
	$oneset->order = '<input type="hidden" name="'.$id.'form_FieldOrders[]" value="'.$fid.'" />';
	$oneset->name = $this->CreateLink($id,'open_field','',$t,
		array('field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->alias = $one->ForceAlias();
	$oneset->type = $one->GetDisplayType();
	$oneset->field_status = $one->GetFieldStatus();
	$oneset->editlink = $this->CreateLink($id,'open_field','',
		$iconedit,
		array('field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->copylink = $this->CreateLink($id,'open_form','',
		$iconcopy,
		array('fieldcopy'=>1,'field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']));
	$oneset->deletelink = $this->CreateLink($id,'open_form','',
		$icondelete,
		array('fielddelete'=>1,'field_id'=>$fid,'form_id'=>$form_id,'formdata'=>$params['formdata']),
		'','','',
		'onclick="delete_field(\''.$this->Lang('confirm_delete_field',htmlspecialchars($one->GetName())).'\');return false;"');

	if ($one->IsDisposition()) {
		if ($dcount > 1)
			$oneset->up = $this->CreateLink($id,'open_form','',
			$iconup,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'up'));
		else
			$oneset->up = '';
		if ($dcount < $dtotal)
			$oneset->down = $this->CreateLink($id,'open_form','',
			$icondown,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'down'));
		else
			$oneset->down = '';

		$dispositions[] = $oneset;
		$dcount++;
	} else {
		if (!$one->DisplayInForm() || !$one->GetChangeRequirement())
			$oneset->required = '';
		elseif ($one->IsRequired())
			$oneset->required = $this->CreateLink($id,'open_form','',
				$icontrue,
				array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'off'),
				'','','','class="true" onclick="open_field_required();return false;"');
		else
			$oneset->required = $this->CreateLink($id,'open_form','',
				$iconfalse,
				array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'active'=>'on'),
				'','','','class="false" onclick="open_field_required();return false;"');

		if ($count > 1)
			$oneset->up = $this->CreateLink($id,'open_form','',
			$iconup,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'up'));
		else
			$oneset->up = '';
		if ($count < $total)
			$oneset->down = $this->CreateLink($id,'open_form','',
			$icondown,
			array('form_id'=>$form_id,'formdata'=>$params['formdata'],'field_id'=>$fid,'dir'=>'down'));
		else
			$oneset->down = '';

		$fields[] = $oneset;
		$count++;
	}
}

if ($fields) {
	$tplvars['fields'] = $fields;

	$jsfuncs[] = <<<EOS
function delete_field (message) {
 if (confirm(message)) {
  var url = $(this).attr('href');
  var parent = $(this).closest('tr');
  $.ajax({
   type: 'GET',
   url: url,
   error: function() {
    alert(errmsg);
   },
   success: function() {
    parent.fadeOut('1000', function() {
     parent.remove();
     var odd = true;
     var oddclass = 'row1';
     var evenclass = 'row2';
     $('.pwf_table').find('tbody tr').each(function() {
      var name = odd ? oddclass : evenclass;
      $(this).removeClass().addClass(name)
      .removeAttr('onmouseover').mouseover(function() {
        $(this).attr('class',name+'hover');
      }).removeAttr('onmouseout').mouseout(function() {
        $(this).attr('class',name);
      });
      odd = !odd;
     });
    });
   }
  });
 }
}
EOS;
} else {
	$tplvars = $tplvars + array(
		'nofields' => $this->Lang('no_fields'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('no_fields')
	);
}

if ($dispositions) {
	$tplvars = $tplvars + array(
		'dispositions' => $dispositions,
		'text_ready' => $this->Lang('title_ready')
	);
} else {
	$tplvars = $tplvars + array(
		'nodispositions' => $this->Lang('no_dispositions'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('help_not_ready')
	);
}

$externals = array(); //TODO
if ($externals) {
	$tplvars['externals'] = $externals;
} else {
	$tplvars['noexternals'] = $this->Lang('no_externals');
}

if (!isset($params['selectfields'])) { //first time
	$selfield = $this->GetPreference('adder_fields','basic');
	$seldisp = $selfield;
	$selext = $selfield;
} else {
	$selfield = $params['selectfields'];
	$seldisp = $params['selectdispos'];
	$selext = $params['selectextern'];
}

$hidden[] = $this->CreateInputHidden($id,'selectfields',$selfield);
$hidden[] = $this->CreateInputHidden($id,'selectdispos',$seldisp);
$hidden[] = $this->CreateInputHidden($id,'selectextern',$selext);

$basicfields = array($this->Lang('select_type')=>''); //non-disposition fields
$basicdispos = $basicfields; //disposition fields
$advancedfields = $basicfields; //non-disposition fields
$advanceddispos = $basicfields; //disposition fields

PWForms\Utils::Collect_Fields($this);
foreach ($this->std_field_types as $l=>$t) {
	$classPath = 'PWForms\\'.$t;
	$one = new $classPath($formdata,$params);
	if ($one->IsDisposition()) {
		if ($one->IsInput)
			$basicfields[$l] = $t;
		else
			$basicdispos[$l] = $t;
	} else
		$basicfields[$l] = $t;
}
unset($one);

foreach ($this->field_types as $l=>$t) {
	$classPath = 'PWForms\\'.$t;
	$one = new $classPath($formdata,$params);
	if ($one->IsDisposition()) {
		if ($one->IsInput)
			$advancedfields[$l] = $t;
		else
			$advanceddispos[$l] = $t;
	} else
		$advancedfields[$l] = $t;
}
unset($one);

$linkargs = array(
	'form_id'=>$form_id,
	'formdata'=>$params['formdata'],
	'active_tab'=>NULL,
	'selectfields'=>$selfield,
	'selectdispos'=>$seldisp,
	'selectextern'=>$selext);
$t1 = $this->Lang('title_switch_advanced');
$t2 = $this->Lang('title_switch_basic');

//fields
$t = $this->Lang('title_add_new_field');
$tplvars['title_fieldpick'] = $t;
$tplvars['add_field_link'] =
	$this->CreateLink($id,'open_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'open_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE);
//selector
if ($selfield == 'basic') {
	$tplvars['input_fieldpick'] = $this->CreateInputDropdown($id,'field_type',
		$basicfields,-1,'','onchange="add_field(this,\'form\');"');
	$tplvars['help_fieldpick'] =
		$t1.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('active_tab'=>'fieldstab','selectfields'=>'advanced') + $linkargs);
} else { //advanced
	$tplvars['input_fieldpick'] = $this->CreateInputDropdown($id,'field_type',
		$advancedfields,-1,'','onchange="add_field(this,\'form\');"');
	$tplvars['help_fieldpick'] =
		$t2.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('active_tab'=>'fieldstab','selectfields'=>'basic') + $linkargs);
}

//dispositions 
$t = $this->Lang('title_add_new_disposition');
$tplvars['title_fieldpick2'] = $t;
$tplvars['add_disposition_link'] =
	$this->CreateLink($id,'open_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'open_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE);
//selector
if ($seldisp == 'basic') {
	$tplvars['input_fieldpick2'] = $this->CreateInputDropdown($id,'disposition_type',
		$basicdispos,-1,'','onchange="add_field(this,\'disposition\');"');
	$t = $this->Lang('title_switch_advanced2');
	$tplvars['help_fieldpick2'] =
		$t.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('active_tab'=>'submittab','selectdispos'=>'advanced') + $linkargs);
} else { //advanced
	$tplvars['input_fieldpick2'] = $this->CreateInputDropdown($id,'disposition_type',
		$advanceddispos,-1,'','onchange="add_field(this,\'disposition\');"');
	$t = $this->Lang('title_switch_basic2');
	$tplvars['help_fieldpick2'] =
		$t.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('active_tab'=>'submittab','selectdispos'=>'basic') + $linkargs);
}

//externals
$t = $this->Lang('title_add_new_external');
$tplvars['title_fieldpick3'] = $t;
$tplvars['add_external_link'] =
	$this->CreateLink($id,'open_field',$returnid,
		$theme->DisplayImage('icons/system/newobject.gif',$t,'','','systemicon'),
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE).' '.
	$this->CreateLink($id,'open_field',$returnid,$t,
		array('field_id'=>-1,
		'form_id'=>$form_id,
		'formdata'=>$params['formdata']),'',FALSE);
//selector
if ($selext == 'basic') {
	$tplvars['input_fieldpick3'] = $this->CreateInputDropdown($id,'external_type',
		$basicfields,-1,'','onchange="add_field(this,\'external\');"');
	$tplvars['help_fieldpick3'] =
		$t1.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_advanced_link'),
		array('active_tab'=>'externtab','selectextern'=>'advanced') + $linkargs);
} else { //advanced
	$tplvars['input_fieldpick3'] = $this->CreateInputDropdown($id,'external_type',
		$advancedfields,-1,'','onchange="add_field(this,\'external\');"');
	$tplvars['help_fieldpick3'] =
		$t2.
		$this->CreateLink($id,'open_form',$returnid,$this->Lang('title_switch_basic_link'),
		array('active_tab'=>'externtab','selectextern'=>'basic') + $linkargs);
}
//js to add selected field
$link = $this->CreateLink($id,'open_field',$returnid,'',
	array('field_id'=>-1,
	'form_id'=>$form_id,
	'formdata'=>$params['formdata']),'',TRUE,TRUE);
$link = str_replace('&amp;','&',$link);
$jsfuncs[] = <<<EOS
function add_field(selector,scope) {
 var type=selector.options[selector.selectedIndex].value;
 this.location='{$link}&{$id}field_pick='+type+'&{$id}in='+scope;
 return TRUE;
}
EOS;

//no scope for !empty() checks for boolean attrs, so we add hidden 0 for checkboxes
$tplvars['title_inline_form'] = $this->Lang('title_inline_form');
$tplvars['input_inline_form'] =
	$this->CreateInputHidden($id,'pdt_inline',0).
	$this->CreateInputCheckbox($id,'pdt_inline',1,
		PWForms\Utils::GetFormOption($formdata,'inline',0)).'<br />'.
	$this->Lang('help_inline_form');

$tplvars['title_form_submit_button'] = $this->Lang('title_form_submit_button');
$tplvars['input_form_submit_button'] =
	$this->CreateInputText($id,'pdt_submit_button_text',
		PWForms\Utils::GetFormOption($formdata,'submit_button_text',$this->Lang('button_submit')),35,35);

$tplvars['title_submit_button_safety'] = $this->Lang('title_submit_button_safety');
$tplvars['input_submit_button_safety'] =
	$this->CreateInputHidden($id,'pdt_input_button_safety',0).
	$this->CreateInputCheckbox($id,'pdt_input_button_safety',1,
		PWForms\Utils::GetFormOption($formdata,'input_button_safety',0)).'<br />'.
	$this->Lang('help_submit_safety');

$tplvars['title_form_prev_button'] = $this->Lang('title_form_prev_button');
$tplvars['input_form_prev_button'] =
	$this->CreateInputText($id,'pdt_prev_button_text',
		PWForms\Utils::GetFormOption($formdata,'prev_button_text',$this->Lang('button_previous')),35,35);

$tplvars['title_form_next_button'] = $this->Lang('title_form_next_button');
$tplvars['input_form_next_button'] =
	$this->CreateInputText($id,'pdt_next_button_text',
		PWForms\Utils::GetFormOption($formdata,'next_button_text',$this->Lang('button_continue')),35,35);

$usertagops = cmsms()->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none')] = '';
foreach ($usertags as $key => $value)
	$usertaglist[$value] = $key;

$tplvars['title_form_predisplay_udt'] = $this->Lang('title_form_predisplay_udt');
$tplvars['input_form_predisplay_udt'] =
	$this->CreateInputDropdown($id,'pdt_predisplay_udt',$usertaglist,-1,
		PWForms\Utils::GetFormOption($formdata,'predisplay_udt'));

$tplvars['title_form_predisplay_each_udt'] = $this->Lang('title_form_predisplay_each_udt');
$tplvars['input_form_predisplay_each_udt'] =
	$this->CreateInputDropdown($id,'pdt_predisplay_each_udt',$usertaglist,-1,
		PWForms\Utils::GetFormOption($formdata,'predisplay_each_udt'));

$tplvars['title_form_validate_udt'] = $this->Lang('title_form_validate_udt');
$tplvars['input_form_validate_udt'] =
	$this->CreateInputDropdown($id,'pdt_validate_udt',$usertaglist,-1,
		PWForms\Utils::GetFormOption($formdata,'validate_udt'));

$tplvars['title_form_required_symbol'] = $this->Lang('title_form_required_symbol');
$tplvars['input_form_required_symbol'] =
	 $this->CreateInputText($id,'pdt_required_field_symbol',
		PWForms\Utils::GetFormOption($formdata,'required_field_symbol','*'),5);

$tplvars['title_list_delimiter'] = $this->Lang('title_list_delimiter');
$tplvars['input_list_delimiter'] =
	$this->CreateInputText($id,'pdt_list_delimiter',
		PWForms\Utils::GetFormOption($formdata,'list_delimiter',','),5);

$tplvars['title_submit_javascript'] = $this->Lang('title_submit_javascript');
$tplvars['input_submit_javascript'] =
	$this->CreateTextArea(FALSE,$id,PWForms\Utils::GetFormOption($formdata,'submit_javascript',''),
		'pdt_submit_javascript','pwf_shortarea','submit_javascript',
		'','',50,8).
		'<br />'.$this->Lang('help_submit_javascript');

$tplvars['title_submit_limit'] = $this->Lang('title_submit_limit');
$tplvars['input_submit_limit'] =
	$this->CreateInputText($id,'pdt_submit_limit',
		PWForms\Utils::GetFormOption($formdata,'submit_limit',$this->GetPreference('submit_limit')),3,5);

$templateList = array(''=>'',
	$this->Lang('default_template')=>'defaultform.tpl',
	$this->Lang('table_left_template')=>'tableform_lefttitles.tpl',
	$this->Lang('table_top_template')=>'tableform_toptitles.tpl');

$allForms = PWForms\Utils::GetForms();
foreach ($allForms as $one) {
	if ($one['form_id'] != $form_id)
		$templateList[$this->Lang('form_template_name',$one['name'])] = $one['form_id'];
}

//CmsLayoutTemplate::get_designs() TODO
//CmsLayoutTemplate::set_designs()

$thisLink = $this->CreateLink($id,'get_template',$returnid,'',array(),'',TRUE);
$tplvars['title_load_template'] = $this->Lang('title_load_template');
$tplvars['input_load_template'] = $this->CreateInputDropdown($id,'template_load',
	$templateList,-1,'','id="template_load" onchange="get_template(\''.$this->Lang('confirm_template').'\',\''.$thisLink.'\');"');

$jsloads[] = <<<EOS
function get_template (message,url) {
 if (confirm(message)) {
  var value = $(this).val();
  $.ajax({
   type: 'GET',
   url: url,
   data: '&{$id}tid='+value,
   error: function() {
    alert(errmsg);
   },
   success: function(data) {
    $('#{$id}form_template').val(data);
   }
  });
 }
}
EOS;

if ($this->before20)
	$tpl = $this->GetTemplate('pwf_'.$form_id);
else {
	$ob = CmsLayoutTemplate::load('pwf_'.$form_id);
	$tpl = $ob->get_content();
}
$tplvars['title_form_template'] = $this->Lang('title_form_template');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_form_template'] = $this->CreateSyntaxArea($id,$tpl,'pdt_form_template',
	'pwf_tallarea','','','',50,24,'','','style="height:30em;"'); //xtra-tall!

$postsubmits = array($this->Lang('redirect_to_page')=>'redir',$this->Lang('display_text')=>'text');
$tplvars['title_submit_action'] = $this->Lang('title_submit_action');
$tplvars['input_submit_action'] =
	$this->CreateInputRadioGroup($id,'pdt_submit_action',$postsubmits,
		PWForms\Utils::GetFormOption($formdata,'submit_action','text'),'','&nbsp;&nbsp;');

$tplvars['title_redirect_page'] = $this->Lang('title_redirect_page');
$tplvars['input_redirect_page'] =
	PWForms\Utils::CreateHierarchyPulldown($this,$id,'pdt_redirect_page',
		PWForms\Utils::GetFormOption($formdata,'redirect_page',0));

if ($this->before20)
	$tpl = $this->GetTemplate('pwf_sub_'.$form_id);
else {
	$ob = CmsLayoutTemplate::load('pwf_sub_'.$form_id);
	$tpl = $ob->get_content();
}
if (!$tpl)
	$tpl = PWForms\Utils::CreateDefaultTemplate($formdata,TRUE,FALSE); //? generate default for CmsLayoutTemplateType
$tplvars['title_submit_template'] = $this->Lang('title_submit_response');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_submit_template'] = $this->CreateSyntaxArea($id,$tpl,'pdt_submission_template',
	'pwf_tallarea','','','',50,15);
//setup to revert to 'sample' submission-template
$ctlData = array();
$ctlData['pdt_submission_template']['general_button'] = TRUE;
list($buttons,$revertscripts) = PWForms\Utils::TemplateActions($formdata,$id,$ctlData);
$jsfuncs[] = $revertscripts[0];
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
foreach (array(
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
if ($formdata->Fields) {
	foreach ($formdata->Fields as &$one) {
		if ($one->DisplayInSubmission()) {
			$oneset = new stdClass();
			$oneset->name = $one->GetVariableName().'} / {$fld_'.$one->GetId();
			$oneset->description = $this->Lang('field_named',$one->GetName());
			$formvars[] = $oneset;
		}
	}
	unset($one);
}
$tplvars['formvars'] = $formvars;

if ($formdata->Fields) {
	$fieldprops = array();
	foreach (array(
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
		if ($name != 'css_class')
			$oneset->description = $this->Lang('desc_'.$name);
		else
			$oneset->description = $this->Lang('desc_cssf_class'); //work around duplicate
		$fieldprops[] = $oneset;
	}
	$tplvars['fieldprops'] = $fieldprops;
}

$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));
$tplvars['save'] = $this->CreateInputSubmit($id,'submit',$this->Lang('save'));
$tplvars['apply'] = $this->CreateInputSubmit($id,'apply',$this->Lang('apply'),
	'title = "'.$this->Lang('save_and_continue').'" onclick="set_tab()"');

$hidden[] = $this->CreateInputHidden($id,'active_tab');
$jsfuncs[] = <<<EOS
function set_tab () {
 var active = $('#page_tabs > .active');
 $('#{$id}active_tab').val(active.attr('id'));
}
EOS;

$tplvars['hidden'] = implode(PHP_EOL,$hidden);

//help for submission-template
PWForms\Utils::SetupSubTemplateVarsHelp($formdata,$this,$tplvars);

//<script type="text/javascript" src="{$baseurl}/include/module.js"></script>
$jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/jquery.tablednd.min.js"></script>
EOS;

$jsloads[] = <<<EOS
 $('.updown').hide();
 $('.showhelp').hide();
 $('.reordermsg').show();
 $('.addslow').hide();
 $('.addfast').show();
 $('img.tipper').css({'display':'inline','padding-left':'10px'})
 .click(function() {
   $(this).parent().parent().find('.showhelp').slideToggle();
 });
 $('.tabledrag').tableDnD({
  dragClass: 'row1hover',
  onDrop: function(table,droprows) {
   var \$tbl = $(table),
    odd = true,
    oddclass = 'row1',
    evenclass = 'row2';
   \$tbl.find('tbody tr').each(function() {
    var name = odd ? oddclass : evenclass;
    if (this === droprows[0]) {
     name = name+'hover';
    }
    $(this).removeClass().addClass(name);
    odd = !odd;
   });
   \$tbl.parent().find('.saveordermsg').show();
  }
 }).find('tbody tr').removeAttr('onmouseover').removeAttr('onmouseout').mouseover(function() {
  var now = $(this).attr('class');
  $(this).attr('class', now+'hover');
 }).mouseout(function() {
  var now = $(this).attr('class');
  var to = now.indexOf('hover');
  $(this).attr('class', now.substring(0,to));
 });
EOS;
