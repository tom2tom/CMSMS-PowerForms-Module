<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->_CheckAccess('ModifyPFForms')) exit;

try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo $this->Lang('err_system');
	exit;
}

$newfield = ($params['field_id'] == 0);

if (isset($params['datakey'])) {
	$formdata = $cache->get($params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		//probably the system has been shut down
$this->Crash();
//		$formdata = $funcs->Load($this,$params['form_id'],$id,$params);
//		$params['datakey'] = 'pwf'.md5($formdata->Id.session_id()); //must persist across requests
		$this->Redirect($id,'defaultadmin','',array('message'=>$this->_PrettyMessage('err_data',FALSE)));
	}
	$formdata->formsmodule = &$this;

	if (!$newfield) {
		$obfld = $formdata->Fields[$params['field_id']];
	} elseif (isset($params['field_pick'])) { //we know what type of field to add
		unset($params['field_id']); //-1 no use
		$obfld = PWForms\FieldOperations::NewField($formdata,$params);
	} else { //add field, whose type is to be selected
		$obfld = FALSE;
	}
	$refresh = FALSE;
} else {
	//should never happen
	$this->Crash();
}

if (isset($params['cancel'])) {
	$obfld = $formdata->Fields[$params['field_id']];
	$t = ($obfld->IsDisposition()) ? 'submittab':'fieldstab';
	$this->Redirect($id,'open_form',$returnid,array(
		'form_id'=>$params['form_id'],
		'datakey'=>$params['datakey'],
		'active_tab'=>$t));
}

$message = '';
if (isset($params['submit'])) {
	if ($newfield) {
		$params['field_pick'] = $params['field_Type'];
		$obfld = PWForms\FieldOperations::NewField($formdata,$params);
	}
	//migrate $params to field data
	foreach (array(
		'field_Name'=>'SetName',
		'field_Alias'=>'SetAlias',
	) as $key=>$val)
	{
		if (array_key_exists($key,$params)) {
			$obfld->$val($params[$key]);
		}
	}
	foreach ($params as $key=>$val) {
		if (strncmp($key,'fp_',3) == 0) {
			$key = substr($key,3);
			if (is_array($val) && $obfld->MultiComponent) {
				$i = 1; //use custom-index, in case rows have been re-ordered
				foreach ($val as $ival) {
					$obfld->SetPropIndexed($key,$i,$ival);
					$i++;
				}
			} else {
				$obfld->SetProperty($key,$val);
			}
		}
	}
	$obfld->PostAdminAction($params);
	list($res,$msg) = $obfld->AdminValidate($id);
	if ($res) {
		if ($newfield) {
			//TODO eliminate race-risk here
			$key = count($formdata->Fields) + 1;
			$obfld->SetId(-$key); //Id < 0, so it gets inserted upon form-submit
			$obfld->SetOrder($key); //order last
			$formdata->Fields[-$key] = $obfld;
			$formdata->FieldOrders = FALSE; //trigger re-sort
		}
		//update cache ready for next use
		$cache->set($params['datakey'],$formdata,84600);

		$t = ($newfield) ? 'added':'updated';
		$message = $this->Lang('field_op',$this->Lang($t));
		if ($msg) {
			$message = $msg.'<br />'.$message;
		}
		$t = ($obfld->IsDisposition()) ? 'submittab':'fieldstab';
		$this->Redirect($id,'open_form',$returnid,array(
			'form_id'=>$params['form_id'],
			'datakey'=>$params['datakey'],
			'active_tab'=>$t,
			'selectfields'=>$params['selectfields'],
			'selectdispos'=>$params['selectdispos'],
			'message'=>$this->_PrettyMessage($message,TRUE,FALSE)));
	} else {
		//start again //TODO if imported field with no tabled data
		if ($newfield)
			$obfld = PWForms\FieldOperations::NewField($formdata,$params);
		else
			$obfld->Load($id,$params); //TODO check for failure
		$message = $this->_PrettyMessage($msg,FALSE,FALSE);
	}
} elseif (isset($params['optionadd'])) {
	$obfld->OptionAdd($params);
	$refresh = TRUE;
} elseif (isset($params['optiondel'])) {
	$obfld->OptionDelete($params);
	$refresh = FALSE;
}
/*else {
	// add etc
}
*/

if ($refresh) {
	foreach ($params as $key=>$val) {
		if (strncmp($key,'fp_',3) == 0)
			$obfld->XtraProps[substr($key,3)] = $val;
	}
	$obfld->SetName($params['field_Name']);
	$obfld->SetAlias($params['field_Alias']);
}

$tplvars = array();

require __DIR__.DIRECTORY_SEPARATOR.'populate.field.php';

$jsall = NULL;
PWForms\Utils::MergeJS($obfld->jsincs,$obfld->jsfuncs,$obfld->jsloads,$jsall);
$obfld->jsincs = FALSE;
$obfld->jsfuncs = FALSE;
$obfld->jsloads = FALSE;

$cache->set($params['datakey'],$formdata,84600);

echo PWForms\Utils::ProcessTemplate($this,'editfield.tpl',$tplvars);
if ($jsall)
	echo $jsall;
