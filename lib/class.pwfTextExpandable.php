<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfTextExpandable extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasUserAddOp = TRUE;
		$this->HasUserDeleteOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'TextExpandable';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_numeric')=>'numeric',
			$mod->Lang('validation_integer')=>'integer',
			$mod->Lang('validation_email_address')=>'email',
			$mod->Lang('validation_regex_match')=>'regex_match',
			$mod->Lang('validation_regex_nomatch')=>'regex_nomatch'
		);
	}

	// Gets all other 'TextExpandable' fields in the form
	function GetFieldSiblings()
	{
		$siblings = array();
		$siblings[$this->formdata->formsmodule->Lang('select_one')] = '';
		$tid = $this->Id;
		foreach($this->formdata->Fields as &$one)
		{
			if($one->GetFieldType() == 'TextExpandable')
			{
				$fid = $one->Id;
 				if($fid != $tid)
					$siblings[$one->GetName()] = $fid;
			}
		}
		unset($one);
		return $siblings;
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('abbreviation_length',$this->GetOption('length',80));
		if(strlen($this->ValidationType)>0)
			$ret .= ",".array_search($this->ValidationType,$this->ValidationTypes);

		return $ret;
	}

	function GetHumanReadableValue($as_string = TRUE)
	{
		if(is_array($this->Value))
		{
			if($as_string)
				return join($this->GetFormOption('list_delimiter',','),$this->Value);
			else
			{
				$ret = $this->Value;
				return $ret;
			}
		}
		elseif($this->Value)
			$ret = $this->Value;
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_maximum_length'),
						$mod->CreateInputText($id,'opt_length',$this->GetOption('length',80),3,3));
		$main[] = array($mod->Lang('title_add_button_text'),
						$mod->CreateInputText($id,'opt_add_button',$this->GetOption('add_button','+'),15,25));
		$main[] = array($mod->Lang('title_del_button_text'),
						$mod->CreateInputText($id,'opt_del_button',$this->GetOption('del_button','X'),15,25));
		$adv[] = array($mod->Lang('title_field_regex'),
						$mod->CreateInputText($id,'opt_regex',$this->GetOption('regex'),25,255),
						$mod->Lang('help_regex_use'));
		$adv[] = array($mod->Lang('title_field_siblings'),
						$mod->CreateInputDropdown($id,'opt_siblings',$this->GetFieldSiblings(),-1,
							$this->GetOption('siblings')),
						$mod->Lang('help_field_siblings'));
		$adv[] = array($mod->Lang('title_field_hidebuttons'),
						$mod->CreateInputHidden($id,'opt_hidebuttons',0).
						$mod->CreateInputCheckbox($id,'opt_hidebuttons',1,$this->GetOption('hidebuttons',0)),
						$mod->Lang('help_field_hidebuttons'));
		return array('main'=>$main,'adv'=>$adv);
	}

	function LabelSubComponents()
	{
		return FALSE;
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$sibling_id = $this->GetOption('siblings');
		$hidebuttons = $this->GetOption('hidebuttons');

		if(!is_array($this->Value))
			$vals = 1;
		else
			$vals = count($this->Value);

		$matched = preg_grep('/^pwfp_\d{3}_Fe[DX]_/',array_keys($params));
		if($matched)
		{
			foreach($matched as $key)
			{
				$pts = explode('_',$key);
				if($pts[3] == $this->Id || $pts[3] == $sibling_id)
				{
					if($key[11] == 'X') //add row
					{
						$this->Value[$vals] = '';
						$vals++;
					}
					else // $key[11] == 'D' delete row
					{
						if(isset($this->Value[$pts[3]]))
							array_splice($this->Value,$pts[3],1);
						$vals--;
					}
				}
			}
		}

		// Input fields
		$ret = array();
		for($i=0; $i<$vals; $i++)
		{
			$oneset = new stdClass();

			$oneset->name = '';
			$oneset->title = '';
			$tmp = $mod->CreateInputText(
				$id,$this->formdata->current_prefix.$this->Id.'[]',
				$this->Value[$i],$this->GetOption('length')<25?$this->GetOption('length'):25,
				$this->GetOption('length'),
				$this->GetScript());
			$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId('_'.$i).'"',$tmp);
			$oneset->input = $this->SetClass($tmp);
			if(!$hidebuttons)
			{
				$tmp = $mod->CreateInputSubmit($id,
					$this->formdata->current_prefix.'FeD_'.$this->Id.'_'.$i,
					$this->GetOption('del_button','X'),($vals==1?' disabled="disabled"':''));
				$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId('_del_'.$i).'"',$tmp);
				$oneset->op = $this->SetClass($tmp);
			}

			$ret[] = $oneset;
		}

		if(!$hidebuttons)
		{
			// Add button
			$oneset = new stdClass();
			$oneset->name = '';
			$oneset->title = '';
			$oneset->input = '';
			$tmp = $mod->CreateInputSubmit($id,
				$this->formdata->current_prefix.'FeX_'.$this->Id.'_'.$i,
				$this->GetOption('add_button','+'));
			$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId('_add_'.$i).'"',$tmp);
			$oneset->op = $this->SetClass($tmp);

			$ret[] = $oneset;
		}

		return $ret;
	}

	function Validate($id)
	{
		$mod = $this->formdata->formsmodule;
		$res = TRUE;
		$messages = array();
		$l = $this->GetOption('length',0);

		if(!is_array($this->Value))
			$this->Value = array($this->Value);
		foreach($this->Value as $one)
		{
			switch ($this->ValidationType)
			{
			 case 'none':
				break;
			 case 'numeric':
				if($one && !preg_match('/^[\d\.\,]+$/',$one))
				{
					$res = FALSE;
					$messages[] = $mod->Lang('please_enter_a_number',$this->Name);
				}
				break;
			 case 'integer':
				if($one && !preg_match('/^\d+$/',$one) || (int)$one != $one)
				{
					$res = FALSE;
					$messages[] = $mod->Lang('please_enter_an_integer',$this->Name);
				}
				break;
			 case 'email':
				if($one && !preg_match($mod->email_regex,$one))
				{
					$res = FALSE;
					$messages[] = $mod->Lang('please_enter_an_email',$this->Name);
				}
				break;
			 case 'regex_match':
				if($one && !preg_match($this->GetOption('regex','/.*/'),$one))
				{
					$res = FALSE;
					$messages[] = $mod->Lang('please_enter_valid',$this->Name);
				}
				break;
			 case 'regex_nomatch':
				if($one && preg_match($this->GetOption('regex','/.*/'),$one))
				{
					$res = FALSE;
					$messages[] = $mod->Lang('please_enter_valid',$this->Name);
				}
				break;
			}

			if($l > 0 && strlen($one) > $l)
			{
				$res = FALSE;
				$messages[] = $mod->Lang('please_enter_no_longer',$l);
			}
		}
		if($res)
		{
			$this->validated = TRUE;
			$this->ValidationMessage = '';
		}
		else
		{
			$this->validated = FALSE;
			$this->ValidationMessage = implode('<br />',$messages);
		}

		return array($this->validated,$this->ValidationMessage);
	}

}

?>
