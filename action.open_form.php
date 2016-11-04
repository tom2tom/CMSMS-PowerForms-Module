<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->_CheckAccess('ModifyPFForms')) exit;

try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo $this->Lang('err_system');
	exit;
}
if (isset($params['cancel'])) {
	$cache->delete($params['datakey']);
	$this->Redirect($id,'defaultadmin');
}

$form_id = (int)$params['form_id'];
$funcs = new PWForms\FormOperations();

if (isset($params['datakey'])) {
	$formdata = $cache->get($params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		$formdata = $funcs->Load($this,$form_id,$id,$params,TRUE);
		$params['datakey'] = 'pwf'.md5($form_id.session_id());
	} else
		$formdata->formsmodule = &$this;
} else { //first time
	$formdata = $funcs->Load($this,$form_id,$id,$params,TRUE);
	$params['datakey'] = 'pwf'.md5($form_id.session_id());
}

$message = '';
if (isset($params['submit']) || isset($params['apply'])) {
	list($res,$message) = $funcs->Store($this,$formdata,$params);
	if ($res) {
		$message = $this->Lang('form_op',$this->Lang('updated'));
		$message = $this->_PrettyMessage($message,TRUE,FALSE);
		if (isset($params['submit'])) {
			$cache->delete($params['datakey']);
			$this->Redirect($id,'defaultadmin','',array('message'=> $message));
		}
	} else {
		$message = $this->_PrettyMessage($message,FALSE,FALSE);
	}
	$cache->delete($params['datakey']);
} elseif (isset($params['fieldcopy'])) {
	$obfield = PWForms\FieldOperations::Replicate($formdata,$params['field_id']);
	if ($obfield) {
		$obfield->Store(TRUE);
		$formdata->Fields[$obfield->Id] = $obfield;
		//update cache ready for next use
		$cache->set($params['datakey'],$formdata,84600);
		$this->Redirect($id,'open_field',$returnid,
			array('field_id'=>$params['field_id'],
				'form_id'=>$fid,
				'datakey'=>$params['datakey']));
	} else {
		$message = $this->_PrettyMessage('err_copy',FALSE);
	}
} elseif (isset($params['dir'])) {
	$srcIndex = PWForms\FieldOperations::GetFieldIndexFromId($formdata,$params['field_id']);
	$destIndex = ($params['dir'] == 'up') ? $srcIndex - 1 : $srcIndex + 1;
	PWForms\FieldOperations::SwapFieldsByIndex($srcIndex,$destIndex);
	$message = $this->_PrettyMessage('field_order_updated');
}

//helpicon file
if (!(empty($params['icondelete']) || empty($params['fp_help_icon']))) {
	PWForms\Utils::DeleteUploadFile($this,$params['fp_help_icon'],$form_id);
//	unset($params['icondelete']);
}
$t = $id.'iconupload';
if (isset($_FILES) && isset($_FILES[$t])) {
	$file_data = $_FILES[$t];
	if ($file_data['name']) {
		if ($file_data['error'] != 0)
			$umsg = $this->Lang('err_upload',$this->Lang('err_system'));
		else {
			//requires GD[2] extension - TODO check - doesn't support many image-types
			$lvl = error_reporting(0);
			$img = @imagecreatefromstring(file_get_contents($file_data['tmp_name']));
			error_reporting($lvl);
			if ($img) {
				imagedestroy($img);
			} else {
				$umsg = $this->Lang('err_upload',$this->Lang('err_file'));
			}
		}
		if (empty($umsg)) {
			$fp = PWForms\Utils::GetUploadsPath($this);
			if ($fp) {
				$fp .= DIRECTORY_SEPARATOR.$file_data['name'];
				if (// !chmod($file_data['tmp_name'],0644) ||
					!cms_move_uploaded_file($file_data['tmp_name'],$fp)) {
					$umsg = $this->Lang('err_upload',$this->Lang('err_perm'));
				}
			} else
				$umsg = $this->Lang('err_upload',$this->Lang('err_system'));
		}
		if (empty($umsg))
			$params['fp_help_icon'] = $file_data['name'];
		else {
			$message .= '<br />'.$umsg;
//			unset($umsg); //for next upload?
		}
	} else {
	}
}
//styles file
if (!(empty($params['stylesdelete']) || empty($params['fp_css_file']))) {
	PWForms\Utils::DeleteUploadFile($this,$params['fp_css_file'],$form_id);
//	unset($params['stylesdelete']);
}
$t = $id.'stylesupload';
if (isset($_FILES) && isset($_FILES[$t])) {
	$file_data = $_FILES[$t];
	if ($file_data['name']) {
		if ($file_data['error'] != 0)
			$umsg = $this->Lang('err_upload',$this->Lang('err_system'));
		else {
			$parts = explode('.',$file_data['name']);
			$ext = end($parts);
			if ($file_data['type'] != 'text/css'
			 || !($ext == 'css' || $ext == 'CSS')
			 || $file_data['size'] <= 0 || $file_data['size'] > 2048) { //plenty big enough in this context
				$umsg = $this->Lang('err_upload',$this->Lang('err_file'));
			} else {
				$fh = fopen($file_data['tmp_name'],'r');
				if ($fh) {
					//basic validation of file-content
					$content = fread($fh,512);
					fclose($fh);
					if ($content == FALSE)
						$umsg = $this->Lang('err_upload',$this->Lang('err_perm'));
//					elseif (!preg_match('/\.bkgtitle/',$content)) //TODO some relevant test
//						$umsg = $this->Lang('err_upload',$this->Lang('err_file'));
					unset($content);
				} else
					$umsg = $this->Lang('err_upload',$this->Lang('err_perm'));
			}
			if (empty($umsg)) {
				$fp = PWForms\Utils::GetUploadsPath($this);
				if ($fp) {
					$fp .= DIRECTORY_SEPARATOR.$file_data['name'];
					if (// !chmod($file_data['tmp_name'],0644) ||
						!cms_move_uploaded_file($file_data['tmp_name'],$fp)) {
						$umsg = $this->Lang('err_upload',$this->Lang('err_perm'));
					}
				} else
					$umsg = $this->Lang('err_upload',$this->Lang('err_system'));
			}
		}
		if (empty($umsg))
			$params['fp_css_file'] = $file_data['name'];
		else
			$message .= '<br />'.$umsg;
	} else {
//TODO adding file	$params['fp_css_file'] = $params['X'];
	}
}

$orders = array();
foreach ($formdata->Fields as $fid=>&$one) {
	$orders[] = $fid;
}
unset($one);
$formdata->FieldOrders = $orders;
$funcs->Arrange($formdata->Fields,$formdata->FieldOrders);

$tplvars = array();

require __DIR__.DIRECTORY_SEPARATOR.'populate.form.php';

$cache->set($params['datakey'],$formdata,84600);

$jsall = NULL;
PWForms\Utils::MergeJS($jsincs,$jsfuncs,$jsloads,$jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo PWForms\Utils::ProcessTemplate($this,'editform.tpl',$tplvars);
if ($jsall)
	echo $jsall;
