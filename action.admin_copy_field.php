<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
if (! $this->CheckAccess()) exit;

$aeform = new fbForm($this, $params, true);
$aefield = $aeform->Replicate($params);
if ($aefield)
{
	$aefield->Store(true);
	$aeform->Fields[] = $aefield;
	echo $aeform->AddEditField ($id, $aefield, 0, $returnid);
}
else
{
	$tab = $this->GetActiveTab($params);
	echo $aeform->AddEditForm($id, $returnid, $tab, $this->Lang('error_copy'));
}

?>
