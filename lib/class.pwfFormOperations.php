<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFormOperations
{
	/**
	AddEdit:
	Setup and display 
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@id:
	@returnid:
	@tab: string, 'maintab' etc, identifier of the focused tab
	@message: string (optional), message to display on the main tab
	*/
	function AddEdit(&$mod,$form_id,$id,$returnid,$tab,$message='')
	{
		$gCms = cmsms();
		$config = $gCms->GetConfig();
		$theme = $gCms->variables['admintheme'];
		$smarty = $gCms->GetSmarty();
TODO		$formdata = $mod->GetFormData($params); LOAD FILE IF EXISTS

		if(!empty($message))
			$smarty->assign('message',$mod->ShowMessage($message));

		$smarty->assign('backtomod_nav', $mod->CreateLink($id, 'defaultadmin', '', $mod->Lang('back_top'), array()));

		$smarty->assign('formid',$mod->CreateInputHidden($id,'form_id',$form_id));
		$smarty->assign('formstart',$mod->CreateFormStart($id,'store_form',$returnid));
		$smarty->assign('tabs_start',$mod->StartTabHeaders().
			$mod->SetTabHeader('maintab',$mod->Lang('tab_main'),($tab == 'maintab')).
			$mod->SetTabHeader('fieldstab',$mod->Lang('tab_fields'),($tab == 'fieldstab')).
			$mod->SetTabHeader('designtab',$mod->Lang('tab_design'),($tab == 'designtab')).
			$mod->SetTabHeader('templatetab',$mod->Lang('tab_templatelayout'),($tab == 'templatetab')).
			$mod->SetTabHeader('udttab',$mod->Lang('tab_udt'),($tab == 'udttab')).
			$mod->SetTabHeader('submittab',$mod->Lang('tab_submit'),($tab == 'submittab')).
			$mod->SetTabHeader('submittpltab',$mod->Lang('tab_submissiontemplate'),($tab == 'submittpltab')).
			$mod->EndTabHeaders().$mod->StartTabContent());

		$smarty->assign('tabs_end',$mod->EndTabContent());
		$smarty->assign('maintab_start',$mod->StartTab('maintab'));
		$smarty->assign('fieldstab_start',$mod->StartTab('fieldstab'));
		$smarty->assign('designtab_start',$mod->StartTab('designtab'));
		$smarty->assign('templatetab_start',$mod->StartTab('templatetab'));
		$smarty->assign('udttab_start',$mod->StartTab('udttab'));
		$smarty->assign('submittab_start',$mod->StartTab('submittab'));
		$smarty->assign('submittemplatetab_start',$mod->StartTab('submittpltab'));
		$smarty->assign('tab_end',$mod->EndTab());
		$smarty->assign('form_end',$mod->CreateFormEnd());

		$smarty->assign('title_form_name',$mod->Lang('title_form_name'));
		$smarty->assign('input_form_name',
			$mod->CreateInputText($id,'form_name',$formdata->Name,50));

		$smarty->assign('title_load_template',$mod->Lang('title_load_template'));
		$smarty->assign('security_key',CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);

		$templateList = array(''=>'',
			$mod->Lang('default_template')=>'RenderFormDefault.tpl',
			$mod->Lang('table_left_template')=>'RenderFormTableTitleLeft.tpl',
			$mod->Lang('table_top_template')=>'RenderFormTableTitleTop.tpl');

		$allForms = pwfUtils::GetForms();
		foreach($allForms as $one)
		{
			if($one['form_id'] != $form_id)
				$templateList[$mod->Lang('form_template_name',$one['name'])] = $one['form_id'];
		}

		$modLink = $mod->CreateLink($id,'get_template',$returnid,'',array(),'',true);
		$smarty->assign('input_load_template',$mod->CreateInputDropdown($id,'template_load',
			$templateList, -1, '', 'id="template_load" onchange="get_template(\''.$mod->Lang('confirm_template').'\',\''.$modLink.'\');"'));

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
			$oneset->description = $mod->Lang('desc_'.$name);
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
			$oneset->description = $mod->Lang('desc_'.$name);
			$attrs[] = $oneset;
		}
		$smarty->assign('attrs',$attrs);

		$smarty->assign('variable', $mod->Lang('variable'));
		$smarty->assign('attribute', $mod->Lang('attribute'));
		$smarty->assign('description', $mod->Lang('description'));
		$smarty->assign('help_globals', $mod->Lang('help_globals'));
		$smarty->assign('help_attrs1', $mod->Lang('help_attrs1'));
		$smarty->assign('help_attrs2', $mod->Lang('help_attrs2'));

		$smarty->assign('title_form_unspecified',$mod->Lang('title_form_unspecified'));
		$smarty->assign('input_form_unspecified',
			$mod->CreateInputText($id, 'forma_unspecified',
				self::GetAttr($formdata,'unspecified',$mod->Lang('unspecified')),30));
		$smarty->assign('title_form_status', $mod->Lang('title_form_status'));
		$smarty->assign('text_ready', $mod->Lang('title_ready_for_deployment'));
		$smarty->assign('title_form_alias',$mod->Lang('title_form_alias'));
		$smarty->assign('input_form_alias',
		$mod->CreateInputText($id,'form_alias',$formdata->Alias,50));
		$smarty->assign('title_form_css_class',$mod->Lang('title_form_css_class'));
		$smarty->assign('input_form_css_class',
			 $mod->CreateInputText($id, 'forma_css_class',
			 	self::GetAttr($formdata,'css_class','formbuilderform'),50,50));
		$smarty->assign('title_form_fields', $mod->Lang('title_form_fields'));
		$smarty->assign('title_form_main', $mod->Lang('title_form_main'));
		$t = ($mod->GetPreference('show_fieldids')) ? $mod->Lang('title_field_id') : '';
		$smarty->assign('title_field_id',$t);
		$t = ($mod->GetPreference('show_fieldaliases')) ? $mod->Lang('title_field_alias_short') : '';
		$smarty->assign('title_field_alias',$t);
		$smarty->assign('title_field_name', $mod->Lang('title_field_name'));
		$smarty->assign('title_field_type', $mod->Lang('title_field_type'));
		$smarty->assign('title_field_type', $mod->Lang('title_field_type'));
		$smarty->assign('title_form_template', $mod->Lang('title_form_template'));
		$smarty->assign('title_form_vars', $mod->Lang('title_form_vars'));
		$smarty->assign('title_list_delimiter', $mod->Lang('title_list_delimiter'));
		$smarty->assign('title_redirect_page', $mod->Lang('title_redirect_page'));
		$smarty->assign('title_submit_action', $mod->Lang('title_submit_action'));
		$smarty->assign('title_submit_template', $mod->Lang('title_submit_response'));
		$smarty->assign('help_can_drag', $mod->Lang('help_can_drag'));
		$smarty->assign('title_must_save_order', $mod->Lang('title_must_save_order'));
		$smarty->assign('title_inline_form', $mod->Lang('title_inline_form'));
		$smarty->assign('title_submit_actions', $mod->Lang('title_submit_actions'));
		$smarty->assign('title_submit_labels', $mod->Lang('title_submit_labels'));
		$smarty->assign('title_submit_javascript', $mod->Lang('title_submit_javascript'));
		$smarty->assign('title_submit_help',$mod->Lang('title_submit_help'));
		$smarty->assign('title_submit_template_help',$mod->Lang('title_submit_response_help'));

		$smarty->assign('icon_info',
			$theme->DisplayImage('icons/system/info.gif',$mod->Lang('info'),'','','systemicon'));
		$submitActions = array($mod->Lang('display_text')=>'text',
			 $mod->Lang('redirect_to_page')=>'redir');
		$smarty->assign('input_submit_action',
		$mod->CreateInputRadioGroup($id, 'forma_submit_action', $submitActions,
			self::GetAttr($formdata,'submit_action','text'), '', '&nbsp;&nbsp;'));

		$captcha = $mod->getModuleInstance('Captcha');
		if($captcha == null)
		{
			 $smarty->assign('title_install_captcha',$mod->Lang('help_captcha_not_installed'));
			 $smarty->assign('captcha_installed',0);
		}
		else
		{
			 $smarty->assign('title_use_captcha',$mod->Lang('title_use_captcha'));
			 $smarty->assign('captcha_installed',1);

			 $smarty->assign('input_use_captcha',$mod->CreateInputHidden($id,'forma_use_captcha','0').
					$mod->CreateInputCheckbox($id,'forma_use_captcha','1',
					self::GetAttr($formdata,'use_captcha','0')).
					$mod->Lang('title_use_captcha_help'));
		}
		$smarty->assign('title_information',$mod->Lang('information'));
		$smarty->assign('title_order',$mod->Lang('order'));
		$smarty->assign('title_field_required_abbrev',$mod->Lang('title_field_required_abbrev'));
TODO		$smarty->assign('hasdisposition',$this->HasDisposition()?1:0);
		$maxOrder = 1;
		if($form_id > 0)
		{
			$smarty->assign('hidden',
				$mod->CreateInputHidden($id,'form_op',$mod->Lang('updated')).
				$mod->CreateInputHidden($id,'sort_order').
				$mod->CreateInputHidden($id,'active_tab'));
			$smarty->assign('adding',0);
			$smarty->assign('save', $mod->CreateInputSubmit($id, 'submit', $mod->Lang('save')));
			$smarty->assign('apply', $mod->CreateInputSubmit($id, 'submit', $mod->Lang('apply'),
					'title = "'.$mod->Lang('save_and_continue').'" onclick="set_tab()"'));
			$fieldList = array();
			$jsfuncs = array();
			$count = 1;
TODO			$last = $this->GetFieldCount();
			
			$icontrue = $theme->DisplayImage('icons/system/true.gif',$mod->Lang('true'),'','','systemicon');
			$iconfalse = $theme->DisplayImage('icons/system/false.gif',$mod->Lang('false'),'','','systemicon');
			$iconedit = $theme->DisplayImage('icons/system/edit.gif',$mod->Lang('edit'),'','','systemicon');
			$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$mod->Lang('copy'),'','','systemicon');
			$icondelete = $theme->DisplayImage('icons/system/delete.gif',$mod->Lang('delete'),'','','systemicon');
			$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$mod->Lang('moveup'),'','','systemicon');
			$icondown = $theme->DisplayImage('icons/system/arrow-d.gif',$mod->Lang('movedn'),'','','systemicon');

			foreach($formdata->Fields as &$fld)
			{
				$oneset = new stdClass();
				$oneset->name = $mod->CreateLink($id,'add_edit_field','',$fld->GetName(),array('field_id'=>$fld->GetId(),'form_id'=>$form_id));
				if($mod->GetPreference('show_fieldids',0) != 0)
				{
					$oneset->id = $mod->CreateLink($id,'add_edit_field','',$fld->GetId(),array('field_id'=>$fld->GetId(),'form_id'=>$form_id));
				}
				$oneset->type = $fld->GetDisplayType();
				$oneset->alias = $fld->GetAlias();
				$oneset->id = $fld->GetID();

				if(!$fld->DisplayInForm() || $fld->IsNonRequirableField())
				{
					$oneset->disposition = '';
					$no_avail = $mod->Lang('not_available');
				}
				else if($fld->IsRequired())
				{
					$oneset->disposition = $mod->CreateLink($id,'update_field_required','',
						$icontrue, array('form_id'=>$form_id,'active'=>'off','field_id'=>$fld->GetId()),'','','',
						'class="true" onclick="update_field_required(); return false;"');
				}
				else
				{
					$oneset->disposition = $mod->CreateLink($id,'update_field_required','',
						$iconfalse, array('form_id'=>$form_id,'active'=>'on','field_id'=>$fld->GetId()),'','','',
						'class="false" onclick="update_field_required(); return false;"');
				}

				$oneset->field_status = $fld->StatusInfo();
				$oneset->editlink = $mod->CreateLink($id,'add_edit_field','',$iconedit,array('field_id'=>$fld->GetId(),'form_id'=>$form_id));
				$oneset->copylink = $mod->CreateLink($id,'copy_field','',$iconcopy,array('field_id'=>$fld->GetId(),'form_id'=>$form_id));
				$oneset->deletelink = $mod->CreateLink($id,'delete_field','',$icondelete,array('field_id'=>$fld->GetId(),'form_id'=>$form_id),'','','',
					'onclick="delete_field(\''.$mod->Lang('confirm_delete_field',htmlspecialchars($fld->GetName())).'\'); return false;"');

				if($count > 1)
				{
					$oneset->up = $mod->CreateLink($id,'update_field_order','',$iconup,array('form_id'=>$form_id,'dir'=>'up','field_id'=>$fld->GetId()));
				}
				else
				{
					$oneset->up = '';
				}
				if($count < $last)
				{
					$oneset->down=$mod->CreateLink($id,'update_field_order','',$icondown,array('form_id'=>$form_id,'dir'=>'down','field_id'=>$fld->GetId()));
				}
				else
				{
					$oneset->down = '';
				}

				$count++;
				if($fld->GetOrder() >= $maxOrder)
				{
					$maxOrder = $fld->GetOrder() + 1;
				}
				$fieldList[] = $oneset;
			}
			unset ($fld);

			$smarty->assign('fields',$fieldList);
			$smarty->assign('add_field_link',
				$mod->CreateLink($id, 'add_edit_field', $returnid,
					$theme->DisplayImage('icons/system/newobject.gif',$mod->Lang('title_add_new_field'),'','','systemicon'),
					array('form_id'=>$form_id, 'order_by'=>$maxOrder), '', false).' '.
					$mod->CreateLink($id, 'add_edit_field', $returnid,$mod->Lang('title_add_new_field'),array('form_id'=>$form_id, 'order_by'=>$maxOrder), '', false));

			if($mod->GetPreference('enable_fastadd',1) == 1)
			{
				$smarty->assign('fastadd',1);
				$smarty->assign('title_fastadd',$mod->Lang('title_fastadd'));
				$link = $mod->CreateLink($id,'add_edit_field',$returnid,'',
					array('form_id'=>$form_id, 'order_by'=>$maxOrder),'',true,true);
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
				$mod->initialize();
				if($mod->GetPreference('show_field_level','basic') == 'basic')
				{
					$smarty->assign('input_fastadd',$mod->CreateInputDropdown($id, 'field_type',
					array_merge(array($mod->Lang('select_type')=>''),$mod->std_field_types), -1,'', 'onchange="fast_add(this)"').
						$mod->Lang('title_switch_advanced').
						$mod->CreateLink($id,'add_edit_form',$returnid,$mod->Lang('title_switch_advanced_link'),
						array('form_id'=>$form_id, 'set_field_level'=>'advanced')));
				}
				else
				{
					$smarty->assign('input_fastadd',$mod->CreateInputDropdown($id, 'field_type',
					array_merge(array($mod->Lang('select_type')=>''),$mod->field_types), -1,'', 'onchange="fast_add(this)"').
						$mod->Lang('title_switch_basic').
						$mod->CreateLink($id,'add_edit_form',$returnid,$mod->Lang('title_switch_basic_link'),
						array('form_id'=>$form_id, 'set_field_level'=>'basic')));
				}
			}
		}
		else
		{
			$smarty->assign('hidden',
				$mod->CreateInputHidden($id,'form_op',$mod->Lang('added')).
				$mod->CreateInputHidden($id,'sort_order').
				$mod->CreateInputHidden($id,'active_tab'));
			$smarty->assign('adding',1);
			$smarty->assign('save','');
			$smarty->assign('apply',
					$mod->CreateInputSubmit($id, 'submit', $mod->Lang('add')));
		}
		$smarty->assign('cancel', $mod->CreateInputSubmit($id, 'cancel', $mod->Lang('cancel')));

		$smarty->assign('link_notready','<strong>'.$mod->Lang('title_not_ready1').'</strong> '.
			$mod->Lang('title_not_ready2').' '.
			$mod->CreateLink($id,'add_edit_field',$returnid,$mod->Lang('title_not_ready_link'),
			array('form_id'=>$form_id,'order_by'=>$maxOrder,'dispose_only'=>1),
			'', false, false,'class="pwf_link"').' '.
			$mod->Lang('title_not_ready3')
		);

		$smarty->assign('input_inline_form',$mod->CreateInputHidden($id,'forma_inline','0').
			$mod->CreateInputCheckbox($id,'forma_inline','1',self::GetAttr($formdata,'inline','0')).
				$mod->Lang('title_inline_form_help'));

		$smarty->assign('title_form_submit_button',$mod->Lang('title_form_submit_button'));
		$smarty->assign('input_form_submit_button',
			$mod->CreateInputText($id, 'forma_submit_button_text',
				self::GetAttr($formdata,'submit_button_text',$mod->Lang('button_submit')), 35, 35));
		$smarty->assign('title_submit_button_safety',$mod->Lang('title_submit_button_safety_help'));
		$smarty->assign('input_submit_button_safety',
			$mod->CreateInputHidden($id,'forma_input_button_safety','0').
			$mod->CreateInputCheckbox($id,'forma_input_button_safety','1',self::GetAttr($formdata,'input_button_safety','0')).
			$mod->Lang('title_submit_button_safety'));
		$smarty->assign('title_form_prev_button',$mod->Lang('title_form_prev_button'));
		$smarty->assign('input_form_prev_button',
			$mod->CreateInputText($id, 'forma_prev_button_text',
				self::GetAttr($formdata,'prev_button_text',$mod->Lang('button_previous')), 35, 35));

		$smarty->assign('input_title_user_captcha',
			$mod->CreateInputText($id, 'forma_title_user_captcha',
				self::GetAttr($formdata,'title_user_captcha',$mod->Lang('title_user_captcha')),50,80));
		$smarty->assign('title_title_user_captcha',$mod->Lang('title_title_user_captcha'));

		$smarty->assign('input_title_user_captcha_error',
			$mod->CreateInputText($id, 'forma_captcha_wrong',
				self::GetAttr($formdata,'captcha_wrong',$mod->Lang('wrong_captcha')),50,80));
		$smarty->assign('title_user_captcha_error',$mod->Lang('title_user_captcha_error'));

		$smarty->assign('title_form_next_button', $mod->Lang('title_form_next_button'));
		$smarty->assign('input_form_next_button',
			$mod->CreateInputText($id, 'forma_next_button_text',
				self::GetAttr($formdata,'next_button_text',$mod->Lang('button_continue')), 35, 35));
		$smarty->assign('title_form_predisplay_udt',$mod->Lang('title_form_predisplay_udt'));
		$smarty->assign('title_form_predisplay_each_udt',$mod->Lang('title_form_predisplay_each_udt'));

		$usertagops = $gCms->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		$usertaglist[$mod->lang('none')] = -1;
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;
		$smarty->assign('input_form_predisplay_udt',
			$mod->CreateInputDropdown($id,'forma_predisplay_udt',$usertaglist,-1,
				self::GetAttr($formdata,'predisplay_udt',-1)));
		$smarty->assign('input_form_predisplay_each_udt',
			$mod->CreateInputDropdown($id,'forma_predisplay_each_udt',$usertaglist,-1,
				self::GetAttr($formdata,'predisplay_each_udt',-1)));

		$smarty->assign('title_form_validate_udt',$mod->Lang('title_form_validate_udt'));
		$usertagops = $gCms->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		$usertaglist[$mod->lang('none')] = -1;
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;
		$smarty->assign('input_form_validate_udt',
			$mod->CreateInputDropdown($id,'forma_validate_udt',$usertaglist,-1,
				self::GetAttr($formdata,'validate_udt',-1)));

		$smarty->assign('title_form_required_symbol',$mod->Lang('title_form_required_symbol'));
		$smarty->assign('input_form_required_symbol',
			 $mod->CreateInputText($id, 'forma_required_field_symbol',
				self::GetAttr($formdata,'required_field_symbol','*'), 5));
		$smarty->assign('input_list_delimiter',
			$mod->CreateInputText($id, 'forma_list_delimiter',
				self::GetAttr($formdata,'list_delimiter',','), 5));

		$contentops = $gCms->GetContentOperations();
		$smarty->assign('input_redirect_page',$contentops->CreateHierarchyDropdown('',self::GetAttr($formdata,'redirect_page','0'), $id.'forma_redirect_page'));

		$smarty->assign('input_form_template',
			$mod->CreateTextArea(false, $id,
TODO				self::GetAttr($formdata,'form_template',$this->DefaultTemplate()),
				'forma_form_template',
				'pwf_tallarea',
				'form_template',
				'', '', 80, 15));

		$smarty->assign('input_submit_javascript',
			$mod->CreateTextArea(false, $id,
				self::GetAttr($formdata,'submit_javascript',''), 'forma_submit_javascript','pwf_shortarea','submit_javascript',
				'', '', 80, 15,'','').
				'<br />'.$mod->Lang('title_submit_javascript_long'));

		$attr_name = 'submission_template';
		$smarty->assign('input_submit_template',
			 $mod->CreateTextArea(false, $id,
TODO				self::GetAttr($formdata,$attr_name,$this->createSampleTemplate(true,false)),
				'forma_'.$attr_name,
				'pwf_tallarea',
				'', '', '', 80, 15));

		self::SetupVarsHelp($mod,$smarty);

		$parms = array();
		$parms[$attr_name]['general_button'] = true;
		list ($popfuncs, $buttons) = self::AdminTemplateActions($id,$parms);

		$smarty->assign('incpath',$mod->GetModuleURLPath().'/include/');
		$smarty->assign('jsfuncs',array_merge($jsfuncs,$popfuncs));
		$smarty->assign('buttons',$buttons);

		return $mod->ProcessTemplate('AddEditForm.tpl');
	}

	/**
	Delete:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	*/
	function Delete(&$mod,$form_id)
	{
TODO		$formdata = $mod->GetFormData($params); LOAD
		$noparms = array();
		self::Load($mod,$form_id,$noparms,true);
		foreach($formdata->Fields as &$fld)
		{
			$fld->Delete();
		}
		unset ($fld);
		$mod->DeleteTemplate('pwf_'.$form_id);
		$pref = cms_db_prefix();
		$sql = 'DELETE FROM '. $pref.'module_pwf_form where form_id=?';
		$db = $mod->dbHandle;
		if(!$db->Execute($sql,array($form_id)))
			return false;
		$sql = 'DELETE FROM '.$pref.'module_pwf_form_attr where form_id=?';
		$res = $db->Execute($sql,array($form_id));
		return ($res != false);
	}

	/**
	Copy:
	Copy and store entire form
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	*/
	function Copy(&$mod,$form_id,&$params)
	{
TODO		$formdata = ;
		if($formdata->loaded != 'full')
		{
			$noparms = array();
			self::Load($mod,$form_id,$noparms,true);
		}

		$pref = cms_db_prefix();
		$db = $mod->dbHandle;
		$sql = 'INSERT INTO '.$pref.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
		$newform = $db->GenID($pref.'module_pwf_form_seq');
		$name = self::GetName($params);
		if($name)
			$name .= ' '.$mod->Lang('copy');
		$alias = $formdata->Alias;
		if($alias)
TODO			$alias .= '_'.$this->MakeAlias($mod->Lang('copy'), true);
		$db->Execute($sql, array($newform,$name,$alias));

		$res = true;
		$order = 1;
		foreach($formda->Fields as &$fld)
		{
TODO			if(!$this->CopyField(intval($fld->GetId()), $newform, $order))
				$res = false;
			$order++;
		}
		unset($fld);
		return $res;
	}

	/**
	Store:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	*/
	function Store(&$mod,$form_id,&$params)
	{
		// For new form, check for duplicate name and/or alias
		if($form_id == -1 && !self::NewID($mod,$params['form_name'],$params['form_alias']))
		{
			$params['message'] = $mod->Lang('duplicate_identifier');
			return false;
		}

		$db = $mod->dbHandle;
		$pref = cms_db_prefix();
		// Check if new or old form
		if($form_id == -1)
		{
			$form_id = $db->GenID($pref.'module_pwf_form_seq');
			$sql = 'INSERT INTO '.$pref.'module_pwf_form (form_id, name, alias) VALUES (?,?,?)';
			$res = $db->Execute($sql, array($form_id,$formdata->Name,$formdata->Alias));
		}
		else
		{
			$sql = 'UPDATE '.$pref.'module_pwf_form SET name=?,alias=? WHERE form_id=?';
			$res = $db->Execute($sql, array($formdata->Name, $formdata->Alias, $form_id));
		}
		if($res == false)
		{
			$params['message'] = $mod->Lang('database_error');
			return false;
		}

		// Save out the attrs
		$sql = 'DELETE FROM '.$pref.'module_pwf_form_attr WHERE form_id=?';
		if($db->Execute($sql,array($form_id)) == false)
		{
			$params['message'] = $mod->Lang('database_error');
			return false;
		}

		foreach($formdata->Attrs as $thisAttrKey=>$thisAttrValue)
		{
			$formAttrId = $db->GenID($pref.'module_pwf_form_attr_seq');
			$sql = 'INSERT INTO '.$pref.'module_pwf_form_attr (form_attr_id, form_id, name, value) VALUES (?,?,?,?)';
			if($db->Execute($sql, array($formAttrId, $form_id, $thisAttrKey, $thisAttrValue)) != false)
			{
				if($thisAttrKey == 'form_template')
					$mod->SetTemplate('pwf_'.$form_id,$thisAttrValue);
			}
			else
			{
				$params['message'] = $mod->Lang('database_error');
				return false;
			}
		}

		// Update field position
		$order_list = false;
		if(isset($params['sort_order']))
		{
			$order_list = explode(',',$params['sort_order']);
		}

		if(is_array($order_list) && count($order_list) > 0)
		{
			$count = 1;
			$sql = 'UPDATE '.$pref.'module_pwf_field SET order_by=? WHERE field_id=?';

			foreach($order_list as $onefldid)
			{
				$fieldid = substr($onefldid,5); //CHECKME
				if($db->Execute($sql, array($count, $fieldid)) != false)
					$count++;
				else
				{
					$params['message'] = $mod->Lang('database_error');
					return false;
				}
			}
		}

		// Reload everything
		self::Load($mod,$form_id,$params,true);
		return true;
	}

	/**
	Load:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	@loadDeep: optional boolean, default false
	@loadResp: optional boolean, default false
	*/
	function Load(&$mod,$form_id,&$params,$loadDeep=false,$loadResp=false)
	{
TODO $formdata = ;
		$db = $mod->dbHandle;
		$pref = cms_db_prefix();
//		error_log("entering Form Load with usage ".memory_get_usage());
		$sql = 'SELECT * FROM '.$pref.'module_pwf_form WHERE form_id=?';
		$result = $db->GetRow($sql, array($form_id));
		if($result)
		{
			$formdata->Id = $result['form_id'];
			if(empty($params['form_name']))
				$formdata->Name = $result['name'];

			if(empty($params['form_alias']))
				$formdata->Alias = $result['alias'];
		}
		else
		{
			return false;
		}

		$sql = 'SELECT name,value FROM '.$pref.'module_pwf_form_attr WHERE form_id=?';
		$formdata->Attrs = $db->GetAssoc($sql, array($form_id));
		$formdata->loaded = 'summary';

		if(isset($params['response_id']))
		{
			$loadDeep = true;
			$loadResp = true;
		}

		if($loadDeep)
		{
			if($loadResp)
			{
				// if it's a stored form, load the results -- but we need to manually merge them,
				// since $params[] should override the database value (say we're resubmitting a form)
				$obfield = $mod->GetFormBrowserField($form_id);
				if($obfield != false)
				{
					// if we're binding to FEU, get the FEU ID, see if there's a response for
					// that user. If so, load it. Otherwise, bring up an empty form.
					if($obfield->GetOption('feu_bind','0')=='1')
					{
						$feu = $mod->GetModuleInstance('FrontEndUsers');
						if($feu == false)
						{
							debug_display("FAILED to instatiate FEU!");
							return;
						}
						if(!isset($_COOKIE['cms_admin_user_id']))
						{
							$response_id = $mod->GetResponseIDFromFEUID($feu->LoggedInId(), $form_id);
							if($response_id !== false)
							{
								$check = $db->GetOne('SELECT count(*) FROM '.$pref.
									'module_pwf_browse WHERE browser_id=?',array($response_id));
								if($check == 1)
								{
									$params['response_id'] = $response_id;
								}
							}
						}
					}
				}
				if(isset($params['response_id']))
				{
					$loadParams = array('response_id'=>$params['response_id']);
					$loadTypes = array();
TODO					$this->LoadResponseValues($loadParams, $loadTypes);
					foreach($loadParams as $thisParamKey=>$thisParamValue)
					{
						if(!isset($params[$thisParamKey]))
						{
							if($formdata->FormState == 'update' && $loadTypes[$thisParamKey] == 'CheckboxField')
							{
								$params[$thisParamKey] = '';
							}
							else
							{
								$params[$thisParamKey] = $thisParamValue;
							}
						}
					}
				}
			}
			$sql = 'SELECT * FROM '.$pref.'module_pwf_field WHERE form_id=? ORDER BY order_by';
			$result = $db->GetArray($sql, array($form_id));
/*			$result = array();
			if($rs && $rs->RecordCount() > 0)
			{
				$result = $rs->GetArray();
			}
*/
			if($result)
			{
TODO				$funcs = new pwfFieldOperations($this,$params,false);
				foreach($result as &$fldArray)
				{
					//error_log("Instantiating Field. usage ".memory_get_usage());
					$className = pwfUtils::MakeClassName($fldArray['type']);
					// create the field object
					if((isset($fldArray['field_id']) && (isset($params['pwfp__'.$fldArray['field_id']]) || isset($params['pwfp___'.$fldArray['field_id']]))) ||
						(isset($fldArray['field_id']) && isset($params['value_'.$fldArray['name']])) || (isset($fldArray['field_id']) && isset($params['value_fld'.$fldArray['field_id']])) ||
						(isset($params['field_id']) && isset($fldArray['field_id']) && $params['field_id'] == $fldArray['field_id']))
					{
						$fldArray = array_merge($fldArray,$params);
					}

					$fld = $funcs->NewField($mod,$fldArray);
					$formdata->Fields[] = $fld;
					if($fld->Type == 'PageBreakField')
					{
						$formdata->FormTotalPages++;
					}
				}
				unset ($fldArray);
			}
			$formdata->loaded = 'full';
		} //end of $loadDeep

		return true;
	}

	/**
	ExportXML:
	@form_id: enumerator of form to be processed
	@exportValues: optional boolean, whether to ... , default = false
	*/
	function ExportXML($form_id,$exportValues = false)
	{
TODO $formdata = ;
		$xmlstr = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xmlstr .= "<form id=\"".$form_id."\"\n";
		$xmlstr .= "\talias=\"".$formdata->Alias."\">\n";
		$xmlstr .= "\t\t<form_name><![CDATA[".$formdata->Name."]]></form_name>\n";
		foreach($formdata->Attrs as $thisAttrKey=>$thisAttrValue)
			$xmlstr .= "\t\t<attribute key=\"$thisAttrKey\"><![CDATA[$thisAttrValue]]></attribute>\n";

		foreach($formdata->Fields as &$fld)
			$xmlstr .= $fld->ExportXML($exportValues);

		unset($fld);
		$xmlstr .= "</form>\n";
		return $xmlstr;
	}

	private function inXML(&$var)
	{
		return (isset($var) && strlen($var) > 0);
	}

	/**
	ImportXML:
	@mod: reference to the current PowerForms module object
	@params:
	notable params:
	  xml_file -- source file for the XML
	  xml_string -- source string for the XML
	*/
	function ImportXML(&$mod,&$params)
	{
		// xml_parser_create, xml_parse_into_struct
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0); // was 1
		if(!empty($params['xml_file']))
		{
			xml_parse_into_struct($parser, file_get_contents($params['xml_file']), $values);
		}
		elseif(!empty($params['xml_string']))
		{
			xml_parse_into_struct($parser, $params['xml_string'], $values);
		}
		else
		{
			return false;
		}
		xml_parser_free($parser);
		$elements = array();
		$stack = array();
		$fieldMap = array();
		foreach($values as $tag)
		{
			$index = count($elements);
			if($tag['type'] == 'complete' || $tag['type'] == 'open')
			{
				$elements[$index] = array();
				$elements[$index]['name'] = $tag['tag'];
				$elements[$index]['attributes'] = empty($tag['attributes']) ? "" : $tag['attributes'];
				$elements[$index]['content']	= empty($tag['value']) ? "" : $tag['value'];
				if($tag['type'] == 'open')
				{
					# push
					$elements[$index]['children'] = array();
					$stack[count($stack)] = &$elements;
					$elements = &$elements[$index]['children'];
				}
			}
			if($tag['type'] == 'close')
			{	# pop
				$elements = &$stack[count($stack) - 1];
				unset($stack[count($stack) - 1]);
			}
		}
		//debug_display($elements);
		if(!isset($elements[0]) || !isset($elements[0]) || !isset($elements[0]['attributes']))
		{
			//parsing failed, or invalid file.
			return false;
		}
		$params['form_id'] = -1; // override any form_id values that may be around
		$formAttrs = &$elements[0]['attributes'];

		$formdata = $mod->GetFormData($params);

		if(!empty($params['import_formalias']))
			$formdata->Alias = $params['import_formalias'];
		else if(self::inXML($formAttrs['alias']))
			$formdata->Alias = $formAttrs['alias'];

		if(!empty($params['import_formname']))
			$formdata->Name = $params['import_formname'];

		$foundfields = false;
		// populate the attributes and field name first. When we see a field, we save the form and then start adding the fields to it.

		foreach($elements[0]['children'] as $thisChild)
		{
			if($thisChild['name'] == 'form_name')
			{
				$curname = self::GetName($params);
				if(empty($curname))
					$formdata->Name = $thisChild['content'];
			}
			elseif($thisChild['name'] == 'attribute')
			{
TODO				$this->SetAttr($thisChild['attributes']['key'], $thisChild['content']);
			}
			else
			{
				// we got us a field
				if(!$foundfields)
				{
					// first field
					$foundfields = true;
					if(isset($params['import_formname']) &&
					   trim($params['import_formname']) != '')
						$formdata->Name = trim($params['import_formname']);

					if(isset($params['import_formalias']) &&
					   trim($params['import_formname']) != '')
						$formdata->Alias = trim($params['import_formalias']);

					self::Store($mod,$params['form_id'],$params);
				}
//				debug_display($thisChild);
				$fieldAttrs = &$thisChild['attributes'];
				$className = pwfUtils::MakeClassName($fieldAttrs['type']);
//				debug_display($className);
				$newField = new $className($formdata,$params);
				$oldId = $fieldAttrs['id'];

				if(self::inXML($fieldAttrs['alias']))
				{
					$newField->SetAlias($fieldAttrs['alias']);
				}
				$newField->SetValidationType($fieldAttrs['validation_type']);
				if(self::inXML($fieldAttrs['order_by']))
				{
					$newField->SetOrder($fieldAttrs['order_by']);
				}
				if(self::inXML($fieldAttrs['required']))
				{
					$newField->SetRequired($fieldAttrs['required']);
				}
				if(self::inXML($fieldAttrs['hide_label']))
				{
					$newField->SetHideLabel($fieldAttrs['hide_label']);
				}
				foreach($thisChild['children'] as $thisOpt)
				{
					if($thisOpt['name'] == 'field_name')
					{
						$newField->SetName($thisOpt['content']);
					}
					if($thisOpt['name'] == 'options')
					{
						foreach($thisOpt['children'] as $thisOption)
						{
							$newField->OptionFromXML($thisOption);
						}
					}
				}
				$newField->Store(true);
				$formdata->Fields[] = $newField;
				$fieldMap[$oldId] = $newField->GetId();
			}
		}

		// clean up references
		if(!empty($params['xml_file']))
		{
			// need to update mappings in templates.
TODO			$tmp = $this->updateRefs(self::GetAttr($formdata,'form_template',''), $fieldMap);
TODO			$this->SetAttr('form_template',$tmp);
TODO			$tmp = $this->updateRefs(self::GetAttr($formdata,'submission_template',''), $fieldMap);
TODO			$this->SetAttr('submission_template',$tmp);

			// need to update mappings in field templates.
			$options = array('email_template','file_template');
			foreach($formdata->Fields as &$fld)
			{
				$changes = false;
				foreach($options as $to)
				{
					$templ = $fld->GetOption($to,'');
					if(!empty($templ))
					{
TODO						$tmp = $this->updateRefs($templ, $fieldMap);
						$fld->SetOption($to,$tmp);
						$changes = true;
					}
				}
				// need to update mappings in FormBrowser sort fields
				if($fld->GetFieldType() == 'DispositionFormBrowser')
				{
					for ($i=1;$i<6;$i++)
					{
						$old = $fld->GetOption('sortfield'.$i);
						if(isset($fieldMap[$old]))
						{
							$fld->SetOption('sortfield'.$i,$fieldMap[$old]);
							$changes = true;
						}
					}
				}
				if($changes)
				{
					$fld->Store(true);
				}
			}
			unset ($fld);

			self::Store($mod,$params['form_id'],$params);
		}

		return true;
	}
	
	/**
	GetBrowsers:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	*/
	function GetBrowsers(&$mod,$form_id)
	{
		$fbr = $mod->GetModuleInstance('FormBrowser');
		if($fbr != false)
		{
			$db = $mod->dbHandle;
			$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_fbr_browser WHERE form_id=?';
			$browsers = $db->GetAll($sql,array($form_id));
		}
		else
			$browsers = array();
		return $browsers;
	}

	function NewID(&$mod,$name = false,$alias = false)
	{
		$where = array();
		$vars = array();

		if($name)
		{
			$where[] = 'name=?';
			$vars[] = $name;
		}
		if($alias)
		{
			$where[] = 'alias=?';
			$vars[] = $alias;
		}
		if(count($where) > 0)
		{
			$db = $mod->dbHandle;
			$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwf_form WHERE ';
			$sql .= implode(' OR ',$where);
			$exists = $db->GetOne($sql, $vars);
			if($exists)
				return false;
		}
		return true;
	}

	function GetId(&$params)
	{
		$fid = (isset($params['form_id'])) ? (int)$params['form_id'] : -1;
		return $fid;
	}

	function GetName(&$params)
	{
		$fname = (isset($params['form_name'])) ? trim($params['form_name']) : '';
		return $fname;
	}

	function GetAttr(&$formdata, $attrname, $default='')
	{
		if(isset($formdata->Attrs[$attrname]))
			return $formdata->Attrs[$attrname];
		else
			return $default;
	}

}

?>
