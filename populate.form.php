<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!empty($message)) {
	$tplvars['message'] = $message;
}

$tplvars['backtomod_nav'] = $this->CreateLink($id, 'defaultadmin', '', '&#171; '.$this->Lang('back_top'));

//multipart form needed for file uploads
$tplvars['form_start'] = $this->CreateFormStart($id, 'open_form', $returnid,
	'POST', 'multipart/form-data', FALSE, '', array(
	'form_id'=>$form_id,
	'datakey'=>$params['datakey']));

$tab = $this->_GetActiveTab($params);
$t = $this->StartTabHeaders().
	$this->SetTabHeader('maintab', $this->Lang('tab_form'), ($tab == 'maintab')).
	$this->SetTabHeader('displaytab', $this->Lang('tab_display'), ($tab == 'displaytab'));
if ($form_id > 0) {
	$t .= $this->SetTabHeader('fieldstab', $this->Lang('tab_fields'), ($tab == 'fieldstab'));
}
$t .=
	$this->SetTabHeader('templatetab', $this->Lang('tab_templatelayout'), ($tab == 'templatetab')).
	$this->SetTabHeader('udttab', $this->Lang('tab_udt'), ($tab == 'udttab')).
	$this->SetTabHeader('submittab', $this->Lang('tab_submit'), ($tab == 'submittab')).
	$this->EndTabHeaders().$this->StartTabContent();
$tplvars['tabs_start'] = $t;
if ($form_id > 0) {
	$tplvars['fieldstab_start'] = $this->StartTab('fieldstab');
}
$tplvars = $tplvars + array(
	'tabs_end' => $this->EndTabContent(),
	'maintab_start' => $this->StartTab('maintab'),
	'displaytab_start' => $this->StartTab('displaytab'),
	'templatetab_start' => $this->StartTab('templatetab'),
	'udttab_start' => $this->StartTab('udttab'),
	'submittab_start' => $this->StartTab('submittab'),
	'tab_end' => $this->EndTab(),
	'form_end' => $this->CreateFormEnd(),

	'help_can_drag' => $this->Lang('help_can_drag'),
	'help_form_alias' => $this->Lang('help_form_alias'),
	'help_save_order' => $this->Lang('help_save_order'),
	'input_form_alias' => $this->CreateInputText($id, 'form_Alias', $formdata->Alias, 50), //NB object name = 'form_'.property-name
	'input_form_name' => $this->CreateInputText($id, 'form_Name', $formdata->Name, 50), //ditto

	'text_alias' => $this->Lang('title_field_alias_short'),
	'text_id' => $this->Lang('title_field_id'),
	'text_info' => $this->Lang('information'),
	'text_move' => $this->Lang('move'),
	'text_name' => $this->Lang('title_field_name'),
	'text_required' => $this->Lang('title_field_required_abbrev'),
	'text_type' => $this->Lang('title_field_type'),

	'title_form_alias' => $this->Lang('title_form_alias'),
	'title_form_dispositions' => $this->Lang('title_form_dispositions'),
	'title_form_fields' => $this->Lang('title_form_fields'),
	'title_form_main' => $this->Lang('title_form_main'),
	'title_form_name' => $this->Lang('title_form_name'),
	'title_form_status' => $this->Lang('title_form_status')
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
	$theme->DisplayImage('icons/system/info.gif', $this->Lang('help_help'), '', '', 'systemicon tipper');

$icontrue = $theme->DisplayImage('icons/system/true.gif', $this->Lang('true'), '', '', 'systemicon');
$iconfalse = $theme->DisplayImage('icons/system/false.gif', $this->Lang('false'), '', '', 'systemicon');
$iconedit = $theme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
$iconcopy = $theme->DisplayImage('icons/system/copy.gif', $this->Lang('copy'), '', '', 'systemicon');
$icondelete = $theme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
$iconup = $theme->DisplayImage('icons/system/arrow-u.gif', $this->Lang('moveup'), '', '', 'systemicon');
$icondown = $theme->DisplayImage('icons/system/arrow-d.gif', $this->Lang('movedn'), '', '', 'systemicon');

$fields = array(); //ordinary fields
$dispositions = array(); //disposition fields
$count = 1; //move-icon counters
$dcount = 1;
$total = count($formdata->Fields);
$dtotal = 0;
$etotal = 0;
if ($total > 0) {
	foreach ($formdata->Fields as $obfld) {
		if ($obfld->IsDisposition() && !$obfld->IsDisplayed()) {
			$dtotal++;
		}
	}
	$total -= $dtotal;
}

if (!isset($params['selectfields'])) { //first time
	$selfield = $this->GetPreference('adder_fields', 'basic');
	$seldisp = $selfield;
} else {
	$selfield = $params['selectfields'];
	$seldisp = $params['selectdispos'];
}

$hidden[] = $this->CreateInputHidden($id, 'selectfields', $selfield);
$hidden[] = $this->CreateInputHidden($id, 'selectdispos', $seldisp);

$linkargs = array(
'field_id'=>0,
'form_id'=>$form_id,
'datakey'=>$params['datakey'],
'active_tab'=>'fieldstab',
'selectfields'=>$selfield,
'selectdispos'=>$seldisp
);

foreach ($formdata->FieldOrders as $one) {
	$obfld = $formdata->Fields[$one];
	$oneset = new stdClass();
	$fid = (int)$obfld->GetId();
	$oneset->id = $fid;
	$t = $obfld->GetName();
	if (!$t) {
		$t = $this->Lang('none2');
	}
	$oneset->order = '<input type="hidden" name="'.$id.'form_FieldOrders[]" value="'.$fid.'" />';
	$linkargs['field_id'] = $fid;
	$oneset->name = $this->CreateLink($id, 'open_field', '', $t, $linkargs);
	$oneset->alias = $obfld->ForceAlias();
	$oneset->type = $obfld->GetDisplayType();
	$oneset->field_status = $obfld->GetSynopsis();
	$oneset->edit = $this->CreateLink($id, 'open_field', '', $iconedit,
		$linkargs);
	$oneset->copy = $this->CreateLink($id, 'open_form', '', $iconcopy,
		$linkargs + array('fieldcopy'=>1));
	$oneset->delete = $this->CreateLink($id, 'delete_field', '', $icondelete,
		$linkargs + array('fielddelete'=>1),
		'', '', '', 'onclick="delete_field(this,'.$fid.',\''.htmlspecialchars($obfld->GetName()).'\');return FALSE;"');
	$oneset->select = $this->CreateInputCheckbox($id, 'selected[]', $fid, -1);

	if ($obfld->IsDisposition() && !$obfld->IsDisplayed()) {
		if ($dcount > 1) {
			$oneset->up = $this->CreateLink($id, 'open_form', '',
			$iconup,
			array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'dir'=>'up'));
		} else {
			$oneset->up = '';
		}
		if ($dcount < $dtotal) {
			$oneset->down = $this->CreateLink($id, 'open_form', '',
			$icondown,
			array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'dir'=>'down'));
		} else {
			$oneset->down = '';
		}

		$dispositions[] = $oneset;
		$dcount++;
	} else {
		if (!$obfld->DisplayInForm() || !$obfld->GetChangeRequirement()) {
			$oneset->required = '';
		} elseif ($obfld->IsRequired()) {
			$oneset->required = $this->CreateLink($id, 'require_field', '',
				$icontrue,
				array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'reqd'=>'off'),
				'', '', '', 'class="true" onclick="require_field(this,'.$fid.',false);return false;"');
		} else {
			$oneset->required = $this->CreateLink($id, 'require_field', '',
				$iconfalse,
				array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'reqd'=>'on'),
				'', '', '', 'class="false" onclick="require_field(this,'.$fid.',true);return false;"');
		}

		if ($count > 1) {
			$oneset->up = $this->CreateLink($id, 'open_form', '',
			$iconup,
			array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'dir'=>'up'));
		} else {
			$oneset->up = '';
		}
		if ($count < $total) {
			$oneset->down = $this->CreateLink($id, 'open_form', '',
			$icondown,
			array('form_id'=>$form_id, 'datakey'=>$params['datakey'], 'field_id'=>$fid, 'dir'=>'down'));
		} else {
			$oneset->down = '';
		}

		$fields[] = $oneset;
		$count++;
	}
}

$tplvars['fields'] = $fields;
if ($fields) {
	$u = $this->create_url($id, 'require_field', '', array('datakey'=>$params['datakey'], 'reqd'=>'off', 'field_id'=>''));
	$offs = strpos($u, '?mact=');
	$u = str_replace('&amp;', '&', substr($u, $offs+1)); //field identifier will be appended at runtime
	$errmsg = $this->Lang('err_server');
	$jsfuncs[] = <<<EOS
function require_field(link,fid,newstate) {
 var udata = '{$u}'+fid;
 if (newstate) {
   udata = udata.replace('reqd=off','reqd=on');
 }
 $.ajax({
  type: 'POST',
  url: 'moduleinterface.php',
  data: udata,
  error: function() {
   alert('{$errmsg}');
  },
  success: function() {
   var \$l = $(link),
	url = \$l.attr('href'),
	js = \$l.attr('onclick');
   var newurl = (newstate) ? url.replace('reqd=on','reqd=off') : url.replace('reqd=off','reqd=on');
   var newjs = (newstate) ? js.replace(',true',',false') : js.replace(',false',',true');
   var img = (newstate) ? '{$icontrue}':'{$iconfalse}';
   \$l.attr('href',newurl).attr('onclick',newjs).html(img);
  }
 });
}
EOS;
} else { //no field
	$tplvars = $tplvars + array(
		'nofields' => $this->Lang('no_fields'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('no_fields')
	);
}

$tplvars['dispositions'] = $dispositions;
if ($dispositions) {
	$tplvars['text_ready'] = $this->Lang('title_ready');
} else {
	$tplvars = $tplvars + array(
		'nodispositions' => $this->Lang('no_dispositions'),
		'text_ready' => '',
		'text_notready' => $this->Lang('title_not_ready'),
		'help_notready' => $this->Lang('help_not_ready')
	);
}

if ($count || $dcount) {
	$tplvars['delete'] = $this->CreateInputSubmit($id, 'delete', $this->Lang('delete'),
		'title="'.$this->Lang('tip_delselfield').
		'" onclick="delete_selected(this,\''.$this->Lang('confirm').'\');return false;"');
	$prompt = $this->Lang('confirm_delete_field', '%s');
	$u = $this->create_url($id, 'delete_field', '', array('datakey'=>$params['datakey'], 'field_id'=>''));
	$offs = strpos($u, '?mact=');
	$u = str_replace('&amp;', '&', substr($u, $offs+1)); //field identifier will be appended at runtime
	$errmsg = $this->Lang('err_server');
	$jsfuncs[] = <<<EOS
function delete_field (link,fid,fname) {
 var message = '{$prompt}'.replace('%s',fname);
 if (confirm(message)) {
  $.ajax({
   type: 'POST',
   url: 'moduleinterface.php',
   data: '{$u}'+fid,
   error: function() {
	alert('{$errmsg}');
   },
   success: function() {
	var \$row = $(link).closest('tr');
	\$row.fadeOut('500', function() {
	 var odd = true,
	  oddclass = 'row1',
	  evenclass = 'row2',
	  \$bod = \$row.closest('tbody');
	 \$row.remove();
	 \$bod.find('tr').each(function() {
	  var \$r = $(this),
		name = odd ? oddclass : evenclass;
	  \$r.removeClass().addClass(name)
	  .removeAttr('onmouseover').mouseover(function() {
		\$r.attr('class',name+'hover');
	  }).removeAttr('onmouseout').mouseout(function() {
		\$r.attr('class',name);
	  });
	  odd = !odd;
	 });
	});
   }
  });
 }
}
function delete_selected(btn,prompt) {
 var \$sel = $(btn).closest('div[class^="add"]').prev('table').find('tbody input[name^="{$id}selected"]:checked');
 if (\$sel.length > 0) {
  if (confirm(prompt)) {
   var fids = \$sel.map(function() {
	return this.value;
   }).get();
   $.ajax({
	type: 'POST',
	url: 'moduleinterface.php',
	data: '{$u}'+fids,
	error: function() {
	 alert('{$errmsg}');
	},
	success: function() {
	 \$sel.each(function() {
	  $(this).closest('tr').remove();
	 });
	 var \$bod = $(btn).closest('div[class^="add"]').prev('table').find('tbody');
	  odd = true,
	  oddclass = 'row1',
	  evenclass = 'row2';
	 \$bod.find('tr').each(function() {
	  var \$r = $(this),
		name = odd ? oddclass : evenclass;
	  \$r.removeClass().addClass(name)
	  .removeAttr('onmouseover').mouseover(function() {
		\$r.attr('class',name+'hover');
	  }).removeAttr('onmouseout').mouseout(function() {
		\$r.attr('class',name);
	  });
	  odd = !odd;
	 });
	}
   });
  }
 }
}
EOS;
} else {
	$tplvars['delete'] = NULL;
}
if ($count > 1 || $dcount > 1) {
	$tplvars['selectall'] = $this->CreateInputCheckbox($id, 'selectall', 1, -1, 'onclick="select_all(this);"');
	$jsfuncs[] = <<<EOS
function select_all(cb) {
 var st = cb.checked;
 $(cb).closest('table').find('tbody input[type="checkbox"]').attr('checked',st);
}
EOS;
} else {
	$tplvars['selectall'] = NULL;
}

$basicfields = array($this->Lang('select_type')=>''); //non-disposition fields accumulator
$basicdispos = $basicfields; //disposition fields accumulator
$extendedfields = $basicfields; //extended non-disposition fields accumulator
$extendeddispos = $basicfields; //extended disposition fields accumulator

PWForms\Utils::Collect_Fields($this);
foreach ($this->std_field_types as $l=>$t) {
	$classPath = 'PWForms\\'.$t;
	$obfld = new $classPath($formdata, $params);
	if ($obfld->IsDisposition()) {
		if ($obfld->IsInput) {
			$basicfields[$l] = $t;
		} else {
			$basicdispos[$l] = $t;
		}
	} else {
		$basicfields[$l] = $t;
	}
}
unset($obfld);

foreach ($this->field_types as $l=>$t) {
	$classPath = 'PWForms\\'.$t;
	$obfld = new $classPath($formdata, $params);
	if ($obfld->IsDisposition()) {
		if ($obfld->IsInput) {
			$extendedfields[$l] = $t;
		} else {
			$extendeddispos[$l] = $t;
		}
	} else {
		$extendedfields[$l] = $t;
	}
}
unset($obfld);

//ordinary fields
$t = $this->Lang('title_add_new_field');
$tplvars['title_fieldpick'] = $t;
$linkargs['field_id'] = 0; //new-field indicator
$tplvars['add_field_link'] =
	$this->CreateLink($id, 'open_field', $returnid,
		$theme->DisplayImage('icons/system/newobject.gif', $t, '', '', 'systemicon'),
		$linkargs, '', FALSE).' '.
	$this->CreateLink($id, 'open_field', $returnid, $t,
		$linkargs, '', FALSE);
//selector
if ($selfield == 'basic') {
	$tplvars['input_fieldpick'] = $this->CreateInputDropdown($id, 'field_type',
		$basicfields, -1, '', 'onchange="add_field(this,\'form\');"');
	$tplvars['help_fieldpick'] = $this->CreateLink($id, 'open_form', $returnid,
		$this->Lang('title_switch_advanced_link'),
		array('selectfields'=>'advanced') + $linkargs);
} else { //advanced
	$tplvars['input_fieldpick'] = $this->CreateInputDropdown($id, 'field_type',
		$extendedfields, -1, '', 'onchange="add_field(this,\'form\');"');
	$tplvars['help_fieldpick'] = $this->CreateLink($id, 'open_form', $returnid,
		$this->Lang('title_switch_basic_link'),
		array('selectfields'=>'basic') + $linkargs);
}

//dispositions
$t = $this->Lang('title_add_new_disposition');
$tplvars['title_fieldpick2'] = $t;
$linkargs['active_tab'] = 'submittab';
$tplvars['add_disposition_link'] =
	$this->CreateLink($id, 'open_field', $returnid,
		$theme->DisplayImage('icons/system/newobject.gif', $t, '', '', 'systemicon'),
		$linkargs, '', FALSE).' '.
	$this->CreateLink($id, 'open_field', $returnid, $t,
		$linkargs, '', FALSE);
//selector
if ($seldisp == 'basic') {
	$tplvars['input_fieldpick2'] = $this->CreateInputDropdown($id, 'disposition_type',
		$basicdispos, -1, '', 'onchange="add_field(this,\'disposition\');"');
	$tplvars['help_fieldpick2'] = $this->CreateLink($id, 'open_form', $returnid,
		$this->Lang('title_switch_advanced_link'),
		array('selectdispos'=>'advanced') + $linkargs);
} else { //advanced
	$tplvars['input_fieldpick2'] = $this->CreateInputDropdown($id, 'disposition_type',
		$extendeddispos, -1, '', 'onchange="add_field(this,\'disposition\');"');
	$tplvars['help_fieldpick2'] = $this->CreateLink($id, 'open_form', $returnid,
		$this->Lang('title_switch_basic_link'),
		array('selectdispos'=>'basic') + $linkargs);
}

//js to add selected field
$link = $this->CreateLink($id, 'open_field', $returnid, '', $linkargs, '', TRUE, TRUE);
$link = str_replace('&amp;', '&', $link);
$jsfuncs[] = <<<EOS
function add_field(sel,scope) {
 var type=sel.options[sel.selectedIndex].value;
 this.location='{$link}&{$id}field_pick='+type+'&{$id}in='+scope;
 return true;
}
EOS;

//====== DISPLAY TAB
$displays = array();

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_required_symbol');
$oneset->input = $this->CreateInputText($id, 'fp_required_field_symbol',
	PWForms\Utils::GetFormProperty($formdata, 'required_field_symbol', '*'), 3);
$displays[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_submit_button');
$oneset->input = $this->CreateInputText($id, 'fp_submit_button_text',
	PWForms\Utils::GetFormProperty($formdata, 'submit_button_text', $this->Lang('button_submit')), 30);
$displays[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_next_button');
$oneset->input = $this->CreateInputText($id, 'fp_next_button_text',
	PWForms\Utils::GetFormProperty($formdata, 'next_button_text', $this->Lang('button_continue')), 30);
$oneset->help = $this->Lang('help_form_button');
$displays[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_prev_button');
$oneset->input = $this->CreateInputText($id, 'fp_prev_button_text',
	PWForms\Utils::GetFormProperty($formdata, 'prev_button_text', $this->Lang('button_previous')), 30);
$oneset->help = $this->Lang('help_form_button');
$displays[] = $oneset;

if (extension_loaded('GD')) { //for verification of uploaded file
	$t = PWForms\Utils::GetFormProperty($formdata, 'help_icon', '');
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_form_help_icon');
	$oneset->input = $this->CreateInputText($id, 'fp_help_icon', $t, 30);
	$oneset->help = $this->Lang('help_form_help_icon');
	$displays[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_upload_help_icon');
	$oneset->input = $this->CreateInputFile($id, 'iconupload', 'image/*', 36,
		'id="'.$id.'iconupload" title="'.$this->Lang('tip_upload').'" onchange="file_selected()"');
	if ($t) {
		$oneset->input .= ' '.$this->CreateInputCheckbox($id, 'icondelete', 1, -1).'&nbsp;'.$this->Lang('delete_upload', $t);
	}
	$displays[] = $oneset;
}

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_css_class');
$oneset->input = $this->CreateInputText($id, 'fp_css_class',
	PWForms\Utils::GetFormProperty($formdata, 'css_class', 'powerform'), 30);
$displays[] = $oneset;

$t = PWForms\Utils::GetFormProperty($formdata, 'css_file', '');
$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_css_file');
$oneset->input = $this->CreateInputText($id, 'fp_css_file', $t, 30);
$oneset->help = $this->Lang('help_form_css_file');
$displays[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_upload_css_file');
$oneset->input = $this->CreateInputFile($id, 'stylesupload', 'text/css', 36,
	'id="'.$id.'stylesupload" title="'.$this->Lang('tip_upload').'" onchange="file_selected()"');
if ($t) {
	$oneset->input .= ' '.$this->CreateInputCheckbox($id, 'stylesdelete', 1, -1).'&nbsp;'.$this->Lang('delete_upload', $t);
}
$displays[] = $oneset;

$tplvars['displays'] = $displays;

$jsfuncs[] = <<<EOS
function file_selected() {
//TODO
}
EOS;

//====== TEMPLATE TAB

$templateList = array($this->Lang('select_one')=>'',
	$this->Lang('default_template')=>'defaultform.tpl',
	$this->Lang('table_left_template')=>'tableform_lefttitles.tpl',
	$this->Lang('table_top_template')=>'tableform_toptitles.tpl');

$allForms = PWForms\Utils::GetForms();
foreach ($allForms as $one) {
	if ($one['form_id'] != $form_id) {
		$templateList[$this->Lang('form_template_name', $one['name'])] = $one['form_id'];
	}
}

$tplvars['title_load_template'] = $this->Lang('title_load_template');
$tplvars['input_load_template'] = $this->CreateInputDropdown($id, 'template_load',
	$templateList, -1, '', 'id="template_load" onchange="get_template(this);"'); //overwrites downstream-generated id

$prompt = $this->Lang('confirm_template');
$msg = $this->Lang('err_server');
$u = $this->create_url($id, 'get_template', '', array('tid'=>''));
$offs = strpos($u, '?mact=');
$u = str_replace('&amp;', '&', substr($u, $offs+1)); //template identifier will be appended at runtime

$jsfuncs[] = <<<EOS
function get_template (sel) {
 if (confirm('{$prompt}')) {
  var value = $(sel).val();
  if (value) {
   var msg = '{$msg}';
   $.ajax({
	type: 'POST',
	url: 'moduleinterface.php',
	data: '{$u}'+value,
	dataType: 'text',
	success: function(data,status) {
	 if (status=='success') {
	  $('#form_template').val(data);
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
}
EOS;

if ($this->before20) {
	$tpl = $this->GetTemplate('pwf_'.$form_id);
} else {
	//	CmsLayoutTemplate::get_designs() TODO
//	CmsLayoutTemplate::set_designs()
	$ob = CmsLayoutTemplate::load('pwf_'.$form_id);
	$tpl = $ob->get_content();
}

$tplvars = $tplvars + array(
	'title_variable' => $this->Lang('variable'),
	'title_property' => $this->Lang('property'),
	'title_description' => $this->Lang('description'),
	'title_tplvars' => $this->Lang('title_tpl_vars'),
	'help_tplvars' => $this->Lang('help_tpl_vars'),
	'help_fieldvars1' => $this->Lang('help_fieldvars1'),
	'help_fieldvars2' => $this->Lang('help_fieldvars2')
);

$tplvars['title_form_template'] = $this->Lang('title_form_template');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_form_template'] = $this->CreateSyntaxArea($id, $tpl, 'fp_form_template',
	'pwf_tallarea', 'form_template', '', '', 50, 24, 'style="height:30em;"'); //xtra-tall!

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
	) as $name) {
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
			$oneset->description = $this->Lang('field_named', $one->GetName());
			$formvars[] = $oneset;
		}
	}
	unset($one);

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
		) as $name) {
		$oneset = new stdClass();
		$oneset->name = $name;
		if ($name != 'css_class') {
			$oneset->description = $this->Lang('desc_'.$name);
		} else {
			$oneset->description = $this->Lang('desc_cssf_class');
		} //work around duplicate
		$fieldprops[] = $oneset;
	}
	$tplvars['fieldprops'] = $fieldprops;
}
$tplvars['formvars'] = $formvars;

//====== UDT TAB

$usertagops = cmsms()->GetUserTagOperations();
$usertags = $usertagops->ListUserTags();
$usertaglist = array();
$usertaglist[$this->Lang('none2')] = '';
foreach ($usertags as $key => $value) {
	$usertaglist[$value] = $key;
}

$tplvars['title_form_predisplay_udt'] = $this->Lang('title_form_predisplay_udt');
$tplvars['input_form_predisplay_udt'] =
	$this->CreateInputDropdown($id, 'fp_predisplay_udt', $usertaglist, -1,
		PWForms\Utils::GetFormProperty($formdata, 'predisplay_udt'));

$tplvars['title_form_predisplay_each_udt'] = $this->Lang('title_form_predisplay_each_udt');
$tplvars['input_form_predisplay_each_udt'] =
	$this->CreateInputDropdown($id, 'fp_predisplay_each_udt', $usertaglist, -1,
		PWForms\Utils::GetFormProperty($formdata, 'predisplay_each_udt'));

$tplvars['title_form_validate_udt'] = $this->Lang('title_form_validate_udt');
$tplvars['input_form_validate_udt'] =
	$this->CreateInputDropdown($id, 'fp_validate_udt', $usertaglist, -1,
		PWForms\Utils::GetFormProperty($formdata, 'validate_udt'));

$tplvars['help_udt'] = $this->Lang('help_udt');

//====== PROCESSING TAB

$submits = array();

$oneset = new stdClass();
$oneset->title = $this->Lang('title_list_delimiter');
$oneset->input = $this->CreateInputText($id, 'fp_list_delimiter',
	PWForms\Utils::GetFormProperty($formdata, 'list_delimiter', ','), 3);
$submits[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_blank_invalid');
$oneset->input = $this->CreateInputHidden($id, 'fp_blank_invalid', 0).
	$this->CreateInputCheckbox($id, 'fp_blank_invalid', 1,
	PWForms\Utils::GetFormProperty($formdata, 'blank_invalid', $this->GetPreference('blank_invalid')));
$oneset->help = $this->Lang('help_blank_invalid');
$submits[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_form_unspecified');
$oneset->input = $this->CreateInputText($id, 'fp_unspecified',
	PWForms\Utils::GetFormProperty($formdata, 'unspecified', $this->Lang('unspecified')), 30);
$submits[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_submit_limit');
$oneset->input = $this->CreateInputText($id, 'fp_submit_limit',
	PWForms\Utils::GetFormProperty($formdata, 'submit_limit', $this->GetPreference('submit_limit')), 3, 5);
$oneset->help = $this->Lang('help_limit_count');
$submits[] = $oneset;

//no scope for !empty() checks for boolean attrs, so we add hidden 0 for checkboxes
$oneset = new stdClass();
$oneset->title = $this->Lang('title_submit_button_safety');
$oneset->input = $this->CreateInputHidden($id, 'fp_input_button_safety', 0).
	$this->CreateInputCheckbox($id, 'fp_input_button_safety', 1,
	PWForms\Utils::GetFormProperty($formdata, 'input_button_safety', 0));
$oneset->help = $this->Lang('help_submit_safety');
$submits[] = $oneset;

$oneset = new stdClass();
$oneset->title = $this->Lang('title_submit_javascript');
$oneset->input = $this->CreateTextArea(FALSE, $id,
	PWForms\Utils::GetFormProperty($formdata, 'submit_javascript', ''),
	'fp_submit_javascript', 'pwf_shortarea', 'submit_javascript', '', '', 50, 8);
$oneset->help = $this->Lang('help_submit_javascript');
$submits[] = $oneset;

$tplvars['presubmits'] = $submits;

$submits = array();

$oneset = new stdClass();
$oneset->title = $this->Lang('title_inline_form');
$oneset->input = $this->CreateInputHidden($id, 'fp_inline', 0).
	$this->CreateInputCheckbox($id, 'fp_inline', 1,
	PWForms\Utils::GetFormProperty($formdata, 'inline', 0));
$oneset->help = $this->Lang('help_inline_form');
$submits[] = $oneset;

$tplvars['postsubmits'] = $submits;

$choices = array($this->Lang('redirect_to_page')=>'redir',$this->Lang('display_text')=>'text');
$tplvars['title_submit_action'] = $this->Lang('title_submit_action');
$tplvars['input_submit_action'] =
	$this->CreateInputRadioGroup($id, 'fp_submit_action', $choices,
		PWForms\Utils::GetFormProperty($formdata, 'submit_action', 'text'), '', '&nbsp;&nbsp;');

$tplvars['title_redirect_page'] = $this->Lang('title_redirect_page');
$tplvars['input_redirect_page'] =
	PWForms\Utils::CreateHierarchyPulldown($this, $id, 'fp_redirect_page',
		PWForms\Utils::GetFormProperty($formdata, 'redirect_page', 0));

if ($this->before20) {
	$tpl = $this->GetTemplate('pwf_sub_'.$form_id);
} else {
	$ob = CmsLayoutTemplate::load('pwf_sub_'.$form_id);
	$tpl = $ob->get_content();
}
if (!$tpl) {
	$tpl = PWForms\Utils::CreateDefaultTemplate($formdata, TRUE, FALSE);
} //? generate default for CmsLayoutTemplateType
$tplvars['title_submit_template'] = $this->Lang('title_submit_response');
//note WYSIWYG is no good, the MCE editor stuffs around with the template contents
$tplvars['input_submit_template'] = $this->CreateSyntaxArea($id, $tpl, 'fp_submission_template',
	'pwf_tallarea', 'submission_template', '', '', 50, 15);
//setup to revert to 'sample' submission-template
$ctlData = array();
$ctlData['fp_submission_template']['general_button'] = TRUE;
list($buttons, $revertscripts) = PWForms\Utils::TemplateActions($formdata, $id, $ctlData);
$jsfuncs[] = $revertscripts[0];
$tplvars = $tplvars + array(
	'sample_submit_template' => $buttons[0],
	'help_submit_template' => $this->Lang('help_submit_template'),
);

$tplvars['cancel'] = $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'));
$tplvars['save'] = $this->CreateInputSubmit($id, 'submit', $this->Lang('save'));
$tplvars['apply'] = $this->CreateInputSubmit($id, 'apply', $this->Lang('apply'),
	'title = "'.$this->Lang('save_and_continue').'" onclick="set_tab()"');

$hidden[] = $this->CreateInputHidden($id, 'active_tab');
$jsfuncs[] = <<<EOS
function set_tab () {
 var active = $('#page_tabs > .active');
 $('#{$id}active_tab').val(active.attr('id'));
}
EOS;

$tplvars['hidden'] = implode(PHP_EOL, $hidden);

//help for submission-template
PWForms\Utils::SetupSubTemplateVarsHelp($formdata, $this, $tplvars);

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
 if($('input[name="{$id}pf_submit_action"]:checked').val() == 'redir') {
  $('#tplobjects').hide();
 } else {
  $('#pageobjects').hide();
 }
 $('input[name="{$id}pf_submit_action"]').change(function() {
  if($(this).val() == 'redir') {
   $('#tplobjects').hide();
   $('#pageobjects').show();
  } else {
   $('#pageobjects').hide();
   $('#tplobjects').show();
  }
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
