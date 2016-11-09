<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

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

//mark for deletion during Store
$fid = (int)$params['field_id'];
$ob = new stdClass();
$ob->Id = $fid;
$formdata->Fields[$fid] = $ob;
if ($formdata->FieldOrders) {
	$key = array_search($fid,$formdata->FieldOrders);
	if ($key !== FALSE)
		unset($formdata->FieldOrders[$key]);
}
$cache->set($params['datakey'],$formdata,84600);

echo '1';
exit;
