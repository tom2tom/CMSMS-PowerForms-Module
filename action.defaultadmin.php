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
		$this->SetPreference('require_fieldnames',!empty($params['require_fieldnames']));
		$t = $params['submit_limit'];
		if($t > 250) $t = 250;
		elseif($t < 0) $t = 0;
		$this->SetPreference('submit_limit',$t);
		$this->SetPreference('email_topdomains',$params['email_topdomains']);
		$this->SetPreference('email_domains',$params['email_domains']);
		$this->SetPreference('email_subdomains',$params['email_subdomains']);
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

		$old = $this->GetPreference('masterpass');
		if($old)
			$old = pwfUtils::Unfusc($oldpw);
		$t = trim($params['masterpass']);
		if($old != $t)
		{
			//re-encrypt all stored records
			$pre = cms_db_prefix();
			$rst = $db->Execute('SELECT record_id,content FROM '.$pre.'module_pwf_record');
			if($rst)
			{
				$sql = 'UPDATE '.$pre.'module_pwf_record SET content=? WHERE record_id=?';
				while(!$rst->EOF)
				{
					$val = pwfUtils::Decrypt($this,$rst->fields[1],$old);
					$val = pwfUtils::Encrypt($this,$val,$t);
					if(!pwfUtils::SafeExec($sql,array($val,$rst->fields[0])))
					{
						//TODO handle error
					}
					if(!$rst->MoveNext())
						break;
				}
				$rst->Close();
			}
			if($t)
				$t = pwfUtils::Fusc($t);
			$this->SetPreference('masterpass',$t);
		}

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
