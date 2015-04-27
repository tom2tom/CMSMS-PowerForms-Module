<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfHiddenField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'HiddenField';
		$this->DisplayInForm = true;
		$this->NonRequirableField = true;
		$this->ValidationTypes = array();
		$this->HasLabel = false;
		$this->NeedsDiv = false;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;

		if($this->Value !== false)
		{
			$v = $this->Value;
		}
		else
		{
			$v = $this->GetOption('value','');
		}

		if($this->GetOption('smarty_eval','0') == '1')
		{
			//process without cacheing
			$v = $mod->ProcessTemplateFromData($v);
		}

		if($this->GetOption('fbr_edit','0') == '1' && $params['in_admin'] == 1)
		{
			$type = "text";
		}
		else
		{
			$type = "hidden";
		}

		return '<input type="'.$type.'" name="'.$id.'pwfp__'.$this->Id.'" value="'.$v.'"'.$this->GetCSSIdTag().' />';
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
				array($mod->Lang('title_value'),
            		$mod->CreateInputText($formDescriptor, 'pwfp_opt_value',$this->GetOption('value',''),25,1024))
		);
		$adv = array(
				array($mod->Lang('title_smarty_eval'),
				$mod->CreateInputHidden($formDescriptor, 'pwfp_opt_smarty_eval','0').
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_smarty_eval',
            		'1',$this->GetOption('smarty_eval','0'))),
				array($mod->Lang('title_fbr_edit'),
				$mod->CreateInputHidden($formDescriptor, 'pwfp_opt_fbr_edit','0').
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_fbr_edit',
					'1', $this->GetOption('fbr_edit','0')))
		);
		return array('main'=>$main,'adv'=>$adv);
	}
}

?>
