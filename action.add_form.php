<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(isset($params['cancel']))
{
	$this->Redirect($id,'defaultadmin');
}
elseif(isset($params['save']))
{
	$name = trim($params['form_name']);
	$alias = trim($params['form_alias']);
	if(!$alias)
	{
		if($name)
			$alias = pwfUtils::MakeAlias($name);
		else
			$name = '<'.$this->Lang('tab_form').'>'; //alias stays empty
		$seetab = 'maintab';
	}
	elseif(!$name)
	{
		$name = $alias;
		$seetab = 'maintab';
	}
	else
		$seetab = 'fieldstab';
	$params['form_name'] = $name;
	$params['form_alias'] = $alias;

	$funcs = new pwfFormOperations();
	if(isset($params['form_id']))
	{
		$newid = $funcs->Copy($this,$params['form_id'],$params);
		if(!$newid)
			$this->Redirect($id,'defaultadmin','',array(
				'message'=>$this->PrettyMessage('error_copy2',FALSE)));
		$seetab = 'maintab'; //name/alias will be different
	}
	else
	{
		$newid = $funcs->Add($this,$params);
		if(!$newid)
			$this->Redirect($id,'defaultadmin','',array(
				'message'=>$this->PrettyMessage('error_name',FALSE)));
	}
	unset($funcs);
	$this->Redirect($id,'update_form','',array(
		'formedit'=>1,'form_id'=>$newid,'active_tab'=>$seetab));
}

if(isset($params['form_id']))
{
	$h = $this->CreateInputHidden($id,'form_id',$params['form_id']); //remember what to copy
	$t = $this->Lang('copy');
	$name = pwfUtils::GetFormNameFromID($params['form_id']);
	if($name)
		$name .= ' '.$t
	$alias = pwfUtils::GetFormAliasFromID($params['form_id']);
	if($alias)
		$alias .= '_'.pwfUtils::MakeAlias($t);
}
else
{
	$h = '';
	$name = '';
	$alias = '';
}

$smarty->assign('form_start',$this->CreateFormStart($id,'add_form',$returnid));
$smarty->assign('form_end',$this->CreateFormEnd());
$smarty->assign('hidden',$h);
$smarty->assign('title_newform',$this->Lang('title_newform'));
$smarty->assign('title_form_name',$this->Lang('title_form_name'));
$smarty->assign('input_form_name',$this->CreateInputText($id,'form_name',$name,50,200));
$smarty->assign('title_form_alias',$this->Lang('title_form_alias'));
$smarty->assign('input_form_alias',$this->CreateInputText($id,'form_alias',$alias,50,100));
$smarty->assign('help_form_alias',$this->Lang('help_form_alias'));
$smarty->assign('save',$this->CreateInputSubmit($id,'save',$this->Lang('save')));
$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

echo $this->ProcessTemplate('addform.tpl');

?>
