<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//this action processes ajax-calls

if (preg_match('/\.tpl$/',$params['tid']))
    $tplstr = ''.@file_get_contents(cms_join_path(__DIR__,'templates',$params['tid']));
else {
	$sql = 'SELECT value,longvalue FROM '.cms_db_prefix().'module_pwf_formdata WHERE form_id=? AND name=\'form_template\'';
	$data = $db->GetRow($sql,array($params['tid']));
	$tplstr = $data['longvalue'];
	if (!$tplstr) $tplstr = $data['value'];
	if ($tplstr && strncmp($tplstr,'pwf_',4) == 0) {
		$name = 'tpl::'.substr($tplstr,4);
		if ($this->before20)
			$tplstr = $this->GetTemplate($name);
		else {
			$ob = CmsLayoutTemplate::load($name);
			$tplstr = $ob->get_content();
		}
	}
}

if ($tplstr) {
	@ob_clean();
	@ob_clean();
	header('Pragma: public');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Cache-Control: private',FALSE);
	header('Content-Description: File Transfer');
	header('Content-Length: '.strlen($tplstr));
	echo $tplstr;
}
exit;
