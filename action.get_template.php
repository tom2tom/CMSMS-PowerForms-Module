<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//this action processes ajax-calls

if(preg_match('/\.tpl$/',$params['tid']))
    $tplstr = ''.@file_get_contents(cms_join_path(dirname(__FILE__),'templates',$params['tid']));
else
{
    $sql = 'SELECT value FROM '.cms_db_prefix().
		'module_pwf_form_opt WHERE form_id=? AND name=\'form_template\'';
	$tplstr = $db->GetOne($sql,array($params['tid']));
}

if($tplstr)
{
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

?>
