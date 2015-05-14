<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfTextFieldExpandable extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'TextFieldExpandable';
		$this->DisplayInForm = true;
		$this->HasUserAddOp = true;
		$this->HasUserDeleteOp = true;
		$this->NonRequirableField = false;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array(
            $mod->Lang('validation_none')=>'none',
            $mod->Lang('validation_numeric')=>'numeric',
            $mod->Lang('validation_integer')=>'integer',
            $mod->Lang('validation_email_address')=>'email',
            $mod->Lang('validation_regex_match')=>'regex_match',
            $mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
           );
		$this->hasMultipleFormComponents = true;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$sibling_id = $this->GetOption('siblings','');
		$hidebuttons = $this->GetOption('hidebuttons','');

		if(!is_array($this->Value))
		{
			$vals = 1;
		}
		else
		{
			$vals = count($this->Value);
		}

		foreach($params as $pKey=>$pVal)
		{
			if(substr($pKey,0,9) == 'pwfp_FeX_')
			{
				$pts = explode('_',$pKey);
				if($pts[2] == $this->Id || $pts[2] == $sibling_id)
				{
					// add row
					$this->Value[$vals]='';
					$vals++;
				}
        	}
			else if(substr($pKey,0,9) == 'pwfp_FeD_')
			{
				$pts = explode('_',$pKey);
				if($pts[2] == $this->Id || $pts[2] == $sibling_id)
				{
					// delete row
					if(isset($this->Value[$pts[2]]))
					{
						array_splice($this->Value, $pts[2], 1);
					}
					$vals--;
				}
        	}
		}

		// Input fields
		$ret = array();
		for ($i=0;$i<$vals;$i++)
		{
			$thisRow = new stdClass();

			//$thisRow->name = '';
			//$thisRow->title = '';
			$thisRow->input = $mod->CustomCreateInputText($id, 'pwfp__'.$this->Id.'[]',$this->Value[$i],$this->GetOption('length')<25?$this->GetOption('length'):25,
							$this->GetOption('length'),$js.$this->GetCSSIdTag('_'.$i));

			if(!$hidebuttons) $thisRow->op = $mod->CustomCreateInputSubmit($id, 'pwfp_FeD_'.$this->Id.'_'.$i, $this->GetOption('del_button','X'), $this->GetCSSIdTag('_del_'.$i).($vals==1?' disabled="disabled"':''));

			$ret[] = $thisRow;
		}

		// Add button
		$thisRow = new stdClass();
		//$thisRow->name = '';
		//$thisRow->title = '';
		//$thisRow->input = '';
		if(!$hidebuttons) $thisRow->op = $mod->CustomCreateInputSubmit($id, 'pwfp_FeX_'.$this->Id.'_'.$i, $this->GetOption('add_button','+'), $this->GetCSSIdTag('_add_'.$i));

		$ret[] = $thisRow;

		return $ret;
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length','80'));
		if(strlen($this->ValidationType)>0)
		{
			$ret .= ", ".array_search($this->ValidationType,$this->ValidationTypes);
		}

		return $ret;
	}

	function GetHumanReadableValue($as_string = true)
	{
		$form = $this->formdata;
		if(!is_array($this->Value))
		{
			$this->Value = array($this->Value);
		}

		if($as_string)
		{
			return join($form->GetAttr('list_delimiter',','),$this->Value);
		}
		else
		{
			return array($ret);
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$main = array(
			array($mod->Lang('title_maximum_length'),$mod->CreateInputText($formDescriptor,'pwfp_opt_length',$this->GetOption('length','80'),25,25)),
			array($mod->Lang('title_add_button_text'),$mod->CreateInputText($formDescriptor,'pwfp_opt_add_button',$this->GetOption('add_button','+'),15,25)),
			array($mod->Lang('title_del_button_text'),$mod->CreateInputText($formDescriptor,'pwfp_opt_del_button',$this->GetOption('del_button','X'),15,25))
		);

		$adv = array(
			array($mod->Lang('title_field_regex'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_regex',$this->GetOption('regex'),25,255),$mod->Lang('title_regex_help')),
			array($mod->Lang('title_field_siblings'),$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_siblings',$this->GetFieldSiblings(),-1,$this->GetOption('siblings','')),$mod->Lang('title_field_siblings_help')),
			array($mod->Lang('title_field_hidebuttons'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_hidebuttons',0).
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_hidebuttons',1,$this->GetOption('hidebuttons',0)),
				$mod->Lang('title_field_hidebuttons_help'))
		);

		return array('main'=>$main,'adv'=>$adv);
	}

	function LabelSubComponents()
	{
		return false;
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		$mod = $this->formdata->pwfmodule;
		if(!is_array($this->Value))
		{
		    $this->Value = array($this->Value);
		}
		foreach($this->Value as $thisVal)
		{
		    switch ($this->ValidationType)
			{
			 case 'none':
				break;
			 case 'numeric':
				if($thisVal !== false &&
					!preg_match("/^([\d\.\,])+$/i", $thisVal))
				{
					$this->validated = false;
					$this->validationErrorText = $mod->Lang('please_enter_a_number',$this->Name);
				}
				break;
			 case 'integer':
				if($thisVal !== false &&
					!preg_match("/^([\d])+$/i", $thisVal) || intval($thisVal) != $thisVal)
				{
					$this->validated = false;
					$this->validationErrorText = $mod->Lang('please_enter_an_integer',$this->Name);
				}
				break;
			 case 'email':
				if($thisVal !== false &&
					!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $thisVal))
				{
					$this->validated = false;
					$this->validationErrorText = $mod->Lang('please_enter_an_email',$this->Name);
				}
				break;
			 case 'regex_match':
				if($thisVal !== false &&
					!preg_match($this->GetOption('regex','/.*/'), $thisVal))
				{
					$this->validated = false;
					$this->validationErrorText = $mod->Lang('please_enter_valid',$this->Name);
				}
				break;
			 case 'regex_nomatch':
				if($thisVal !== false &&
				   preg_match($this->GetOption('regex','/.*/'), $thisVal))
				{
					$this->validated = false;
					$this->validationErrorText = $mod->Lang('please_enter_valid',$this->Name);
				}
				break;
			}

			if($this->GetOption('length',0) > 0 && strlen($thisVal) > $this->GetOption('length',0))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_no_longer',$this->GetOption('length',0));
			}
		}

		return array($this->validated, $this->validationErrorText);
	}

	// Gets all mirror fields of this field
	function GetFieldSiblings()
	{
		$siblings = array();

		$siblings[$this->formdata->pwfmodule->Lang('select_one')] = '';

		foreach($this->formdata->Fields as &$thisField)
		{
			if($thisField->GetFieldType() == 'TextFieldExpandable')
			{
				$fid = $thisField->GetId();
 				if($fid != $this->GetId())
					$siblings[$thisField->GetName()] = $fid;
			}
		}
		unset ($thisField);
		return $siblings;
	}
}

?>