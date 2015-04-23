<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfStaticTextField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'StaticTextField';
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->HasLabel = 0;
		$this->ValidationTypes = array();
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		if($this->GetOption('smarty_eval','0') == '1')
		{
			$this->SetSmartyEval(true);
		}
		return $this->GetOption('text','');
	}

	function StatusInfo()
	{
		return $this->form_ptr->module_ptr->Lang('text_length',strlen($this->GetOption('text','')));
	}

	function GetHumanReadableValue($as_string=true)
	{
		$ret = '[static text field]';
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;
		$main = array(
				array($mod->Lang('title_text'),
				$mod->CreateTextArea((get_preference(get_userid(), 'use_wysiwyg')=='1'), $formDescriptor,  $this->GetOption('text',''), 'pwfp_opt_text','pageheadtags'))
		);
		$adv = array(
				array($mod->Lang('title_smarty_eval'),
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_smarty_eval',
            		'1',$this->GetOption('smarty_eval','0')))
		);
		return array('main'=>$main,'adv'=>$adv);
	}
}

?>
