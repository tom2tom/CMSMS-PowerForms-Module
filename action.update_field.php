<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

if(isset($params['cancel']))
	$this->Redirect($id,'update_form',$returnid,array(
		'formedit'=>1,
		'form_id'=>$params['form_id'],
		'formdata'=>$params['formdata'],
		'active_tab'=>'fieldstab'));

$cache = pwfCache::Get($this);
if(isset($params['formdata']))
{
	$formdata = unserialize($cache->driver_get($params['formdata']));
	$formdata->formsmodule =& $this;
//	$cache->driver_delete($params['formdata']);
}
else
{
	//should never happen
$this->Crash();
//	$params['formdata'] = md5(uniqid().mt_rand(10,10000010));
//	$formdata = $this->GetFormData($params);
}
$obfield = pwfFieldOperations::NewField($formdata,$id,$params);

$message = '';

if(isset($params['fieldupdate']) ||
  (isset($params['fieldadd']) && $obfield->GetFieldType()))
{
	$obfield->PostAdminSubmitCleanup($params);
	$val = $obfield->AdminValidate($id);
	if($val[0])
	{
		$obfield->Store(TRUE);
		$obfield->PostFieldSaveProcess($params);
		$message = $this->Lang('field_op',$params['field_op']);
		//update cache ready for next use
		$formdata->formsmodule = NULL;
		$cache->driver_set($params['formdata'],serialize($formdata));
		$this->Redirect($id,'update_form',$returnid,array(
			'formedit'=>1,
			'form_id'=>$params['form_id'],
			'formdata'=>$params['formdata'],
			'active_tab'=>'fieldstab',
			'message'=>	$this->PrettyMessage($message,TRUE,FALSE,FALSE)));
	}
	else
	{
		//start again
		$obfield->Load($id,$params); //TODO check for failure
		$message = $this->PrettyMessage($val[1],FALSE,FALSE,FALSE);
	}
}
elseif(isset($params['fieldadd']))
{
	// should have got a field type definition, so give rest of the field options
	// reserve this space for special ops :)
}
elseif(isset($params['optionadd']))
{
	// call the field's option-add method, with all available parameters
	$obfield->DoOptionAdd($params);
}
elseif(isset($params['optiondel']))
{
	// call the field's option-delete method, with all available parameters
	$obfield->DoOptionDelete($params);
}
else
{
	// new field, or implicit fieldadd
}

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.update_field.php';

$formdata->formsmodule = NULL; //no need to cache this
$cache->driver_set($params['formdata'],serialize($formdata));

echo $this->ProcessTemplate('editfield.tpl');

?>
