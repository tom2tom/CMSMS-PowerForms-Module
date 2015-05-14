<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCheckboxField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type =  'CheckboxField';
		$this->DisplayInForm = true;
		$this->NonRequirableField = false;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_must_check')=>'checked');
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$label = '';
		if(strlen($this->GetOption('label','')) > 0)
		{
			$label = '&nbsp;<label for="'.$this->GetCSSId().'">'.$this->GetOption('label').'</label>';
		}
		if($this->Value === false && $this->GetOption('is_checked','0')=='1')
		{
			$this->Value = 't';
		}
		$js = $this->GetOption('javascript','');
		return $mod->CreateInputCheckbox($id, 'pwfp__'.$this->Id, 't',$this->Value,$js.$this->GetCSSIdTag()).$label;
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->Value === false)
		{
			$ret = $this->GetOption('unchecked_value',$mod->Lang('value_unchecked'));
		}
		else
		{
			$ret = $this->GetOption('checked_value',$mod->Lang('value_checked'));
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

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$ret =  ($this->GetOption('is_checked','0')=='1'?$mod->Lang('checked_by_default'):$mod->Lang('unchecked_by_default'));
		if(strlen($this->ValidationType)>0)
		{
		  	$ret .= ", ".array_search($this->ValidationType,$this->ValidationTypes);
		}
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
			array($mod->Lang('title_checkbox_label'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_label',
						$this->GetOption('label',''),25,255)),
			array($mod->Lang('title_checked_value'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_checked_value',
         		$this->GetOption('checked_value',$mod->Lang('value_checked')),25,255)),
			array($mod->Lang('title_unchecked_value'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_unchecked_value',
          	$this->GetOption('unchecked_value',$mod->Lang('value_unchecked')),25,255)),
			array($mod->Lang('title_default_set'),
					$mod->CreateInputHidden($formDescriptor,'pwfp_opt_is_checked','0').
					$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_is_checked', '1', $this->GetOption('is_checked','0')))
			);
		return array('main'=>$main);
	}

	function Validate()
	{
		$mod = $this->formdata->pwfmodule;
		$this->validated = true;
		$this->validationErrorText = '';

		switch ($this->ValidationType)
		{
		 case 'none':
			break;
		 case 'checked':
			if($this->Value === false)
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('you_must_check',$this->GetOption('label',''));
			}
			break;
		}
		return array($this->validated, $this->validationErrorText);
	}

}

?>