<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(isset($params['fbrp_cancel']))
{
	$parms['form_id'] = $params['form_id'];
	$this->Redirect($id, 'admin_add_edit_form', $returnid, $parms);
}

if(!$this->CheckAccess()) exit;

$this->initialize();

$aeform = new pwfForm($this, $params,true);

if(isset($params['fbrp_aef_cancel']))
{
	$tab = $this->GetActiveTab($params);
	echo $aeform->AddEditForm($id, $returnid, $tab);
	return;
}

if(isset($params['fbrp_opt_destination_address']))
{
	//store the 'blended' address details
	$aeform->MergeEmails($params);
}
$aefield = $aeform->NewField($params);

if(isset($params['fbrp_aef_upd']) ||
	(isset($params['fbrp_aef_add']) && $aefield->GetFieldType() != ''))
{
	// save the field. DO NOT ->Redirect - that flattens any $params[] that's an array
	$this->DoAction('admin_store_field', $id, $params);
	return;
}
elseif(isset($params['fbrp_aef_add']))
{
	// should have got a field type definition, so give rest of the field options
	// reserve this space for special ops :)
}
elseif(isset($params['fbrp_aef_optadd']))
{
	// call the field's option add method, with all available parameters
	$aefield->DoOptionAdd($params);
}
elseif(isset($params['fbrp_aef_optdel']))
{
	// call the field's option delete method, with all available parameters
	$aefield->DoOptionDelete($params);
}
else
{
	// new field, or implicit aef_add
}

echo $aeform->AddEditField($id, $aefield, (isset($params['fbrp_dispose_only'])?$params['fbrp_dispose_only']:0), $returnid, isset($params['fbrp_message'])?$this->ShowMessage($params['fbrp_message']):'');

?>
