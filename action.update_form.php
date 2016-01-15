<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

try
{
	$cache = pwfUtils::GetCache();
}
catch (Exception $e)
{
	echo $this->Lang('error_system');
	exit;
}
if(isset($params['cancel']))
{
	$cache->delete($params['formdata']);
	$this->Redirect($id,'defaultadmin');
}

$form_id = (int)$params['form_id'];
$funcs = new pwfFormOperations();

if(isset($params['formdata']))
{
	$formdata = $cache->get($params['formdata']);
	if(is_null($formdata) || !$formdata->Fields)
	{
		$formdata = $funcs->Load($this,$id,$params,$form_id);
		$params['formdata'] = base64_encode($formdata->Id.session_id()); //must persist across requests
	}
	else
		$formdata->formsmodule = &$this;
}
else //first time
{
	$formdata = $funcs->Load($this,$id,$params,$form_id);
	$params['formdata'] = base64_encode($formdata->Id.session_id());
}

if(isset($params['submit']))
{
	$funcs->Arrange($formdata->Fields,$params['orders'],TRUE);
	list($res,$message) = $funcs->Store($this,$formdata); //may alter $formdata (field-order)
	if($res)
	{
		$message = $this->Lang('form_op',$this->Lang('updated'));
		$message = $this->PrettyMessage($message,TRUE,FALSE,FALSE);
	}
	else
	{
		$message = $this->PrettyMessage($message,FALSE,FALSE,FALSE);
	}
	$cache->delete($params['formdata']);
	$this->Redirect($id,'defaultadmin','',array('message'=> $message));
}
elseif(isset($params['apply']))
{
	$funcs->Arrange($formdata->Fields,$params['orders'],TRUE);
	list($res,$message) = $funcs->Store($this,$params['orders'],$formdata); //may alter $formdata (field-order)
	if($res)
	{
		$message = $this->Lang('form_op',$this->Lang('updated'));
		$message = $this->PrettyMessage($message,TRUE,FALSE,FALSE);
	}
	else
	{
		$this->Redirect($id,'defaultadmin','',array(
		'message' => $this->PrettyMessage($message,FALSE,FALSE,FALSE)));
	}
}
elseif(isset($params['formedit']))
{
	if(isset($params['set_field_level']))
		$this->SetPreference('adder_fields',$params['set_field_level']);
	$message = '';
}
elseif(isset($params['fielddelete']))
{
	pwfFieldOperations::DeleteField($formdata,$params['field_id']);
	$message = $this->PrettyMessage('field_deleted');
}
elseif(isset($params['fieldcopy']))
{
	$obfield = pwfFieldOperations::Replicate($formdata,$params['field_id']);
	if($obfield)
	{
		$obfield->Store(TRUE);
		$formdata->Fields[$obfield->Id] = $obfield;
		//update cache ready for next use
		$formdata->formsmodule = NULL;
		$cache->set($params['formdata'],$formdata);
		$this->Redirect($id,'update_field',$returnid,
			array('field_id'=>$params['field_id'],
				'form_id'=>$fid,
				'formdata'=>$params['formdata']));
	}
	else
	{
		$message = $this->PrettyMessage('error_copy',FALSE);
	}
}
elseif(isset($params['dir']))
{
	$srcIndex = pwfFieldOperations::GetFieldIndexFromId($formdata,$params['field_id']);
	$destIndex = ($params['dir'] == 'up') ? $srcIndex - 1 : $srcIndex + 1;
	pwfFieldOperations::SwapFieldsByIndex($srcIndex,$destIndex);
	$message = $this->PrettyMessage('field_order_updated');
}
elseif(isset($params['active']))
{
	$obfield = $formdata->Fields[$params['field_id']];
	if($obfield !== FALSE)
	{
//		$obfield->SetRequired(($params['active']=='on'));
		$obfield->ToggleRequired();
		$obfield->Store();
		$message = $this->PrettyMessage('field_requirement_updated');
	}
	else
	{
		$message = $this->PrettyMessage('TODO',FALSE);
	}
}
else
	exit;

$orders = array();
foreach($formdata->Fields as $fid=>&$one)
{
	$orders[] = $fid;
}
unset($one);
$formdata->FieldOrders = $orders;
$funcs->Arrange($formdata->Fields,$formdata->FieldOrders);

require dirname(__FILE__).DIRECTORY_SEPARATOR.'populate.update_form.php';

$formdata->formsmodule = NULL; //no need to cache this
$cache->set($params['formdata'],$formdata);

echo $this->ProcessTemplate('editform.tpl');

?>

