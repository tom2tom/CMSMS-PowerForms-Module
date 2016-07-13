<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class TextArea extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'TextArea';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_length')=>'length'
		);
	}

	public function GetFieldStatus()
	{
		if ($this->ValidationType)
			$ret = array_search($this->ValidationType,$this->ValidationTypes);
		else
			$ret = '';

		if ($this->GetOption('wysiwyg',0))
			$ret .= ' wysiwyg';
		else
			$ret .= ' non-wysiwyg';

		$mod = $this->formdata->formsmodule;
		$ret .= ','.$mod->Lang('rows',$this->GetOption('rows',15)).
		','.$mod->Lang('cols',$this->GetOption('cols',80));

		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_use_wysiwyg'),
						$mod->CreateInputHidden($id,'opt_wysiwyg',0).
						$mod->CreateInputCheckbox($id,'opt_wysiwyg',1,$this->GetOption('wysiwyg',0)));
		$main[] = array($mod->Lang('title_textarea_rows'),
						$mod->CreateInputText($id,'opt_rows',$this->GetOption('rows',15),2,2));
//TODO this is stupid - prefer 100%
//		$main[] = array($mod->Lang('title_textarea_cols'),
//						$mod->CreateInputText($id,'opt_cols',$this->GetOption('cols',80),5,5));
		$main[] = array($mod->Lang('title_textarea_length'),
						$mod->CreateInputText($id,'opt_length',$this->GetOption('length'),5,5));
		//omit "javascript" TODO why ? maybe justified if we add our own (for autogrow)
		$this->RemoveAdminField($adv,$mod->Lang('title_field_javascript'));
		$adv[] = array($mod->Lang('title_field_default_value'),
						$mod->CreateTextArea(FALSE,$id,$this->GetOption('default'),'opt_default',
						'pwf_tallarea','','','',50,15));
		$adv[] = array($mod->Lang('title_clear_default'),
						$mod->CreateInputHidden($id,'opt_clear_default',0).
						$mod->CreateInputCheckbox($id,'opt_clear_default',1,$this->GetOption('clear_default',0)),
						$mod->Lang('help_clear_default'));
		$adv[] = array($mod->Lang('title_html5'),
						$mod->CreateInputHidden($id,'opt_html5',0).
						$mod->CreateInputCheckbox($id,'opt_html5',1,$this->GetOption('html5',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$cols = $this->GetOption('cols',80);
		$rows = $this->GetOption('rows',15);
		$wysiwyg = $this->GetOption('wysiwyg',0);
		$add = ($wysiwyg) ? ' style="width:100%;"':' style="overflow:auto;height:'.$rows.'em;width:100%;"';
		$htmlid = $id.$this->GetInputId(); //html may get id="$id.$htmlid", or maybe not ...
		$clear = $this->GetOption('clear_default',0);
//TODO make this auto-grow see http://www.impressivewebs.com/textarea-auto-resize
		$mod = $this->formdata->formsmodule;
		if ($this->GetOption('html5',0)) {
			$tmp = $mod->CreateTextArea($wysiwyg,$id,
				$this->Value,
				$this->formdata->current_prefix.$this->Id,
				'',$htmlid,'','',$cols,$rows,'','',
				' placeholder="'.$this->GetOption('default').'"'.$add);
		} else {
			$tmp = $mod->CreateTextArea($wysiwyg,$id,
				($this->Value?$this->Value:$this->GetOption('default')),
				$this->formdata->current_prefix.$this->Id,
				'',$htmlid,'','',$cols,$rows,'','',$add);
		}

		if ($this->GetOption('clear_default',0)) {
			//arrange for all such fields to be cleared
			$this->formdata->jscripts['cleararea'] =<<<EOS
$(document).ready(function() {
 $('.formarea').each(function(index,element) {
  $(element).focus(function() {
   if (this.value == this.defaultValue) {
    this.value = '';
   }
  }).blur(function() {
   if (this.value === '') {
    this.value = this.defaultValue;
   }
  });
 });
});
EOS;
			$xclass = 'formarea';
		} else
			$xclass = '';
		return $this->SetClass($tmp,$xclass);
	}

	public function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$length = $this->GetOption('length');
		if (is_numeric($length) && $length > 0) {
			if ((strlen($this->Value)-1) > $length) {
				$this->validated = FALSE;
				$mod = $this->formdata->formsmodule;
				$this->ValidationMessage = $mod->Lang('please_enter_no_longer',$length);
			}
			$this->Value = substr($this->Value,0,$length+1);
		}
		return array($this->validated,$this->ValidationMessage);
	}

}

