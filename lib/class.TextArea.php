<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class TextArea extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'TextArea';
		$this->ValidationType = 'none';
		$mod = $formdata->pwfmod;
		$this->ValidationTypes = [
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_length')=>'length'
		];
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'cols' => 11,
		'rows' => 11,
		'length' => 11,
		'default' => 12,
		'clear_default' => 10,
		'html5' => 10,
		'wysiwyg' => 10,
		];
	}

	public function GetSynopsis()
	{
		if ($this->ValidationType) {
//			$this->EnsureArray($this->ValidationTypes);
			if (is_object($this->ValidationTypes)) {
				$this->ValidationTypes = (array)$this->ValidationTypes;
			}
			$ret = array_search($this->ValidationType, $this->ValidationTypes);
		} else {
			$ret = '';
		}

		if ($this->GetProperty('wysiwyg', 0)) {
			$ret .= ' wysiwyg';
		} else {
			$ret .= ' non-wysiwyg';
		}

		$mod = $this->formdata->pwfmod;
		$ret .= ','.$mod->Lang('rows', $this->GetProperty('rows', 15)).
		','.$mod->Lang('cols', $this->GetProperty('cols', 80));

		return $ret;
	}

	public function AdminPopulate($id)
	{
		//omit "javascript" TODO why ? maybe justified if we add our own (for autogrow)
		list($main, $adv) = $this->AdminPopulateCommon($id, 'title_field_javascript');
		$mod = $this->formdata->pwfmod;

		$main[] = [$mod->Lang('title_use_wysiwyg'),
					$mod->CreateInputHidden($id, 'fp_wysiwyg', 0).
					$mod->CreateInputCheckbox($id, 'fp_wysiwyg', 1, $this->GetProperty('wysiwyg', 0))];
		$main[] = [$mod->Lang('title_textarea_rows'),
					$mod->CreateInputText($id, 'fp_rows', $this->GetProperty('rows', 15), 2, 2)];
		$main[] = [$mod->Lang('title_textarea_cols'),
					$mod->CreateInputText($id, 'fp_cols', $this->GetProperty('cols'), 5, 5),
					$mod->Lang('help_textarea_cols')];
		$main[] = [$mod->Lang('title_field_default_value'),
					$mod->CreateTextArea(FALSE, $id, $this->GetProperty('default'), 'fp_default',
					'pwf_shortarea', '', '', '', 50, 8)];
		$adv[] = [$mod->Lang('title_clear_default'),
					$mod->CreateInputHidden($id, 'fp_clear_default', 0).
					$mod->CreateInputCheckbox($id, 'fp_clear_default', 1, $this->GetProperty('clear_default', 0)),
					$mod->Lang('help_clear_default')];
		$main[] = [$mod->Lang('title_textarea_length'),
					$mod->CreateInputText($id, 'fp_length', $this->GetProperty('length'), 5, 5),
					$mod->Lang('help_limit_count')];
		$adv[] = [$mod->Lang('title_html5'),
					$mod->CreateInputHidden($id, 'fp_html5', 0).
					$mod->CreateInputCheckbox($id, 'fp_html5', 1, $this->GetProperty('html5', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$rows = $this->GetProperty('rows', 15);
		$cols = $this->GetProperty('cols');
		$wysiwyg = $this->GetProperty('wysiwyg', 0);
		$add = ' style="overflow:auto;height:'.$rows.'em;';
		if ($cols) {
			$add .= 'width:'.$cols.'em;';
		}
		$add .= '"';
		$htmlid = $id.$this->GetInputId(); //html may get id="$id.$htmlid", or maybe not ...
		$clear = $this->GetProperty('clear_default', 0);
//TODO make this auto-grow see http://www.impressivewebs.com/textarea-auto-resize
		$mod = $this->formdata->pwfmod;
		if ($this->GetProperty('html5', 0)) {
			$tmp = $mod->CreateTextArea($wysiwyg, $id,
				$this->Value,
				$this->formdata->current_prefix.$this->Id,
				'', $htmlid, '', '', $cols, $rows, '', '',
				' placeholder="'.$this->GetProperty('default').'"'.$add);
		} else {
			$tmp = $mod->CreateTextArea($wysiwyg, $id,
				($this->Value?$this->Value:$this->GetProperty('default')),
				$this->formdata->current_prefix.$this->Id,
				'', $htmlid, '', '', $cols, $rows, '', '', $add);
		}

		if ($this->GetProperty('clear_default', 0)) {
			$xclass = 'formarea';
			//arrange for all such fields to be cleared
			if (!isset($this->formdata->Jscript->jsloads['cleararea'])) {
				$this->formdata->Jscript->jsloads['cleararea'] = <<<'EOS'
 $('.formarea').focus(function() {
  if (this.value == this.defaultValue) {
   this.value = '';
  }
 }).blur(function() {
   if (this.value === '') {
	this.value = this.defaultValue;
   }
 });
EOS;
			}
		} else {
			$xclass = '';
		}
		return $this->SetClass($tmp, $xclass);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_STRING);
		}
		$val = TRUE;
		$this->ValidationMessage = '';
		$length = $this->GetProperty('length');
		if (is_numeric($length) && $length > 0) {
			if ((strlen($this->Value)-1) > $length) {
				$val = FALSE;
				$this->ValidationMessage = $this->formdata->pwfmod->Lang('enter_no_longer', $length);
			}
			$this->Value = substr($this->Value, 0, $length+1);
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
