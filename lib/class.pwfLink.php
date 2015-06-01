<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfLink extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasMultipleFormComponents = TRUE;
		$this->NonRequirableField = TRUE;
		$this->Required = FALSE;
		$this->Type = 'Link';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_none')=>'none');
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->Value === FALSE || ! is_array($this->Value))
			$ret = '';
		else
			$ret = '<a href="'.$this->Value[0].'">'.$this->Value[1].'</a>';

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_default_link'),$mod->CreateInputText($id,'opt_default_link',$this->GetOption('default_link'),25,128)),
			array($mod->Lang('title_default_link_title'),$mod->CreateInputText($id,'opt_default_link_title',$this->GetOption('default_link_title'),25,128))
		);
		return array('main'=>$main);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$mod = $this->formdata->formsmodule;
		// remove the "required" field,since this can only be done via validation
		$this->RemoveAdminField($mainArray,$mod->Lang('title_field_required'));
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		if($this->Value !== FALSE && is_array($this->Value))
			$val = $this->Value;
		else
			$val = array($this->GetOption('default_link'),$this->GetOption('default_link_title'));

		$fieldDisp = array();
		$thisBox = new stdClass();
		$thisBox->name = '<label for="'.$id.$this->formdata->current_prefix.$this->Id.'_1">'.$mod->Lang('link_destination').'</label>';
		$thisBox->title = $mod->Lang('link_destination');
//TODO was PowerForms::CreateCustomInputText(), but PowerForms::CustomCreateInputType() is sufficient?
//does $val[0] need html_entity_decode()?
		$thisBox->input = $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id.'[]',html_entity_decode($val[0]),'','',$js.$this->GetCSSIdTag('_1'));
		$fieldDisp[] = $thisBox;
		$thisBox = new stdClass();
		$thisBox->name = '<label for="'.$id.$this->formdata->current_prefix.$this->Id.'_2">'.$mod->Lang('link_label').'</label>';
		$thisBox->title = $mod->Lang('link_label');
//TODO ibid does $val[1] ever need html_entity_decode()?
		$thisBox->input = $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id.'[]',$val[1],'','',$js.$this->GetCSSIdTag('_2'));
		$fieldDisp[] = $thisBox;
		return $fieldDisp;
	}

}

?>
