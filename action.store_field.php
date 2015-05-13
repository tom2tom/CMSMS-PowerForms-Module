<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

TODO $funcs = new pwfFieldOperations($this, $params, true);
$obfield = $funcs->NewField($this,$params);
$val = $obfield->AdminValidate();
if($val[0])
{
	$obfield->PostAdminSubmitCleanup();
	$obfield->Store(true);
	$obfield->PostFieldSaveProcess($params);
	$params['message'] = $params['op'];
	$params['formedit'] = 1;
	//DO NOT ->Redirect - that flattens any $params[] that's an array
	$this->DoAction('update_form',$id,$params);
}
else
{
	$obfield->LoadField($params);
	$params['message'] = $val[1];
	echo $funcs->AddEdit($obfield,(!empty($params['dispose_only'])),$id,$returnid);
}

?>
