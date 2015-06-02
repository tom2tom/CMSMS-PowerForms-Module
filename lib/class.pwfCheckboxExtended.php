<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCheckboxExtended extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasMultipleFormComponents = TRUE;
		$this->IsInput = TRUE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'CheckboxExtended';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_must_check')=>'checked',
            $mod->Lang('validation_empty')=>'empty');
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = ($this->GetOption('is_checked',0)?$mod->Lang('checked_by_default'):$mod->Lang('unchecked_by_default'));
		if($this->ValidationType)
			$ret .= ",".array_search($this->ValidationType,$this->ValidationTypes);
		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		$val = $this->Value;

		if($val['box'])
			$ret = $this->GetOption('checked_value',$mod->Lang('value_checked'));
		else
			$ret = $this->GetOption('unchecked_value',$mod->Lang('value_unchecked'));

		if(!empty($val['text']))
			$ret .= $this->GetFormOption('list_delimiter',',').$val['text'];

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_checkbox_label'),
           		$mod->CreateInputText($id,'opt_box_label',
	           		$this->GetOption('box_label'),25,255)),
            array($mod->Lang('title_checked_value'),
           		$mod->CreateInputText($id,'opt_checked_value',
	           		$this->GetOption('checked_value',$mod->Lang('yes')),25,255)),
            array($mod->Lang('title_unchecked_value'),
            		$mod->CreateInputText($id,'opt_unchecked_value',
	            		$this->GetOption('unchecked_value',$mod->Lang('no')),25,255)),
			array($mod->Lang('title_default_set'),
				$mod->CreateInputHidden($id,'opt_is_checked',0).
				$mod->CreateInputCheckbox($id,'opt_is_checked',1,
					$this->GetOption('is_checked',0))),
			array($mod->Lang('title_textfield_label'),
           		$mod->CreateInputText($id,'opt_text_label',
            		$this->GetOption('text_label'),25,255)),
			array($mod->Lang('title_show_textfield'),
				$mod->CreateInputHidden($id,'opt_show_textfield',0).
				$mod->CreateInputCheckbox($id,'opt_show_textfield',1,
					$this->GetOption('show_textfield',0)))
		);

		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		if($this->GetOption('box_label'))
			$box_label = '<label for="'.$this->GetCSSId('_0').'">'.$this->GetOption('box_label').'</label>';
		else
			$box_label = '';

		if($this->Value)
		{
			$box_value = !empty($this->Value['box']);
			if(!$box_value && $this->GetOption('is_checked',0))
				$this->Value['box'] = 't';
		}
		else
			$box_value = FALSE;

		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$ret = array();

		$oneset = new stdClass();
		$oneset->title = '';
		$oneset->name = $box_label;
		$oneset->input = $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.$this->Id.'[box]','t',$this->Value['box'],$js.$this->GetCSSIdTag('_0'));
		$ret[] = $oneset;

		if($this->GetOption('show_textfield'))
		{
			if($this->GetOption('text_label'))
				$text_label = '<label for="'.$this->GetCSSId('_1').'">'.$this->GetOption('text_label').'</label>';
			else
				$text_label = '';

			if($this->Value)
				$text_value = !empty($this->Value['text']);
			else
				$text_value = FALSE;

			$oneset = new stdClass();
			$oneset->title = '';
			$oneset->name = $text_label;
			$oneset->input = $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id.'[text]',($text_value?$this->Value['text']:''),25,25,$js.$this->GetCSSIdTag('_1'));
			$ret[] = $oneset;
		}

		return $ret;
	}

	function Validate($id)
	{
		$mod = $this->formdata->formsmodule;
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		switch ($this->ValidationType)
		{
		 case 'none':
			break;
		 case 'checked':
			if($this->Value['box'] == FALSE)
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('you_must_check',$this->GetOption('box_label'));
			}
			break;
			case 'empty':
			if(empty($this->Value['text']))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_a_value',$this->GetOption('text_label'));
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

}

?>
