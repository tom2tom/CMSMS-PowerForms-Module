<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

if(isset($params['cancel']))
	$this->Redirect($id,'update_form',$returnid,array(
		'form_id'=>$params['form_id'],
		'formdata'=>$params['formdata'],
		'formedit'=>1,
		'active_tab'=>'fieldstab'));
try
{
	$cache = pwfUtils::GetCache();
}
catch (Exception $e)
{
	echo $this->Lang('error_system');
	exit;
}

$newfield = ($params['field_id'] <= 0);

if(isset($params['formdata']))
{
	$formdata = $cache->get($params['formdata']);
	if(is_null($formdata) || !$formdata->Fields)
	{
		//probably the system has been shut down
$this->Crash();
//		$formdata = $funcs->Load($this,$id,$params,$params['form_id']);
//		$params['formdata'] = base64_encode($formdata->Id.session_id()); //must persist across requests
		$this->Redirect($id,'defaultadmin','',array('message'=>$this->PrettyMessage('error_data',FALSE)));
	}
	$formdata->formsmodule = &$this;

	if(!$newfield)
		$obfield = $formdata->Fields[$params['field_id']];
	elseif(isset($params['field_type']) //we know what display-field type to add
	    || isset($params['disposition_type'])) //we know what disposition-field type to add
		$obfield = pwfFieldOperations::NewField($formdata,$id,$params);
	else //add field, whose type is to be selected
		$obfield = FALSE;
	$refresh = FALSE;
}
else
{
	//should never happen
	$this->Crash();
}

$message = '';
if(isset($params['submit']))
{
	//migrate $params to field data
	foreach(array(
		'field_name'=>'SetName',
		'field_required'=>'SetRequired',
		'hide_label'=>'SetHideLabel',
		'opt_field_alias'=>'SetAlias',
	) as $key=>$val)
	{
		if(array_key_exists($key,$params))
		{
			$t = $params[$key];
			if(is_numeric($t))
				$t = (int)$t;
			$obfield->$val($t);
		}
	}
	foreach($params as $key=>$val)
	{
		if(strncmp($key,'opt_',4) == 0)
			$obfield->SetOption(substr($key,4),$val); //TODO check for OptionRef
	}
	$obfield->PostAdminAction($params);
	list($res,$message) = $obfield->AdminValidate($id);
	if($res)
	{
		if($newfield)
		{
			//TODO eliminate race-risk here
			$key = count($formdata->Fields) + 1;
			$obfield->SetId(-$key); //Id < 0, so it gets inserted upon form-submit
			$obfield->SetOrder($key); //order last
			$formdata->Fields[-$key] = $obfield;
			$formdata->FieldOrders = FALSE; //trigger re-sort
		}
		//update cache ready for next use
		$formdata->formsmodule = NULL;
		$cache->set($params['formdata'],$formdata);
		$t = ($newfield) ? 'added':'updated';
		$message = $this->Lang('field_op',$this->Lang($t));
		$this->Redirect($id,'update_form',$returnid,array(
			'form_id'=>$params['form_id'],
			'formdata'=>$params['formdata'],
			'formedit'=>1,
			'active_tab'=>'fieldstab',
			'message'=>$this->PrettyMessage($message,TRUE,FALSE,FALSE)));
	}
	else
	{
		//start again //TODO if imported field with no tabled data
		if($newfield)
			$obfield = pwfFieldOperations::NewField($formdata,$id,$params);
		else
			$obfield->Load($id,$params); //TODO check for failure
		$message = $this->PrettyMessage($message,FALSE,FALSE,FALSE);
	}
}
elseif(isset($params['optionadd']))
{
	// call the field's option-add method, with all available parameters
	$obfield->DoOptionAdd($params);
	$refresh = TRUE;
}
elseif(isset($params['optiondel']))
{
	// call the field's option-delete method, with all available parameters
	$obfield->DoOptionDelete($params);
	$refresh = TRUE;
}
/*else
{
	// add etc
}
*/

if($refresh)
{
	foreach($params as $key=>$val)
	{
		if(strncmp($key,'opt_',4) == 0)
			$obfield->Options[substr($key,4)] = $val;
	}
	$obfield->SetName($params['field_name']);
	$obfield->SetAlias($params['opt_field_alias']);
	if(isset($params['hide_label']))
		$obfield->SetHideLabel((int)$params['hide_label']);
	if(isset($params['field_required']))
		$obfield->SetHideLabel((int)$params['field_required']);
}

$tplvars = array();

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.update_field.php';

$formdata->formsmodule = NULL; //no need to cache this
$cache->set($params['formdata'],$formdata);

echo pwfUtils::ProcessTemplate($this,'editfield.tpl',$tplvars);

?>
