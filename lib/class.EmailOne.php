<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class EmailOne extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = TRUE;
		$this->IsInput = TRUE;
		$this->Required = TRUE;
		$this->Type = 'EmailOne';
	}

	public function GetSynopsis()
	{
		return $this->TemplateStatus();
	}

	public function AdminPopulate($id)
	{
		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id);
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
	}

	public function Populate($id, &$params)
	{
		$this->SetEmailJS();
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id, $this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES), 25, 128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp, 'emailaddr');
	}

	public function Validate($id)
	{
		if ($this->Value !== '') {
			$this->Value = filter_var(trim($this->Value), FILTER_SANITIZE_EMAIL);
		}
		$val = TRUE;
		$this->ValidationMessage = '';
		if ($this->Value) {
			list($rv, $msg) = $this->validateEmailAddr($this->Value);
			if (!$rv) {
				$val = FALSE;
				$this->ValidationMessage = $msg;
			}
		} else {
			$val = FALSE;
			$this->ValidationMessage = $this->formdata->formsmodule->Lang('enter_an_email', $this->Name);
		}
		$this->SetStatus('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	public function Dispose($id, $returnid)
	{
		if ($this->HasValue()) {
			return $this->SendForm($this->Value, $this->GetProperty('email_subject'));
		} else {
			return [TRUE,''];
		}
	}
}
