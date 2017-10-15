<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

$pre = cms_db_prefix();
$dict = NewDataDictionary($db);

$sqlarray = $dict->DropIndexSQL($pre.'module_pwf_field_idx', $pre.'module_pwf_field');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_field');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropIndexSQL($pre.'module_pwf_form_idx', $pre.'module_pwf_form');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_form');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_ip_log');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_session');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pre.'module_pwf_trans');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pre.'module_pwf_uniquefield_seq');

if ($this->oldtemplates) {
	$this->DeleteTemplate();
} else {
	$types = CmsLayoutTemplateType::load_all_by_originator($this->GetName());
	if ($types) {
		foreach ($types as $type) {
			$templates = $type->get_template_list();
			if ($templates) {
				foreach ($templates as $tpl) {
					$tpl->delete();
				}
			}
			$type->delete();
		}
	}
}

$cache = PWForms\Utils::GetCache();
if ($cache) {
	$cache->clearall(PWForms::ASYNCSPACE);
}
/*$mutex = ;
if ($mutex) {
	$mutex->cleanall(PWForms::ASYNCSPACE);
}
*/

$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$ud = $this->GetPreference('uploads_dir');
	if ($ud) {
		$fp .= DIRECTORY_SEPARATOR.$ud;
		if (is_dir($fp)) {
			recursive_delete($fp);
		}
	}
}

$this->RemovePreference();

$this->RemovePermission('ModifyPFForms');
$this->RemovePermission('ModifyPFSettings');

$this->RemoveEvent('OnFormDisplay');
$this->RemoveEvent('OnFormSubmit');
$this->RemoveEvent('OnFormSubmitError');

$db->Execute('DELETE FROM '.$pre.'css WHERE css_name = ?', ['PWForms Default Style']);
