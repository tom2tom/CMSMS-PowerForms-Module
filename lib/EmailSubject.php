<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class EmailSubject extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailSubject';
	}

	public function Populate($id,&$params)
	{
		$tmp = $this->formdata->formsmodule->CreateInputText(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		if ($this->Value) {
			$this->validated = TRUE;
			$this->ValidationMessage = '';
		} else {
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('subject'));
		}
		return array($this->validated,$this->ValidationMessage);
	}

	public function PreDisposeAction()
	{
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsDisposition() && is_subclass_of($one,'EmailBase'))
				$one->SetOption('email_subject',$this->Value);
		}
		unset($one);
	}

}
