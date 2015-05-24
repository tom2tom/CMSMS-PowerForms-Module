<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldCheck
{
	// returns reference to field-objects array in $formdata
/*	function &GetFields(&$formdata)
	{
		return $formdata->Fields;
	}

	// returns count of field-objects in $formdata
	function GetFieldCount(&$formdata)
	{
		return count($formdata->Fields);
	}
*/
	// returns reference to first-found field-object in $formdata and whose id matches $field_id
	function &GetFieldByID(&$formdata,$field_id)
	{
		foreach($formdata->Fields as &$one)
		{
			if($one->GetId() == $field_id)
				return $one;
		}
		unset($one);
		$one = FALSE; //need ref to this
		return $one;
	}

	// returns reference to first-found field-object in $formdata and whose alias matches $field_alias
	function &GetFieldByAlias(&$formdata,$field_alias)
	{
		foreach($formdata->Fields as &$one)
		{
			if($one->GetOption('alias') == $field_alias)
				return $one;
		}
		unset($one);
		$one = FALSE; //need ref to this
		return $one;
	}

	// returns reference to first-found field-object in $formdata and whose name matches $field_name
	function &GetFieldByName(&$formdata,$field_name)
	{
		foreach($formdata->Fields as &$one)
		{
			if($one->GetName() == $field_name)
				return $one;
		}
		unset($one);
		$one = FALSE; //need ref to this
		return $one;
	}

}

?>
