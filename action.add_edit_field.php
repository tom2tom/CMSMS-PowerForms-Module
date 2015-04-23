<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(isset($params['pwfp_cancel']))
{
	$parms['form_id'] = $params['form_id'];
	$this->Redirect($id, 'admin_add_edit_form', $returnid, $parms);
}

if(!$this->CheckAccess()) exit;

$this->initialize();

$funcs = new pwfUtils($this, $params,true);

if(isset($params['pwfp_aef_cancel']))
{
	$tab = $this->GetActiveTab($params);
	echo $funcs->AddEditForm($id, $returnid, $tab);
	return;
}

if(isset($params['pwfp_opt_destination_address']))
{
	//store the 'blended' address details
	$funcs->MergeEmails($params);
}
$obfield = $funcs->NewField($params);

if(isset($params['pwfp_aef_upd']) ||
	(isset($params['pwfp_aef_add']) && $obfield->GetFieldType() != ''))
{
	// save the field. DO NOT ->Redirect - that flattens any $params[] that's an array
	$this->DoAction('store_field', $id, $params);
	return;
}
elseif(isset($params['pwfp_aef_add']))
{
	// should have got a field type definition, so give rest of the field options
	// reserve this space for special ops :)
}
elseif(isset($params['pwfp_aef_optadd']))
{
	// call the field's option add method, with all available parameters
	$obfield->DoOptionAdd($params);
}
elseif(isset($params['pwfp_aef_optdel']))
{
	// call the field's option delete method, with all available parameters
	$obfield->DoOptionDelete($params);
}
else
{
	// new field, or implicit aef_add
}

echo $funcs->AddEditField($id, $obfield, (isset($params['pwfp_dispose_only'])?$params['pwfp_dispose_only']:0), $returnid, isset($params['pwfp_message'])?$this->ShowMessage($params['pwfp_message']):'');

?>
