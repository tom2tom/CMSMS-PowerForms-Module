<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
//TODO duplication with non-disposition field
class pwfFromEmailAddressField extends pwfEmailFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Type = 'FromEmailAddressFieldField';
		$this->ModifiesOtherFields = TRUE;
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(
			$mod->Lang('validation_none')=>'none',
			$mod->Lang('validation_email_address')=>'email'
		);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$retstr = '<input type="text" name="'.$id.'pwfp_'.$this->Id.'[]" '.
			$this->GetCSSIdTag('_1').' value="'.htmlspecialchars($this->Value[0],ENT_QUOTES).
			'" size="25" maxlength="128" '.$js.'/>';
 		if($this->GetOption('send_user_copy','n') == 'c')
		{
			$retstr .= $mod->CreateInputCheckbox($id,'pwfp_'.$this->Id.'[]',1,
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
			$cssid = 'pwfp_'.$this->Id;
		else
			$cssid = $alias;

		if(empty($suffix))
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

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$ret = $this->PrePopulateAdminFormBase($module_id);
		$main = (isset($ret['main'])) ? $ret['main'] : array();
//TODO check this crap
		$opts = array($mod->Lang('option_never')=>'n',$mod->Lang('option_user_choice')=>'c',$mod->Lang('option_always')=>'a');
	
		$main[] = array($mod->Lang('title_send_usercopy'),
			$mod->CreateInputDropdown($module_id,'opt_send_user_copy',$opts,-1,$this->GetOption('send_user_copy','n')));
		$main[] = array($mod->Lang('title_send_usercopy_label'),
			$mod->CreateInputText($module_id,'opt_send_user_label',
				$this->GetOption('send_user_label',$mod->Lang('title_send_me_a_copy')),25,125));
		$hopts = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');
		$main[] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($module_id,'opt_headers_to_modify',$hopts,-1,$this->GetOption('headers_to_modify','f')));
		$ret['main'] = $main;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$mod = $this->formdata->formsmodule;
		$this->RemoveAdminField($mainArray,$mod->Lang('title_email_from_address'));
	}

	function ModifyOtherFields()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
		$htm = $this->GetOption('headers_to_modify','f');
		if($this->Value !== FALSE)
		{
			for($i=0; $i<count($others); $i++)
			{
				$replVal = '';
				if($others[$i]->IsDisposition() &&
					is_subclass_of($others[$i],'pwfEmailFieldBase'))
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
  		$this->validated = TRUE;
  		$this->ValidationMessage = '';
		$result = TRUE;
		$message = '';
		$mod = $this->formdata->formsmodule;
		if($this->ValidationType != 'none')
		{
			if($this->Value !== FALSE &&
				!preg_match($mod->email_regex,$this->Value[0]))
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
