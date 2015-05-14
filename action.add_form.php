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
	if(!($name || $alias))
		$this->Redirect($id,'defaultadmin');
	if(!$name)
		$name = $alias;
	$pref = cms_db_prefix();
	$newid = $db->GenID($pref.'module_pwf_form_seq');
	$sql = 'INSERT INTO '.$pref.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
	$db->Execute($sql,array($newid,$name,$alias));
	$this->Redirect($id,'update_form','',array('formedit'=>1,'form_id'=>$newid));
}

$smarty->assign('form_start',$this->CreateFormStart($id,'add_form',$returnid));
$smarty->assign('form_end',$this->CreateFormEnd());
$smarty->assign('title_newform',$this->Lang('title_newform'));
$smarty->assign('title_form_name',$this->Lang('title_form_name'));
$smarty->assign('input_form_name',$this->CreateInputText($id,'form_name','',50));
$smarty->assign('title_form_alias',$this->Lang('title_form_alias'));
$smarty->assign('input_form_alias',$this->CreateInputText($id,'form_alias','',50));
$smarty->assign('help_form_alias',$this->Lang('help_form_alias'));
$smarty->assign('save',$this->CreateInputSubmit($id,'save',$this->Lang('save')));
$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

echo $this->ProcessTemplate('addform.tpl');

?>
