<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

$padm = $this->CheckPermission('ModifyPFSettings');
$pmod = $this->CheckPermission('ModifyPFForms');
if (!($padm || $pmod)) {
	exit;
}
$pdev = $this->CheckPermission('Modify Any Page');

if ($padm) {
	if (isset($params['submit'])) {
		$this->SetPreference('blank_invalid', !empty($params['blank_invalid']));
		$this->SetPreference('require_fieldnames', !empty($params['require_fieldnames']));
		$t = $params['submit_limit'];
		if ($t > 250) {
			$t = 250;
		} elseif ($t < 0) {
			$t = 0;
		}
		$this->SetPreference('submit_limit', $t);
		$this->SetPreference('email_topdomains', $params['email_topdomains']);
		$this->SetPreference('email_domains', $params['email_domains']);
		$this->SetPreference('email_subdomains', $params['email_subdomains']);
		$t = trim($params['uploads_dir']);
		if ($t && $t[0] == DIRECTORY_SEPARATOR) {
			$t = substr($t, 1);
		}
		if ($t) {
			$fp = $config['uploads_path'];
			if ($fp && is_dir($fp)) {
				$fp = $fp.DIRECTORY_SEPARATOR.$t;
				if (!(is_dir($fp) || mkdir($fp, 0755))) {
					$t = '';
				}
			} else {
				$t = '';
			}
		}
		$this->SetPreference('uploads_dir', $t);

		$cfuncs = new PWForms\Crypter($this);
		$key = PWForms\Crypter::MKEY;
		$oldpw = $cfuncs->decrypt_preference($key);
		$t = trim($params[$key]);
		if ($oldpw != $t) {
			//re-encrypt all stored records
			$pre = cms_db_prefix();
			$rst = $db->Execute('SELECT sess_id,content FROM '.$pre.'module_pwf_session');
			if ($rst) {
				$sql = 'UPDATE '.$pre.'module_pwf_session SET content=? WHERE sess_id=?';
				while (!$rst->EOF) {
					$val = $cfuncs->decrypt_value($rst->fields['content'], $oldpw);
					$val = $cfuncs->encrypt_value( $val, $t);
					if (!PWForms\Utils::SafeExec($sql, [$val, $rst->fields['sess_id']])) {
						//TODO handle error
					}
					if (!$rst->MoveNext()) {
						break;
					}
				}
				$rst->Close();
			}
			$cfuncs->encrypt_preference($key, $t);
		}

		$params['message'] = $this->_PrettyMessage('settings_updated');
		$params['active_tab'] = 'settings';
	} elseif (isset($params['cancel'])) {
		$params['active_tab'] = 'settings';
	}
}

$tplvars = [];

require __DIR__.DIRECTORY_SEPARATOR.'populate.defaultadmin.php';

$jsall = NULL;
PWForms\Utils::MergeJS($jsincs, $jsfuncs, $jsloads, $jsall);
unset($jsincs);
unset($jsfuncs);
unset($jsloads);

echo PWForms\Utils::ProcessTemplate($this, 'adminpanel.tpl', $tplvars);
if ($jsall) {
	echo $jsall;
}
