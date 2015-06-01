<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfTextArea extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
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

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$cssid = $this->GetCSSId();
		$cols = $this->GetOption('cols','80');
		$rows = $this->GetOption('rows','15');
		$wysiwyg = ($this->GetOption('wysiwyg','0') == '1');
		$extra = ($wysiwyg) ? '':' style="height:'.$rows.'em;width:'.$cols.'em;"';
		if($this->GetOption('html5','0') == '1')
		{
			$ret = $mod->CreateTextArea($wysiwyg,$id,$this->Value,
					$this->formdata->current_prefix.$this->Id,'',$cssid,'','',$cols,$rows,
					'','',' placeholder="'.$this->GetOption('default').'"'.$extra);
		}
		else
		{
			$ret = $mod->CreateTextArea($wysiwyg,$id,($this->Value?$this->Value:$this->GetOption('default')),
					$this->formdata->current_prefix.$this->Id,'',$cssid,'','',$cols,$rows,'','',$extra);
		}

		if($this->GetOption('clear_default','0')=='1')
		{
			$ret .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 var f = document.getElementById('{$cssid}');
 if(f) {
  f.onfocus = function() {
   if(this.value == this.defaultValue) {
    this.value = '';
   }
  };
  f.onblur = function() {
   if(this.value == '') {
    this.value = this.defaultValue;
   }
  };
 }
});
//]]>
</script>

EOS;
		}

		return $ret;
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$length = $this->GetOption('length');
		if(is_numeric($length) && $length > 0)
		{
			if((strlen($this->Value)-1) > $length)
			{
				$this->validated = FALSE;
				$mod = $this->formdata->formsmodule;
				$this->ValidationMessage = $mod->Lang('please_enter_no_longer',$length);
			}
			$this->Value = substr($this->Value,0,$length+1);
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function GetFieldStatus()
	{
		if(strlen($this->ValidationType)>0)
			$ret = array_search($this->ValidationType,$this->ValidationTypes);
		else
			$ret = '';

		if($this->GetOption('wysiwyg','0') == '1')
			$ret .= ' wysiwyg';
		else
			$ret .= ' non-wysiwyg';

		$mod = $this->formdata->formsmodule;
		$ret .= ','.$mod->Lang('rows',$this->GetOption('rows','15')).
		','.$mod->Lang('cols',$this->GetOption('cols','80'));

		return $ret;
	}


	function PrePopulateAdminForm($id)
	{
	   $mod = $this->formdata->formsmodule;
	   $main = array(
			array($mod->Lang('title_use_wysiwyg'),
				$mod->CreateInputHidden($id,'opt_wysiwyg','0').
				$mod->CreateInputCheckbox($id,'opt_wysiwyg','1',$this->GetOption('wysiwyg','0'))),
			array($mod->Lang('title_textarea_rows'),
				$mod->CreateInputText($id,'opt_rows',$this->GetOption('rows','15'),5,5)),
			array($mod->Lang('title_textarea_cols'),
				$mod->CreateInputText($id,'opt_cols',$this->GetOption('cols','80'),5,5)),
			array($mod->Lang('title_textarea_length'),
				$mod->CreateInputText($id,'opt_length',$this->GetOption('length'),5,5))
           );

	   $adv = array(
			array($mod->Lang('title_field_default_value'),
				$mod->CreateTextArea(FALSE,$id,$this->GetOption('default'),'opt_default')),
			array($mod->Lang('title_html5'),
				$mod->CreateInputHidden($id,'opt_html5','0').
				$mod->CreateInputCheckbox($id,'opt_html5','1',$this->GetOption('html5','0'))),
			array($mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($id,'opt_clear_default','0').
				$mod->CreateInputCheckbox($id,'opt_clear_default','1',$this->GetOption('clear_default','0')),
				$mod->Lang('help_clear_default'))
		);

        return array('main'=>$main,'adv'=>$adv);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		// omit "javascript"
		$this->RemoveAdminField($advArray,
			$this->formdata->formsmodule->Lang('title_field_javascript'));
	}

}

?>
