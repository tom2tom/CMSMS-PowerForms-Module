<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!$this->_CheckAccess('ModifyPFSettings')) {
	exit;
}
/*
$dict = NewDataDictionary($db);
$taboptarray = ['mysql' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci',
'mysqli' => 'ENGINE MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
$pre = cms_db_prefix();
*/
switch ($oldversion) {
/* case '0.7':
	$cfuncs = new PWForms\Crypter($this);
	$key = 'masterpass';
	$s = base64_decode($this->GetPreference($key));
	$t = $config['ssl_url'].$this->GetModulePath();
	$val = hash('crc32b',$this->GetPreference('nQCeESKBr99A').$t);
	$pw = $cfuncs->decrypt($s,$val);
	if (!$pw) {
		$pw = base64_decode('U3VjayBpdCB1cCwgY3JhY2tlcnMhIFlvdSBjYW4ndCBndWVzcw==');
	}
	$this->RemovePreference($key);
	$this->RemovePreference('nQCeESKBr99A');
	$cfuncs->init_crypt();
	$cfuncs->encrypt_preference(PWForms\Crypter::MKEY,$pw);
*/
}
