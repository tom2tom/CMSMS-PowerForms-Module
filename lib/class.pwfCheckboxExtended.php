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
		$this->ChangeRequirement = FALSE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
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

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_checkbox_label'),
						$mod->CreateInputText($id,'opt_box_label',
							$this->GetOption('box_label'),25,255));
		$main[] = array($mod->Lang('title_checked_value'),
						$mod->CreateInputText($id,'opt_checked_value',
							$this->GetOption('checked_value',$mod->Lang('yes')),25,255));
		$main[] = array($mod->Lang('title_unchecked_value'),
						$mod->CreateInputText($id,'opt_unchecked_value',
							$this->GetOption('unchecked_value',$mod->Lang('no')),25,255));
		$main[] = array($mod->Lang('title_default_set'),
						$mod->CreateInputHidden($id,'opt_is_checked',0).
						$mod->CreateInputCheckbox($id,'opt_is_checked',1,
							$this->GetOption('is_checked',0)));
		$main[] = array($mod->Lang('title_textfield_label'),
						$mod->CreateInputText($id,'opt_text_label',
							$this->GetOption('text_label'),25,255));
		$main[] = array($mod->Lang('title_show_textfield'),
						$mod->CreateInputHidden($id,'opt_show_textfield',0).
						$mod->CreateInputCheckbox($id,'opt_show_textfield',1,
							$this->GetOption('show_textfield',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$show = $this->GetOption('show_textfield');
		$sf = ($show)?'_0':'';
		$tid = $this->GetInputId($sf);

		$mod = $this->formdata->formsmodule;
		$js = $this->GetScript();
		$ret = array();

		$oneset = new stdClass();
		$oneset->title = '';
		$label = $this->GetOption('box_label');
		if($label)
		{
			$tmp = '<label for="'.$tid.'">'.$label.'</label>';
			$label = $this->SetClass($tmp);
		}
		$oneset->name = $label;

		if($this->Value)
		{
			$hasvalue = !empty($this->Value['box']);
			if(!$hasvalue && $this->GetOption('is_checked',0))
			{
				$this->Value['box'] = 't';
				$hasvalue = TRUE;
			}
		}
		else
			$hasvalue = FALSE;
		$tmp = $mod->CreateInputCheckbox(
			$id,$this->formdata->current_prefix.$this->Id.'[box]','t',
			($hasvalue?$this->Value['box']:0),
			'id="'.$tid.'"'.$js);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $oneset;

		if($show)
		{
			$tid = $this->GetInputId('_1');
			if($this->GetOption('text_label'))
			{
				$tmp = '<label for="'.$tid.'">'.$this->GetOption('text_label').'</label>';
				$label = $this->SetClass($tmp);
			}
			else
				$label = '';

			if($this->Value)
				$hasvalue = !empty($this->Value['text']);
			else
				$hasvalue = FALSE;

			$oneset = new stdClass();
			$oneset->title = '';
			$oneset->name = $label;
			$tmp = $mod->CreateInputText(
				$id,$this->formdata->current_prefix.$this->Id.'[text]',
				($hasvalue?$this->Value['text']:''),25,25,
				$js);
			$tmp = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$oneset->input = $this->SetClass($tmp);
			
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
