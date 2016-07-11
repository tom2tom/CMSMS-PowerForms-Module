<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (isset($params['cancel'])) {
	$this->Redirect($id,'defaultadmin');
} elseif (isset($params['save'])) {
	$name = trim($params['form_name']);
	$alias = trim($params['form_alias']);
	if (!$alias) {
		if ($name)
			$alias = PowerForms\Utils::MakeAlias($name);
		else
			$name = '<'.$this->Lang('tab_form').'>'; //alias stays empty
		$seetab = 'maintab';
	} elseif (!$name) {
		$name = $alias;
		$seetab = 'maintab';
	} else
		$seetab = 'fieldstab';
	$params['form_name'] = $name;
	$params['form_alias'] = $alias;

	$funcs = new PowerForms\FormOperations();
	if (isset($params['form_id'])) {
		$newid = $funcs->Copy($this,$id,$params,$params['form_id']);
		if (!$newid)
			$this->Redirect($id,'defaultadmin','',array(
				'message'=>$this->PrettyMessage('error_copy2',FALSE)));
		$seetab = 'maintab'; //name/alias will be different
	} else {
		$newid = $funcs->Add($this,$params);
		if (!$newid)
			$this->Redirect($id,'defaultadmin','',array(
				'message'=>$this->PrettyMessage('error_name',FALSE)));
	}
	unset($funcs);
	$this->Redirect($id,'update_form','',array(
		'formedit'=>1,'form_id'=>$newid,'active_tab'=>$seetab)); //no formdata parameter
}

if (isset($params['form_id'])) {
	$h = $this->CreateInputHidden($id,'form_id',$params['form_id']); //remember what to copy
	$t = $this->Lang('copy');
	$name = PowerForms\Utils::GetFormNameFromID($params['form_id']);
	if ($name)
		$name .= ' '.$t;
	$alias = PowerForms\Utils::GetFormAliasFromID($params['form_id']);
	if ($alias)
		$alias .= '_'.PowerForms\Utils::MakeAlias($t);
} else {
	$h = '';
	$name = '';
	$alias = '';
}

$tplvars = array(
	'form_start' => $this->CreateFormStart($id,'add_form',$returnid),
	'form_end' => $this->CreateFormEnd(),
	'hidden' => $h,
	'title_newform' => $this->Lang('title_newform'),
	'title_form_name' => $this->Lang('title_form_name'),
	'input_form_name' => $this->CreateInputText($id,'form_name',$name,50,200),
	'title_form_alias' => $this->Lang('title_form_alias'),
	'input_form_alias' => $this->CreateInputText($id,'form_alias',$alias,50,100),
	'help_form_alias' => $this->Lang('help_form_alias'),
	'save' => $this->CreateInputSubmit($id,'save',$this->Lang('save')),
	'cancel' => $this->CreateInputSubmit($id,'cancel',$this->Lang('cancel'))
);

echo PowerForms\Utils::ProcessTemplate($this,'addform.tpl',$tplvars);
