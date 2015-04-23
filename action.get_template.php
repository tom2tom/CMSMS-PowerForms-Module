<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(preg_match('/\.tpl$/',$params['fbrp_tid']))
{
    $tplstr = file_get_contents(cms_join_path(dirname(__FILE__),'templates',$params['fbrp_tid']));
}
else
{
    $query = "SELECT value FROM ".cms_db_prefix().
		"module_fb_form_attr WHERE form_id=? AND name='form_template'";
	$rs = $db->Execute($query,array($params['fbrp_tid']));
	if($rs)
	{
		$row = $rs->FetchRow();
		$tplstr = $row['value'];
		$rs->Close();
	}
}

@ob_clean();
@ob_clean();
header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private',false);
header('Content-Description: File Transfer');
header('Content-Length: ' . strlen($tplstr));
echo $tplstr;
exit;

?>
