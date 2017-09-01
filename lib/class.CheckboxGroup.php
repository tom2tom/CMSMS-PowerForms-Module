<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class CheckboxGroup extends FieldBase
{
	private $boxAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->MultiComponent = TRUE;
		$this->Type = 'CheckboxGroup';
		$this->ValidationType = 'none';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_empty')=>'empty'];
	}

	// Get add-button label
	public function ComponentAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_checkboxes');
	}

	// Get delete-button label
	public function ComponentDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_checkboxes');
	}

	// Add action
	public function ComponentAdd(&$params)
	{
		$this->boxAdd = TRUE;
	}

	// Delete action
	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('box_name', $indx);
				$this->RemovePropIndexed('box_checked', $indx);
				$this->RemovePropIndexed('box_unchecked', $indx);
				$this->RemovePropIndexed('box_is_set', $indx);
			}
		}
	}

	public function SetValue($newvalue)
	{
		$set = $this->GetPropIndexed('box_checked', 1);
		$unset = array_values($this->GetPropArray('box_unchecked')); //0-based
		if (is_array($newvalue)) {
			foreach ($newvalue as $val) { //$val = 1-based index
				$unset[$val-1] = $set;
			}
		}
		$this->Value = $unset;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$names = $this->GetPropArray('box_name');
		if ($names) {
			$ret = [];
			foreach ($names as $i=>&$one) {
				if ($this->InArrayValue($i) === FALSE) { //TODO OR sequence GetArrayValue($index)
					if (!$this->GetProperty('no_empty', 0)) {
						$unchecked = trim($this->GetPropIndexed('box_unchecked', $i));
						if ($unchecked) {
							$ret[$one] = $unchecked;
						}
					}
				} else {
					$checked = trim($this->GetPropIndexed('box_checked', $i));
					if ($checked) {
						$ret[$one] = $checked;
					}
				}
			}
			unset($one);

			if ($as_string) {
				// Check if we should include labels
				if ($this->GetProperty('include_labels', 0)) {
					$output = '';
					foreach ($ret as $key=>$value) {
						$output .= $key.': '.$value.$this->GetFormProperty('list_delimiter', ',');
					}

					$output = substr($output, 0, strlen($output)-1);
					return $output;
				}
				return implode($this->GetFormProperty('list_delimiter', ','), $ret);
			} else {
				return $ret;
			}
		}
		return ''; //TODO upspecified
	}

	public function GetSynopsis()
	{
		$pt = $this->GetPropArray('box_name');
		if ($pt) {
			$boxCount = count($pt);
		} else {
			$boxCount = 0;
		}
		return $this->formdata->formsmodule->Lang('boxes', $boxCount);
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE);
		$mod = $this->formdata->formsmodule;
		$main[] = [$mod->Lang('title_no_empty'),
						$mod->CreateInputHidden($id, 'fp_no_empty', 0).
						$mod->CreateInputCheckbox($id, 'fp_no_empty', 1,
							$this->GetProperty('no_empty', 0)),
						$mod->Lang('help_no_empty')];
		$adv[] = [$mod->Lang('title_single_check'),
						$mod->CreateInputHidden($id, 'fp_single_check', 0).
						$mod->CreateInputCheckbox($id, 'fp_single_check', 1,
							$this->GetProperty('single_check', 0)),
						$mod->Lang('help_single_check')];
		$adv[] = [$mod->Lang('title_field_includelabels'),
						$mod->CreateInputHidden($id, 'fp_include_labels', 0).
						$mod->CreateInputCheckbox($id, 'fp_include_labels', 1,
							$this->GetProperty('include_labels', 0)),
						$mod->Lang('help_field_includelabels')];

		if ($this->boxAdd) {
			$this->AddPropIndexed('box_name', '');
			$this->AddPropIndexed('box_checked', '');
			$this->AddPropIndexed('box_is_set', 'y');
			$this->boxAdd = FALSE;
		}
		$names = $this->GetPropArray('box_name');
		if ($names) {
			$boxes = [];
			$boxes[] = [
				$mod->Lang('title_default_check'),
				$mod->Lang('title_checkbox_label'),
				$mod->Lang('title_checked_value'),
				$mod->Lang('title_unchecked_value'),
				$mod->Lang('title_select')
			];
			$fieldclass = 'field'.$this->Id;
			foreach ($names as $i=>$one) {
				$arf = '['.$i.']';
				$tmp = $mod->CreateInputCheckbox($id, 'fp_box_is_set'.$arf, 'y', $this->GetPropIndexed('box_is_set', $i),
					'style="display:block;margin:auto;"');
				$boxes[] = [
					$mod->CreateInputHidden($id, 'fp_box_is_set'.$arf, 'n').
						str_replace('class="', 'class="'.$fieldclass.' ', $tmp),
					$mod->CreateInputText($id, 'fp_box_name'.$arf, $one, 30, 128),
					$mod->CreateInputText($id, 'fp_box_checked'.$arf, $this->GetPropIndexed('box_checked', $i), 20, 128),
					$mod->CreateInputText($id, 'fp_box_unchecked'.$arf, $this->GetPropIndexed('box_unchecked', $i), 20, 128),
					$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
/*			//TODO js for field e.g.
			$this->jsfuncs['X'] = <<<'EOS'
function select_only(cb,fclass) {
 if (cb.checked) {
  $('input.'+fclass).attr('checked',false);
  cb.checked = true;
 }
}
EOS;
			$this->jsloads[] = <<<EOS
 $('input.{$fieldclass}').change(function(){
  select_only(this,'{$fieldclass}');
 });
EOS;
*/
			return ['main'=>$main,'adv'=>$adv,'table'=>$boxes];
		} else {
			$main[] = ['','',$mod->Lang('missing_type', $mod->Lang('member'))];
			return ['main'=>$main,'adv'=>$adv];
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('box_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!($one || $this->GetPropIndexed('box_checked', $i))) {
					$this->RemovePropIndexed('box_name', $i); //should be ok in loop
					$this->RemovePropIndexed('box_checked', $i);
					$this->RemovePropIndexed('box_unchecked', $i);
					$this->RemovePropIndexed('box_is_set', $i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id, &$params)
	{
		$names = $this->GetPropArray('box_name');
		if ($names) {
			$mod = $this->formdata->formsmodule;
			$hidden = $mod->CreateInputHidden(
				$id, $this->formdata->current_prefix.$this->Id, 0);

			$limit = $this->GetProperty('single_check', 0);
			if ($limit) {
				if (!isset($this->formdata->jsfuncs['cbgroup'])) {
					//TODO correct js for array of boxes
					$this->formdata->jsfuncs['cbgroup'] = <<<'EOS'
function select_only(cb) {
 if (cb.checked) {
  $('input:checkbox[name="'+cb.name+'"]').attr('checked',false);
  cb.checked = true;
 }
}
EOS;
				}
				$jsl = ' onchange="select_only(this);"';
			} else {
				$jsl = '';
			}
			$js = $this->GetScript();
			$ret = [];
			foreach ($names as $i=>&$one) { //$i is 1-based
				$oneset = new \stdClass();
				$tid = $this->GetInputId('_'.$i);
				if ($one) {
					$oneset->title = $one;
					$tmp = '<label class= "" for="'.$tid.'">'.$one.'</label>';
					$oneset->name = $this->SetClass($tmp);
				} else {
					$oneset->title = '';
					$oneset->name = '';
				}

				if ($this->Value) {
					$v = $this->GetArrayValue($i-1);
					if ($v == $this->GetPropIndexed('box_checked', $i)) {
						$checked = $i;
					} else {
						$checked = -1;
					}
				} elseif ($this->GetPropIndexed('box_is_set', $i) == 'y') {
					$checked = $i;
				} else {
					$checked = -1;
				}
				$tmp = $mod->CreateInputCheckbox(
					$id, $this->formdata->current_prefix.$this->Id.'[]', $i, $checked,
					'id="'.$tid.'"'.$jsl.$js);
				if ($hidden) {
					$oneset->input = $hidden.$this->SetClass($tmp);
					$hidden = NULL;
				} else {
					$oneset->input = $this->SetClass($tmp);
				}
				$ret[] = $oneset;
			}
			unset($one);
			$this->MultiPopulate = TRUE;
			return $ret;
		}
		$this->MultiPopulate = FALSE;
		return '';
	}

	public function Validate($id)
	{
		$mod = $this->formdata->formsmodule;
		$this->valid = TRUE;
		$this->ValidationMessage = '';

		switch ($this->ValidationType) {
		 case 'none':
			break;
			case 'empty':
			if (0) { //TODO
				$this->valid = FALSE;
				$this->ValidationMessage = $mod->Lang('TODO', $this->GetProperty('text_label'));
			}
			break;
		}
		return [$this->valid,$this->ValidationMessage];
	}
}
