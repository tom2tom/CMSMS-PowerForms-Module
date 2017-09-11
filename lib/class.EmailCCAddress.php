<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class is for an optional CC-address input (single address, not ','-separated)

namespace PWForms;

class EmailCCAddress extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailCCAddress';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'field_to_modify' => 11,
		];
	}

	public function GetSynopsis()
	{
		//TODO advice about email addr
		foreach ($this->formdata->Fields as &$one) {
			if ($one->GetId() == $this->GetProperty('field_to_modify')) {
				$ret = $this->formdata->pwfmod->Lang('title_modifies', $one->GetName());
				unset($one);
				return $ret;
			}
		}
		unset($one);
		return '';
	}

	public function AdminPopulate($id)
	{
		$choices = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->IsDisposition() && is_subclass_of($one, 'EmailBase')) {
				$txt = $one->GetName().': '.$one->GetDisplayType().
					' ('.$one->ForceAlias().')';
				$choices[$txt] = $one->GetId();
			}
		}
		unset($one);

		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_field_to_modify'),
						$mod->CreateInputDropdown($id, 'fp_field_to_modify', $choices, -1,
							$this->GetProperty('field_to_modify'))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$this->SetEmailJS();
		$tmp = $this->formdata->pwfmod->CreateInputEmail(
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
		switch ($this->ValidationType) {
		 case 'email':
			$mod = $this->formdata->pwfmod;
			//no ','-separator support
			if ($this->Value && !preg_match($mod->email_regex, $this->Value)) {
				$val = FALSE;
				$this->ValidationMessage = $mod->Lang('enter_an_email', $this->Name);
			}
			break;
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	public function PreDisposeAction()
	{
		if ($this->Value) {
			foreach ($this->formdata->Fields as &$one) {
				if ($one->IsDisposition()
				 && is_subclass_of($one, 'pwfEmailBase')
				 && $one->GetId() == $this->GetProperty('field_to_modify')) {
					$cc = $one->GetProperty('email_cc_address');
					if ($cc) {
						$cc .= ',';
					}
					$cc .= $this->Value;
					$one->SetProperty('email_cc_address', $cc);
				}
			}
			unset($one);
		}
	}
}
