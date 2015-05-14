<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

$form_id = (int)$params['form_id'];
$funcs = FALSE;
if(isset($params['cancel']))
{
	$this->Redirect($id,'defaultadmin');
}
elseif(isset($params['submit']))
{
	$funcs = new pwfFormOperations();
	if($funcs->Store($this,$form_id,$params))
	{
		$msg = $this->Lang('form_op',$params['form_op']); //updated or added
		$msg = $this->PrettyMessage($msg,TRUE,FALSE,FALSE);
	}
	else
	{
		$msg = $this->PrettyMessage($message,FALSE,FALSE,FALSE);
	}
	$this->Redirect($id,'defaultadmin','',array('message'=> $msg));
}
elseif(isset($params['apply']))
{
	$funcs = new pwfFormOperations();
	if(!$funcs->Store($this,$form_id,$params))
	{
		$this->Redirect($id,'defaultadmin','',array(
		'message' => $this->PrettyMessage($message,FALSE,FALSE,FALSE)));
	}
	$msg = $this->Lang('form_op',$params['form_op']);//updated or added
	$message = $this->PrettyMessage($msg,TRUE,FALSE,FALSE);
}
elseif(isset($params['formedit']))
{
	if(isset($params['set_field_level']))
		$this->SetPreference('adder_fields',$params['set_field_level']);
	$message = '';
}
elseif(isset($params['formcopy']))
{
	$funcs = new pwfFormOperations();
	$form_id = $funcs->Copy($this,$form_id,$params);
	if($form_id)
	{
		$message = '';
	}
	else
	{
		$this->Redirect($id,'defaultadmin','',array(
			'message'=>$this->PrettyMessage('error_copy2',FALSE)));
	}
}
elseif(isset($params['fielddelete']))
{
	$ops = new pwfFieldOperations();
//TODO $ops->DeleteField($formdata,$params['field_id']);
	$message = $this->PrettyMessage('field_deleted');
}
elseif(isset($params['fieldcopy']))
{
	$ops = new pwfFieldOperations();
//TODO	$obfield = $ops->Replicate($formdata,$params['field_id');
	if($obfield)
	{
		$obfield->Store(TRUE);
		$funcs->Fields[] = $obfield; //QQQ
		echo $funcs->AddEdit($obfield,false,$id,$returnid);
		return;
	}
	else
	{
		$message = $this->PrettyMessage('error_copy',FALSE);
	}
}
elseif(isset($params['dir']))
{
	$ops = new pwfFieldOperations();
//TODO	$srcIndex = $ops->GetFieldIndexFromId($formdata,$params['field_id']);
	if($params['dir'] == 'up')
		$destIndex = $srcIndex - 1;
	else
		$destIndex = $srcIndex + 1;
	$ops->SwapFieldsByIndex($srcIndex,$destIndex);
	$message = $this->PrettyMessage('field_order_updated');
}
elseif(isset($params['active']))
{
	$ops = new pwfFieldOperations();
//TODO 	$obfield = $ops->GetFieldById($formdata,$params['field_id']);
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

if(!$funcs)
	$funcs = new pwfFormOperations();

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.update_form.php';

echo $this->ProcessTemplate('editform.tpl');

?>

