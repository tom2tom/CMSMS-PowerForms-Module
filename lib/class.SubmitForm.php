<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SubmitForm extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->IsDisposition = TRUE;
		$this->Type = 'SubmitForm';
	}

	public function GetSynopsis()
	{
		if (function_exists('curl_init')) {
			return $this->GetProperty('method').' '.$this->GetProperty('url');
		}
		return $this->Lang('missing_type', 'PHP cURL extension');
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE, FALSE);
		$mod = $formdata->formsmodule;
		
		if (function_exists('curl_init')) {
			$formdata = $this->formdata;
			$methods = ['POST'=>'POST','GET'=>'GET'];
			$main[] = [$mod->Lang('title_method'),
				$mod->CreateInputDropdown($id, 'fp_method', $methods, -1,
					$this->GetProperty('method'))];
			$main[] = [$mod->Lang('title_url'),
				$mod->CreateInputText($id, 'fp_url', $this->GetProperty('url'), 40, 255),
				$mod->Lang('help_url')];
			foreach ($formdata->Fields as &$one) {
				$alias = $one->ForceAlias();
				$fid = $one->GetId();
				$adv[] = [$mod->Lang('title_maps_to', $one->GetName()),
					$mod->CreateInputText($id, 'fp_fld_'.$fid,
						 $this->GetProperty('fld_'.$fid, $alias), 40, 255).
					$mod->CreateInputHidden($id, 'fp_sub_'.$fid, 0).
					$mod->CreateInputCheckbox($id, 'fp_sub_'.$fid, 1,
						$this->GetProperty('sub_'.$fid, ($one->DisplayInSubmission()?1:0))),
					$mod->Lang('title_include_in_submission')];
			}
			unset($one);
			$adv[] = [$mod->Lang('title_additional'),
				$mod->CreateInputText($id, 'fp_additional',
					$this->GetProperty('additional'), 40, 255),
				$mod->Lang('help_additional_payload')];
		} else {
			$main[] = ['','',$mod->Lang('title_install_curl')];
		}
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Dispose($id, $returnid)
	{
		$payload = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($this->GetProperty('sub_'.$one->GetId(), 0)) {
				$payload[] = urlencode($this->GetProperty('fld_'.$one->GetId())).'='.
				urlencode($one->DisplayableValue());
			}
		}
		unset($one);
		if ($this->GetProperty('additional')) {
			$payload[] = $this->GetProperty('additional');
		}
		$send_payload = implode('&', $payload);

		$msg = '';

		if ($this->GetProperty('method', 'POST') == 'POST') {
			$ch = curl_init($this->GetProperty('url'));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $send_payload);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
			$res = curl_exec($ch);
			if (!$res) {
				$msg = curl_error($ch);
			}
			curl_close($ch);
		} else {
			$url = $this->GetProperty('url');
			if (strpos($url, '?')) {
				$url .= '&'.$send_payload;
			} else {
				$url .= '?'.$send_payload;
			}
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
			$res = curl_exec($ch);
			if (!$res) {
				$msg = curl_error($ch);
			}
			curl_close($ch);
		}
		return [$res,$msg];
	}
}
