<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class CheckboxGroup extends FieldBase
{
	var $boxAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->IsSortable = FALSE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'CheckboxGroup';
	}

	// Get add-button label
	public function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_checkboxes');
	}

	// Get delete-button label
	public function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_checkboxes');
	}

	// Add action
	public function DoOptionAdd(&$params)
	{
		$this->boxAdd = TRUE;
	}

	// Delete action
	public function DoOptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemoveOptionElement('box_name',$indx);
				$this->RemoveOptionElement('box_checked',$indx);
				$this->RemoveOptionElement('box_unchecked',$indx);
				$this->RemoveOptionElement('box_is_set',$indx);
			}
		}
	}

	public function GetFieldStatus()
	{
		$opt = $this->GetOptionRef('box_name');
		if ($opt)
			$boxCount = count($opt);
		else
			$boxCount = 0;
		return $this->formdata->formsmodule->Lang('boxes',$boxCount);
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		$names = $this->GetOptionRef('box_name');
		if ($names) {
			$ret = array();
			foreach ($names as $i=>&$one) {
				if ($this->FindArrayValue($i) === FALSE) { //TODO sequence
					if (!$this->GetOption('no_empty',0)) {
						$unchecked = trim($this->GetOptionElement('box_unchecked',$i));
						if ($unchecked)
							$ret[$one] = $unchecked;
					}
				} else {
					$checked = trim($this->GetOptionElement('box_checked',$i));
					if ($checked)
						$ret[$one] = $checked;
				}
			}
			unset($one);

			if ($as_string) {
				// Check if we should include labels
				if ($this->GetOption('include_labels',0)) {
					$output = '';
					foreach ($ret as $key=>$value)
						$output .= $key.': '.$value.$this->GetFormOption('list_delimiter',',');

					$output = substr($output,0,strlen($output)-1);
					return $output;
				}
				return implode($this->GetFormOption('list_delimiter',','),$ret);
			} else {
				return $ret;
			}
		}
		return ''; //TODO upspecified
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_no_empty'),
						$mod->CreateInputHidden($id,'opt_no_empty',0).
						$mod->CreateInputCheckbox($id,'opt_no_empty',1,
							$this->GetOption('no_empty',0)),
						$mod->Lang('help_no_empty'));
		$adv[] = array($mod->Lang('title_single_check'),
						$mod->CreateInputHidden($id,'opt_single_check',0).
						$mod->CreateInputCheckbox($id,'opt_single_check',1,
							$this->GetOption('single_check',0)),
						$mod->Lang('help_single_check'));
		$adv[] = array($mod->Lang('title_field_includelabels'),
						$mod->CreateInputHidden($id,'opt_include_labels',0).
						$mod->CreateInputCheckbox($id,'opt_include_labels',1,
							$this->GetOption('include_labels',0)),
						$mod->Lang('help_field_includelabels'));

		if ($this->boxAdd) {
			$this->AddOptionElement('box_name','');
			$this->AddOptionElement('box_checked','');
			$this->AddOptionElement('box_is_set','y');
			$this->boxAdd = FALSE;
		}
		$names = $this->GetOptionRef('box_name');
		if ($names) {
			$boxes = array();
			$boxes[] = array(
				$mod->Lang('title_checkbox_label'),
				$mod->Lang('title_checked_value'),
				$mod->Lang('title_unchecked_value'),
				$mod->Lang('title_default_set'),
				$mod->Lang('title_select')
			);
			$yesNo = array($mod->Lang('no')=>'n',$mod->Lang('yes')=>'y');
			foreach ($names as $i=>&$one) {
				$boxes[] = array(
					$mod->CreateInputText($id,'opt_box_name'.$i,$one,30,128),
					$mod->CreateInputText($id,'opt_box_checked'.$i,$this->GetOptionElement('box_checked',$i),20,128),
					$mod->CreateInputText($id,'opt_box_unchecked'.$i,$this->GetOptionElement('box_unchecked',$i),20,128),
					$mod->CreateInputDropdown($id,'opt_box_is_set'.$i,$yesNo,-1,$this->GetOptionElement('box_is_set',$i)),
					$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array('','',$mod->Lang('title_checkbox_details'),$boxes);
			return array('main'=>$main,'adv'=>$adv,'table'=>$boxes);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('item')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetOptionRef('box_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!($one || $this->GetOptionElement('box_checked',$i))) {
					$this->RemoveOptionElement('box_name',$i); //should be ok in loop
					$this->RemoveOptionElement('box_checked',$i);
					$this->RemoveOptionElement('box_unchecked',$i);
					$this->RemoveOptionElement('box_is_set',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$names = $this->GetOptionRef('box_name');
		if ($names) {
			$mod = $this->formdata->formsmodule;
			$limit = $this->GetOption('single_check',0);
			if ($limit) {
				$this->formdata->jscripts['checkboxgroup'] = <<<EOS
function select_only(cb) {
 if (cb.checked) {
  $('input:checkbox[name="'+cb.name+'"]').attr('checked',false);
  cb.checked = true;
 }
}
EOS;
				$jsl = ' onchange="select_only(this);"';
			} else
				$jsl = '';
			$js = $this->GetScript();
			$ret = array();
			foreach ($names as $i=>&$one) {
				$oneset = new stdClass();
				$tid = $this->GetInputId('_'.$i);
				if ($one) {
					$oneset->title = $one;
					$tmp = '<label class= "" for="'.$tid.'">'.$one.'</label>';
					$oneset->name = $this->SetClass($tmp);
				} else {
					$oneset->title = '';
					$oneset->name = '';
				}

				if (property_exists($this,'Value'))
					$checked = $this->FindArrayValue($i) ? $i:-1; //TODO
				elseif ($this->GetOptionElement('box_is_set',$i) == 'y')
					$checked = $i;
				else
					$checked = -1;
				$tmp = $mod->CreateInputCheckbox(
					$id,$this->formdata->current_prefix.$this->Id.'[]',$i,$checked,
					'id="'.$tid.'"'.$jsl.$js);
				$oneset->input = $this->SetClass($tmp);
				$ret[] = $oneset;
			}
			unset($one);
			$this->MultiPopulate = TRUE;
			return $ret;
		}
		$this->MultiPopulate = FALSE;
		return '';
	}
}
