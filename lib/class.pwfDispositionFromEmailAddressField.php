<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//require_once(cms_join_path(dirname(__FILE__),'class.pwfDispositionEmailBase.php'));

class pwfDispositionFromEmailAddressField extends pwfDispositionEmailBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$mod = $formdata->pwfmodule;
		$this->Type = 'DispositionFromEmailAddressField';
		$this->IsDisposition = true;
		$this->DisplayInForm = true;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_email_address')=>'email'
			);
		$this->modifiesOtherFields = true;
		$this->NonRequirableField = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$retstr = '<input type="text" name="'.$id.'pwfp__'.$this->Id.'[]" '.
			$this->GetCSSIdTag('_1').' value="'.htmlspecialchars($this->Value[0], ENT_QUOTES).
			'" size="25" maxlength="128" '.$js.'/>';
 		if($this->GetOption('send_user_copy','n') == 'c')
		{
			$retstr .= $mod->CreateInputCheckbox($id, 'pwfp__'.$this->Id.'[]', 1,
					0,$this->GetCSSIdTag('_2'),'email');
			$retstr .= '<label for="'.$this->GetCSSId('_2').'" class="label">'.$this->GetOption('send_user_label',
				$mod->Lang('title_send_me_a_copy')).'</label>';
		}
		return $retstr;
	}

	function GetCSSId($suffix='')
	{
		$alias = $this->GetAlias();
		if(empty($alias))
		{
			$cssid = 'pwfp__'.$this->Id;
		}
		else
		{
			$cssid = $alias;
		}

		if(empty($suffix))
		{
			$cssid .= '_1';
		}
		else
		{
			$cssid .= $suffix;
		}
		return $cssid;
	}

	function HasValue($deny_blank_responses=false)
	{
		return ($this->Value[0] !== false && !empty($this->Value[0]));
	}

  	function GetValue()
  	{
    	return $this->Value[0];
  	}

	function SetValue($valStr)
	{
		if(!is_array($valStr))
		{
			$this->Value = array($valStr);
		}
		else
		{
			$this->Value = $valStr;
		}
 	}

	function GetHumanReadableValue($as_string=true)
	{
		if(is_array($this->Value))
		{
			return $this->Value[0];
		}
		else
		{
			return $this->Value;
		}
	}

	function DisposeForm($returnid)
	{
		if($this->HasValue() != false &&
		($this->GetOption('send_user_copy','n') == 'a' ||
		($this->GetOption('send_user_copy','n') == 'c' && isset($this->Value[1]) && $this->Value[1] == 1))
		)
		{
			return $this->SendForm($this->Value[0],$this->GetOption('email_subject'));
		}
		else
		{
			return array(true,'');
		}
	}

	function StatusInfo()
	{
		return $this->TemplateStatus();
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
		$main = (isset($ret['main'])) ? $ret['main'] : array();
		$opts = array($mod->Lang('option_never')=>'n',$mod->Lang('option_user_choice')=>'c',$mod->Lang('option_always')=>'a');
		$main[] = array($mod->Lang('title_send_usercopy'),
			$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_send_user_copy', $opts, -1, $this->GetOption('send_user_copy','n')));
		$main[] = array($mod->Lang('title_send_usercopy_label'),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_send_user_label', $this->GetOption('send_user_label',
				$mod->Lang('title_send_me_a_copy')),25,125));
		$hopts = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');
		$main[] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_headers_to_modify', $hopts, -1, $this->GetOption('headers_to_modify','f')));
		$ret['main'] = $main;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->formdata->pwfmodule;
		$this->RemoveAdminField($mainArray, $mod->Lang('title_email_from_address'));
	}

	function ModifyOtherFields()
	{
		$mod = $this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		$htm = $this->GetOption('headers_to_modify','f');
		if($this->Value !== false)
		{
			for($i=0;$i<count($others);$i++)
			{
				$replVal = '';
				if($others[$i]->IsDisposition() &&
					is_subclass_of($others[$i],'pwfDispositionEmailBase'))
				{
					if($htm == 'f' || $htm == 'b')
					{
						$others[$i]->SetOption('email_from_address',$this->Value[0]);
					}
					if($htm == 'r' || $htm == 'b')
					{
						$others[$i]->SetOption('email_reply_to_address',$this->Value[0]);
					}
				}
			}
		}
	}

	function Validate()
	{
  		$this->validated = true;
  		$this->validationErrorText = '';
		$result = true;
		$message = '';
		$mod = $this->formdata->pwfmodule;
		if($this->ValidationType != 'none')
		{
			if($this->Value !== false &&
				!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $this->Value[0]))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_an_email',$this->Name);
			}
		}
		return array($this->validated, $this->validationErrorText);
	}

}

?>