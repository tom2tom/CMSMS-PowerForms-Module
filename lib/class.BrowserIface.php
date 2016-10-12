<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

# methods for use by PowerBrowse module

namespace PWForms;

class BrowserIface
{	
	//forms are considered browsable if they include a 'DispositionFormBrowser' field
	//returns array in which key = form id, value = form name
	public function GetBrowsableForms()
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT DISTINCT FM.form_id,FM.name FROM {$pre}module_pwf_form FM
JOIN {$pre}module_pwf_field FD ON FM.form_id=FD.form_id
WHERE FD.type='DispositionFormBrowser'
EOS;
		$db = \cmsms()->GetDb();
		return $db->GetAssoc($sql);
	}

	//fields are considered browsable if they are flagged as sortable or input or explicitly DisplayExternal
	//returns array in which key = field id, value = field name
	public function GetBrowsableFields($form_id)
	{
		$pre = \cms_db_prefix();
		$sql = <<<EOS
SELECT field_id,name,type FROM {$pre}module_pwf_field
WHERE form_id=? AND type LIKE '%Field%' ORDER BY order_by
EOS;
		$db = \cmsms()->GetDb();
		$all = $db->GetAssoc($sql,array($form_id));
		$result = array();
		if ($all) {
			$mod = \cms_utils::get_module('PWForms');
			$dummy = $mod->GetFormData();
			$params = array();
			foreach ($all as $key=>&$row) {
				$classPath = 'PWForms\\'.$row['type'];
				$fld = new $classPath($dummy,$params);
				if ($fld->IsSortable || $fld->IsInput || $fld->DisplayExternal)
					$result[$key] = $row['name'];
				unset($fld);
			}
			unset($row);
		}
		return $result;
	}
}
