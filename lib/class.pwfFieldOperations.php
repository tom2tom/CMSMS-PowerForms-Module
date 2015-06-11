<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldOperations
{
	// returns reference to new field-object corresponding to $params['field_id']
	public static function &NewField(&$formdata,$id,&$params)
	{
		$obfield = FALSE;//may need ref to this
		if(!empty($params['field_id']))
		{
			// we're loading an extant field
			$sql = 'SELECT type FROM '.cms_db_prefix().'module_pwf_field WHERE field_id=?';
			$db = cmsms()->GetDb();
			$type = $db->GetOne($sql,array($params['field_id']));
			if($type)
			{
				$className = pwfUtils::MakeClassName($type);
				$obfield = new $className($formdata,$params);
				$obfield->Load($id,$params); //TODO check for failure
/*TODO rationalise this
				if(!empty($params['value_'.$this->Name]))
					$obfield->SetValue($params['value_'.$this->Name]);
				if(!empty($params['value_fld'.$this->Id]))
					$obfield->SetValue($params['value_fld'.$this->Id]);
*/
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

	// 'hard' copy an existing field returns TRUE/FALSE
	public static function CopyField($field_id,$newform=FALSE,$neworder=FALSE)
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
			$neworder = $db->GetOne($sql,array($newform));
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
	public static function &Replicate(&$formdata,$field_id)
	{
		$obfield = FALSE;//may need ref to this
		if($field_id != 0)
		{
			foreach($formdata->Fields as &$one)
			{
				if($one->GetId() == $field_id)
				{
					$name = $one->GetName();
					$obfield = clone($one);
					$obfield->Id = 0;
					$obfield->SetName($name.' '.$formdata->formsmodule->Lang('copy'));
					$obfield->SetOrder(count($formdata->Fields)+1); //bit racy!
					break;
				}
			}
			unset($one);
		}
		return $obfield;
	}

	/**
	StoreField:
	@obfield: reference to field data object
	@deep: optional boolean, whether to also save all options for the field, default=FALSE
	Stores (by insert or update) data for @obfield in database tables.
	Multi-valued (array) options are saved merely as multiple records with same name
	Sets @obfield->Id to real value if it was -1 i.e. a new field
	Returns: boolean T/F per success of executed db commands
	*/
	public static function StoreField(&$obfield,$deep=FALSE)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		if($obfield->Id == 0)
		{
			$obfield->Id = $db->GenID($pre.'module_pwf_field_seq');
			$sql = 'INSERT INTO '.$pre.'module_pwf_field (field_id,form_id,name,type,' .
			  'required,validation_type,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
					array($obfield->Id,$obfield->FormId,$obfield->Name,$obfield->Type,
						($obfield->Required?1:0),$obfield->ValidationType,$obfield->HideLabel,
						$obfield->OrderBy));
		}
		else
		{
			$sql = 'UPDATE ' .$pre.
			  'module_pwf_field SET name=?,type=?,required=?,validation_type=?,order_by=?,hide_label=? WHERE field_id=?';
			$res = $db->Execute($sql,
					array($obfield->Name,$obfield->Type,($obfield->Required?1:0),
						$obfield->ValidationType,$obfield->OrderBy,$obfield->HideLabel,$obfield->Id));
		}

		if($deep)
		{
			// drop all current options
			$sql = 'DELETE FROM '.$pre.'module_pwf_field_opt where field_id=?';
			$res = $db->Execute($sql,array($obfield->Id)) && $res;
			// add back current ones
			foreach($obfield->Options as $name=>$optvalue)
			{
				if(!is_array($optvalue))
					$optvalue = array($optvalue);
				$sql = 'INSERT INTO ' .$pre.
				'module_pwf_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
				foreach($optvalue as &$one)
				{
					$newid = $db->GenID($pre.'module_pwf_field_opt_seq');
					$res = $db->Execute($sql,
						array($newid,$obfield->Id,$obfield->FormId,$name,$one)) && $res;
				}
				unset($one);
			}
		}
		return $res;
	}

	/**
	LoadField:
	@obfield: reference to field data object
	@deep: optional boolean, whether to also load all options for the field, default=TRUE

	Loads data for @obfield from database tables and possibly from @params.
	Field options are merged with any existing options TODO OK?
	TODO If @deep, sets field->Value from non-empty @params['value_'.$obfield->Name]
	and/or @params['value_fld'.$obfield->Id]
	Returns: boolean T/F per successful operation
	*/
	public static function LoadField(&$obfield) //,$deep=TRUE)
	{
		$pre = cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = cmsms()->GetDb();
		if($row = $db->GetRow($sql,array($obfield->Id)))
		{
			if(!$obfield->Name)
				$obfield->Name = $row['name'];
			$obfield->Type = $row['type'];
			$obfield->OrderBy = $row['order_by'];
		}
		else
			return FALSE;

		$obfield->loaded = TRUE;

//		if($deep) never FALSE
//		{
			$sql = 'SELECT name,value FROM '.$pre.
			  'module_pwf_field_opt WHERE field_id=? ORDER BY option_id';
			$rs = $db->Execute($sql,array($obfield->Id));
			if($rs)
			{
				$newopts = array();
				while ($row = $rs->FetchRow())
				{
					$nm = $row['name'];
					//accumulate options with the same name into array
					if(isset($newopts[$nm]))
					{
						if(!is_array($newopts[$nm]))
							$newopts[$nm] = array($newopts[$nm]);
						$newopts[$nm][] = $row['value'];
					}
					else
					{
						$newopts[$nm] = $row['value'];
						//TODO former properties, now migrated to options
						if($nm == 'validation_type')
							$obfield->ValidationType = $row['value'];
						elseif($nm == 'required')
							$obfield->Required = (int)$row['value'];
						elseif($nm == 'hide_label')
							$obfield->HideLabel = (int)$row['value'];
					}
				}
				$rs->Close();
				$obfield->Options = array_merge($newopts,$obfield->Options);
			}
//		}
		return TRUE;
	}

	public static function RealDeleteField(&$obfield)
	{
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.$pre.'module_pwf_field where field_id=?';
		$res = $db->Execute($sql,array($obfield->Id));
		$sql = 'DELETE FROM '.$pre.'module_pwf_field_opt where field_id=?';
		$res = $db->Execute($sql,array($obfield->Id)) && $res;
		return $res;
	}

	public static function DeleteField(&$formdata,$field_id)
	{
		//clear table data
		//unless subclassed, it just calls RealDeleteField()
		$formdata->Fields[$field_id]->Delete();
		unset($formdata->Fields[$field_id]);
	}

/*	public static function ResetFields(&$formdata)
	{
		foreach($formdata->Fields as &$one)
			$one->ResetValue();

		unset($one);
	}
*/

	// Swaps field display-orders
	// This is intended for swapping adjacent fields but works more generally
	public static function SwapFieldsByIndex($field_index1,$field_index2)
	{
		$keys = array_keys($formdata->Fields);
		if(isset($keys[$field_index1]) && isset($keys[$field_index2]))
		{
			$k1 = $keys[$field_index1];
			$o1 = $formdata->Fields[$k1]->GetOrder();
			$k2 = $keys[$field_index2];
			$o2 = $formdata->Fields[$k2]->GetOrder();
			$formdata->Fields[$k1]->SetOrder($o2);
			$formdata->Fields[$k2]->SetOrder($o1);
		}
	}

	// Returns 0-based index of field in $formdata->Fields[] and with id matching $field_id
	// Check returned value with !== FALSE
	public static function GetFieldIndexFromId(&$formdata,$field_id)
	{
		return array_search($field_id,array_keys($formdata->Fields));
	}

}

?>
