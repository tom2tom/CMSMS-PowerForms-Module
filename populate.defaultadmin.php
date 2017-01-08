<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$tab = $this->_GetActiveTab($params);

$starts = $this->StartTabHeaders().
	$this->SetTabHeader('maintab', $this->Lang('forms'), ($tab == 'maintab'));
if ($pmod) {
	$starts .= $this->SetTabHeader('import', $this->Lang('import'), ($tab == 'import'));
}
if ($padm) {
	$starts .= $this->SetTabHeader('settings', $this->Lang('settings'), ($tab == 'settings'));
}
$starts .= $this->EndTabHeaders().$this->StartTabContent();

$tplvars['tabs_start'] = $starts;
if ($pmod) {
	$tplvars['importstab_start'] = $this->StartTab('import');
}
if ($padm) {
	$tplvars['settingstab_start'] = $this->StartTab('settings');
}

$tplvars = $tplvars + array(
	'formstab_start' => $this->StartTab('maintab'),
	'form_end' => $this->CreateFormEnd(),
	'tabs_end' => $this->EndTabContent(),
	'tab_end' => $this->EndTab(), //CMSMS 2+ can't cope if this is before EndTabContent() !!
);
$tplvars['message'] = (isset($params['message']))?$params['message']:'';

$theme = ($this->before20) ? cmsms()->variables['admintheme']:
	cms_utils::get_theme_object();
//script accumulators
$jsincs = array();
$jsfuncs = array();
$jsloads = array();
$baseurl = $this->GetModuleURLPath();

//list all the extant forms
$allforms = PWForms\Utils::GetForms();
if ($allforms) {
	$tplvars['title_name'] = $this->Lang('title_form_name');
	$tplvars['title_alias'] = ($pdev) ? $this->Lang('title_page_tag'):
		$this->Lang('title_alias');

	if ($pmod) {
		$iconedit = $theme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'), '', '', 'systemicon');
		$iconcopy = $theme->DisplayImage('icons/system/copy.gif', $this->Lang('copy'), '', '', 'systemicon');
		$icondelete = $theme->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
	}
	$iconexport = '<img src="'.$this->GetModuleURLPath().'/images/xml.gif" class="systemicon" title="'.$this->Lang('export').'" alt="'.$this->Lang('export_tip').'" />';
	if ($pdev) {
		$modname = $this->GetName();
	}
	$data = array();
	foreach ($allforms as $one) {
		$fid = (int)$one['form_id'];
		$oneset = new stdClass();
		if ($pmod) {
			$oneset->name = $this->CreateLink($id, 'open_form', '',
				$one['name'], array('form_id'=>$fid)); //no datakey
			$oneset->edit = $this->CreateLink($id, 'open_form', '',
				$iconedit, array('form_id'=>$fid));
			$oneset->copy = $this->CreateLink($id, 'add_form', '',
				$iconcopy, array('form_id'=>$fid));
			$oneset->delete = $this->CreateLink($id, 'delete_form', '',
				$icondelete, array('form_id'=>$fid),
				$this->Lang('confirm_delete_form', $one['name']));
		} else {
			$oneset->name = $one['name'];
		}
		$oneset->alias = ($pdev) ?
			'{'.$modname.' form=\''.$one['alias'].'\'}' : $one['alias'];
		$oneset->exportlink = $this->CreateLink($id, 'export_form', '',
			$iconexport, array('form_id'=>$fid));
		$oneset->select = $this->CreateInputCheckbox($id, 'selected[]', $fid, -1);
		$data[] = $oneset;
	}
	$tplvars['forms'] = $data;

	if (count($data) > 1) {
		$t = $this->CreateInputCheckbox($id, 'item', TRUE, FALSE, 'onclick="select_all(this);"');
	} else {
		$t = '';
	}
	$tplvars['selectall_forms'] = $t;
	$tplvars['start_formsform'] = $this->CreateFormStart($id, 'selected_forms', $returnid);
	$tplvars['exportbtn'] = $this->CreateInputSubmit($id, 'export', $this->Lang('export'),
		'title="'.$this->Lang('tip_exportsel').'" onclick="return any_selected();"');
	if ($pmod) {
		$tplvars['clonebtn'] = $this->CreateInputSubmit($id, 'clone', $this->Lang('copy'),
			'title="'.$this->Lang('tip_clonesel').'" onclick="return any_selected();"');
		$tplvars['deletebtn'] = $this->CreateInputSubmit($id, 'delete', $this->Lang('delete'),
			'title="'.$this->Lang('tip_deletesel').
			'" onclick="return confirm_selected(\''.$this->Lang('confirm').'\');"');
	}
} else {
	$tplvars['noforms'] = $this->Lang('no_forms');
}

if ($pmod) {
	$tplvars['addlink'] = $this->CreateLink($id, 'add_form', '',
		$theme->DisplayImage('icons/system/newobject.gif', $this->Lang('title_add_new_form'), '', '', 'systemicon'));
	$tplvars['addform'] = $this->CreateLink($id, 'add_form', '',
		$this->Lang('title_add_new_form'));

	$xmls = array();

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_to_upload');
	$oneset->input = $this->CreateInputFile($id, 'xmlfile', '', 25);
	$xmls[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_upload_formname');
	$oneset->input = $this->CreateInputText($id, 'import_formname', '', 25);
	$oneset->help = $this->Lang('help_import_name');
	$xmls[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_xml_upload_formalias');
	$oneset->input = $this->CreateInputText($id, 'import_formalias', '', 25);
	$oneset->help = $this->Lang('help_import_alias');
	$xmls[] = $oneset;

	$tplvars = $tplvars + array(
		'legend_xmlimport' => $this->Lang('title_importxml_legend'),
		'start_importxmlform' => $this->CreateFormStart($id, 'import_formfile', $returnid, 'POST', 'multipart/form-data'),
		'xmls' => $xmls,
		'submitxml' => $this->CreateInputSubmit($id, 'submitxml', $this->Lang('upload'))
	);

	$ob = cms_utils::get_module('FormBuilder');
	if ($ob) {
		unset($ob);
		$tplvars['legend_fbimport'] = $this->Lang('title_importfb_legend');
		$tplvars['start_importfbform'] = $this->CreateFormStart($id, 'import_formbuilder', $returnid);
		$tplvars['submitfb'] = $this->CreateInputSubmit($id, 'import',
			$this->Lang('import_fb'),
			'title="'.$this->Lang('tip_import_fb').'"');
		$pre = cms_db_prefix();
		$rs = $db->SelectLimit('SELECT trans_id FROM '.$pre.'module_pwf_trans', 1);
		if ($rs) {
			if (!$rs->EOF) {
				$rs->Close();
				$rs = $db->SelectLimit('SELECT * FROM '.$pre.'module_pwbr_browser', 1);
				if ($rs) {
					if (!$rs->EOF) {
						$tplvars['submitdata'] = $this->CreateInputSubmit($id, 'conform',
							$this->Lang('import_browsedata'),
							'title="'.$this->Lang('tip_import_browsedata').'"');
					}
					$rs->Close();
				}
			} else {
				$rs->Close();
			}
		}
	}
	$tplvars['pmod'] = 1;
} else {
	$tplvars['pmod'] = 0;
}

if ($padm) {
	$cfgs = array();

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_require_fieldnames');
	$oneset->input = $this->CreateInputCheckbox($id, 'require_fieldnames', 1, $this->GetPreference('require_fieldnames'));
	$oneset->help = $this->Lang('help_require_fieldnames');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_blank_invalid');
	$oneset->input = $this->CreateInputCheckbox($id, 'blank_invalid', 1, $this->GetPreference('blank_invalid'));
	$oneset->help = $this->Lang('help_blank_invalid');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_submit_limit');
	$oneset->input = $this->CreateInputText($id, 'submit_limit', $this->GetPreference('submit_limit'), 3, 5);
	$oneset->help = $this->Lang('help_submit_limit');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_email_topdomains');
	$oneset->input = $this->CreateInputText($id, 'email_topdomains', $this->GetPreference('email_topdomains'), 40, 80);
	$oneset->help = $this->Lang('help_email_topdomains');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_email_domains');
	$oneset->input = $this->CreateInputText($id, 'email_domains', $this->GetPreference('email_domains'), 40, 80);
	$oneset->help = $this->Lang('help_email_domains');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_email_subdomains');
	$oneset->input = $this->CreateInputText($id, 'email_subdomains', $this->GetPreference('email_subdomains'), 40, 80);
	$oneset->help = $this->Lang('help_email_subdomains');
	$cfgs[] = $oneset;

	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_uploads_dir');
	$oneset->input = $this->CreateInputText($id, 'uploads_dir', $this->GetPreference('uploads_dir'), 40, 80);
	$oneset->help = $this->Lang('help_uploads_dir');
	$cfgs[] = $oneset;

	$t = $this->GetPreference('masterpass');
	if ($t) {
		$t = PWForms\Utils::Unfusc($t);
	}
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_password');
	$oneset->input = $this->CreateTextArea(FALSE, $id, $t, 'masterpass', 'cloaked',
		$id.'passwd', '', '', 50, 3);
	$cfgs[] = $oneset;

	$jsincs[] = '<script type="text/javascript" src="'.$baseurl.'/include/jquery-inputCloak.min.js"></script>';
	$jsloads[] = <<<EOS
 $('#{$id}passwd').inputCloak({
  type:'see4',
  symbol:'\u25CF'
 });
EOS;

	$tplvars = $tplvars + array(
		'configs' => $cfgs,
		'start_configform' => $this->CreateFormStart($id, 'defaultadmin', $returnid),
		'submitcfg' => $this->CreateInputSubmit($id, 'submit', $this->Lang('save')),
		'cancel' => $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')),
		'padm' => 1
	);
} else {
	$tplvars['padm'] = 0;
}

$jsfuncs[] = <<<EOS
function select_all(cb) {
 $('input[name="{$id}selected[]"][type="checkbox"]').attr('checked',cb.checked);
}
function sel_count() {
 var cb = $('input[name="{$id}selected[]"]:checked');
 return cb.length;
}
function any_selected() {
 return (sel_count() > 0);
}
function confirm_selected(msg) {
 if (sel_count() > 0) {
  return confirm(msg);
 } else {
  return false;
 }
}
EOS;
