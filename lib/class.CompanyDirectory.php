<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
/*
 A class by Jeremy Bass <jeremyBass@cableone.net>
 to provide a dynamic multiselect list to allow selecting one or more
 items from the CompanyDirectory module.
 The list is filtered by an array of options as specified in the admin.
*/
namespace PWForms;

class CompanyDirectory extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'CompanyDirectory';
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$CompanyDirectory = $mod->GetModuleInstance('CompanyDirectory');
		if (!$CompanyDirectory)
			return $mod->Lang('error_module_CompanyDirectory');
		return '';
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				if ($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$this->Value);
				else {
					$ret = $this->Value;
					return $ret; //array copy
				}
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		$CompanyDirectory = $mod->GetModuleInstance('CompanyDirectory');
		if ($CompanyDirectory)
			unset($CompanyDirectory);
		else
			return array('main'=>array('<span style="color:red">'.$mod->Lang('error').'</span>',
				'',$mod->Lang('error_module_CompanyDirectory')));

		$Categories = array('All'=>$mod->Lang('all'));
		$pre = \cms_db_prefix();
		$sql = 'SELECT name FROM '.$pre.'module_compdir_categories';
		$db = \cmsms()->GetDb();
		$all = $db->GetCol($sql);
		if ($all) {
			$Categories += array_combine($all,$all);
		}
		$CategorySelected = $this->GetOption('Category');
		//check and force the right type
		if (!is_array($CategorySelected))
			$CategorySelected = explode(',',$CategorySelected);

		$FieldDefs = array('none'=>$this->Lang('none2'));
		$sql = 'SELECT name FROM '.$pre.'module_compdir_fielddefs ORDER BY item_order';
		$all = $db->GetCol($sql);
		if ($all) {
			$FieldDefs += array_combine($all,$all);
		}
		$FieldDefsSelected = $this->GetOption('FieldDefs');
		if (!is_array($FieldDefsSelected))
			$FieldDefsSelected = explode(',',$FieldDefsSelected);

		$choices = array(
			$mod->Lang('option_dropdown')=>'Dropdown',
			$mod->Lang('option_selectlist_single')=>'Select List-single',
			$mod->Lang('option_selectlist_multiple')=>'Select List-multiple',
			$mod->Lang('option_radiogroup')=>'Radio Group'
		);

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$main[] = array('','',$mod->Lang('help_company_field'));
		$main[] = array($mod->Lang('title_pick_categories'),
						$mod->CreateInputSelectList($id,'opt_Category',$Categories,$CategorySelected,
						5,'',TRUE));
		$main[] = array($mod->Lang('title_pick_fielddef'),
						$mod->CreateInputSelectList($id,'opt_FieldDefs',$FieldDefs,$FieldDefsSelected,
						5,'',FALSE));
		$adv[] = array($mod->Lang('title_choose_user_input'),
						$mod->CreateInputDropdown($id,'opt_UserInput',$choices,'-1',
							$this->GetOption('UserInput')));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$CompanyDirectory = $mod->GetModuleInstance('CompanyDirectory');
		if ($CompanyDirectory)
			unset($CompanyDirectory);
		else
			return $mod->Lang('error_module_CompanyDirectory');

		$results = array();

		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$Like = $this->GetOption('Category','%');
		if ($Like=='' || $Like=='%' || $Like=='All')
			$processPath=" LIKE '%'";
		else
			$processPath='=?';

		$sql = <<<EOS
SELECT com.id,com.company_name FROM {$pre}module_compdir_company_categories AS comcat
LEFT JOIN {$pre}module_compdir_categories AS cat ON cat.id=comcat.category_id
LEFT JOIN {$pre}module_compdir_companies AS com ON com.id=comcat.company_id
WHERE cat.name{$processPath} AND status='published'
EOS;
		$sql2 = <<<EOS
SELECT value FROM {$pre}module_compdir_fieldvals AS comfv
LEFT JOIN {$pre}module_compdir_fielddefs AS fdd ON fdd.id=comfv.fielddef_id
LEFT JOIN {$pre}module_compdir_companies AS com ON comfv.company_id=com.id
WHERE com.company_name=? AND fdd.name=?
EOS;
		$val = array();
		$companies = array();
		$field = $this->GetOption('FieldDefs');
		if ($Like=='' || $Like=='%' || $Like=='All') {
			$rs = $db->Execute($sql,array());
			if ($rs) {
				while ($row = $rs->FetchRow()) {
					$company = $row['company_name'];
					$FDval = '';
					$rs2 = $db->Execute($sql2,array($company,$field));
					if ($rs2) {
						while ($row = $rs2->FetchRow())
							$FDval = $row['value'];
						$rs2->Close();
					}

					$companies[$company] = $FDval;
				}
				$rs->Close();
			}
		} else {
			if (is_array($Like)) {
				foreach ($Like as $key => $value) {
					$rs = $db->Execute($sql,array($value));
					if ($rs) {
						while ($row = $rs->FetchRow()) {
							$company = $row['company_name'];
							$FDval = '';
							$rs2 = $db->Execute($sql2,array($company,$field));
							if ($rs2) {
								while ($row = $rs2->FetchRow())
									$FDval = $row['value'];
								$rs2->Close();
							}
							$companies[$company] = $FDval;
						}
						$rs->Close();
					}
				}
			} else {
				$rs = $db->Execute($sql ,array($Like));
				if ($rs) {
					while ($row = $rs->FetchRow()) {
						$company=$row['company_name'];
						$FDval='';
						$rs2 = $db->Execute($sql2 ,array($company,$field));
						if ($rs2) {
							while ($row = $rs2->FetchRow())
								$FDval = $row['value'];
							$rs2->Close();
						}
						$companies[$company] = $FDval;
					}
					$rs->Close();
				}
			}
		}

		foreach ($companies as $key=>$val) {
			if (empty($val))
				$companies[$key] = $key;
		}
		// Do we have something to display?
		if ($companies) {
			$size = min(50,count($companies)); // maximum 50 lines,though this is probably big

			$val = array();
			if (property_exists($this,'Value')) {
				$val = $this->Value;
				if (!is_array($this->Value))
					$val = array($this->Value);
			}

			switch ($this->GetOption('UserInput','Dropdown')) {
			 case 'Dropdown':
				$tmp = $mod->CreateInputDropdown(
					$id,$this->formdata->current_prefix.$this->Id,
					$companies,'-1',$val);
			 case 'Radio Group':
				$tmp = $mod->CreateInputRadioGroup(
					$id,$this->formdata->current_prefix.$this->Id,
					$companies,$val,'','&nbsp;&nbsp;');
			 case 'Select List-single':
				$tmp = $mod->CreateInputSelectList(
					$id,$this->formdata->current_prefix.$this->Id.'[]',
					$companies,$val,$size,
					'id="'.$this->GetInputId().'"'.$this->GetScript(),FALSE);
			 case 'Select List-multiple':
				$tmp = $mod->CreateInputSelectList(
					$id,$this->formdata->current_prefix.$this->Id.'[]',
					$companies,$val,$size,
					'id="'.$this->GetInputId().'"'.$this->GetScript());
			 default:
			 	$tmp = FALSE;
			}
			if ($tmp)
				return $this->SetClass($tmp);
		}
		return ''; // error
	}
}
