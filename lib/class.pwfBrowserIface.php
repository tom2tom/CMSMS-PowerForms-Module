<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//methods for use by PowerBrowse module

class pwfBrowserIface
{	
	//returns array in which key = form id, value = form name
	function GetBrowsableForms()
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		return $db->GetAssoc(
		'SELECT DISTINCT FM.form_id,FM.name FROM '.$pre.
		'module_pwf_form FM JOIN '.$pre.
		'module_pwf_field FD ON FM.form_id=FD.form_id WHERE FD.type=\'DispositionFormBrowser\'');
	}

	//returns array in which key = field id, value = field name
	function GetBrowsableFields($form_id)
	{
		$db = cmsms()->GetDb();
		$result = array();
		$pre = cms_db_prefix();
		$all = $db->GetAssoc('SELECT field_id,name,type FROM '.$pre.
		'module_pwf_field WHERE form_id=? AND type LIKE \'%Field%\' ORDER BY order_by',
			array($form_id));
		if($all)
		{
			$mod = cms_utils::get_module('PowerForms');
			$dummy = $mod->GetFormData();
			$params = array();
			foreach($all as $key=>&$row)
			{
				$classname = 'pwf'.$row['type'];
				$fld = new $classname($dummy,$params);
				if($fld->IsSortable || $fld->IsInput)
					$result[$key] = $row['name'];
				unset($fld);
			}
			unset($row);
		}
		return $result;
	}
	
	//$notify = FALSE suppresses notifications by email etc when new form is submitted
	function AddRecord($form_id,$notify=TRUE)
	{
$this->DoNothing();
	}

}

?>
