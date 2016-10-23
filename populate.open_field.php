<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright(C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!empty($message))
	$tplvars['message'] = $message;

$tplvars['backtomod_nav'] = $this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top'));
$tplvars['backtoform_nav'] = $this->CreateLink($id,'open_form',$returnid,'&#171; '.$this->Lang('back_form'),
	array('formedit'=>1,'form_id'=>$params['form_id'],'formdata'=>$params['formdata']));

$jsincs = FALSE; //array();
$jsfuncs = array();
$jsloads = FALSE; //array();

if ($obfield) { //field data are loaded
	$fid = $obfield->GetId(); //maybe <= 0, if adding
	$nm = 'submit'; //submit-control name

	$allOpts = $obfield->AdminPopulate($id);
	$hasmain = (isset($allOpts['main']) && count($allOpts['main']) > 0);
	$hasadv = (isset($allOpts['adv']) && count($allOpts['adv']) > 0);

	$tab = $this->_GetActiveTab($params);
	$t = $this->StartTabHeaders();
	if ($hasmain)
		$t .= $this->SetTabHeader('maintab',$this->Lang('tab_field'),($tab == 'maintab'));
	if ($hasadv)
		$t .= $this->SetTabHeader('advancedtab',$this->Lang('tab_advanced'),($tab == 'advancedtab'));
	$t .= $this->EndTabHeaders().$this->StartTabContent();
	$tplvars['tabs_start'] = $t;

	$tplvars['tabs_end'] = $this->EndTabContent();
	if ($hasmain)
		$tplvars['maintab_start'] = $this->StartTab('maintab');
	if ($hasadv)
		$tplvars['advancedtab_start'] = $this->StartTab('advancedtab');
	$tplvars['tab_end'] = $this->EndTab();

	$tplvars['add'] = ($obfield->HasOptionAdd())?
		$this->CreateInputSubmit($id,'optionadd',$obfield->GetOptionAddLabel()):NULL;

	if ($obfield->HasOptionDelete()) {
		$tplvars['del'] = $this->CreateInputSubmit($id,'optiondel',$obfield->GetOptionDeleteLabel(),
			'onclick="return confirm_selected(this)"');
		$prompt = $this->Lang('confirm');
		$jsfuncs['optiondel'] = <<<EOS
function confirm_selected(btn) {
 var sel = $(btn).closest('div').find('input[name^="{$id}selected"]:checked');
 if (sel.length > 0) {
   return confirm('{$prompt}');
 } else {
  return false;
 }
}
EOS;
	} else {
		$tplvars['del'] = NULL;
	}

	$tplvars['requirable'] = (/*!$obfield->IsDisposition() && */!$obfield->GetChangeRequirement())?1:0;

	$mainList = array();
	if ($hasmain) {
		foreach ($allOpts['main'] as $item) {
			$oneset = new stdClass();
			$oneset->title = (isset($item[0]))?$item[0]:'';
			if (!empty($item[1])) $oneset->input = $item[1]; //optional
			if (!empty($item[2])) $oneset->help = $item[2];
			$mainList[] = $oneset;
		}
	}
	$tplvars['mainList'] = $mainList;

	$advList = array();
	if ($hasadv) {
		foreach ($allOpts['adv'] as $item) {
			$oneset = new stdClass();
			$oneset->title = (isset($item[0]))?$item[0]:'';
			if (!empty($item[1])) $oneset->input = $item[1]; //optional
			if (!empty($item[2])) $oneset->help = $item[2];
			$advList[] = $oneset;
		}
	}
	$tplvars['advList'] = $advList;

	if (isset($allOpts['table']))
		$tplvars['mainTable'] = $allOpts['table'];
	if (isset($allOpts['funcs'])) {
		$jsfuncs = array_merge($jsfuncs,$allOpts['funcs']);
	}
	if (isset($allOpts['extra'])) {
		switch ($allOpts['extra']) {
		 case 'varshelpmain':
			if ($hasmain)
				$tplvars['mainvarhelp'] = 1;
			break;
		 case 'varshelpadv':
			if ($hasadv) {
				$tplvars['advvarhelp'] = 1;
				PWForms\Utils::SetupSubTemplateVarsHelp($formdata,$this,$tplvars);
			}
			break;
		 case 'varshelpboth':
			if ($hasmain)
				$tplvars['mainvarhelp'] = 1;
			if ($hasadv) {
				$tplvars['advvarhelp'] = 1;
				PWForms\Utils::SetupSubTemplateVarsHelp($formdata,$this,$tplvars);
			}
			break;
		}
	}
} else { //no field
	$fid = 0;
	$nm = 'add';
	//setup to select a type, then come back to edit it
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_add_new_field');
	PWForms\Utils::Collect_Fields($this);
	$oneset->input = $this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->field_types),-1,'');
//	$oneset->help = ;
	$tplvars['mainitem'] = $oneset;
}

$tplvars['form_start'] = $this->CreateFormStart($id,'open_field',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$params['form_id'],
	'formdata'=>$params['formdata'],
	'field_id'=>$fid));
$tplvars['form_end'] = $this->CreateFormEnd();

$t = ($fid > 0) ? 'update':'add'; //field edit or add
$tplvars['submit'] = $this->CreateInputSubmit($id,$nm,$this->Lang($t));
$tplvars['cancel'] = $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'));
