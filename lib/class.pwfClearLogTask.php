<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfClearLogTask implements CmsRegularTask
{
	const MODNAME = 'PowerForms';

	public function get_name() 
	{
		return get_class($this);
	}

	public function get_description()
	{
		$module = cms_utils::get_module(self::MODNAME);
		return $module->Lang('taskdescription_clearlog');
	}

	public function test($time = '')
	{
		$module = cms_utils::get_module(self::MODNAME);
		if(!$module->GetPreference('enable_antispam'))
			return FALSE;
		if(!$time) $time = time();
		$last_cleared = $module->GetPreference('lastcleared',0);
		return ($time >= $last_cleared + 86400);
	}

	public function execute($time = '')
	{
		$module = cms_utils::get_module(self::MODNAME);
		if(!$time) $time = time();
		pwfUtils::CleanLog($module,$time);
		return TRUE;
	}

	public function on_success($time = '')
	{
		$module = cms_utils::get_module(self::MODNAME);
		if(!$time) $time = time();
		$module->SetPreference('lastcleared',$time);
	}

	public function on_failure($time = '')
	{
	}
}

?>
