<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailSubject extends pwfFieldBase {

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailSubject';
	}

	function Populate($id,&$params)
	{
		$tmp = $this->formdata->formsmodule->CreateInputText(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,
			$this->GetScript());
		return preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
	}

	function Validate($id)
	{
		if($this->Value)
		{
			$this->validated = TRUE;
			$this->ValidationMessage = '';
		}
		else
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('subject'));
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function PreDisposeAction()
	{
		foreach($this->formdata->Fields as &$one)
		{
			if($one->IsDisposition() && is_subclass_of($one,'pwfEmailBase'))
				$one->SetOption('email_subject',$this->Value);
		}
		unset($one);
	}

}

?>
