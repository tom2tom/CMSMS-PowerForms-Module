<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldOperations
{
	// returns reference to field-objects array
	function &GetFields(&$formdata)
	{
		return $formdata->Fields;
	}

	function GetFieldCount(&$formdata)
	{
		return count($formdata->Fields);
	}

	// returns reference to first-found field-object whose id matches $field_id
	function &GetFieldById(&$formdata,$field_id)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetId() == $field_id)
				return $fld;
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	// returns reference to first-found field-object whose alias matches $field_alias
	function &GetFieldByAlias(&$formdata,$field_alias)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetAlias() == $field_alias)
				return $fld;
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	// returns reference to first-found field-object whose name matches $field_name
	function &GetFieldByName(&$formdata,$field_name)
	{
		foreach($formdata->Fields as &$fld)
		{
			if($fld->GetName() == $field_name)
				return $fld;
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	// returns reference to field-object whose array-key is $field_index
	function &GetFieldByIndex(&$formdata,$field_index)
	{
		return &$formdata->Fields[$field_index];
	}

	// returns index of first-found field with matching id
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

	// add new field
	function &NewField(&$formdata,&$params)
	{
		$obfield = false;//may need ref to this
		if(isset($params['field_id']) && $params['field_id'] != -1)
		{
			// we're loading an extant field
			$sql = 'SELECT type FROM '.cms_db_prefix().'module_pwf_field WHERE field_id=?';
TODO DB			$type = $formdata->pwfmodule->dbHandle->GetOne($sql,array($params['field_id']));
			if($type != '')
			{
				$className = pwfUtils::MakeClassName($type);
				$obfield = new $className($formdata,$params);
				$obfield->LoadField($params);
			}
		}
		if($obfield === false)
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

	function AddEdit(&$formdata,&$obfield,$dispose_only,$id,$returnid,$message='')
	{
		$mod = $formdata->pwfmodule;
		$smarty = cmsms()->GetSmarty();

		if(!empty($message))
			$smarty->assign('message',$mod->ShowMessage($message)); //success message
		elseif(isset($params['message']))
			$smarty->assign('message',$params['message']); //probably an error message
		$smarty->assign('backtomod_nav', $mod->CreateLink($id,'defaultadmin','',$mod->Lang('back_top'), array()));
		$smarty->assign('backtoform_nav',$mod->CreateLink($id,'add_edit_form',$returnid, $mod->Lang('link_back_to_form'), array('form_id'=>$formdata->Id)));

		$mainList = array();
		$advList = array();
		$baseList = $obfield->PrePopulateBaseAdminForm($id, $dispose_only);
		if($obfield->GetFieldType() == '')
		{
			// still need type
			$fieldList = array();
		}
		else
		{
			// we have our type
			$fieldList = $obfield->PrePopulateAdminForm($id);
		}

		$hasmain = isset($baseList['main']) || isset($fieldList['main']);
		$hasadvanced = isset($baseList['adv']) || isset($fieldList['adv']);

		$smarty->assign('start_form',$mod->CreateFormStart($id,'add_edit_field',$returnid));
		$smarty->assign('end_form', $mod->CreateFormEnd());
		$tmp = $mod->StartTabHeaders();
		if($hasmain)
			$tmp .= $mod->SetTabHeader('maintab',$mod->Lang('tab_main'));
		if($hasadvanced)
			$tmp .= $mod->SetTabHeader('advancedtab',$mod->Lang('tab_advanced'));
		$tmp .= $mod->EndTabHeaders() . $mod->StartTabContent();
		$smarty->assign('tabs_start',$tmp);
		$smarty->assign('tabs_end',$mod->EndTabContent());
		if($hasmain)
			$smarty->assign('maintab_start',$mod->StartTab('maintab'));
		if($hasadvanced)
			$smarty->assign('advancedtab_start',$mod->StartTab('advancedtab'));
		$smarty->assign('tab_end',$mod->EndTab());
		$smarty->assign('notice_select_type',$mod->Lang('notice_select_type'));

		if($obfield->GetId() != -1)
		{
			$smarty->assign('op',$mod->CreateInputHidden($id, 'op',$mod->Lang('updated')));
			$smarty->assign('submit',$mod->CreateInputSubmit($id, 'aef_upd', $mod->Lang('update')));
		}
		elseif($obfield->GetFieldType() != '')
		{
			$smarty->assign('op',$mod->CreateInputHidden($id, 'op', $mod->Lang('added')));
			$smarty->assign('submit',$mod->CreateInputSubmit($id, 'aef_add', $mod->Lang('add')));
		}
		$smarty->assign('cancel',$mod->CreateInputSubmit($id,'aef_cancel',$mod->Lang('cancel')));

		if($obfield->HasAddOp())
		{
			$smarty->assign('add',$mod->CreateInputSubmit($id,'aef_optadd',$obfield->GetOptionAddButton()));
		}
		else
		{
			$smarty->assign('add','');
		}
		if($obfield->HasDeleteOp())
		{
			$smarty->assign('del',$mod->CreateInputSubmit($id,'aef_optdel',$obfield->GetOptionDeleteButton()));
		}
		else
		{
			$smarty->assign('del','');
		}

		$smarty->assign('hidden', $mod->CreateInputHidden($id, 'form_id', $formdata->Id) .
			$mod->CreateInputHidden($id, 'field_id', $obfield->GetId()) .
			$mod->CreateInputHidden($id, 'pwfp_order_by', $obfield->GetOrder()) .
			$mod->CreateInputHidden($id, 'pwfp_set_from_form','1'));

		if(/*!$obfield->IsDisposition() && */ !$obfield->IsNonRequirableField())
		{
			$smarty->assign('requirable',1);
		}
		else
		{
			$smarty->assign('requirable',0);
		}

		if(isset($baseList['main']))
		{
			foreach($baseList['main'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$mainList[] = $oneset;
			}
		}
		if(isset($baseList['adv']))
		{
			foreach($baseList['adv'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$advList[] = $oneset;
			}
		}
		if(isset($fieldList['main']))
		{
			foreach($fieldList['main'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$mainList[] = $oneset;
			}
		}
		if(isset($fieldList['adv']))
		{
			foreach($fieldList['adv'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$advList[] = $oneset;
			}
		}
		$obfield->PostPopulateAdminForm($mainList, $advList);

		$smarty->assign('mainList',$mainList);
		$smarty->assign('advList',$advList);
		if(isset($fieldList['table']))
			$smarty->assign('mainTable', $fieldList['table']);
		else
			$smarty->clear_assign('mainTable');
		if(isset($fieldList['funcs']))
			$smarty->assign('jsfuncs',$fieldList['funcs']);
		else
			$smarty->clear_assign('jsfuncs');
		if(isset($fieldList['extra']))
		{
			$showvars = false;
			switch ($fieldList['extra'])
			{
			 case 'varshelpadv':
				$showvars = true;
				$smarty->assign('advvarhelp',1);
				break;
			 case 'varshelpmain':
				$showvars = true;
				$smarty->assign('mainvarhelp',1);
				break;
			 case 'varshelpboth':
				$showvars = true;
				$smarty->assign('mainvarhelp',1);
				$smarty->assign('advvarhelp',1);
				break;
			}
			if($showvars)
				self::SetupVarsHelp($mod, $smarty);
		}
		$smarty->assign('incpath',$mod->GetModuleURLPath().'/include/');

		return $mod->ProcessTemplate('AddEditField.tpl');
	}

	function SwapFieldsByIndex($src_field_index,$dest_field_index)
	{
		$srcField = self::GetFieldByIndex($src_field_index);
		$destField = self::GetFieldByIndex($dest_field_index);
		$tmpOrderBy = $destField->GetOrder();
		$destField->SetOrder($srcField->GetOrder());
		$destField->Store();
		$srcField->SetOrder($tmpOrderBy);
		$srcField->Store();
	}

	//'hard' copy an existing field
	function CopyField(&$formdata,$field_id,$newform=false,$neworder=false)
	{
		$pref = cms_db_prefix();
TODO DB		$db = $formdata->pwfmodule->dbHandle;
		$sql = 'SELECT * FROM '.$pref.'module_pwf_field WHERE field_id=?';
		$row = $db->GetRow($sql,array($field_id));
		if(!$row)
			return false;

		$fid = $db->GenID($pref.'module_pwf_field_seq');
		if($newform == false)
			$newform = (int)$row['form_id'];

		$row['field_id'] = $fid;
		$row['form_id'] = $newform;
//		$row['name'] .= ' '.$mod->Lang('copy');
		if($row['validation_type'] == '')
			$row['validation_type'] = null;

		if($neworder === false)
		{
			$sql = 'SELECT MAX(order_by) AS last FROM '.$pref.'module_pwf_field WHERE form_id=?';
			$neworder = $db->GetOne($sql, array($newform));
			if($neworder == false)
				$neworder = 0;
			$neworder++;
		}
		$row['order_by'] = $neworder;
		$sql = 'INSERT INTO '.$pref.
		 'module_pwf_field (field_id,form_id,name,type,validation_type,required,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
		$db->Execute($sql,$row);

		$sql = 'SELECT * FROM '.$pref.'module_pwf_field_opt WHERE field_id=?';
		$rs = $db->Execute($sql,array($field_id));
		if($rs)
		{
			$sql = 'INSERT INTO '.$pref.
			 'module_pwf_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
			while ($row = $rs->FetchRow())
			{
				$row['option_id'] = $db->GenID($pref.'module_pwf_field_opt_seq');
				$row['field_id'] = $fid;
				$row['form_id'] = $newform;
				$db->Execute($sql,$row);
			}
			$rs->Close();
		}
		return true;
	}

	//clone an existing field-object
	function &Replicate(&$formdata,&$params)
	{
		$obfield = false;//may need ref to this
		if(isset($params['field_id']) && $params['field_id'] != -1)
		{
			$last = -1;
			$orig = $params['field_id'];
			foreach($formdata->Fields as &$fld)
			{
				if($fld->GetId() == $orig)
				{
					$obfield = clone($fld);
					$obfield->Id = -1;
					$name = $obfield->GetName();
					$obfield->SetName($name.' '.$formdata->pwfmodule->Lang('copy'));
				}
				if($fld->GetOrder() > $last)
					$last = $fld->GetOrder();
			}
			unset($fld);
			if($obfield)
				$obfield->SetOrder($last+1);
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
		foreach($formdata->Fields as &$fld)
			$fld->ResetValue();

		unset($fld);
	}

}

?>
