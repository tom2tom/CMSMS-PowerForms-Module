<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfTextAreaField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$mod = $formdata->pwfmodule;
		$this->Type = 'TextAreaField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_length')=>'length'
		);
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->GetOption('html5','0') == '1')
		{
			$ret = $mod->CreateTextArea(($this->GetOption('wysiwyg','0') == '1'?true:false),$id,$this->Value,
					'pwfp__'.$this->Id,'',$this->GetCSSId(),'','',$this->GetOption('cols','80'),$this->GetOption('rows','15'),
					'', '', ' placeholder="'.$this->GetOption('default').'"');
		}
		else
		{
			$ret = $mod->CreateTextArea(($this->GetOption('wysiwyg','0') == '1'?true:false),$id,($this->Value?$this->Value:$this->GetOption('default')),
					'pwfp__'.$this->Id,'',$this->GetCSSId(),'','',$this->GetOption('cols','80'),$this->GetOption('rows','15'));
		}

		if($this->GetOption('clear_default','0')=='1')
		{
			$ret .= '<script type="text/javascript">';
			$ret .= "\nvar f = document.getElementById('".$this->GetCSSId()."');\n";
			$ret .= "if(f)\n{\nf.onfocus=function(){\nif(this.value==this.defaultValue) {this.value='';}\n}\n";
			$ret .= "f.onblur=function(){\nif(this.value=='') {this.value=this.defaultValue;}\n}\n";
			$ret .= "}\n;";
			$ret .= "</script>\n";
		}

		return $ret;
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		$mod = $this->formdata->pwfmodule;
		$length = $this->GetOption('length','');
		if(is_numeric($length) && $length > 0)
		{
			if((strlen($this->Value)-1) > $length)
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_no_longer', $length);
			}
			$this->Value = substr($this->Value, 0, $length+1);
		}
		return array($this->validated, $this->validationErrorText);
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$ret = '';

		if(strlen($this->ValidationType)>0)
		{
			$ret = array_search($this->ValidationType,$this->ValidationTypes);
		}

		if($this->GetOption('wysiwyg','0') == '1')
		{
			$ret .= ' wysiwyg';
		}
		else
		{
			$ret .= ' non-wysiwyg';
		}

		$ret .=  ', '.$mod->Lang('rows',$this->GetOption('rows','15'));
		$ret .=  ', '.$mod->Lang('cols',$this->GetOption('cols','80'));

		return $ret;
	}


	function PrePopulateAdminForm($formDescriptor)
	{
	   $mod = $this->formdata->pwfmodule;
	   $main = array(
			array($mod->Lang('title_use_wysiwyg'),
						$mod->CreateInputHidden($formDescriptor, 'pwfp_opt_wysiwyg','0').
						$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_wysiwyg','1',$this->GetOption('wysiwyg','0'))),
			array($mod->Lang('title_textarea_rows'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_rows',$this->GetOption('rows','15'),5,5)),
			array($mod->Lang('title_textarea_cols'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_cols',$this->GetOption('cols','80'),5,5)),
			array($mod->Lang('title_textarea_length'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_length',$this->GetOption('length',''), 5, 5))
           );

	   $adv = array(
			array($mod->Lang('title_field_default_value'),
				$mod->CreateTextArea(false, $formDescriptor, $this->GetOption('default'), 'pwfp_opt_default')),
			array($mod->Lang('title_html5'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_html5','0').
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_html5','1',$this->GetOption('html5','0'))),
			array($mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_clear_default','0').
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_clear_default','1',$this->GetOption('clear_default','0')),
				$mod->Lang('help_clear_default'))
		);

        return array('main'=>$main,'adv'=>$adv);
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->formdata->pwfmodule;
		// hide "javascript"
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_javascript'));
	}

}

?>