<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$tab = $this->GetActiveTab($params);

$starts = $this->StartTabHeaders().
	$this->SetTabHeader('maintab',$this->Lang('forms'),($tab == 'maintab'));
if($pmod)
	$starts .= $this->SetTabHeader('import',$this->Lang('import'),($tab == 'import'));
if($padm)
	$starts .= $this->SetTabHeader('settings',$this->Lang('settings'),($tab == 'settings'));
$starts .= $this->EndTabHeaders().$this->StartTabContent();

$smarty->assign('tabs_start',$starts);
$smarty->assign('formstab_start',$this->StartTab('maintab'));
if($pmod)
	$smarty->assign('importstab_start',$this->StartTab('import'));
if($padm)
	$smarty->assign('settingstab_start',$this->StartTab('settings'));
$smarty->assign('tab_end',$this->EndTab());
$smarty->assign('tabs_end',$this->EndTabContent());
$smarty->assign('form_end',$this->CreateFormEnd());

$smarty->assign('message',(isset($params['message']))?$params['message']:'');

$theme = $gCms->variables['admintheme'];

//list all the extant forms
$allforms = pwfUtils::GetForms();
if($allforms)
{
	$smarty->assign('title_name',$this->Lang('title_form_name'));
	if($pdev)
		$smarty->assign('title_alias',$this->Lang('title_page_tag'));
	else
		$smarty->assign('title_alias',$this->Lang('title_alias'));

	if($pmod)
	{
		$iconedit = $theme->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon');
		$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$this->Lang('copy'),'','','systemicon');
		$icondelete = $theme->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon');
	}
	$iconexport = '<img src="'.$this->GetModuleURLPath().'/images/xml.gif" class="systemicon" title="'.$this->Lang('export').'" alt="'.$this->Lang('export_tip').'" />';
	if($pdev)
		$modname = $this->GetName();
	$data = array();
	foreach($allforms as $one)
	{
		$fid = (int)$one['form_id'];
		$oneset = new stdClass();
		if($pmod)
		{
			$oneset->name = $this->CreateLink($id,'update_form','',
				$one['name'],array('formedit'=>1,'form_id'=>$fid));
			$oneset->editlink = $this->CreateLink($id,'update_form','',
				$iconedit,array('formedit'=>1,'form_id'=>$fid));
			$oneset->copylink = $this->CreateLink($id,'add_form','',
				$iconcopy,array('form_id'=>$fid));
			$oneset->deletelink = $this->CreateLink($id,'delete_form','',
				$icondelete,array('form_id'=>$fid),
				$this->Lang('confirm_delete_form',$one['name']));
		}
		else
		{
			$oneset->name = $one['name'];
		}
		$oneset->alias = ($pdev) ?
			'{'.$modname.' form=\''.$one['alias'].'\'}' : $one['alias'];
		$oneset->exportlink = $this->CreateLink($id,'export_form','',
			$iconexport,array('form_id'=>$fid));
		$oneset->selected = $this->CreateInputCheckbox($id,'sel[]',$fid,-1);
		$data[] = $oneset;
	}
	$smarty->assign('forms',$data);

	if(count($data) > 1)
		$t = $this->CreateInputCheckbox($id,'item',TRUE,FALSE,'onclick="select_all(this);"');
	else
		$t = '';
	$smarty->assign('selectall_forms',$t);
	$smarty->assign('start_formsform',$this->CreateFormStart($id,'selected_forms',$returnid));
	$smarty->assign('exportbtn',$this->CreateInputSubmit($id,'export',$this->Lang('export'),
		'title="'.$this->Lang('tip_exportsel').'" onclick="return any_selected();"'));
	if($pmod)
	{
		$smarty->assign('clonebtn',$this->CreateInputSubmit($id,'clone',$this->Lang('copy'),
			'title="'.$this->Lang('tip_clonesel').'" onclick="return any_selected();"'));		
		$smarty->assign('deletebtn',$this->CreateInputSubmit($id,'delete',$this->Lang('delete'),
			'title="'.$this->Lang('tip_deletesel').'" onclick="return confirm_selected(\''.
			$this->Lang('confirm').'\');"'));		
	}
}
else
{
	$smarty->assign('noforms',$this->Lang('no_forms'));
}

if($pmod)
{
	$smarty->assign('addlink',$this->CreateLink($id,'add_form','',
		$theme->DisplayImage('icons/system/newobject.gif',$this->Lang('title_add_new_form'),'','','systemicon')));
	$smarty->assign('addform',$this->CreateLink($id,'add_form','',
		$this->Lang('title_add_new_form')));

	$xmls = array();

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_to_upload');
	$oneset->input = $this->CreateInputFile($id,'xmlfile','',25);
	$xmls[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_upload_formname');
	$oneset->input = $this->CreateInputText($id,'import_formname','',25);
	$oneset->help = $this->Lang('help_import_name');
	$xmls[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_upload_formalias');
	$oneset->input = $this->CreateInputText($id,'import_formalias','',25);
	$oneset->help = $this->Lang('help_import_alias');
	$xmls[] = $oneset;

	$smarty->assign('legend_xmlimport',$this->Lang('title_importxml_legend'));
	$smarty->assign('start_importxmlform',$this->CreateFormStart($id,'import_formfile',$returnid,'POST','multipart/form-data'));
	$smarty->assign('xmls',$xmls);
	$smarty->assign('submitxml', $this->CreateInputSubmit($id,'submitxml', $this->Lang('upload')));

	$ob = $this->GetModuleInstance('FormBuilder');
	if($ob)
	{
		unset($ob);
		$smarty->assign('legend_fbimport',$this->Lang('title_importfb_legend'));
		$smarty->assign('start_importfbform',$this->CreateFormStart($id,'import_formbuilder',$returnid));
		$smarty->assign('submitfb',$this->CreateInputSubmit($id,'import',
			$this->Lang('import_fb'),
			'title="'.$this->Lang('tip_import_fb').'"'));
		$pre = cms_db_prefix();
		$rs = $db->SelectLimit('SELECT trans_id FROM '.$pre.'module_pwf_trans',1);
		if($rs)
		{
			if(!$rs->EOF)
			{
				$rs->Close();
				$rs = $db->SelectLimit('SELECT * FROM '.$pre.'module_pwbr_browser',1);
				if($rs)
				{
					if(!$rs->EOF)
					{
						$smarty->assign('submitdata',$this->CreateInputSubmit($id,'conform',
							$this->Lang('import_browsedata'),
							'title="'.$this->Lang('tip_import_browsedata').'"'));
					}
					$rs->Close();
				}
			}
			else
				$rs->Close();
		}
	}
	$smarty->assign('pmod',1);
}
else
	$smarty->assign('pmod',0);

if($padm)
{
	$cfgs = array();

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_require_fieldnames');
	$oneset->input = $this->CreateInputCheckbox($id,'require_fieldnames',1,$this->GetPreference('require_fieldnames'));
	$oneset->help = $this->Lang('help_require_fieldnames');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_blank_invalid');
	$oneset->input = $this->CreateInputCheckbox($id,'blank_invalid',1,$this->GetPreference('blank_invalid'));
	$oneset->help = $this->Lang('help_blank_invalid');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_enable_antispam');
	$oneset->input = $this->CreateInputCheckbox($id,'enable_antispam',1,$this->GetPreference('enable_antispam'));
	$oneset->help = $this->Lang('help_enable_antispam');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_uploads_dir');
	$oneset->input = $this->CreateInputText($id,'uploads_dir',$this->GetPreference('uploads_dir'),40,80);
	$oneset->help = $this->Lang('help_uploads_dir');
	$cfgs[] = $oneset;

	$smarty->assign('configs',$cfgs);
	$smarty->assign('start_configform',$this->CreateFormStart($id,'defaultadmin',$returnid));
	$smarty->assign('submitcfg', $this->CreateInputSubmit($id,'submit',$this->Lang('save')));
	$smarty->assign('padm',1);
}
else
	$smarty->assign('padm',0);

$js = <<<EOS
function select_all(b) {
 var st = $(b).attr('checked');
 if(!st) st = false;
 $('input[name="{$id}sel[]"][type="checkbox"]').attr('checked',st);
}
function sel_count() {
 var cb = $('input[name="{$id}sel[]"]:checked');
 return cb.length;
}
function any_selected() {
 return (sel_count() > 0);
}
function confirm_selected(msg) {
 if(sel_count() > 0) {
  return confirm(msg);
 } else {
  return false;
 }
}

EOS;

$smarty->assign('jsfuncs',$js);

?>
