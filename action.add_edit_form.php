<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

if(isset($params['pwfp_set_field_level']))
{
	$this->SetPreference('show_field_level',$params['pwfp_set_field_level']);
}

$tab = $this->GetActiveTab($params);

$funcs = new pwfUtils($this, $params, true);

echo $funcs->AddEditForm($id, $returnid, $tab, isset($params['pwfp_message'])?$params['pwfp_message']:'');

?>
