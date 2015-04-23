<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$aeform = new pwfForm($this, $params, true);
$aefield = $aeform->NewField($params);
$val = $aefield->AdminValidate();
if($val[0])
{
	$aefield->PostAdminSubmitCleanup();
	$aefield->Store(true);
	$aefield->PostFieldSaveProcess($params);
	$params['fbrp_message']=$params['fbrp_op'];
	//DO NOT ->Redirect - that flattens any $params[] that's an array
	$this->DoAction('admin_add_edit_form', $id, $params);
}
else
{
	$aefield->LoadField($params);
	$params['fbrp_message'] = $val[1];
	echo $aeform->AddEditField($id, $aefield, (isset($params['fbrp_dispose_only'])?$params['fbrp_dispose_only']:0), $returnid);
}

?>
