<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//this action processes ajax-calls

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

$obfld = $formdata->Fields[$params['field_id']];
if ($obfld !== FALSE) {
	$obfld->SetRequired(($params['reqd']=='on'));

	$cache->set($params['datakey'], $formdata, 84600);
}

echo '1';
exit;
