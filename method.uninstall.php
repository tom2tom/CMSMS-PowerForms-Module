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
					return false;
			}
			else
				unlink($fp);
		}
		unset($files);
	}
	return rmdir($dir);
}

$pref = cms_db_prefix();
$dict = NewDataDictionary($db);

$sqlarray = $dict->DropTableSQL($pref.'module_pwf_field');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_pwf_field_opt');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_pwf_flock');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_pwf_form');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_pwf_form_attr');
$dict->ExecuteSQLArray($sqlarray);
//$sqlarray = $dict->DropTableSQL($pref.'module_pwf_browse');
//$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_pwf_ip_log');
$dict->ExecuteSQLArray($sqlarray);
//$sqlarray = $dict->DropTableSQL($pref.'module_pwf_resp');
//$dict->ExecuteSQLArray($sqlarray);
//$sqlarray = $dict->DropTableSQL($pref.'module_pwf_resp_attr');
//$dict->ExecuteSQLArray($sqlarray);
//$sqlarray = $dict->DropTableSQL($pref.'module_pwf_resp_val');
//$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_pwf_field_seq');
$db->DropSequence($pref.'module_pwf_field_opt_seq');
$db->DropSequence($pref.'module_pwf_form_seq');
$db->DropSequence($pref.'module_pwf_form_attr_seq');
//$db->DropSequence($pref.'module_pwf_browse_seq');
$db->DropSequence($pref.'module_pwf_ip_log_seq');
//$db->DropSequence($pref.'module_pwf_resp_seq');
//$db->DropSequence($pref.'module_pwf_resp_attr_seq');
//$db->DropSequence($pref.'module_pwf_resp_val_seq');
$db->DropSequence($pref.'module_pwf_uniquefield_seq');

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

//CHECKME clean templates
//$this->DeleteTemplate('pwf_*');

$db->Execute('DELETE FROM '.$pref.'css WHERE css_name = ?', array('PowerForms Default Style'));

?>
