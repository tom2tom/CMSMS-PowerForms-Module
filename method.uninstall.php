<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
if (! $this->CheckAccess()) exit;

$pref = cms_db_prefix();

$dict = NewDataDictionary($db);
$sqlarray = $dict->DropTableSQL($pref.'module_fb_form');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_fb_form_seq');

$sqlarray = $dict->DropTableSQL($pref.'module_fb_form_attr');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_fb_form_attr_seq');

$sqlarray = $dict->DropTableSQL($pref.'module_fb_field');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_fb_field_seq');

$sqlarray = $dict->DropTableSQL($pref.'module_fb_field_opt');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_fb_field_opt_seq');

$sqlarray = $dict->DropTableSQL($pref.'module_fb_flock');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL($pref.'module_fb_resp_val');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_fb_resp');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($pref.'module_fb_resp_attr');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL($pref.'module_fb_ip_log');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL($pref.'module_fb_formbrowser');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence($pref.'module_fb_resp_seq');
$db->DropSequence($pref.'module_fb_resp_val_seq');
$db->DropSequence($pref.'module_fb_resp_attr_seq');
$db->DropSequence($pref.'module_fb_ip_log_seq');
$db->DropSequence($pref.'module_fb_formbrowser_seq');
$db->DropSequence($pref.'module_fb_uniquefield_seq');

$this->RemovePreference();

$this->RemovePermission('Modify Forms');

$this->RemoveEvent( 'OnFormBuilderFormSubmit' );
$this->RemoveEvent( 'OnFormBuilderFormDisplay' );
$this->RemoveEvent( 'OnFormBuilderFormSubmitError' );

$db->Execute('DELETE FROM '.$pref.'css WHERE css_name = ?', array('FormBuilder Default Style'));

?>
