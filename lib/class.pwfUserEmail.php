<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class allows the form user to initiate an email, with customised sender
//and replyto, to a specified destination with optional copy to the form user

class pwfUserEmail extends pwfEmailBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->ModifiesOtherFields = TRUE;
		$this->Type = 'UserEmail';
		$mod = $formdata->formsmodule;
		$this->ValidationType = 'email';
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_email_address')=>'email'
		);
	}

	function GetCSSId($suffix='')
	{
		$cssid = $this->ForceAlias();
		if(!$suffix)
			$cssid .= '_1';
		else
			$cssid .= $suffix;
		return $cssid;
	}

	function HasValue($deny_blank_responses=FALSE)
	{
		return ($this->Value[0] !== FALSE && !empty($this->Value[0]));
	}

  	function GetValue()
  	{
    	return $this->Value[0];
  	}

	function SetValue($valStr)
	{
		if(!is_array($valStr))
			$this->Value = array($valStr);
		else
			$this->Value = $valStr;
 	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if(is_array($this->Value))
		{
			if($as_string)
				return implode($this->GetFormOption('list_delimiter',','),$this->Value);
			else
			{
				$ret = $this->Value;
				return $ret; //a copy
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

	function GetFieldStatus()
	{
		return $this->TemplateStatus();
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$ret = '<input type="text" name="'.$id.'pwfp_'.$this->Id.'[]" '.
			$this->GetCSSIdTag('_1').' value="'.htmlspecialchars($this->Value[0],ENT_QUOTES).
			'" size="25" maxlength="128" '.$js.'/>';
 		if($this->GetOption('send_user_copy','n') == 'c')
		{
			$ret .= $mod->CreateInputCheckbox($id,'pwfp_'.$this->Id.'[]',1,0,
				$this->GetCSSIdTag('_2'),'email').
				'<label for="'.$this->GetCSSId('_2').'" class="label">'.
				$this->GetOption('send_user_label',$mod->Lang('title_send_me_a_copy')).'</label>';
		}
		return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$ret = $this->PrePopulateAdminFormCommonEmail($module_id);
		$opts = array(
			$mod->Lang('option_never')=>'n',
			$mod->Lang('option_user_choice')=>'c',
			$mod->Lang('option_always')=>'a');
		$ret['main'][] = array($mod->Lang('title_send_user_copy'),
			$mod->CreateInputDropdown($module_id,'opt_send_user_copy',$opts,-1,
				$this->GetOption('send_user_copy','n')));
		$ret['main'][] = array($mod->Lang('title_send_user_label'),
			$mod->CreateInputText($module_id,'opt_send_user_label',
				$this->GetOption('send_user_label',$mod->Lang('title_send_me_a_copy')),25,125));
		$hopts = array(
			$mod->Lang('option_from')=>'f',
			$mod->Lang('option_reply')=>'r',
			$mod->Lang('option_both')=>'b');
		$ret['main'][] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($module_id,'opt_headers_to_modify',$hopts,-1,
				$this->GetOption('headers_to_modify','f')));
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$mod = $this->formdata->formsmodule;
		$this->RemoveAdminField($mainArray,$mod->Lang('title_email_from_address'));
	}

	function ModifyOtherFields()
	{
		if($this->Value !== FALSE)
		{
			$htm = $this->GetOption('headers_to_modify','f');
			foreach($this->formdata->Fields as &$one)
			{
				if($one->IsDisposition() && is_subclass_of($one,'pwfEmailBase'))
				{
					if($htm == 'f' || $htm == 'b')
						$one->SetOption('email_from_address',$this->Value[0]);

					if($htm == 'r' || $htm == 'b')
						$one->SetOption('email_reply_to_address',$this->Value[0]);
				}
			}
			unset($one);
		}
	}

	function Validate()
	{
  		$this->validated = TRUE;
  		$this->ValidationMessage = '';
		if($this->ValidationType != 'none')
		{
			$mod = $this->formdata->formsmodule;
			if($this->Value)
			{
				if(!preg_match($mod->email_regex,$this->Value[0]))
				{
					$this->validated = FALSE;
					$this->ValidationMessage = $mod->Lang('TODO bad email');
				}
			}
			else
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function DisposeForm($returnid)
	{
		if($this->HasValue() &&
		($this->GetOption('send_user_copy','n') == 'a' ||
		($this->GetOption('send_user_copy','n') == 'c' && isset($this->Value[1]) && $this->Value[1] == 1))
		)
		{
			return $this->SendForm($this->Value[0],$this->GetOption('email_subject'));
		}
		else
		{
			return array(TRUE,'');
		}
	}


}

?>
