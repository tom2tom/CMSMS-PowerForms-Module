<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class EmailSubject extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailSubject';
	}

/*	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [];
	}
*/
	public function Populate($id, &$params)
	{
		$tmp = $this->formdata->pwfmod->CreateInputText(
			$id, $this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES), 25, 128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp);
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = trim($this->Value);
		}
		if ($this->Value !== '') {
			$this->Value = filter_var($this->Value, FILTER_SANITIZE_STRING);
			if ($this->Value !== '') {
				$val = TRUE;
				$this->ValidationMessage = '';
			} else {
				$val = FALSE;
				$this->ValidationMessage = $this->formdata->pwfmod->Lang('err_typed', $mod->Lang('subject'));
			}
		} else {
			$val = FALSE;
			$this->ValidationMessage = $this->formdata->pwfmod->Lang('missing_type', $mod->Lang('subject'));
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	public function PreDisposeAction()
	{
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsDisposition() && is_subclass_of($one, 'EmailBase')) {
				$one->SetProperty('email_subject', $this->Value);
			}
		}
		unset($one);
	}
}
