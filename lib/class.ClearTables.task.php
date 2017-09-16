<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class ClearTablesTask implements \CmsRegularTask
{
	public function get_name()
	{
		return get_class();
	}

	protected function &get_module()
	{
		return \ModuleOperations::get_instance()->get_module_instance('PWForms', '', TRUE);
	}

	public function get_description()
	{
		return $this->get_module()->Lang('taskdescription_cleartables');
	}

	public function test($time = '')
	{
		if (!$time) {
			$time = time();
		}
		$last_cleared = $this->get_module()->GetPreference('lastcleared', 0);
		return ($time >= $last_cleared + 1800);
	}

	public function execute($time = '')
	{
		if (!$time) {
			$time = time();
		}
		$mod = $this->get_module();
		Utils::CleanTables($mod, $time);
		return TRUE;
	}

	public function on_success($time = '')
	{
		if (!$time) {
			$time = time();
		}
		$this->get_module()->SetPreference('lastcleared', $time);
	}

	public function on_failure($time = '')
	{
	}
}
