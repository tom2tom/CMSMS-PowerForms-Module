<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
//get a recorded (main or submission) form-template or content of a template file
//this action processes ajax-calls

if (preg_match('/\.tpl$/', $params['tid'])) {
	$tplstr = ''.@file_get_contents(cms_join_path(__DIR__, 'templates', $params['tid']));
} else {
	$sql = 'SELECT value,longvalue FROM '.cms_db_prefix().'module_pwf_formprops WHERE form_id=? AND name=\'form_template\'';
	$data = $db->GetRow($sql, [$params['tid']]);
	$tplstr = $data['longvalue'];
	if (!$tplstr) {
		$tplstr = $data['value'];
	}
	if ($tplstr && strncmp($tplstr, 'pwf_', 4) == 0) {
		if ($this->oldtemplates) {
			$tplstr = $this->GetTemplate($tplstr);
		} else {
			$ob = CmsLayoutTemplate::load($tplstr);
			$tplstr = $ob->get_content();
		}
	}
}
echo $tplstr;
exit;
