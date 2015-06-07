<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright(C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!empty($message))
	$smarty->assign('message',$message);

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top')));
$smarty->assign('backtoform_nav',$this->CreateLink($id,'update_form',$returnid,'&#171; '.$this->Lang('back_form'),array('formedit'=>1,'form_id'=>$params['form_id'])));

$smarty->assign('form_start',$this->CreateFormStart($id,'update_field',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$params['form_id'],
	'formdata'=>$params['formdata'],
	'field_id'=>$obfield->GetId(),
	'order_by'=>$obfield->GetOrder(), //TODO check used in pwfFieldBase::__construct()
	'set_from_form'=>1))); //TODO check used in pwfFieldBase::__construct()
$smarty->assign('form_end',$this->CreateFormEnd());

$mainList = array();
$advList = array();
$baseList = $obfield->PrePopulateAdminFormCommon($id);
/*if($obfield->GetFieldType() == '')
{
	// still need type
	$fieldList = array();
}
else
{
*/
	// we have our type
	$fieldList = $obfield->PrePopulateAdminForm($id);
//}

$hasmain = isset($baseList['main']) || isset($fieldList['main']);
$hasadvanced = isset($baseList['adv']) || isset($fieldList['adv']);

$tab = $this->GetActiveTab($params);
$t = $this->StartTabHeaders();
if($hasmain)
	$t .= $this->SetTabHeader('maintab',$this->Lang('tab_field'),($tab == 'maintab'));
if($hasadvanced)
	$t .= $this->SetTabHeader('advancedtab',$this->Lang('tab_advanced'),($tab == 'advancedtab'));
$t .= $this->EndTabHeaders().$this->StartTabContent();

$smarty->assign('tabs_start',$t);
$smarty->assign('tabs_end',$this->EndTabContent());
if($hasmain)
	$smarty->assign('maintab_start',$this->StartTab('maintab'));
if($hasadvanced)
	$smarty->assign('advancedtab_start',$this->StartTab('advancedtab'));
$smarty->assign('tab_end',$this->EndTab());

if($obfield->GetId())
{
	$smarty->assign('op',$this->CreateInputHidden($id,'field_op',$this->Lang('updated')));
	$smarty->assign('submit',$this->CreateInputSubmit($id,'fieldupdate',$this->Lang('update')));
}
elseif($obfield->GetFieldType())
{
	$smarty->assign('op',$this->CreateInputHidden($id,'field_op',$this->Lang('added')));
	$smarty->assign('submit',$this->CreateInputSubmit($id,'fieldadd',$this->Lang('add')));
}
$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

$t = ($obfield->HasAddOp())?
	$this->CreateInputSubmit($id,'optionadd',$obfield->GetOptionAddButton()):'';
$smarty->assign('add',$t);

$t = ($obfield->HasDeleteOp())?
	$this->CreateInputSubmit($id,'optiondel',$obfield->GetOptionDeleteButton()):'';
$smarty->assign('del',$t);

$t = (/*!$obfield->IsDisposition() && */!$obfield->IsNonRequirableField())?1:0;
$smarty->assign('requirable',$t);

if(isset($baseList['main']))
{
	foreach($baseList['main'] as $item)
	{
		list($titleStr,$inputStr,$helpStr) = $item + array(NULL,NULL,NULL);
		$oneset = new stdClass();
		if($titleStr) $oneset->title = $titleStr;
		if($inputStr) $oneset->input = $inputStr;
		if($helpStr) $oneset->help = $helpStr;
		$mainList[] = $oneset;
	}
}
if(isset($baseList['adv']))
{
	foreach($baseList['adv'] as $item)
	{
		list($titleStr,$inputStr,$helpStr) = $item + array(NULL,NULL,NULL);
		$oneset = new stdClass();
		if($titleStr) $oneset->title = $titleStr;
		if($inputStr) $oneset->input = $inputStr;
		if($helpStr) $oneset->help = $helpStr;
		$advList[] = $oneset;
	}
}
if(isset($fieldList['main']))
{
	foreach($fieldList['main'] as $item)
	{
		list($titleStr,$inputStr,$helpStr) = $item + array(NULL,NULL,NULL);
		$oneset = new stdClass();
		if($titleStr) $oneset->title = $titleStr;
		if($inputStr) $oneset->input = $inputStr;
		if($helpStr) $oneset->help = $helpStr;
		$mainList[] = $oneset;
	}
}
if(isset($fieldList['adv']))
{
	foreach($fieldList['adv'] as $item)
	{
		list($titleStr,$inputStr,$helpStr) = $item + array(NULL,NULL,NULL);
		$oneset = new stdClass();
		if($titleStr) $oneset->title = $titleStr;
		if($inputStr) $oneset->input = $inputStr;
		if($helpStr) $oneset->help = $helpStr;
		$advList[] = $oneset;
	}
}
$obfield->PostPopulateAdminForm($mainList,$advList);
if(!$advList)
{
	$oneset = new stdClass();
	$oneset->help = $this->Lang('title_no_advanced_options');
	$advList[] = $oneset;
//TODO no advanced vars help for page
}

$smarty->assign('mainList',$mainList);
$smarty->assign('advList',$advList);
if(isset($fieldList['table']))
	$smarty->assign('mainTable',$fieldList['table']);
else
	$smarty->clear_assign('mainTable');
if(isset($fieldList['funcs']))
	$smarty->assign('jsfuncs',$fieldList['funcs']);
else
	$smarty->clear_assign('jsfuncs');

if(isset($fieldList['extra']))
{
	switch($fieldList['extra'])
	{
	 case 'varshelpadv':
		$smarty->assign('advvarhelp',1);
		pwfUtils::SetupFormVarsHelp($this,$smarty,$formdata->Fields);
		break;
	 case 'varshelpmain':
		$smarty->assign('mainvarhelp',1);
		break;
	 case 'varshelpboth':
		$smarty->assign('mainvarhelp',1);
		$smarty->assign('advvarhelp',1);
		pwfUtils::SetupFormVarsHelp($this,$smarty,$formdata->Fields);
		break;
	}

}

$smarty->assign('incpath',$this->GetModuleURLPath().'/include/');

?>
