<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCheckbox extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type =  'Checkbox';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_must_check')=>'checked');
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = ($this->GetOption('is_checked',0)?$mod->Lang('checked_by_default'):$mod->Lang('unchecked_by_default'));
		if(strlen($this->ValidationType) > 0)
		  	$ret .= ",".array_search($this->ValidationType,$this->ValidationTypes);

		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		if($this->Value === FALSE)
			$ret = $this->GetOption('unchecked_value',$mod->Lang('value_unchecked'));
		else
			$ret = $this->GetOption('checked_value',$mod->Lang('value_checked'));

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
					$mod->CreateInputText($id,'opt_label',
						$this->GetOption('label'),25,255)),
			array($mod->Lang('title_checked_value'),
					$mod->CreateInputText($id,'opt_checked_value',
         		$this->GetOption('checked_value',$mod->Lang('value_checked')),25,255)),
			array($mod->Lang('title_unchecked_value'),
					$mod->CreateInputText($id,'opt_unchecked_value',
          	$this->GetOption('unchecked_value',$mod->Lang('value_unchecked')),25,255)),
			array($mod->Lang('title_default_set'),
					$mod->CreateInputHidden($id,'opt_is_checked',0).
					$mod->CreateInputCheckbox($id,'opt_is_checked',1,
						$this->GetOption('is_checked',0)))
			);
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		if($this->Value === FALSE && $this->GetOption('is_checked',0))
			$this->Value = 't';

		$tid = $this->GetInputId();
		$tmp = $this->formdata->formsmodule->CreateInputCheckbox(
			$id,$this->formdata->current_prefix.$this->Id,'t',$this->Value,
			'id="'.$tid.'"'.$this->GetScript());
		$label = $this->GetOption('label');
		if($label)
			$label = '&nbsp;<label for="'.$tid.'">'.$label.'</label>';
		return $tmp.$label;
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';

		switch ($this->ValidationType)
		{
		 case 'checked':
			if($this->Value === FALSE)
			{
				$this->validated = FALSE;
				$mod = $this->formdata->formsmodule;
				$label = $this->GetOption('label',$mod->Lang('thisbox')); //TODO translation
				$this->ValidationMessage = $mod->Lang('you_must_check',$label);
			}
			break;
		 default:
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

}

?>
