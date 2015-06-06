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
		$this->Type = 'UserEmail';
		$this->ValidationType = 'email';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_email_address')=>'email'
		);
	}

	function HasValue($deny_blank_responses=FALSE)
	{
		return !empty($this->Value[0]);
	}

  	function GetValue()
  	{
    	return $this->Value[0];
  	}

	//c.f. parent::SetValue() which calls html_myentities_decode()
	function SetValue($newvalue)
	{
		if(is_array($newvalue))
			$this->Value = $newvalue;
		else
			$this->Value = array($newvalue);
 	}

	function GetFieldStatus()
	{
		return $this->TemplateStatus();
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

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$ret = $this->PrePopulateAdminFormCommonEmail($id);
		$choices = array(
			$mod->Lang('option_never')=>'n',
			$mod->Lang('option_user_choice')=>'c',
			$mod->Lang('option_always')=>'a');
		$ret['main'][] = array($mod->Lang('title_send_user_copy'),
			$mod->CreateInputDropdown($id,'opt_send_user_copy',$choices,-1,
				$this->GetOption('send_user_copy','n')));
		$ret['main'][] = array($mod->Lang('title_send_user_label'),
			$mod->CreateInputText($id,'opt_send_user_label',
				$this->GetOption('send_user_label',$mod->Lang('title_send_me_a_copy')),25,125));
		$choices = array(
			$mod->Lang('option_from')=>'f',
			$mod->Lang('option_reply')=>'r',
			$mod->Lang('option_both')=>'b');
		$ret['main'][] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($id,'opt_headers_to_modify',$choices,-1,
				$this->GetOption('headers_to_modify','f')));
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->RemoveAdminField($mainArray,$this->formdata->formsmodule->Lang('title_email_from_address'));
	}

	function Populate($id,&$params)
	{
		$toself = ($this->GetOption('send_user_copy','n') == 'c');
//		$multi = ($toself) ? '[]':'';
		$sf = ($toself) ? '_1':'';
//TODO check this logic
//		$val = ($toself) ? $this->$this->Value[0] : $this->Value;
		$mod = $this->formdata->formsmodule;

		//returned value always array, even if 1 member(i.e. not $toself)
		$tmp = $mod->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id.'[]',
			htmlspecialchars($this->Value[0],ENT_QUOTES),25,128,
			$this->GetScript());
		$ret = preg_replace('/id="\S+"/','id="'.$this->GetInputId($sf).'"',$tmp);

 		if($toself)
		{
			$tid = $this->GetInputId('_2');
			$tmp = $mod->CreateInputCheckbox(
				$id,$this->formdata->current_prefix.$this->Id.'[]',1,0,'id="'.$tid.'"');
			$ret .= '<br />'.$tmp.'&nbsp;<label for="'.$tid.'">'.
			$this->GetOption('send_user_label',$mod->Lang('title_send_me_a_copy')).'</label>';
		}
		return $ret;
	}

	function Validate($id)
	{
  		$this->validated = TRUE;
  		$this->ValidationMessage = '';
		if($this->ValidationType != 'none')
		{
			if($this->Value)
			{
				list($rv,$msg) = $this->validateEmailAddr($this->Value);
				if(!$rv)
				{
					$this->validated = FALSE;
					$this->ValidationMessage = $msg;
				}
			}
			else
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $this->formdata->formsmodule->Lang('please_enter_an_email',$this->Name);
			}
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function PreDispositionAction()
	{
		if(property_exists($this,$Value))
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

	function Dispose($id,$returnid)
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
