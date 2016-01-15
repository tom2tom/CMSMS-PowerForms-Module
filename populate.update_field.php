<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright(C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!empty($message))
	$smarty->assign('message',$message);

$smarty->assign('backtomod_nav',$this->CreateLink($id,'defaultadmin','','&#171; '.$this->Lang('back_top')));
$smarty->assign('backtoform_nav',$this->CreateLink($id,'update_form',$returnid,'&#171; '.$this->Lang('back_form'),
	array('formedit'=>1,'form_id'=>$params['form_id'],'formdata'=>$params['formdata'])));

if($obfield) //field data are loaded
{
	$fid = $obfield->GetId(); //maybe <= 0, if adding
	$nm = 'submit'; //submit-control name

	$allOpts = $obfield->AdminPopulate($id);
	$hasmain = (isset($allOpts['main']) && count($allOpts['main']) > 0);
	$hasadv = (isset($allOpts['adv']) && count($allOpts['adv']) > 0);

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

	$t = ($obfield->HasAddOp())?
		$this->CreateInputSubmit($id,'optionadd',$obfield->GetOptionAddButton()):'';
	$smarty->assign('add',$t);

	$t = ($obfield->HasDeleteOp())?
		$this->CreateInputSubmit($id,'optiondel',$obfield->GetOptionDeleteButton()):'';
	$smarty->assign('del',$t);

	$t = (/*!$obfield->IsDisposition() && */!$obfield->GetChangeRequirement())?1:0;
	$smarty->assign('requirable',$t);

	$mainList = array();
	if($hasmain)
	{
		foreach($allOpts['main'] as $item)
		{
			$oneset = new stdClass();
			$oneset->title = (isset($item[0]))?$item[0]:'';
			if(!empty($item[1])) $oneset->input = $item[1]; //optional
			if(!empty($item[2])) $oneset->help = $item[2];
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
			if(!empty($item[1])) $oneset->input = $item[1]; //optional
			if(!empty($item[2])) $oneset->help = $item[2];
			$advList[] = $oneset;
		}
	}
	$smarty->assign('advList',$advList);

	if(isset($allOpts['table']))
		$smarty->assign('mainTable',$allOpts['table']);
	if(isset($allOpts['funcs']))
		$smarty->assign('jsfuncs',$allOpts['funcs']);

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
				pwfUtils::SetupSubTemplateVarsHelp($formdata,$this,$smarty);
			}
			break;
		 case 'varshelpboth':
			if($hasmain)
				$smarty->assign('mainvarhelp',1);
			if($hasadv)
			{
				$smarty->assign('advvarhelp',1);
				pwfUtils::SetupSubTemplateVarsHelp($formdata,$this,$smarty);
			}
			break;
		}
	}
}
else //no field
{
	$fid = 0;
	$nm = 'add';
	//setup to select a type, then come back to edit it
	$oneset = new stdClass();
	$oneset->title = $this->Lang('title_add_new_field');
	pwfUtils::Collect_Fields($this);
	$oneset->input = $this->CreateInputDropdown($id,'field_type',
		array_merge(array($this->Lang('select_type')=>''),$this->field_types),-1,'');
//	$oneset->help = ;
	$smarty->assign('mainitem',$oneset);
}

$smarty->assign('form_start',$this->CreateFormStart($id,'update_field',$returnid,
	'POST','',FALSE,'',array(
	'form_id'=>$params['form_id'],
	'formdata'=>$params['formdata'],
	'field_id'=>$fid)));
$smarty->assign('form_end',$this->CreateFormEnd());

$t = ($fid > 0) ? 'update':'add'; //field edit or add
$smarty->assign('submit',$this->CreateInputSubmit($id,$nm,$this->Lang($t)));
$smarty->assign('cancel',$this->CreateInputSubmit($id,'cancel',$this->Lang('cancel')));

?>