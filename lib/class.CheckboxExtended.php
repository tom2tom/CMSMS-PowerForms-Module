<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class CheckboxExtended extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->IsInput = TRUE;
		$this->Type = 'CheckboxExtended';
		$this->ValidationType = 'none';
		$mod = $formdata->pwfmod;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_empty')=>'empty'];
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'is_checked' => 10,
		'checked_value' => 14,
		'unchecked_value' => 14,
		'box_label' => 12,
		'text_label' => 12,
		'show_textfield' => 10,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$ret = ($this->GetProperty('is_checked', 0) ?
			$mod->Lang('checked_by_default'):
			$mod->Lang('unchecked_by_default'));
		if ($this->ValidationType) {
//			$this->EnsureArray($this->ValidationTypes);
			if (is_object($this->ValidationTypes)) {
				$this->ValidationTypes = (array)$this->ValidationTypes;
			}
			$ret .= ','.array_search($this->ValidationType, $this->ValidationTypes);
		}
		return $ret;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->pwfmod;
		$val = $this->Value;

		if ($val['box']) {
			$ret = $this->GetProperty('checked_value', $mod->Lang('value_checked'));
		} else {
			$ret = $this->GetProperty('unchecked_value', $mod->Lang('value_unchecked'));
		}

		if (!empty($val['text'])) {
			$ret .= $this->GetFormProperty('list_delimiter', ',').$val['text'];
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, 'title_field_required');
		$mod = $this->formdata->pwfmod;

		$main[] = [$mod->Lang('title_checkbox_label'),
					$mod->CreateInputText($id, 'fp_box_label',
						$this->GetProperty('box_label'), 25, 255)];
		$main[] = [$mod->Lang('title_checked_value'),
					$mod->CreateInputText($id, 'fp_checked_value',
						$this->GetProperty('checked_value', $mod->Lang('yes')), 25, 255)];
		$main[] = [$mod->Lang('title_unchecked_value'),
					$mod->CreateInputText($id, 'fp_unchecked_value',
						$this->GetProperty('unchecked_value', $mod->Lang('no')), 25, 255)];
		$main[] = [$mod->Lang('checked_by_default'),
					$mod->CreateInputHidden($id, 'fp_is_checked', 0).
					$mod->CreateInputCheckbox($id, 'fp_is_checked', 1,
						$this->GetProperty('is_checked', 0))];
		$main[] = [$mod->Lang('title_textfield_label'),
					$mod->CreateInputText($id, 'fp_text_label',
						$this->GetProperty('text_label'), 25, 255)];
		$main[] = [$mod->Lang('title_show_textfield'),
					$mod->CreateInputHidden($id, 'fp_show_textfield', 0).
					$mod->CreateInputCheckbox($id, 'fp_show_textfield', 1,
						$this->GetProperty('show_textfield', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$show = $this->GetProperty('show_textfield');
		$sf = ($show)?'_0':'';
		$tid = $this->GetInputId($sf);

		$mod = $this->formdata->pwfmod;
		$js = $this->GetScript();
		$ret = [];

		$hidden = $this->formdata->pwfmod->CreateInputHidden(
			$id, $this->formdata->current_prefix.$this->Id, 0);

		$oneset = new \stdClass();
		$oneset->title = '';
		$label = $this->GetProperty('box_label');
		if ($label) {
			$tmp = '<label for="'.$tid.'">'.$label.'</label>';
			$label = $this->SetClass($tmp);
		}
		$oneset->name = $label;

		if ($this->Value) {
			$hasvalue = !empty($this->Value['box']);
			if (!$hasvalue && $this->GetProperty('is_checked', 0)) {
				$this->Value['box'] = 't';
				$hasvalue = TRUE;
			}
		} else {
			$hasvalue = FALSE;
		}

		$tmp = $mod->CreateInputCheckbox(
			$id, $this->formdata->current_prefix.$this->Id.'[box]', 't',
			($hasvalue?$this->Value['box']:0),
			'id="'.$tid.'"'.$js);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $hidden.$oneset;

		if ($show) {
			$tid = $this->GetInputId('_1');
			if ($this->GetProperty('text_label')) {
				$tmp = '<label for="'.$tid.'">'.$this->GetProperty('text_label').'</label>';
				$label = $this->SetClass($tmp);
			} else {
				$label = '';
			}

			if ($this->Value) {
				$hasvalue = !empty($this->Value['text']);
			} else {
				$hasvalue = FALSE;
			}

			$oneset = new \stdClass();
			$oneset->title = '';
			$oneset->name = $label;
			$tmp = $mod->CreateInputText(
				$id, $this->formdata->current_prefix.$this->Id.'[text]',
				($hasvalue?$this->Value['text']:''), 25, 25,
				$js);
			$tmp = preg_replace('/id="\S+"/', 'id="'.$tid.'"', $tmp);
			$oneset->input = $this->SetClass($tmp);

			$ret[] = $oneset;
		}
		$this->MultiPopulate = TRUE;
		return $ret;
	}

	public function Validate($id)
	{
		if ($this->Value['text'] !== '') {
			$this->Value['text'] = filter_var(trim($this->Value['text']), FILTER_SANITIZE_STRING);
		}

		$val = TRUE;
		$this->ValidationMessage = '';

		switch ($this->ValidationType) {
		 case 'none':
			break;
		 case 'empty':
			if (empty($this->Value['text'])) {
				$val = FALSE;
				$this->ValidationMessage = $this->formdata->pwfmod->Lang('enter_a_value', $this->GetProperty('text_label'));
			}
			break;
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
