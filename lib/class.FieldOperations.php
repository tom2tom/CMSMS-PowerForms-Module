<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FieldOperations
{
	/**
	NewField:
	@formdata: reference to FormData-class object to be set in the field
	@params: reference to array of table-row fields and/or request parameters,
	 should include 'field_id' (in which case, maybe 'type' too) or 'field_pick'
	 @params representing wanted values may be present, with keys:
	 $formdata->current_prefix.<FID> or $formdata->prior_prefix.<FID> or
	 'value_'.<FIELDNAME> or 'value_fld'.<FID>
	 where <FID> is the relevant field enumerator, <FIELDNAME> is recorded fieldname
	Constructs new field-object per $params['field_id'] or $params['field_pick']
	Returns: the object, or FALSE
	*/
	public static function NewField(&$formdata, &$params)
	{
		$obfld = FALSE;
		if (!empty($params['field_id'])) {
			// we're loading an extant field
			if (empty($params['type'])) {
				$pre = \cms_db_prefix();
				$sql = 'SELECT type FROM '.$pre.'module_pwf_field WHERE field_id=?';
				$db = \cmsms()->GetDb();
				$type = $db->GetOne($sql,array($params['field_id']));
			} else {
				$type = $params['type'];
			}
			if ($type) {
				$className = Utils::MakeClassName($type);
				//check file to prevent fatal autoloader error if class N/A
				$classPath = __DIR__.DIRECTORY_SEPARATOR.'class.'.$className.'.php';
				if (is_file($classPath)) {
					$classPath = 'PWForms\\'.$className;
					$obfld = new $classPath($formdata,$params);
					if (self::LoadField($obfld)) {
//TODO rationalise value setting
						if (!empty($params[$formdata->current_prefix.$obfld->Id])) {
							$obfld->SetValue($params[$formdata->current_prefix.$obfld->Id]);
						} elseif (!empty($params[$formdata->prior_prefix.$obfld->Id])) {
							$obfld->SetValue($params[$formdata->prior_prefix.$obfld->Id]);
						} elseif (!empty($params['value_'.$obfld->Name])) {
							$obfld->SetValue($params['value_'.$obfld->Name]);
						} elseif (!empty($params['value_fld'.$obfld->Id])) {
							$obfld->SetValue($params['value_fld'.$obfld->Id]);
						}
					}
				}
			}
		} elseif (!empty($params['field_pick'])) { //new field triggered by open_form field-picker change/choice
			// specified field type via params
			$className = Utils::MakeClassName($params['field_pick']);
			$classPath = __DIR__.DIRECTORY_SEPARATOR.'class.'.$className.'.php';
			if (is_file($classPath)) {
				$classPath = 'PWForms\\'.$className;
				$obfld = new $classPath($formdata,$params);
				if (isset($params['in'])) {
					switch ($params['in']) {
						case 'disposition':
							if (!$obfld->GetProperty('IsDisposition')) {
								$obfld = FALSE;
							}
							break;
						case 'form':
							if ($obfld->GetProperty('IsDisposition')) {
								$obfld = FALSE;
							} else {
								$obfld->SetProperty('DisplayInForm',TRUE);
							}
							break;
					}
				}
			}
		}
		return $obfld;
	}

	/**
	CopyField:
	@field_id: field enumerator
	@form_id: optional form enumerator, default FALSE to use the form for @field_id
	@neworder: optional display-order for the field, default FALSE to place it last
	Copy an existing field from and to the database
	Returns: boolean T/F indicating success
	*/
	public static function CopyField($field_id, $form_id=FALSE, $neworder=FALSE)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = \cmsms()->GetDb();
		$row = $db->GetRow($sql,array($field_id));
		if (!$row)
			return FALSE;

		$fid = $db->GenID($pre.'module_pwf_field_seq');
		$row['field_id'] = $fid;
		if ($form_id === FALSE)
			$form_id = $row['form_id'];
		else
			$row['form_id'] = $form_id;
//		$row['name'] .= ' '.$mod->Lang('copy');

		if ($neworder === FALSE) {
			$sql = 'SELECT MAX(order_by) AS last FROM '.$pre.'module_pwf_field WHERE form_id=?';
			$neworder = $db->GetOne($sql,array($form_id));
			if (!$neworder)
				$neworder = 0;
			$neworder++;
		}
		$row['order_by'] = $neworder;
		$sql = 'INSERT INTO '.$pre.'module_pwf_field
(field_id,form_id,name,alias,type,order_by) VALUES (?,?,?,?,?,?)';
		$db->Execute($sql,$row);

		$sql = 'SELECT * FROM '.$pre.'module_pwf_fieldprops WHERE field_id=?';
		$rs = $db->Execute($sql,array($field_id));
		if ($rs) {
			$sql = 'INSERT INTO '.$pre.'module_pwf_fieldprops
(prop_id,field_id,form_id,name,value,longvalue) VALUES (?,?,?,?,?,?)';
			while ($row = $rs->FetchRow()) {
				$row['prop_id'] = $db->GenID($pre.'module_pwf_fieldprops_seq');
				$row['field_id'] = $fid;
				$row['form_id'] = $form_id;
				$db->Execute($sql,$row);
			}
			$rs->Close();
		}
		return TRUE;
	}

	/**
	Replicate:
	@formdata: reference to FormData-class object including the field to be cloned
	@field_id: field enumerator, key in @formdata->Fields[]
	Returns: a tailored clone of existing field-object identified by @field_id, or FALSE
	*/
	public static function Replicate(&$formdata, $field_id)
	{
		$obfld = FALSE;//may need ref to this
		if (isset($formdata->Fields[$field_id])) {
			$field = $formdata->Fields[$field_id];
			$obfld = clone($field);
			$obfld->Id = 0;
			$obfld->SetName($field->GetName().' '.$formdata->formsmodule->Lang('copy'));
			$obfld->SetOrder(count($formdata->Fields)+1); //bit racy!
		}
		return $obfld;
	}

	/**
	StoreField:
	@obfld: reference to field-object
	@allprops: optional boolean, whether to also save all field properties, default=FALSE
	Stores (by insert or update) data for @obfld in database tables.
	Multi-valued (array) options are saved merely as multiple records with same name
	Sets @obfld->Id to real value if it was -1 i.e. a new field
	Returns: boolean T/F per success of executed db commands
	*/
	public static function StoreField(&$obfld, $allprops=FALSE)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		if ($obfld->Id <= 0) {
			$obfld->Id = $db->GenID($pre.'module_pwf_field_seq');
			$sql = 'INSERT INTO '.$pre.'module_pwf_field
(field_id,form_id,name,alias,type,order_by) VALUES (?,?,?,?,?,?)';
			$res = $db->Execute($sql,array(
				$obfld->Id,
				$obfld->FormId,
				$obfld->Name,
				$obfld->Alias,
				$obfld->Type,
				$obfld->OrderBy));
		} else {
			$sql = 'UPDATE '.$pre.'module_pwf_field SET name=?,alias=?,order_by=? WHERE field_id=?';
			$res = $db->Execute($sql,array(
				$obfld->Name,
				$obfld->Alias,
				$obfld->OrderBy,
				$obfld->Id));
		}

		if ($allprops) {
			// drop all current properties
			$sql = 'DELETE FROM '.$pre.'module_pwf_fieldprops where field_id=?';
			$res = $db->Execute($sql,array($obfld->Id)) && $res;
			// add back current ones
			$sql = 'INSERT INTO '.$pre.'module_pwf_fieldprops
(prop_id,field_id,form_id,name,value,longvalue) VALUES (?,?,?,?,?,?)';
			foreach ($obfld->XtraProps as $name=>$value) {
				if (!is_scalar($value)) {
					$value = json_encode($value);
				}
				$newid = $db->GenID($pre.'module_pwf_fieldprops_seq');
				if (strlen($value) <= \PWForms::LENSHORTVAL) {
					$sval = $value;
					$lval = NULL;
				} else {
					$sval = NULL;
					$lval = $value;
				}
				$res = $db->Execute($sql,
					array($newid,$obfld->Id,$obfld->FormId,$name,$sval,$lval)) && $res;
			}
		}
		return $res;
	}

	/**
	LoadField:
	@obfld: reference to field-object, including (at least) the appropriate Id
	Populates @obfld data from database tables.
	Table data replace existing data TODO OK?
	Returns: boolean T/F indicating successful operation
	*/
	public static function LoadField(&$obfld)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = \cmsms()->GetDb();
		if ($row = $db->GetRow($sql,array($obfld->Id))) {
			$obfld->FormId = (int)$row['form_id'];
			if (!$obfld->Name)
				$obfld->Name = $row['name'];
			if (!$obfld->Alias)
				$obfld->Alias = $row['alias'];
			$obfld->Type = $row['type'];
			$obfld->OrderBy = (int)$row['order_by'];
		} else
			return FALSE;

		$obfld->loaded = TRUE;

		$sql = 'SELECT name,value,longvalue FROM '.$pre.'module_pwf_fieldprops WHERE field_id=? ORDER BY prop_id';
		$defaults = $db->GetArray($sql,array($obfld->Id));
		if ($defaults) {
			$merged = array();
			$rc = count($defaults);
			for ($r=0; $r<$rc; $r++) {
				$row = $defaults[$r];
				$nm = $row['name'];
				$val = $row['value'];
				if ($val === NULL)
					$val = $row['longvalue']; //maybe still FALSE
				//accumulate properties with the same name into array
				if (isset($merged[$nm])) {
					if (!is_array($merged[$nm]))
						$merged[$nm] = array($merged[$nm]);
					$merged[$nm][] = $val;
				} else {
					$merged[$nm] = $val;
				}
			}
			foreach ($merged as $nm=>$val) {
				if ($val && is_string($val) && ($val[0] == '[' || $val[0] == '{')) {
					$ar = json_decode($val);
					if (json_last_error() == JSON_ERROR_NONE) {
						$val = is_array($ar) ? $ar : (array)$ar;
					}
				}
				if (property_exists($obfld,$nm)) {
					$obfld->$nm = $val;
				} else {
					$obfld->XtraProps[$nm] = $val;
				}
			}
		}
		return TRUE;
	}

	/**
	RealDeleteField:
	@obfld: reference to field-object, including (at least) the appropriate Id
	Returns: boolean T/F indicating success
	*/
	public static function RealDeleteField(&$obfld)
	{
		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwf_field where field_id=?';
		$db = \cmsms()->GetDb();
		$res = $db->Execute($sql,array($obfld->Id));
		$sql = 'DELETE FROM '.$pre.'module_pwf_fieldprops where field_id=?';
		$res = $db->Execute($sql,array($obfld->Id)) && $res;
		return $res;
	}

	/**
	DeleteField:
	@formdata: reference to FormData-class object
	@field_id: field enumerator, key in @formdata->Fields[]
	Clear table data
	Unless the-field->Delete() is subclassed, it just calls self::RealDeleteField()
	*/
	public static function DeleteField(&$formdata, $field_id)
	{
		$formdata->Fields[$field_id]->Delete();
		unset($formdata->Fields[$field_id]);
	}

/*	public static function ResetFields(&$formdata)
	{
		foreach ($formdata->Fields as &$one)
			$one->ResetValue();

		unset($one);
	}
*/

	/**
	SwapFieldsByIndex:
	@field_index1:
	@field_index2:
	Swaps field display-orders
	This is intended for swapping adjacent fields but works more generally
	*/
	public static function SwapFieldsByIndex($field_index1, $field_index2)
	{
		$keys = array_keys($formdata->Fields);
		if (isset($keys[$field_index1]) && isset($keys[$field_index2])) {
			$k1 = $keys[$field_index1];
			$o1 = $formdata->Fields[$k1]->GetOrder();
			$k2 = $keys[$field_index2];
			$o2 = $formdata->Fields[$k2]->GetOrder();
			$formdata->Fields[$k1]->SetOrder($o2);
			$formdata->Fields[$k2]->SetOrder($o1);
		}
	}

	/**
	GetFieldIndexFromId:
	@formdata: reference to FormData-class object
	@field_id: field enumerator, key in @formdata->Fields[]
	Returns: 0-based index of a field in @formdata->Fields[] and with id matching @field_id
	Check returned value with !== FALSE
	*/
	public static function GetFieldIndexFromId(&$formdata, $field_id)
	{
		return array_search($field_id,array_keys($formdata->Fields));
	}
}
