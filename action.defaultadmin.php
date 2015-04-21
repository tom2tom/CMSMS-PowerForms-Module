<?php
/*
   FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
   More info at http://dev.cmsmadesimple.org/projects/formbuilder

   A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
   This project's homepage is: http://www.cmsmadesimple.org
*/

	//list all the extant forms.
	$forms = $this->GetForms();
	$num_forms = count($forms);

	$smarty->assign('tabheaders', $this->StartTabHeaders() .
		$this->SetTabHeader('forms',$this->Lang('forms')) .
		$this->SetTabHeader('config',$this->Lang('configuration')) .
		$this->EndTabHeaders().
		$this->StartTabContent());
	$smarty->assign('start_formtab',$this->StartTab("forms"));
	$smarty->assign('start_configtab',$this->StartTab("config"));
	$smarty->assign('end_tab',$this->EndTab());
	$smarty->assign('end_tabs',$this->EndTabContent());
	$smarty->assign('title_form_name',$this->Lang('title_form_name'));
	$smarty->assign('title_form_alias',$this->Lang('title_form_alias'));
	$smarty->assign('start_configform',$this->CreateFormStart($id,
		'admin_update_config', $returnid));
	$smarty->assign('message', isset($params['fbrp_message'])?$params['fbrp_message']:'');

	$formArray = array();
	$currow = "row1";
	foreach ($forms as $thisForm)
	{
		$oneset = new stdClass();
		$oneset->rowclass = $currow;
		if ($this->CheckPermission('Modify Forms'))
		{
			$oneset->name = $this->CreateLink($id, 'admin_add_edit_form', '',
				$thisForm['name'], array('form_id'=>$thisForm['form_id']));
			$oneset->xmllink = $this->CreateLink($id, 'exportxml','',
				'<img src="'.$config['root_url'].'/modules/'.$this->GetName().'/images/xml_rss.gif" class="systemicon" title="'.$this->Lang('export').'" alt="Export Form as XML" />',
				array('form_id'=>$thisForm['form_id']));
			$oneset->editlink = $this->CreateLink($id, 'admin_add_edit_form', '',
				$gCms->variables['admintheme']->DisplayImage('icons/system/edit.gif',$this->Lang('edit'),'','','systemicon'),
					array('form_id'=>$thisForm['form_id']));
			$oneset->copylink = $this->CreateLink($id, 'admin_copy_form', '',
				$gCms->variables['admintheme']->DisplayImage('icons/system/copy.gif',$this->Lang('copy'),'','','systemicon'),
					array('form_id'=>$thisForm['form_id']));
			$oneset->deletelink = $this->CreateLink($id, 'admin_delete_form', '',
				$gCms->variables['admintheme']->DisplayImage('icons/system/delete.gif',$this->Lang('delete'),'','','systemicon'),
				array('form_id'=>$thisForm['form_id']),
				$this->Lang('are_you_sure_delete_form',$thisForm['name']));
		}
		else
		{
			$oneset->name=$thisForm['name'];
			$oneset->xmllink = '';
			$oneset->editlink = '';
			$oneset->copylink = '';
			$oneset->deletelink = '';
		}
		$oneset->usage = $thisForm['alias'];
		$formArray[] = $oneset;
		($currow == "row1"?$currow="row2":$currow="row1");
		}
	if ($this->CheckPermission('Modify Forms'))
	{
		$smarty->assign('addlink',$this->CreateLink($id,
			'admin_add_edit_form', '',
			$gCms->variables['admintheme']->DisplayImage('icons/system/newobject.gif', $this->Lang('title_add_new_form'),'',
				'','systemicon'), array()));
		$smarty->assign('addform',$this->CreateLink($id,
			'admin_add_edit_form', '', $this->Lang('title_add_new_form'),
			array()));
		$smarty->assign('may_config',1);
	}
	else
	{
		$smarty->assign('no_permission',$this->Lang('lackpermission'));
	}

	$smarty->assign('title_hide_errors',$this->Lang('title_hide_errors'));
	$smarty->assign('input_hide_errors',$this->CreateInputCheckbox($id, 'fbrp_hide_errors', 1, $this->GetPreference('hide_errors','0')). $this->Lang('title_hide_errors_long'));

	$smarty->assign('title_relaxed_email_regex',$this->Lang('title_relaxed_email_regex'));
	$smarty->assign('input_relaxed_email_regex',$this->CreateInputCheckbox($id, 'fbrp_relaxed_email_regex', 1, $this->GetPreference('relaxed_email_regex','0')). $this->Lang('title_relaxed_regex_long'));

	$smarty->assign('title_enable_fastadd',$this->Lang('title_enable_fastadd'));
	$smarty->assign('input_enable_fastadd',$this->CreateInputCheckbox($id, 'fbrp_enable_fastadd', 1, $this->GetPreference('enable_fastadd','1')). $this->Lang('title_enable_fastadd_long'));

	$smarty->assign('title_require_fieldnames',$this->Lang('title_require_fieldnames'));
	$smarty->assign('input_require_fieldnames',$this->CreateInputCheckbox($id, 'fbrp_require_fieldnames', 1, $this->GetPreference('require_fieldnames','1')). $this->Lang('title_require_fieldnames_long'));

	$smarty->assign('title_unique_fieldnames',$this->Lang('title_unique_fieldnames'));
	$smarty->assign('input_unique_fieldnames',$this->CreateInputCheckbox($id, 'fbrp_unique_fieldnames', 1, $this->GetPreference('unique_fieldnames','1')). $this->Lang('title_unique_fieldnames_long'));

	$smarty->assign('title_enable_antispam',$this->Lang('title_enable_antispam'));
	$smarty->assign('input_enable_antispam',$this->CreateInputCheckbox($id, 'fbrp_enable_antispam', 1, $this->GetPreference('enable_antispam','1')). $this->Lang('title_enable_antispam_long'));

	$smarty->assign('title_show_fieldids',$this->Lang('title_show_fieldids'));
	$smarty->assign('input_show_fieldids',
		$this->CreateInputcheckbox($id,'fbrp_show_fieldids',1,
		$this->GetPreference('show_fieldids','0')). $this->Lang('title_show_fieldids_long'));

	$smarty->assign('title_show_fieldaliases',$this->Lang('title_show_fieldaliases'));
	$smarty->assign('input_show_fieldaliases',
		$this->CreateInputcheckbox($id,'fbrp_show_fieldaliases',1,
		$this->GetPreference('show_fieldaliases','1')). $this->Lang('title_show_fieldaliases_long'));

	$smarty->assign('title_show_version',$this->Lang('title_show_version'));
	$smarty->assign('input_show_version',$this->CreateInputCheckbox($id, 'fbrp_show_version', 1, $this->GetPreference('show_version','1')). $this->Lang('title_show_version_long'));

	$smarty->assign('title_blank_invalid',$this->Lang('title_blank_invalid'));
	$smarty->assign('input_blank_invalid',$this->CreateInputCheckbox($id,
      'fbrp_blank_invalid', 1, $this->GetPreference('blank_invalid','0')).
      $this->Lang('title_blank_invalid_long'));

	$smarty->assign('submit', $this->CreateInputSubmit($id, 'fbrp_submit', $this->Lang('save')));
	$smarty->assign('end_configform',$this->CreateFormEnd());

	$smarty->assign('start_xmlform',$this->CreateFormStart($id, 'importxml', $returnid, 'post','multipart/form-data'));
	$smarty->assign('submitxml', $this->CreateInputSubmit($id, 'fbrp_submit', $this->Lang('upload')));
	$smarty->assign('end_xmlform',$this->CreateFormEnd());

	$smarty->assign('input_xml_to_upload',$this->CreateInputFile($id, 'fbrp_xmlfile', '', 25));
	$smarty->assign('title_xml_to_upload',$this->Lang('title_xml_to_upload'));
	$smarty->assign('title_xml_upload_formname',$this->Lang('title_xml_upload_formname'));
	$smarty->assign('input_xml_upload_formname',
	      $this->CreateInputText($id,'fbrp_import_formname','',25));
	$smarty->assign('title_xml_upload_formalias',$this->Lang('title_xml_upload_formalias'));
	$smarty->assign('input_xml_upload_formalias',
	      $this->CreateInputText($id,'fbrp_import_formalias','',25));
	$smarty->assign('info_leaveempty',$this->Lang('help_leaveempty'));
	$smarty->assign('legend_xml_import',$this->Lang('title_import_legend'));

	$smarty->assign_by_ref('forms', $formArray);
	echo $this->ProcessTemplate('AdminMain.tpl');
?>
