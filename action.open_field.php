<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->_CheckAccess('ModifyPFForms')) exit;

if (isset($params['cancel']))
	$this->Redirect($id,'open_form',$returnid,array(
		'form_id'=>$params['form_id'],
		'formdata'=>$params['formdata'],
		'formedit'=>1,
		'active_tab'=>'fieldstab'));
try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo $this->Lang('err_system');
	exit;
}

$newfield = ($params['field_id'] <= 0);

if (isset($params['formdata'])) {
	$formdata = $cache->get($params['formdata']);
	if (is_null($formdata) || !$formdata->Fields) {
		//probably the system has been shut down
$this->Crash();
//		$formdata = $funcs->Load($this,$params['form_id'],$id,$params);
//		$params['formdata'] = base64_encode($formdata->Id.session_id()); //must persist across requests
		$this->Redirect($id,'defaultadmin','',array('message'=>$this->_PrettyMessage('err_data',FALSE)));
	}
	$formdata->formsmodule = &$this;

	if (!$newfield) {
		$obfield = $formdata->Fields[$params['field_id']];
	} elseif (isset($params['field_pick'])) { //we know what type of field to add
		unset($params['field_id']); //-1 no use
		$obfield = PWForms\FieldOperations::NewField($formdata,$params);
	} else { //add field, whose type is to be selected
		$obfield = FALSE;
	}
	$refresh = FALSE;
} else {
	//should never happen
	$this->Crash();
}

$message = '';
if (isset($params['submit'])) {
	//migrate $params to field data
	foreach (array(
		'field_Name'=>'SetName',
		'field_Alias'=>'SetAlias',
	) as $key=>$val)
	{
		if (array_key_exists($key,$params)) {
			$obfield->$val($params[$key]);
		}
	}
	foreach ($params as $key=>$val) {
		if (strncmp($key,'pdt_',4) == 0)
			//TODO check for prop. array or indexed
			$obfield->SetProperty(substr($key,4),$val);
	}
	$obfield->PostAdminAction($params);
	list($res,$message) = $obfield->AdminValidate($id);
	if ($res) {
		if ($newfield) {
			//TODO eliminate race-risk here
			$key = count($formdata->Fields) + 1;
			$obfield->SetId(-$key); //Id < 0, so it gets inserted upon form-submit
			$obfield->SetOrder($key); //order last
			$formdata->Fields[-$key] = $obfield;
			$formdata->FieldOrders = FALSE; //trigger re-sort
		}
		//update cache ready for next use
		$cache->set($params['formdata'],$formdata,84600);
		$t = ($newfield) ? 'added':'updated';
		$message = $this->Lang('field_op',$this->Lang($t));
		$this->Redirect($id,'open_form',$returnid,array(
			'form_id'=>$params['form_id'],
			'formdata'=>$params['formdata'],
			'formedit'=>1,
			'active_tab'=>'fieldstab',
			'message'=>$this->_PrettyMessage($message,TRUE,FALSE)));
	} else {
		//start again //TODO if imported field with no tabled data
		if ($newfield)
			$obfield = PWForms\FieldOperations::NewField($formdata,$params);
		else
			$obfield->Load($id,$params); //TODO check for failure
		$message = $this->_PrettyMessage($message,FALSE,FALSE);
	}
} elseif (isset($params['optionadd'])) {
	$obfield->OptionAdd($params);
	$refresh = TRUE;
} elseif (isset($params['optiondel'])) {
	$obfield->OptionDelete($params);
	$refresh = FALSE;
}
/*else {
	// add etc
}
*/

if ($refresh) {
	foreach ($params as $key=>$val) {
		if (strncmp($key,'pdt_',4) == 0)
			$obfield->XtraProps[substr($key,4)] = $val;
	}
	$obfield->SetName($params['field_Name']);
	$obfield->SetAlias($params['field_Alias']);
}

$tplvars = array();

require __DIR__.DIRECTORY_SEPARATOR.'populate.field.php';

$cache->set($params['formdata'],$formdata,84600);

$jsall = NULL;
PWForms\Utils::MergeJS($jsincs,$jsfuncs,$jsloads,$jsall);

echo PWForms\Utils::ProcessTemplate($this,'editfield.tpl',$tplvars);
if ($jsall)
	echo $jsall;
