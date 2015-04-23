<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(isset ($params['fbrp_cancel']))
{
	unset ($params); //maybe a message param?
	$this->Redirect($id, 'defaultadmin');
}

if(!$this->CheckAccess()) exit;

// Store data
$funcs = new pwfUtils($this, $params, true);
if($funcs->Store())
{
	if($params['fbrp_submit'] == $this->Lang('save')) //submit
	{
		$op = $params['fbrp_form_op'];
		unset ($params);
		$params['fbrp_message'] = $this->Lang('form',$params['fbrp_form_op']);
		$this->Redirect($id, 'defaultadmin', '', $params);
	}
	else //apply
	{
		$tab = $this->GetActiveTab($params);
		echo $funcs->AddEditForm($id, $returnid, $tab, $this->Lang('form',$params['fbrp_form_op']));
	}
}
else
{
	//error msg set downstream
	$msg = $params['fbrp_message'];
	unset ($params);
	$params['fbrp_message'] = $this->ShowErrors($msg);
	$this->Redirect($id, 'defaultadmin', '', $params);
}

?>
