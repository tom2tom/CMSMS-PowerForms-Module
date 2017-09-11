<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class FieldOperations
{
	/**
	Get:
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
	public static function Get(&$formdata, &$params)
	{
		$obfld = FALSE;
		if (!empty($params['field_id']) ||
			!empty($params['compadd']) || !empty($params['compdel'])) {
			// we're loading an extant field, or adjusting a new one
			if (!empty($params['type'])) {
				$type = $params['type'];
			} elseif (!empty($params['field_Type'])) {
				$type = $params['field_Type'];
			} else {
				$pre = \cms_db_prefix();
				$sql = 'SELECT type FROM '.$pre.'module_pwf_field WHERE field_id=?';
				$db = \cmsms()->GetDb();
				$type = $db->GetOne($sql, [$params['field_id']]);
			}
			if ($type) {
				$className = Utils::MakeClassName($type);
				//check file to prevent fatal autoloader error if class N/A
				$classPath = __DIR__.DIRECTORY_SEPARATOR.'class.'.$className.'.php';
				if (is_file($classPath)) {
					$classPath = 'PWForms\\'.$className;
					$obfld = new $classPath($formdata, $params);
					if (self::Load($obfld)) {
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
				$obfld = new $classPath($formdata, $params);
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
								$obfld->SetProperty('DisplayInForm', TRUE);
							}
							break;
					}
				}
			}
		}
		return $obfld;
	}

	/**
	Copy:
	@field_id: field enumerator
	@form_id: optional form enumerator, default FALSE to use the form for @field_id
	@neworder: optional display-order for the field, default FALSE to place it last
	Copy an existing field from and to the database
	Returns: boolean T/F indicating success
	*/
	public static function Copy($field_id, $form_id=FALSE, $neworder=FALSE)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT form_id,name,alias,type,order_by,props FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = \cmsms()->GetDb();
		$row = $db->GetRow($sql, [$field_id]);
		if (!$row) {
			return FALSE;
		}

		if ($form_id === FALSE) {
			$form_id = $row['form_id'];
		} else {
			$row['form_id'] = $form_id;
		}
//		$row['name'] .= ' '.$mod->Lang('copy');

		$props = $row['props'];
		unset($row['props']);

		if ($neworder === FALSE) {
			$sql = 'SELECT MAX(order_by) AS last FROM '.$pre.'module_pwf_field WHERE form_id=?';
			$neworder = $db->GetOne($sql, [$form_id]);
			if ($neworder) {
				$neworder++;
			} else {
				$neworder = 1;
			}
		}
		$row['order_by'] = $neworder;
		$sql = 'INSERT INTO '.$pre.'module_pwf_field (form_id,name,alias,type,order_by) VALUES (?,?,?,?,?)';
		$db->Execute($sql, $row);
		$fid = $db->Insert_ID();
		if ($db->Affected_Rows() == 0) {
			return FALSE;
		}

		$t = unserialize($props);
		if ($t) {
$X = $CRASH; //TODO revise props for new field
			$val = serialize($t);
			$sql = 'UPDATE '.$pre.'module_pwf_field SET props=? WHERE field_id='.$fid;
			$db->Execute($sql, [$val]);
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
			$obfld->SetName($field->GetName().' '.$formdata->pwfmod->Lang('copy'));
			$obfld->SetOrder(count($formdata->Fields)+1); //bit racy!
		}
		return $obfld;
	}

	/**
	Store:
	@obfld: reference to field-object
	@allprops: optional boolean, whether to also save all field properties, default=FALSE
	Stores (by insert or update) data for @obfld in database tables.
	Multi-valued (array) options are saved merely as multiple records with same name
	Sets @obfld->Id to real value if it was -1 i.e. a new field
	Returns: boolean T/F per success of executed db commands
	*/
	public static function Store(&$obfld, $allprops=FALSE)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		if ($obfld->Id <= 0) {
			$sql = 'INSERT INTO '.$pre.'module_pwf_field (form_id,name,alias,type,order_by) VALUES (?,?,?,?,?)';
			$db->Execute($sql, [
			$obfld->FormId,
			$obfld->Name,
			$obfld->Alias,
			$obfld->Type,
			$obfld->OrderBy]);
			if ($db->Affected_Rows() > 0) {
				$obfld->Id = $db->Insert_ID();
			} else {
				return FALSE;
			}
		} else {
			$sql = 'UPDATE '.$pre.'module_pwf_field SET name=?,alias=?,order_by=? WHERE field_id=?';
			$db->Execute($sql, [
			$obfld->Name,
			$obfld->Alias,
			$obfld->OrderBy,
			$obfld->Id]);
			//post-UPDATE $db->Affected_Rows() can't be relied on
		}

		if ($allprops) {
			//exclude 'constant' properties
			$includes = $obfld->GetMutables();
			$saves = array_intersect_key($obfld->XtraProps, $includes);
			foreach ($saves as $name => &$val) {
				switch ($includes[$name]) {
				 case 0:
				 case 10:
					$val = ($val) ? 1:0;
					break;
				 case 1:
				 case 11:
					$val += 0;
					break;
//				 case 3: nothing special for templates
//				 case 13:
				 case 4:
				 case 14:
					if ($val === NULL || is_scalar($val)) {
						if (is_numeric($val)) {
							$val += 0;
						}
					} else {
						$val = serialize($val);
					}
					//no break here
				 default:
				}
			}
			unset($val);
			$props = serialize($saves);
			$sql = 'UPDATE '.$pre.'module_pwf_field SET props=? WHERE field_id=?';
			$db->Execute($sql, [$props, $obfld->Id]);
		}
		return TRUE;
	}

	/**
	Load:
	@obfld: reference to field-object, including (at least) the appropriate Id
	Populates @obfld data from database tables.
	Table data replace existing data TODO OK?
	Returns: boolean T/F indicating successful operation
	*/
	public static function Load(&$obfld)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = \cmsms()->GetDb();
		if ($row = $db->GetRow($sql, [$obfld->Id])) {
			$obfld->FormId = (int)$row['form_id'];
			if (!$obfld->Name) {
				$obfld->Name = $row['name'];
			}
			if (!$obfld->Alias) {
				$obfld->Alias = $row['alias'];
			}
			$obfld->Type = $row['type'];
			$obfld->OrderBy = (int)$row['order_by'];
			$props = unserialize($row['props']);
			if ($props) {
				$obfld->XtraProps = array_merge($obfld->XtraProps, $props);
			}
		} else {
			return FALSE;
		}
		$obfld->SetProperty('loaded', TRUE);

		return TRUE;
	}

	/**
	RealDelete:
	@obfld: reference to field-object, including (at least) the appropriate Id
	Returns: boolean T/F indicating success
	*/
	public static function RealDelete(&$obfld)
	{
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwf_field where field_id=?';
		$db->Execute($sql, [$obfld->Id]);
		return $db->Affected_Rows() > 0;
	}

	/**
	Delete:
	@formdata: reference to FormData-class object
	@field_id: field enumerator, key in @formdata->Fields[]
	Clear table data
	Unless the-field->Delete() is subclassed, it comes back to self::RealDelete()
	*/
	public static function Delete(&$formdata, $field_id)
	{
		$formdata->Fields[$field_id]->Delete();
		unset($formdata->Fields[$field_id]);
	}

/*	public static function ResetFields(&$formdata)
	{
		foreach ($formdata->Fields as &$one) {
			$one->ResetValue();
		}

		unset($one);
	}
*/

	/**
	SwapByIndex:
	@field_index1:
	@field_index2:
	Swaps field display-orders
	This is intended for swapping adjacent fields but works more generally
	*/
	public static function SwapByIndex($field_index1, $field_index2)
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
	GetIndexFromId:
	@formdata: reference to FormData-class object
	@field_id: field enumerator, key in @formdata->Fields[]
	Returns: 0-based index of a field in @formdata->Fields[] and with id matching @field_id
	Check returned value with !== FALSE
	*/
	public static function GetIndexFromId(&$formdata, $field_id)
	{
		return array_search($field_id, array_keys($formdata->Fields));
	}
}
