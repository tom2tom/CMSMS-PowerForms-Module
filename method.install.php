<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/*QUEUE
if (!function_exists('curl_init'))
	return 'PWForms needs the PHP cURL extension';
TODO mutex check
*/
//TODO cache check

$pre = cms_db_prefix();
$taboptarray = ['mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = NewDataDictionary($db);

$flds = '
form_id I(8) AUTO KEY,
name C(256),
alias C(32)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_form', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->Execute('CREATE INDEX '.$pre.'module_pwf_form_idx ON '.$pre.'module_pwf_form (alias)');

$flds = '
prop_id I(8) AUTO KEY,
form_id I(8),
name C(64),
value C('.PWForms::LENSHORTVAL.'),
longvalue X(16384)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_formprops', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->Execute('CREATE INDEX '.$pre.'module_pwf_formprops_idx ON '.$pre.'module_pwf_formprops (form_id)');

$flds = '
field_id I(8) KEY,
form_id I(8),
name C(96),
alias C(32),
type C(48),
order_by I(2)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_field', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_field_seq');
$db->Execute('CREATE INDEX '.$pre.'module_pwf_field_idx ON '.$pre.'module_pwf_field (form_id)');

$flds = '
prop_id I(8) KEY,
field_id I(8),
form_id I(8),
name C(256),
value C('.PWForms::LENSHORTVAL.'),
longvalue X(16384)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_fieldprops', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_fieldprops_seq');
$db->Execute('CREATE INDEX '.$pre.'module_pwf_fieldprops_idx ON '.$pre.'module_pwf_fieldprops (field_id,form_id)');

$flds = '
sess_id I(4) AUTO KEY,
pubkey C(40),
submitted I,
content B(16384)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_session', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

/*MUTEX
$flds = '
flock_id I(8) KEY,
flock T
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_flock',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);
*/

$flds = '
cache_id I(2) AUTO KEY,
keyword C(48),
value B(16384),
savetime I(8),
lifetime I(4)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_cache', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
log_id I(2) AUTO KEY,
src C(40),
howmany I(2) DEFAULT 1,
basetime I
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_ip_log', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
trans_id I(2) AUTO KEY,
old_id I(2),
new_id I(2),
isform I(1)
';
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_trans', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_uniquefield_seq');

$cfuncs = new PWForms\CryptInit($this);
$cfuncs->init_crypt();
$t = substr(str_shuffle(base64_encode(time().$config['root_url'].rand(10000000, 99999999))), 0, 10);
$t = sprintf(base64_decode('U29tZSByYW5kb21uZXNzICglcykgaXMgaW5jbHVkZWQ='), $t);
$cfuncs->encrypt_preference(PWForms\Crypter::MKEY, $t);

$this->SetPreference('adder_fields', 'basic'); //or 'advanced'
$this->SetPreference('blank_invalid', 0);
//for email address checking by mailcheck.js
$this->SetPreference('email_domains', ''); //specific/complete domains for initial check
$this->SetPreference('email_subdomains', ''); //partial domains for secondary check
$this->SetPreference('email_topdomains', 'biz,co,com,edu,gov,info,mil,name,net,org'); //for final check
$this->SetPreference('require_fieldnames', 1);
$this->SetPreference('submit_limit', 0);

$fp = $config['uploads_path'];
if ($fp && is_dir($fp)) {
	$ud = $this->GetName();
	$fp = $fp.DIRECTORY_SEPARATOR.$ud;
	if (!(is_dir($fp) || mkdir($fp, 0777, TRUE))) { //don't know how server is running!
		$ud = '';
	}
} else {
	$ud = '';
}
$this->SetPreference('uploads_dir', $ud); //path relative to host uploads dir

$this->CreatePermission('ModifyPFForms', $this->Lang('perm_modify'));
$this->CreatePermission('ModifyPFSettings', $this->Lang('perm_admin'));

$this->CreateEvent('OnFormDisplay');
$this->CreateEvent('OnFormSubmit');
$this->CreateEvent('OnFormSubmitError');

$css = @file_get_contents(cms_join_path(__DIR__, 'css', 'default.css'));
$css_id = $db->GenID($pre.'css_seq');
$db->Execute('INSERT INTO '.$pre.'css (css_id,css_name,css_text,media_type,create_date) VALUES (?,?,?,?,?)',
	[$css_id, 'PWForms Default Style', $css, 'screen', date('Y-m-d')]);

if (!$this->oldtemplates) {
	$myname = $this->GetName();
//	$me = get_userid(FALSE);
	foreach (['form', 'submission'] as $name) {
		$ttype = new CmsLayoutTemplateType();
		$ttype->set_originator($myname);
		$ttype->set_name($myname.$name);
//		$ttype->set_owner($me);
//		$ttype->set_dflt_flag(TRUE);
		try {
			$ttype->save();
//			$tid = $ttype->get_id();
		} catch (Exception $e) {
//			$tid = FALSE;
		}
	}
}

$funcs = new PWForms\FormOperations();
$path = cms_join_path(__DIR__, 'lib', 'init');
$dir = opendir($path);
while ($filespec = readdir($dir)) {
	if (preg_match('/.xml$/', $filespec) > 0) {
		$fp = cms_join_path($path, $filespec);
		$funcs->ImportXML($this, $fp);
	}
}
