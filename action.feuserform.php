<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
$feu = &$this->GetModuleInstance('FrontEndUsers');
if ($feu == false || $feu->LoggedIn() == false)
	return;
$this->Redirect($id, 'default', $returnid, $params);
?>
