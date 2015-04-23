<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$aeform = new pwfForm($this, $params, true);
$aefield = $aeform->Replicate($params);
if($aefield)
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
