<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

if (isset($params['active_tab'])) {
	//we've reached here via a request or redirect
	$cache = PWForms\Utils::GetCache();
	if (!$cache) {
		echo 'system error';
		return;
	}
	$formdata = $cache->get(PWForms::ASYNCSPACE, $params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		echo 'system error';
		return;
	}
	if (is_array($params['field_id'])) {
		$all = $params['field_id'];
	} else {
		$all = [$params['field_id']];
	}
	//null/mark field(s) for deletion during store
	foreach ($all as $one) {
		$fid = (int)$one;
		$formdata->Fields[$fid] = NULL;
		if ($formdata->FieldOrders) {
			$key = array_search($fid, $formdata->FieldOrders);
			if ($key !== FALSE) {
				unset($formdata->FieldOrders[$key]);
			}
		}
	}
	$cache->set(PWForms::ASYNCSPACE, $params['datakey'], $formdata, 84600);

	$this->Redirect($id, 'open_form', $returnid, [
		'form_id'=>$params['form_id'],
		'active_tab'=>$params['active_tab'],
		'datakey'=>$params['datakey']
	]);
} else {
	//we've reached here via an ajax-call
	$cache = PWForms\Utils::GetCache();
	if (!$cache) {
		echo '0';
		exit;
	}
	$formdata = $cache->get(PWForms::ASYNCSPACE, $params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		echo '0';
		exit;
	}
	//null/mark field(s)
	if (strpos($params['field_id'], ',') !== FALSE) {
		$all = explode(',', $params['field_id']);
	} else {
		$all = [$params['field_id']];
	}
	foreach ($all as $one) {
		$fid = (int)$one;
		$formdata->Fields[$fid] = NULL;
		if ($formdata->FieldOrders) {
			$key = array_search($fid, $formdata->FieldOrders);
			if ($key !== FALSE) {
				unset($formdata->FieldOrders[$key]);
			}
		}
	}
	$cache->set(PWForms::ASYNCSPACE, $params['datakey'], $formdata, 84600);

	echo '1';
	exit;
}
