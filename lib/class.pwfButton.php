<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfButton extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'Button';
	}

	function GetFieldInput($id,&$params)
	{
		$js = $this->GetOption('javascript');
		$cssid = $this->GetCSSIdTag();

		return '<input type="button" name="'.$id.$this->formdata->current_prefix.$this->Id.
		'" value="'.$this->GetOption('text').'" '.$js.$cssid.' />';
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_button_text'),
			$mod->CreateInputText($module_id,'opt_text',$this->GetOption('text'),40)));
		return array('main'=>$main);
	}
}

?>
