<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms

This class (by Jeremy Bass <jeremyBass@cableone.net>) provides a dynamic
multiselect list to allow selecting one or more items from the CompanyDirectory
module. The list is filtered by an array of options as specified in the admin.
DEPRECATED - should be applied dynamically by CompanyDirectory module
*/

namespace PWForms;

class CompanyDirectory extends FieldBase
{
	const MODNAME = 'CompanyDirectory'; //initiator/owner module name
	public $MenuKey = 'field_label'; //owner-module lang key for this field's menu label, used by PWForms
	public $mymodule; //used also by PWForms, do not rename

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'CompanyDirectory';
		$this->mymodule = \cms_utils::get_module(self::MODNAME);
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'Category' => 12,
		'FieldDefs' => 12,
		'UserInput' => 12,
		];
	}

	public function GetSynopsis()
	{
		if (!$this->mymodule) {
			return '';
		}
		return $this->formdata->pwfmod->Lang('missing_module', self::MODNAME);
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				if ($as_string) {
					return implode($this->GetFormProperty('list_delimiter', ','), $this->Value);
				} else {
					$ret = $this->Value; //copy
					return $ret;
				}
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->pwfmod->Lang('unspecified'));
		}
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetDisplayType()
	{
		return $this->mymodule->Lang($this->MenuKey);
	}

	public function AdminPopulate($id)
	{
		if (!$this->mymodule) {
			return ['main'=>[$this->GetErrorMessage('err_module', self::MODNAME)]];
		}

		$mod = $this->formdata->pwfmod;
		$pre = \cms_db_prefix();
		$sql = 'SELECT name FROM '.$pre.'module_compdir_categories';
		$db = \cmsms()->GetDb();
		$all = $db->GetCol($sql);
		if ($all) {
			$Categories = ['All'=>$mod->Lang('all')] + array_combine($all, $all);
		} else {
			$Categories = ['none'=>$this->Lang('none')];
		}
		$CategorySelected = explode(',', $this->GetProperty('Category'));

		$FieldDefs = ['none'=>$this->Lang('none')];
		$sql = 'SELECT name FROM '.$pre.'module_compdir_fielddefs ORDER BY item_order';
		$all = $db->GetCol($sql);
		if ($all) {
			$FieldDefs += array_combine($all, $all);
		}
		$FieldDefsSelected = explode(',', $this->GetProperty('FieldDefs'));

		$choices = [
			$mod->Lang('option_dropdown')=>'Dropdown',
			$mod->Lang('option_selectlist_single')=>'Select List-single',
			$mod->Lang('option_selectlist_multiple')=>'Select List-multiple',
			$mod->Lang('option_radiogroup')=>'Radio Group'
		];

		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$main[] = ['','',$mod->Lang('help_company_field')];
		$main[] = [$mod->Lang('title_pick_categories'),
					$mod->CreateInputSelectList($id, 'fp_Category', $Categories, $CategorySelected,
					5, '', TRUE)];
		$main[] = [$mod->Lang('title_pick_fielddef'),
					$mod->CreateInputSelectList($id, 'fp_FieldDefs', $FieldDefs, $FieldDefsSelected,
					5, '', FALSE)];
		$adv[] = [$mod->Lang('title_choose_user_input'),
					$mod->CreateInputDropdown($id, 'fp_UserInput', $choices, '-1',
						$this->GetProperty('UserInput'))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function PostAdminAction(&$params)
	{
		if (is_array($params['fp_Category'])) {
			$val = implode(',', $params['fp_Category']);
		} else {
			$val = $params['fp_Category'];
		}
		$this->SetProperty('Category', $val);

		if (is_array($params['fp_FieldDefs'])) {
			$val = implode(',', $params['fp_FieldDefs']);
		} else {
			$val = $params['fp_FieldDefs'];
		}
		$this->SetProperty('FieldDefs', $val);
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$CompanyDirectory = $this->mymodule;
		if (!$CompanyDirectory) {
			return $mod->Lang('err_module', self::MODNAME);
		}

		$results = [];

		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$Like = $this->GetProperty('Category', '%');
		if ($Like=='' || $Like=='%' || $Like=='All') {
			$processPath=" LIKE '%'";
		} else {
			$processPath='=?';
		}

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
		$val = [];
		$companies = [];
		$field = $this->GetProperty('FieldDefs');
		if ($Like=='' || $Like=='%' || $Like=='All') {
			$rst = $db->Execute($sql, []);
			if ($rst) {
				while ($row = $rst->FetchRow()) {
					$company = $row['company_name'];
					$FDval = '';
					$rst2 = $db->Execute($sql2, [$company, $field]);
					if ($rst2) {
						while ($row = $rst2->FetchRow()) {
							$FDval = $row['value'];
						}
						$rst2->Close();
					}

					$companies[$company] = $FDval;
				}
				$rst->Close();
			}
		} else {
			if (is_array($Like)) {
				foreach ($Like as $key => $value) {
					$rst = $db->Execute($sql, [$value]);
					if ($rst) {
						while ($row = $rst->FetchRow()) {
							$company = $row['company_name'];
							$FDval = '';
							$rst2 = $db->Execute($sql2, [$company, $field]);
							if ($rst2) {
								while ($row = $rst2->FetchRow()) {
									$FDval = $row['value'];
								}
								$rst2->Close();
							}
							$companies[$company] = $FDval;
						}
						$rst->Close();
					}
				}
			} else {
				$rst = $db->Execute($sql, [$Like]);
				if ($rst) {
					while ($row = $rst->FetchRow()) {
						$company=$row['company_name'];
						$FDval='';
						$rst2 = $db->Execute($sql2, [$company, $field]);
						if ($rst2) {
							while ($row = $rst2->FetchRow()) {
								$FDval = $row['value'];
							}
							$rst2->Close();
						}
						$companies[$company] = $FDval;
					}
					$rst->Close();
				}
			}
		}

		foreach ($companies as $key=>$val) {
			if (empty($val)) {
				$companies[$key] = $key;
			}
		}
		// Do we have something to display?
		if ($companies) {
			$size = min(50, count($companies)); // maximum 50 lines,though this is probably big

			$val = [];
			if ($this->Value || is_numeric($this->Value)) {
				$val = $this->Value;
				if (!is_array($this->Value)) {
					$val = [$this->Value];
				}
			}

			switch ($this->GetProperty('UserInput', 'Dropdown')) {
			 case 'Dropdown':
				$tmp = $mod->CreateInputDropdown(
					$id, $this->formdata->current_prefix.$this->Id,
					$companies, '-1', $val);
			 case 'Radio Group':
				$tmp = $mod->CreateInputRadioGroup(
					$id, $this->formdata->current_prefix.$this->Id,
					$companies, $val, '', '&nbsp;&nbsp;');
			 case 'Select List-single':
				$tmp = $mod->CreateInputSelectList(
					$id, $this->formdata->current_prefix.$this->Id.'[]',
					$companies, $val, $size,
					'id="'.$this->GetInputId().'"'.$this->GetScript(), FALSE);
			 case 'Select List-multiple':
				$tmp = $mod->CreateInputSelectList(
					$id, $this->formdata->current_prefix.$this->Id.'[]',
					$companies, $val, $size,
					'id="'.$this->GetInputId().'"'.$this->GetScript());
			 default:
				$tmp = FALSE;
			}
			if ($tmp) {
				return $this->SetClass($tmp);
			}
		}
		return ''; // error
	}

	public function __toString()
	{
		$ob = $this->mymodule;
		$this->mymodule = NULL;
		$ret = parent::__toString();
		$this->mymodule = $ob;
		return $ret;
	}

	public function unserialize($serialized)
	{
		parent::unserialize($serialized);
		$this->mymodule = \cms_utils::get_module(self::MODNAME);
	}
}
