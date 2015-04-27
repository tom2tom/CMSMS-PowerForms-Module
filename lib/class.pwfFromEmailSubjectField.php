<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFromEmailSubjectField extends pwfFieldBase {

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'FromEmailSubjectField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array();
		$this->modifiesOtherFields = true;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');

		return $mod->CustomCreateInputText($id, 'pwfp__'.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES),
           25,128,$js.$this->GetCSSIdTag());
	}

	function ModifyOtherFields()
	{
		$mod = $this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		if($this->Value !== false)
		{
			for($i=0;$i<count($others);$i++)
			{
				$replVal = '';
				if($others[$i]->IsDisposition() && is_subclass_of($others[$i],'pwfDispositionEmailBase'))
				{
					$others[$i]->SetOption('email_subject',$this->Value);
				}
			}
		}
	}

	function StatusInfo()
	{
		return '';
	}

}

?>
