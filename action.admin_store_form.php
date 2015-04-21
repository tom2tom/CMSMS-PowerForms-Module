<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
if (isset ($params['fbrp_cancel']))
{
  unset ($params); //maybe a message param?
  $this->Redirect($id, 'defaultadmin');
}

if (! $this->CheckAccess()) exit;

// Store data
$aeform = new fbForm($this, $params, true);
if ($aeform->Store())
{
  if ($params['fbrp_submit'] == $this->Lang('save')) //submit
  {
	$op = $params['fbrp_form_op'];
	unset ($params);
	$params['fbrp_message'] = $this->Lang('form',$params['fbrp_form_op']);
	$this->Redirect($id, 'defaultadmin', '', $params);
  }
  else //apply
  {
	$tab = $this->GetActiveTab($params);
	echo $aeform->AddEditForm($id, $returnid, $tab, $this->Lang('form',$params['fbrp_form_op']));
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
