<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

if (!$this->_CheckAccess('ModifyPFForms')) {
	exit;
}

$cache = PWForms\Utils::GetCache();
if (!$cache) {
	echo $this->Lang('err_system');
	exit;
}

$newfield = ($params['field_id'] == 0);

if (isset($params['datakey'])) {
	$formdata = $cache->get(PWForms::ASYNCSPACE, $params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		//probably the system has been shut down
$this->Crash();
//		$formdata = $funcs->Load($this,$params['form_id'],$id,$params);
//		$params['datakey'] = 'pwf'.md5($formdata->Id.session_id()); //must persist across requests
		$this->Redirect($id, 'defaultadmin', '', ['message'=>$this->_PrettyMessage('err_data', FALSE)]);
	}
	$formdata->pwfmod = &$this;

	if (!$newfield) {
		$obfld = $formdata->Fields[$params['field_id']];
	} elseif (isset($params['field_pick'])) { //we know what type of field to add
		unset($params['field_id']); //-1 no use
		$obfld = PWForms\FieldOperations::Get($formdata, $params);
	} elseif (isset($params['compadd'])) { //add component to new field
		$obfld = PWForms\FieldOperations::Get($formdata, $params);
	} elseif (isset($params['compdel'])) { //remove component from new field
		$obfld = PWForms\FieldOperations::Get($formdata, $params);
	} else { //add field, whose type is to be selected
		$obfld = FALSE;
	}
	$refresh = FALSE;
} else {
	//should never happen
	$this->Crash();
}

if (isset($params['cancel'])) {
	if ($params['field_id']) {
		$obfld = $formdata->Fields[$params['field_id']];
		$t = ($obfld->IsDisposition()) ? 'submittab':'fieldstab';
	} else {
		$t = 'fieldstab';
	}
	$this->Redirect($id, 'open_form', $returnid, [
		'form_id'=>$params['form_id'],
		'datakey'=>$params['datakey'],
		'active_tab'=>$t]);
}

$message = '';
if (isset($params['submit'])) {
	if ($newfield) {
		$params['field_pick'] = $params['field_Type'];
		$obfld = PWForms\FieldOperations::Get($formdata, $params);
	}
	//migrate $params to field data
	foreach ([
		'field_Name'=>'SetName',
		'field_Alias'=>'SetAlias',
	] as $key=>$val) {
		if (array_key_exists($key, $params)) {
			$obfld->$val($params[$key]);
		}
	}
	foreach ($params as $key=>$val) {
		if (strncmp($key, 'fp_', 3) == 0) {
			$key = substr($key, 3);
			if (is_array($val) && $obfld->MultiComponent) {
				$i = 1; //use custom-index, in case rows have been re-ordered
				foreach ($val as $ival) {
					$obfld->SetPropIndexed($key, $i, $ival);
					++$i;
				}
			} else {
				$obfld->SetProperty($key, $val);
			}
		}
	}
	$obfld->PostAdminAction($params);
	list($res, $msg) = $obfld->AdminValidate($id);
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
		$cache->set(PWForms::ASYNCSPACE, $params['datakey'], $formdata, 84600);

		$t = ($newfield) ? 'added':'updated';
		$message = $this->Lang('field_op', $this->Lang($t));
		if ($msg) {
			$message = $msg.'<br />'.$message;
		}
		$t = ($obfld->IsDisposition()) ? 'submittab':'fieldstab';
		$this->Redirect($id, 'open_form', $returnid, [
			'form_id'=>$params['form_id'],
			'datakey'=>$params['datakey'],
			'active_tab'=>$t,
			'selectfields'=>$params['selectfields'],
			'selectdispos'=>$params['selectdispos'],
			'message'=>$this->_PrettyMessage($message, TRUE, FALSE)]);
	} else {
		//start again //TODO if imported field with no tabled data
		if ($newfield) {
			$obfld = PWForms\FieldOperations::Get($formdata, $params);
		} else {
			$obfld->Load($id, $params);
		} //TODO check for failure
		$message = $this->_PrettyMessage($msg, FALSE, FALSE);
	}
} elseif (isset($params['compadd'])) {
	$obfld->ComponentAdd($params);
	$refresh = TRUE;
} elseif (isset($params['compdel'])) {
	$obfld->ComponentDelete($params);
	$refresh = FALSE;
}
/*else {
	// add etc
}
*/

if ($refresh) {
	$obfld->SetName($params['field_Name']);
	$obfld->SetAlias($params['field_Alias']);
	foreach ($params as $key=>$val) {
		if (strncmp($key, 'fp_', 3) == 0) {
			$key = substr($key, 3);
			if (is_array($val) && $obfld->MultiComponent) {
				//pending 'submit', doesn't matter if components have been re-ordered
				foreach ($val as $i=>$ival) {
					$obfld->SetPropIndexed($key, $i, $ival);
				}
			} else {
				$obfld->SetProperty($key, $val);
			}
		}
	}
}

$tplvars = [];
$ob = new stdClass();
$ob->jsincs = [];
$ob->jsfuncs = [];
$ob->jsloads = [];
$obfld->Jscript = &$ob;

require __DIR__.DIRECTORY_SEPARATOR.'populate.field.php';

$jsall = NULL;
PWForms\Utils::MergeJS($ob->jsincs, $ob->jsfuncs, $ob->jsloads, $jsall);

$ob->jsincs = NULL;
$ob->jsfuncs = NULL;
$ob->jsloads = NULL;
unset($obfld->Jscript);

$cache->set(PWForms::ASYNCSPACE, $params['datakey'], $formdata, 84600);

echo PWForms\Utils::ProcessTemplate($this, 'editfield.tpl', $tplvars);
if ($jsall) {
	echo $jsall;
}
