<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* TODO replace actions
add_edit_form >> formedit Y
copy_form >> formcopy Y
copy_field >> fieldcopy Y
delete_field >> fielddelete Y
store_form >> submit, apply, cancel Y
update_field_order >> dir Y
update_field_required >> active Y

>> update_form
*/

if(!$this->CheckAccess('ModifyPFForms')) exit;

$form_id = (int)$params['form_id'];

if(isset($params['cancel']))
{
	$this->Redirect($id,'defaultadmin');
}
elseif(isset($params['submit']))
{
	$funcs = new pwfFormOperations();
	if($funcs->Store($this,$form_id,$params))
	{
		$msg = $this->Lang('form',$params['form_op']); //updated or added
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
	$msg = $this->Lang('form',$params['form_op']);//updated or added
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
	$funcs->Copy($this,$form_id,$params);
	$message = '';
}
elseif(isset($params['fielddelete']))
{
//TODO $funcs = new pwfFieldOperations($this,$params,TRUE);
	$funcs->DeleteField($params['field_id']);
	$message = $this->PrettyMessage('field_deleted');
}
elseif(isset($params['fieldcopy']))
{
//TODO $funcs = new pwfFieldOperations($this,$params,TRUE);
	$obfield = $funcs->Replicate($this,$params);
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
//TODO $funcs = new pwfFieldOperations($this,$params,TRUE);
	$srcIndex = $funcs->GetFieldIndexFromId($params['field_id']);
	if($params['dir'] == 'up')
		$destIndex = $srcIndex - 1;
	else
		$destIndex = $srcIndex + 1;
	$funcs->SwapFieldsByIndex($srcIndex,$destIndex);
	$message = $this->PrettyMessage('field_order_updated');
}
elseif(isset($params['active']))
{
//TODO $funcs = new pwfFieldOperations($this,$params,TRUE);
	$obfield = $funcs->GetFieldById($params['field_id']);
	if($obfield !== false)
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

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.update_form.php';

echo $this->ProcessTemplate('AddEditForm.tpl');

?>

