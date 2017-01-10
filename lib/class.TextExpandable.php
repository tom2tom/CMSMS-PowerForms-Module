<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class TextExpandable extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
//		$this->HasUserAddOp = TRUE;
//		$this->HasUserDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'TextExpandable';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_numeric')=>'numeric',
			$mod->Lang('validation_integer')=>'integer',
			$mod->Lang('validation_email_address')=>'email',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		];
	}

	// Gets all other 'TextExpandable' fields in the form
	public function GetFieldSiblings()
	{
		$siblings = [];
		$siblings[$this->formdata->formsmodule->Lang('select_one')] = '';
		$tid = $this->Id;
		foreach ($this->formdata->Fields as &$one) {
			if ($one->GetFieldType() == 'TextExpandable') {
				$fid = $one->Id;
				if ($fid != $tid) {
					$siblings[$one->GetName()] = $fid;
				}
			}
		}
		unset($one);
		return $siblings;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if (is_array($this->Value)) {
			if ($as_string) {
				return join($this->GetFormProperty('list_delimiter', ','), $this->Value);
			} else {
				$ret = $this->Value;
				return $ret;
			}
		} elseif ($this->Value) {
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length', $this->GetProperty('length', 80));
		if ($this->ValidationType) {
			//			$this->EnsureArray($this->ValidationTypes);
			if (is_object($this->ValidationTypes)) {
				$this->ValidationTypes = (array)$this->ValidationTypes;
			}
			$ret .= ','.array_search($this->ValidationType, $this->ValidationTypes);
		}

		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = [$mod->Lang('title_maximum_length'),
						$mod->CreateInputText($id, 'fp_length', $this->GetProperty('length', 80), 3, 3)];
		$main[] = [$mod->Lang('title_add_button_text'),
						$mod->CreateInputText($id, 'fp_add_button', $this->GetProperty('add_button', '+'), 15, 25)];
		$main[] = [$mod->Lang('title_del_button_text'),
						$mod->CreateInputText($id, 'fp_del_button', $this->GetProperty('del_button', 'X'), 15, 25)];
		$adv[] = [$mod->Lang('title_field_regex'),
						$mod->CreateInputText($id, 'fp_regex', $this->GetProperty('regex'), 25, 255),
						$mod->Lang('help_regex_use')];
		$adv[] = [$mod->Lang('title_field_siblings'),
						$mod->CreateInputDropdown($id, 'fp_siblings', $this->GetFieldSiblings(), -1,
							$this->GetProperty('siblings')),
						$mod->Lang('help_field_siblings')];
		//TODO c.f. $this->HasUserAddOp, $this->HasUserDeleteOp
		$adv[] = [$mod->Lang('title_field_hidebuttons'),
						$mod->CreateInputHidden($id, 'fp_hidebuttons', 0).
						$mod->CreateInputCheckbox($id, 'fp_hidebuttons', 1, $this->GetProperty('hidebuttons', 0)),
						$mod->Lang('help_field_hidebuttons')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function LabelSubComponents()
	{
		return FALSE;
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		$sibling_id = $this->GetProperty('siblings');
		//TODO c.f. $this->HasUserAddOp, $this->HasUserDeleteOp
		$hidebuttons = $this->GetProperty('hidebuttons');

		if (!is_array($this->Value)) {
			$vals = 1;
		} else {
			$vals = count($this->Value);
		}

		$matched = preg_grep('/^pwfp_\d{3}_Fe[DX]_/', array_keys($params));
		if ($matched) {
			foreach ($matched as $key) {
				preg_match('/_Fe([DX])_(\d+)_(\d+)/', $key, $pts);
				if ($pts[2] == $this->Id || $pts[2] == $sibling_id) {
					if ($pts[1] == 'X') { //add row
						$this->Value[$vals] = '';
						$vals++;
					} else { //delete row
						if (isset($this->Value[$pts[3]])) {
							array_splice($this->Value, $pts[3], 1);
						} //TODO check off-by-1
						$vals--;
					}
				}
			}
		}

		// Input fields
		$ret = [];
		for ($i=0; $i<$vals; $i++) {
			$oneset = new \stdClass();

			$oneset->name = '';
			$oneset->title = '';
			$tmp = $mod->CreateInputText(
				$id, $this->formdata->current_prefix.$this->Id.'[]',
				$this->Value[$i], $this->GetProperty('length')<25?$this->GetProperty('length'):25,
				$this->GetProperty('length'),
				$this->GetScript());
			$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId('_'.$i).'"', $tmp);
			$oneset->input = $this->SetClass($tmp);
			if (!$hidebuttons) {
				$tmp = $mod->CreateInputSubmit($id,
					$this->formdata->current_prefix.'FeD_'.$this->Id.'_'.$i,
					$this->GetProperty('del_button', 'X'), ($vals==1?' disabled="disabled"':''));
				$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId('_del_'.$i).'"', $tmp);
				$oneset->op = $this->SetClass($tmp);
			}

			$ret[] = $oneset;
		}

		if (!$hidebuttons) {
			// Add button
			$oneset = new \stdClass();
			$oneset->name = '';
			$oneset->title = '';
			$oneset->input = '';
			$tmp = $mod->CreateInputSubmit($id,
				$this->formdata->current_prefix.'FeX_'.$this->Id.'_'.$i,
				$this->GetProperty('add_button', '+'));
			$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId('_add_'.$i).'"', $tmp);
			$oneset->op = $this->SetClass($tmp);

			$ret[] = $oneset;
		}

		return $ret;
	}

	public function Validate($id)
	{
		$mod = $this->formdata->formsmodule;
		$res = TRUE;
		$messages = [];
		$l = $this->GetProperty('length', 0);

		if (!is_array($this->Value)) {
			$this->Value = [$this->Value];
		}
		foreach ($this->Value as $one) {
			switch ($this->ValidationType) {
			 case 'none':
				break;
			 case 'numeric':
				if ($one && !preg_match('/^[\d\.\,]+$/', $one)) {
					$res = FALSE;
					$messages[] = $mod->Lang('enter_a_number', $this->Name);
				}
				break;
			 case 'integer':
				if ($one && !preg_match('/^\d+$/', $one) || (int)$one != $one) {
					$res = FALSE;
					$messages[] = $mod->Lang('enter_an_integer', $this->Name);
				}
				break;
			 case 'email':
				if ($one && !preg_match($mod->email_regex, $one)) {
					$res = FALSE;
					$messages[] = $mod->Lang('enter_an_email', $this->Name);
				}
				break;
			 case 'regex_match':
				if ($one && !preg_match($this->GetProperty('regex', '/.*/'), $one)) {
					$res = FALSE;
					$messages[] = $mod->Lang('enter_valid', $this->Name);
				}
				break;
			 case 'regex_nomatch':
				if ($one && preg_match($this->GetProperty('regex', '/.*/'), $one)) {
					$res = FALSE;
					$messages[] = $mod->Lang('enter_valid', $this->Name);
				}
				break;
			}

			if ($l > 0 && strlen($one) > $l) {
				$res = FALSE;
				$messages[] = $mod->Lang('enter_no_longer', $l);
			}
		}
		if ($res) {
			$this->valid = TRUE;
			$this->ValidationMessage = '';
		} else {
			$this->valid = FALSE;
			$this->ValidationMessage = implode('<br />', $messages);
		}

		return [$this->valid,$this->ValidationMessage];
	}
}
