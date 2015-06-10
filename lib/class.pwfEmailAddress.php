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
		$this->Type = 'EmailAddress';
		$this->ValidationType = 'email';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
	}

	function AdminPopulate($id)
	{
		$choices = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_headers_to_modify'),
						$mod->CreateInputDropdown($id,'opt_headers_to_modify',$choices,-1,
							$this->GetOption('headers_to_modify','f')));
		$adv[] = array($mod->Lang('title_field_default_value'),
						$mod->CreateInputText($id,'opt_default',
							$this->GetOption('default'),25,1024));
		$adv[] = array($mod->Lang('title_clear_default'),
						$mod->CreateInputHidden($id,'opt_clear_default',0).
						$mod->CreateInputCheckbox($id,'opt_clear_default',1,
							$this->GetOption('clear_default',0)),
						$mod->Lang('help_clear_default'));
		$adv[] = array($mod->Lang('title_html5'),
						$mod->CreateInputHidden($id,'opt_html5',0).
						$mod->CreateInputCheckbox($id,'opt_html5',1,
							$this->GetOption('html5',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		if($this->GetOption('html5',0))
		{
			$addr = ($this->HasValue()) ? $this->Value : '';
			$place = 'placeholder="'.$this->GetOption('default').'"';
		}
		else
		{
			$addr = ($this->HasValue()) ? $this->Value : $this->GetOption('default');
			$place = '';
		}
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($addr,ENT_QUOTES),25,128,
			$place.$this->GetScript());
		return preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
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

	function PreDisposeAction()
	{
		if(property_exists($this,'Value'))
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

}

?>
