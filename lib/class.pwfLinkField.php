<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfLinkField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'LinkField';
		$this->DisplayInForm = true;
		$this->NonRequirableField = true;
		$this->Required = false;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->hasMultipleFormComponents = true;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');

		if($this->Value !== false && is_array($this->Value))
		{
			$val = $this->Value;
		}
		else
		{
			$val = array($this->GetOption('default_link',''),$this->GetOption('default_link_title',''));
		}
		$fieldDisp = array();
		$thisBox = new stdClass();
		$thisBox->name = '<label for="'.$id.'pwfp__'.$this->Id.'_1">'.$mod->Lang('link_destination').'</label>';
		$thisBox->title = $mod->Lang('link_destination');
		$thisBox->input = $this->TextField($id, 'pwfp__'.$this->Id.'[]', $val[0],'','',$js.$this->GetCSSIdTag('_1'));
		$fieldDisp[] = $thisBox;
		$thisBox = new stdClass();
		$thisBox->name = '<label for="'.$id.'pwfp__'.$this->Id.'_2">'.$mod->Lang('link_label').'</label>';
		$thisBox->title = $mod->Lang('link_label');
		$thisBox->input = $this->TextField($id, 'pwfp__'.$this->Id.'[]', $val[1],'','',$js.$this->GetCSSIdTag('_2'));
		$fieldDisp[] = $thisBox;
		return $fieldDisp;
	}

	function GetHumanReadableValue($as_string=true)
	{
		if($this->Value === false || ! is_array($this->Value))
		{
			$ret = '';
		}
		else
		{
			$ret = '<a href="'.$this->Value[0].'">'.$this->Value[1].'</a>';
		}
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->formdata->pwfmodule;
		// remove the "required" field, since this can only be done via validation
		$this->RemoveAdminField($mainArray, $mod->Lang('title_field_required'));
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
			array($mod->Lang('title_default_link'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_default_link',$this->GetOption('default_link',''),25,128)),
			array($mod->Lang('title_default_link_title'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_default_link_title',$this->GetOption('default_link_title',''),25,128))
		);
		return array('main'=>$main);
	}
}

?>
