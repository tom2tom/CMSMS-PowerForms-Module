<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$this->initialize();
$dict = NewDataDictionary($db);
//$taboptarray = array('mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
//'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci');
$pref = cms_db_prefix();
switch($oldversion)
{
/*
		$sqlarray = $dict->AlterColumnSQL($pref."module_fb_ip_log", "src_ip C(40)");
		$dict->ExecuteSQLArray ($sqlarray);
		//renamed subdir
		$dir = cms_join_path(dirname(__FILE__),'classes');
		if(is_dir($dir))
		{
			$files = array_diff(scandir($dir),array('.','..'));
			if($files)
			{
				foreach($files as $file)
				{
					$fp = cms_join_path($dir,$file);
					unlink($fp);
				}
				unset($files);
			}
			rmdir($dir);
		}


foreach (array(
	'module_fb_field_opt_seq',
	'module_fb_field_seq',
	'module_fb_form_attr_seq',
	'module_fb_form_seq',
	'module_fb_formbrowser_seq',
	'module_fb_ip_log_seq',
	'module_fb_resp_attr_seq',
	'module_fb_resp_seq',
	'module_fb_resp_val_seq',
	'module_fb_uniquefield_seq,
	'module_fb_field',
	'module_fb_field_opt',
	'module_fb_flock',
	'module_fb_form',
	'module_fb_form_attr',
	'module_fb_formbrowser',
	'module_fb_ip_log',
	'module_fb_resp',
	'module_fb_resp_attr',
	'module_fb_resp_val') as $name)
{
	$oldname = $pref.$name;
	$newname = $pref.str_replace('_fb_','_pwf_',$name);
	$sqlarray = $dict->;
	$dict->ExecuteSQLArray ($sqlarray);
}

foreach (array(
	'module_fb_field_idx',
	'module_fb_field_opt_idx') as $name)
{
	$oldname = $pref.$name;
	$newname = $pref.str_replace('_fb_','_pwf_',$name);
	$sqlarray = $dict->;
	$dict->ExecuteSQLArray ($sqlarray);
}

$this->RemovePermission('Modify Forms');
$this->CreatePermission('ModifyForms',$this->Lang('perm_modify'));

$this->RemoveEvent('OnFormBuilderFormDisplay');
$this->RemoveEvent('OnFormBuilderFormSubmit');
$this->RemoveEvent('OnFormBuilderFormSubmitError');
$this->CreateEvent('OnFormDisplay');
$this->CreateEvent('OnFormSubmit');
$this->CreateEvent('OnFormSubmitError');

$db->Execute('UPDATE '.$pref.'css SET css_name=? WHERE css_name=?',
	array('PowerForms Default Style','FormBuilder Default Style'));
*/

}

?>
