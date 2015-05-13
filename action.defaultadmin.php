<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$padm = $this->CheckPermission('ModifyPFSettings');
$pmod = $this->CheckPermission('ModifyPFForms');
if(!($padm || $pmod)) exit;
$pdev = $this->CheckPermission('Modify Any Page');

if($padm)
{
	if(isset($params['submit']))
	{
		$this->SetPreference('blank_invalid',!empty($params['blank_invalid']));
		$this->SetPreference('enable_antispam',!empty($params['enable_antispam']));
		$this->SetPreference('require_fieldnames',!empty($params['require_fieldnames']));
		$t = trim($params['uploads_dir']);
		if($t && $t[0] == DIRECTORY_SEPARATOR)
			$t = substr($t,1);
		if($t)
		{
			$fp = $config['uploads_path'];
			if($fp && is_dir($fp))
			{
				$fp = $fp.DIRECTORY_SEPARATOR.$t;
				if(!(is_dir($fp) || mkdir($fp,0644)))
					$t = '';
			}
			else
				$t = '';
		}
		$this->SetPreference('uploads_dir',$t);

		$params['message'] = $this->PrettyMessage('settings_updated');
		$params['active_tab'] = 'settings';
	}
	elseif(isset($params['cancel']))
	{
		$params['active_tab'] = 'settings';
	}
}

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.defaultadmin.php';

echo $this->ProcessTemplate('adminpanel.tpl');

?>
