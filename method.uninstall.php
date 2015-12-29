<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//NB caller must be very careful that top-level dir is valid!
function delTree($dir)
{
	$files = array_diff(scandir($dir),array('.','..'));
	if($files)
	{
		foreach($files as $file)
		{
			$fp = cms_join_path($dir,$file);
			if(is_dir($fp))
			{
			 	if(!delTree($fp))
					return FALSE;
			}
			else
				unlink($fp);
		}
		unset($files);
	}
	return rmdir($dir);
}

$pre = cms_db_prefix();
$dict = NewDataDictionary($db);

$sqlarray = $dict->DropTableSQL($pre.'module_pwf_field');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_field_opt');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_flock');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_form');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_form_opt');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_cache');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_ip_log');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_record');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_trans');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pre.'module_pwf_field_seq');
$db->DropSequence($pre.'module_pwf_field_opt_seq');
$db->DropSequence($pre.'module_pwf_form_seq');
$db->DropSequence($pre.'module_pwf_form_opt_seq');
$db->DropSequence($pre.'module_pwf_record_seq');
$db->DropSequence($pre.'module_pwf_uniquefield_seq');

if($this->before20)
	$this->DeleteTemplate();
else
{
	$types = CmsLayoutTemplateType::load_all_by_originator($this->GetName());
	if($types)
	{
		foreach($types as $type)
		{
			$templates = $type->get_template_list();
			if($templates)
			{
				foreach($templates as $tpl)
					$tml->delete();
			}
			$type->delete();
		}
	}
}

$fp = $config['uploads_path'];
if($fp && is_dir($fp))
{
	$upd = $this->GetPreference('uploads_dir');
	if($upd)
	{
		$fp = cms_join_path($fp,$upd);
		if($fp && is_dir($fp))
			delTree($fp);
	}
}
$this->RemovePreference();

$this->RemovePermission('ModifyPFForms');
$this->RemovePermission('ModifyPFSettings');

$this->RemoveEvent('OnFormDisplay');
$this->RemoveEvent('OnFormSubmit');
$this->RemoveEvent('OnFormSubmitError');

$db->Execute('DELETE FROM '.$pre.'css WHERE css_name = ?', array('PowerForms Default Style'));

?>
