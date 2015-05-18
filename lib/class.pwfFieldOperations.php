<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldOperations
{
	// returns reference to field-objects array in $formdata
	function &GetFields(&$formdata)
	{
		return $formdata->Fields;
	}

	// returns count of field-objects in $formdata
	function GetFieldCount(&$formdata)
	{
		return count($formdata->Fields);
	}

	// returns reference to first-found field-object in $formdata and whose alias matches $field_alias
	function &GetFieldByAlias(&$formdata,$field_alias)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetAlias() == $field_alias)
				return $fld;
		}
		unset ($fld);
		$fld = FALSE; //need ref to this
		return $fld;
	}

	// returns reference to first-found field-object in $formdata and whose name matches $field_name
	function &GetFieldByName(&$formdata,$field_name)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetName() == $field_name)
				return $fld;
		}
		unset ($fld);
		$fld = FALSE; //need ref to this
		return $fld;
	}

	// returns reference to field-object in $formdata and whose array-key is $field_index
	function &GetFieldByIndex(&$formdata,$field_index)
	{
		return $formdata->Fields[$field_index];
	}

	// returns index of first-found field in $formdata and with id matching $field_id
	function GetFieldIndexFromId(&$formdata,$field_id)
	{
		$i = 0; //don't assume anything about fields-array key
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetId() == $field_id)
			{
				unset($fld);
				return $i;
			}
			$i++;
		}
		unset ($fld);
		return -1;
	}

	// returns reference to new field-object corresponding to $params['field_id']
	function &NewField(&$formdata,&$params)
	{
		$obfield = FALSE;//may need ref to this
		if(isset($params['field_id']) && $params['field_id'] != -1)
		{
			// we're loading an extant field
			$sql = 'SELECT type FROM '.cms_db_prefix().'module_pwf_field WHERE field_id=?';
			$db = cmsms()->GetDb();
			$type = $db->GetOne($sql,array($params['field_id']));
			if($type != '')
			{
				$className = pwfUtils::MakeClassName($type);
				$obfield = new $className($formdata,$params);
				$obfield->LoadField($params);
			}
		}
		if($obfield === FALSE)
		{
			// new field
			if(!empty($params['field_type']))
			{
				// specified field type via params
				$className = pwfUtils::MakeClassName($params['field_type']);
				$obfield = new $className($formdata,$params);
			}
			else
			{
				// unknown field type
				$obfield = new pwfFieldBase($formdata,$params);
			}
		}
		return $obfield;
	}

	// swaps field display-orders 
	function SwapFieldsByIndex($field_index1,$field_index2)
	{
		$Field1 = self::GetFieldByIndex($field_index1);
		$Field2 = self::GetFieldByIndex($field_index2);
		$tmp = $Field2->GetOrder();
		$Field2->SetOrder($Field1->GetOrder());
		$Field2->Store();
		$Field1->SetOrder($tmp);
		$Field1->Store();
	}

	//'hard' copy an existing field returns TRUE/FALSE
	function CopyField($field_id,$newform=FALSE,$neworder=FALSE)
	{
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$row = $db->GetRow($sql,array($field_id));
		if(!$row)
			return FALSE;

		$fid = $db->GenID($pre.'module_pwf_field_seq');
		if($newform == FALSE)
			$newform = (int)$row['form_id'];

		$row['field_id'] = $fid;
		$row['form_id'] = $newform;
//		$row['name'] .= ' '.$mod->Lang('copy');
		if($row['validation_type'] == '')
			$row['validation_type'] = NULL;

		if($neworder === FALSE)
		{
			$sql = 'SELECT MAX(order_by) AS last FROM '.$pre.'module_pwf_field WHERE form_id=?';
			$neworder = $db->GetOne($sql, array($newform));
			if($neworder == FALSE)
				$neworder = 0;
			$neworder++;
		}
		$row['order_by'] = $neworder;
		$sql = 'INSERT INTO '.$pre.
		 'module_pwf_field (field_id,form_id,name,type,validation_type,required,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
		$db->Execute($sql,$row);

		$sql = 'SELECT * FROM '.$pre.'module_pwf_field_opt WHERE field_id=?';
		$rs = $db->Execute($sql,array($field_id));
		if($rs)
		{
			$sql = 'INSERT INTO '.$pre.
			 'module_pwf_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
			while ($row = $rs->FetchRow())
			{
				$row['option_id'] = $db->GenID($pre.'module_pwf_field_opt_seq');
				$row['field_id'] = $fid;
				$row['form_id'] = $newform;
				$db->Execute($sql,$row);
			}
			$rs->Close();
		}
		return TRUE;
	}

	// returns reference to a clone of existing field-object corresponding to $field_id
	function &Replicate(&$formdata,$field_id)
	{
		$obfield = FALSE;//may need ref to this
		if($field_id != -1)
		{
			foreach($formdata->Fields as &$one)
			{
				if($one->GetId() == $field_id)
				{
					$name = $one->GetName();
					$obfield = clone($one);
					$obfield->Id = -1;
					$obfield->SetName($name.' '.$formdata->formsmodule->Lang('copy'));
					$obfield->SetOrder(count($formdata->Fields)+1); //bit racy!
					break;
				}
			}
			unset($one);
		}
		return $obfield;
	}

	function DeleteField(&$formdata,$field_id)
	{
		$index = self::GetFieldIndexFromId($field_id);
		if($index != -1)
		{
			$formdata->Fields[$index]->Delete();
			array_splice($formdata->Fields,$index,1);
		}
	}

	function ResetFields(&$formdata)
	{
		foreach($formdata->Fields as &$one)
			$one->ResetValue();

		unset($one);
	}

}

?>
