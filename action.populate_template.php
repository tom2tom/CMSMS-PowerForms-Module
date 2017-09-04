<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
//get default template for form-submission or a field
//this action processes ajax

if (isset($params['datakey'])) {
	try {
		$cache = PWForms\Utils::GetCache($this);
	} catch (Exception $e) {
		echo '0';
		exit;
	}
	$formdata = $cache->get($params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		echo '0';
		exit;
	}
}

if (isset($params['form_id'])) {
	$tplstr = PWForms\Utils::CreateDefaultTemplate($formdata, TRUE, FALSE);
	echo $tplstr;
} elseif (isset($params['field_id'])) {
	if (!empty($params['captcha'])) {
		$obfld = $formdata->Fields[$params['field_id']];
		if ($obfld) {
			$tplstr = $obfld->GetDefaultTemplate();
		} else {
			echo '0';
			exit;
		}
	} else {
		$tplstr = PWForms\Utils::CreateDefaultTemplate($formdata,
			!empty($params['html']),
			!empty($params['email']));
	}
	echo $tplstr;
} else {
	echo '0';
}
exit;
