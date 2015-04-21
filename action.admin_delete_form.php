<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
if (! $this->CheckAccess()) exit;

$aeform = new fbForm($this, $params, true);
$aeform->Delete();

$params['fbrp_message'] = $this->Lang('form_deleted');
$this->Redirect($id, 'defaultadmin', '', $params);

?>
