<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class ClearcacheTask implements \CmsRegularTask
{
	const MODNAME = 'PWForms';

	public function get_name()
	{
		return get_class($this);
	}

	public function get_description()
	{
		$mod = \cms_utils::get_module(self::MODNAME);
		return $mod->Lang('taskdescription_clearcache');
	}

	public function test($time='')
	{
		$mod = \cms_utils::get_module(self::MODNAME);
		$dir = Utils::GetUploadsPath($mod);
		if ($dir) {
			foreach (new DirectoryIterator($dir) as $fInfo) {
				$fn = $fInfo->getFilename();
				if (strncmp($fn,'pwf',3) == 0)
					return TRUE;
			}
		}
		//if file-cache N/A, check for database-cache
		$pre = \cms_db_prefix();
		$sql = 'SELECT cache_id FROM '.$pre.'module_pwf_cache';
		$res = $mod->dbHandle->GetOne($sql);
		return ($res != FALSE);
	}

	public function execute($time='')
	{
		if (!$time)
			$time = time();
		$time -= 43200; //half-day cache retention-period (as seconds)
		$mod = \cms_utils::get_module(self::MODNAME);
		$dir = Utils::GetUploadsPath($mod);
		if ($dir) {
			foreach (new DirectoryIterator($dir) as $fInfo) {
				if ($fInfo->isFile() && !$fInfo->isDot()) {
					$fn = $fInfo->getFilename();
					if (strncmp($fn,'pwf',3) == 0) {
						$mtime = $fInfo->getMTime();
						if ($mtime < $time) {
							@unlink($dir.DIRECTORY_SEPARATOR.$fn);
						}
					}
				}
			}
		}
		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwf_cache WHERE savetime+lifetime <?';
		$mod->dbHandle->Execute($sql,array($time));
		return TRUE;
	}

	public function on_success($time='')
	{
	}

	public function on_failure($time='')
	{
	}
}