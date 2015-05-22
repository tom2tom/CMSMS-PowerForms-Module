<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFromEmailSubjectField extends pwfFieldBase {

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'FromEmailSubjectField';
		$this->ModifiesOtherFields = TRUE;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),
           25,128,$js.$this->GetCSSIdTag());
	}

	function ModifyOtherFields()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
		if($this->Value !== FALSE)
		{
			for($i=0; $i<count($others); $i++)
			{
				$replVal = '';
				if($others[$i]->IsDisposition() && is_subclass_of($others[$i],'pwfEmailFieldBase'))
				{
					$others[$i]->SetOption('email_subject',$this->Value);
				}
			}
		}
	}

	function GetFieldStatus()
	{
		return '';
	}

}

?>
