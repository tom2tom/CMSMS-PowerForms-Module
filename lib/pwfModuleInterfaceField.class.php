<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This class written by Jeremy Bass <jeremyBass@cableone.net> */

class pwfModuleInterfaceField extends pwfFieldBase
{
	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'ModuleInterfaceField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array();
		$this->HasLabel = 0;
		$this->NeedsDiv = 0;
		$this->sortable = false;
		$this->DisplayInSubmission = true;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('FBid',$id.'pwfp__'.$this->Id);
		// for selected... what to do here
		// for things like checked="checked" on the back page
		$smarty->assign('FBvalue',$this->Value);

		$v = $this->GetOption('value','');
		//process without cacheing (->fetch() fails)
		$mod = $this->form_ptr->module_ptr;
		return $mod->ProcessTemplateFromData($v);
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;
		$main = array(
				array($mod->Lang('help_module_interface'),
            		$mod->Lang('help_module_interface_long')),
				array($mod->Lang('title_add_tag'),
            		$mod->CreateInputText($formDescriptor, 'pwfp_opt_value',$this->GetOption('value',''),100,1024))
		);
		return array('main'=>$main);
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->form_ptr->module_ptr;
		$form = $this->form_ptr;

		if($this->HasValue())
		{
			$fieldRet = array();
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
				return array($this->Value);
			}

		}
		else
		{
			if($as_string)
			{
				return $mod->Lang('unspecified');
			}
			else
			{
				return array($mod->Lang('unspecified'));
			}
		}
	}

}

?>
