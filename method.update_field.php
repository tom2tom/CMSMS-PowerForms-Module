<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright(C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!empty($message))
	$smarty->assign('message',$message);

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top')));
$smarty->assign('backtoform_nav',$this->CreateLink($id,'update_form',$returnid,'&#171; '.$this->Lang('back_form'),array('formedit'=>1,'form_id'=>$params['form_id'])));

$fid = $obfield->GetId();

$smarty->assign('form_start',$this->CreateFormStart($id,'update_field',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$params['form_id'],
	'formdata'=>$params['formdata'],
	'field_id'=>$fid,
	'order_by'=>$obfield->GetOrder(), //TODO check used in pwfFieldBase::__construct()
	'set_from_form'=>1))); //TODO check used in pwfFieldBase::__construct()
$smarty->assign('form_end',$this->CreateFormEnd());

$baseOpts = $obfield->PrePopulateAdminFormCommon($id);
/*if($obfield->GetFieldType() == '') TODO relevant?
{
	// still need type
	$fieldOpts = array();
}
else
{
*/
	// type is known
	$fieldOpts = $obfield->PrePopulateAdminForm($id);
//}

$allOpts = array_merge_recursive($baseOpts,$fieldOpts);
if(!isset($allOpts['main']) $allOpts['main'] = array();
if(!isset($allOpts['adv']) $allOpts['adv'] = array();
$obfield->PostPopulateAdminForm($allOpts['main'],$allOpts['adv']);
$hasmain = (count($allOpts['main']) > 0);
$hasadv = (count($allOpts['adv']) > 0);

$tab = $this->GetActiveTab($params);
$t = $this->StartTabHeaders();
if($hasmain)
	$t .= $this->SetTabHeader('maintab',$this->Lang('tab_field'),($tab == 'maintab'));
if($hasadv)
	$t .= $this->SetTabHeader('advancedtab',$this->Lang('tab_advanced'),($tab == 'advancedtab'));
$t .= $this->EndTabHeaders().$this->StartTabContent();

$smarty->assign('tabs_start',$t);
$smarty->assign('tabs_end',$this->EndTabContent());
if($hasmain)
	$smarty->assign('maintab_start',$this->StartTab('maintab'));
if($hasadv)
	$smarty->assign('advancedtab_start',$this->StartTab('advancedtab'));
$smarty->assign('tab_end',$this->EndTab());

if($fid > 0) //editing existing field
	$t = $this->CreateInputSubmit($id,'fieldupdate',$this->Lang('update')));
else //if($obfield->GetFieldType())
	$t = $this->CreateInputSubmit($id,'fieldadd',$this->Lang('add')));

$smarty->assign('submit',$t);
$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

$t = ($obfield->HasAddOp())?
	$this->CreateInputSubmit($id,'optionadd',$obfield->GetOptionAddButton()):'';
$smarty->assign('add',$t);

$t = ($obfield->HasDeleteOp())?
	$this->CreateInputSubmit($id,'optiondel',$obfield->GetOptionDeleteButton()):'';
$smarty->assign('del',$t);

$t = (/*!$obfield->IsDisposition() && */!$obfield->IsNonRequirableField())?1:0;
$smarty->assign('requirable',$t);

$mainList = array();
if($hasmain)
{
	foreach($allOpts['main'] as $item)
	{
		$oneset = new stdClass();
		$oneset->title = (isset($item[0]))?$item[0]:'';
		$oneset->input = (isset($item[1]))?$item[1]:'';
		if(!empty($item[2])) $oneset->help = $item[2]; //optional
		$mainList[] = $oneset;
	}
}
$smarty->assign('mainList',$mainList);

$advList = array();
if($hasadv)
{
	foreach($allOpts['adv'] as $item)
	{
		$oneset = new stdClass();
		$oneset->title = (isset($item[0]))?$item[0]:'';
		$oneset->input = (isset($item[1]))?$item[1]:'';
		if(!empty($item[2])) $oneset->help = $item[2]; //optional
		$advList[] = $oneset;
	}
}
$smarty->assign('advList',$advList);

if(isset($allOpts['table']))
	$smarty->assign('mainTable',$allOpts['table']);
if(isset($allOpts['funcs']))
	$smarty->assign('jsfuncs',$allOpts['funcs']);
$smarty->assign('incpath',$this->GetModuleURLPath().'/include/');

if(isset($allOpts['extra']))
{
	switch($allOpts['extra'])
	{
	 case 'varshelpmain':
		if($hasmain)
			$smarty->assign('mainvarhelp',1);
		break;
	 case 'varshelpadv':
		if($hasadv)
		{
			$smarty->assign('advvarhelp',1);
			pwfUtils::SetupFormVarsHelp($this,$smarty,$formdata->Fields);
		}
		break;
	 case 'varshelpboth':
		if($hasmain)
			$smarty->assign('mainvarhelp',1);
		if($hasadv)
		{
			$smarty->assign('advvarhelp',1);
			pwfUtils::SetupFormVarsHelp($this,$smarty,$formdata->Fields);
		}
		break;
	}
}

?>
