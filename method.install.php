<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$pre = cms_db_prefix();
$taboptarray = array('mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci');
$dict = NewDataDictionary($db);

$flds = "
	form_id I KEY,
	name C(256),
	alias C(32)
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_form',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_form_seq');
$db->Execute('create index '.$pre.'module_pwf_form_idx on '.$pre.'module_pwf_form (alias)');

$flds = "
	option_id I KEY,
	form_id I,
	name C(64),
	value X
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_form_opt',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_form_opt_seq');
$db->Execute('create index '.$pre.'module_pwf_form_opt_idx on '.$pre.'module_pwf_form_opt (form_id)');

$flds = "
	field_id I KEY,
	form_id I,
	name C(96),
	type C(48),
	order_by I(2)
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_field',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_field_seq');
$db->Execute('CREATE INDEX '.$pre.'module_pwf_field_idx ON '.$pre.'module_pwf_field (form_id)');

$flds = "
	option_id I KEY,
	field_id I,
	form_id I,
	name C(256),
	value X
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_field_opt',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_field_opt_seq');
$db->Execute('CREATE INDEX '.$pre.'module_pwf_field_opt_idx ON '.$pre.'module_pwf_field_opt (field_id,form_id)');

$flds = "
	flock_id I KEY,
	flock T
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_flock',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = "
	record_id I(4) KEY,
	code C(16),
	submitted ".CMS_ADODB_DT.",
	contents B
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_record',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_record_seq');

$flds = "
	cache_id I(2) AUTO KEY,
	keyword C(48),
	value B,
	save_time ".CMS_ADODB_DT;
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_cache',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = "
	log_id I(2) KEY AUTO,
	src C(40),
	howmany I(2) DEFAULT 1,
	basetime ".CMS_ADODB_DT;
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_ip_log',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = "
	trans_id I(2) AUTO KEY,
	old_id I(2),
	new_id I(2),
	isform I(1)
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_trans',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_uniquefield_seq');

$this->SetPreference('default_phrase',uniqid('Enter at your own risk! Dangerous data!')); //TODO make this adjustable via UI
$this->SetPreference('blank_invalid',0);
$this->SetPreference('enable_antispam',1);
$this->SetPreference('require_fieldnames',1);
$this->SetPreference('adder_fields','basic'); //or 'advanced'

$fp = $config['uploads_path'];
if($fp && is_dir($fp))
{
	$ud = $this->GetName();
	$fp = $fp.DIRECTORY_SEPARATOR.$ud;
	if(!(is_dir($fp) || mkdir($fp,0644)))
		$ud = '';
}
else
	$ud = '';
$this->SetPreference('uploads_dir',$ud); //path relative to host uploads dir

$this->CreatePermission('ModifyPFForms',$this->Lang('perm_modify'));
$this->CreatePermission('ModifyPFSettings',$this->Lang('perm_admin'));

$this->CreateEvent('OnFormDisplay');
$this->CreateEvent('OnFormSubmit');
$this->CreateEvent('OnFormSubmitError');

$css = @file_get_contents(cms_join_path(dirname(__FILE__),'css','default.css'));
$css_id = $db->GenID($pre.'css_seq');
$db->Execute('INSERT INTO '.$pre.'css (css_id,css_name,css_text,media_type,create_date) VALUES (?,?,?,?,?)',
	array($css_id,'PowerForms Default Style',$css,'screen',date('Y-m-d')));

$funcs = new pwfFormOperations();
$path = cms_join_path(dirname(__FILE__),'include');
$dir = opendir($path);
while($filespec = readdir($dir))
{
	if(preg_match('/.xml$/',$filespec) > 0)
	{
		$fp = cms_join_path($path,$filespec);
		$funcs->ImportXML($this,$fp);
	}
}


?>
