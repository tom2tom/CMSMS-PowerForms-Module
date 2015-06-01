<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for a mandatory email-address input (single address, not ','-separated)

class pwfEmailAddress extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->ModifiesOtherFields = TRUE;
		$this->Type = 'EmailAddress';
		$this->ValidationType = 'email';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$html5 = $this->GetOption('html5','0') == '1' ? ' placeholder="'.$this->GetOption('default').'"' : '';
		$default = $html5 ? '' : htmlspecialchars($this->GetOption('default'),ENT_QUOTES);

		return $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id,
			($this->HasValue()?htmlspecialchars($this->Value,ENT_QUOTES):$default),
			25,128,$html5.$js.$this->GetCSSIdTag(),'email');
	}

	function GetFieldStatus()
	{
		return '';
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array();
		$hopts = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');
		$main[] = array($mod->Lang('title_headers_to_modify'),
			$mod->CreateInputDropdown($id,'opt_headers_to_modify',$hopts,-1,$this->GetOption('headers_to_modify','f')));
		$adv = array(
			array(
				$mod->Lang('title_field_default_value'),
				$mod->CreateInputText($id,'opt_default',$this->GetOption('default'),25,1024)),
			array(
				$mod->Lang('title_html5'),
				$mod->CreateInputHidden($id,'opt_html5',0).
				$mod->CreateInputCheckbox($id,'opt_html5',1,$this->GetOption('html5',0))),
			array(
				$mod->Lang('title_clear_default'),
				$mod->CreateInputHidden($id,'opt_clear_default',0).
				$mod->CreateInputCheckbox($id,'opt_clear_default',1,$this->GetOption('clear_default',0)),
				$mod->Lang('help_clear_default'))
		);

		return array('main'=>$main,'adv'=>$adv);
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
						$one->SetOption('email_from_address',$this->Value);
					if($htm == 'r' || $htm == 'b')
						$one->SetOption('email_reply_to_address',$this->Value);
				}
			}
			unset($one);
		}
	}

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			$mod = $this->formdata->formsmodule;
			//no ','-separator support
			if(!$this->Value || !preg_match($mod->email_regex,$this->Value))
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
