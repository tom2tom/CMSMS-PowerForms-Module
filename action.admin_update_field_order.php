<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$aeform = new pwfForm($this, $params, true);
$srcIndex = $aeform->GetFieldIndexFromId($params['field_id']);
if($params['fbrp_dir'] == 'up')
{
	$destIndex = $srcIndex - 1;
}
else
{
	$destIndex = $srcIndex + 1;
}
$aeform->SwapFieldsByIndex($srcIndex,$destIndex);

// force reload of form, this is kinda hackish but can't think of anything else ;)
$aeform = new pwfForm($this, $params, true);
$tab = $this->GetActiveTab($params);

echo $aeform->AddEditForm($id, $returnid, $tab, $this->Lang('field_order_updated'));
?>
