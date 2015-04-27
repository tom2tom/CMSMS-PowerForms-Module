<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfButtonField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'ButtonField';
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->ValidationTypes = array();
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$cssid = $this->GetCSSIdTag();

		$ret = '<input type="button" name="'.$id.'pwfp__'.$this->Id.'" value="' .
		   $this->GetOption('text','').'" '.$js.$cssid.'/>';

		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
			  array($mod->Lang('title_button_text'),
					$mod->CreateInputText($formDescriptor,'pwfp_opt_text',
							  $this->GetOption('text',''), 40)));
		return array('main'=>$main);
	}
}

?>
