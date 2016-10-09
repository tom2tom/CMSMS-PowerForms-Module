<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SubmitForm extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'SubmitForm';
	}

	public function GetFieldStatus()
	{
		if (function_exists('curl_init'))
			return $this->GetOption('method').' '.$this->GetOption('url');
		return $this->Lang('missing_type','PHP cURL extension');
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);
		$mod = $formdata->formsmodule;
		if (function_exists('curl_init')) {
			$formdata = $this->formdata;
			$methods = array('POST'=>'POST','GET'=>'GET');
			$main[] = array($mod->Lang('title_method'),
				$mod->CreateInputDropdown($id,'opt_method',$methods,-1,
					$this->GetOption('method')));
			$main[] = array($mod->Lang('title_url'),
				$mod->CreateInputText($id,'opt_url',$this->GetOption('url'),40,255),
				$mod->Lang('help_url'));
			foreach ($formdata->Fields as &$one) {
				$alias = $one->ForceAlias();
				$fid = $one->GetId();
				$adv[] = array($mod->Lang('title_maps_to',$one->GetName()),
					$mod->CreateInputText($id,'opt_fld_'.$fid,
						 $this->GetOption('fld_'.$fid,$alias),40,255).
					$mod->CreateInputHidden($id,'opt_sub_'.$fid,0).
					$mod->CreateInputCheckbox($id,'opt_sub_'.$fid,1,
						$this->GetOption('sub_'.$fid,($one->DisplayInSubmission()?1:0))),
					$mod->Lang('title_include_in_submission'));
			}
			unset($one);
			$adv[] = array($mod->Lang('title_additional'),
				$mod->CreateInputText($id,'opt_additional',
					$this->GetOption('additional'),40,255),
				$mod->Lang('help_additional_payload'));
		} else {
			$main[] = array('','',$mod->Lang('title_install_curl'));
		}
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Dispose($id,$returnid)
	{
		$payload = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($this->GetOption('sub_'.$one->GetId(),0)) {
				$payload[] = urlencode($this->GetOption('fld_'.$one->GetId())).'='.
				urlencode($one->GetDisplayableValue());
			}
		}
		unset($one);
		if ($this->GetOption('additional'))
			$payload[] = $this->GetOption('additional');
		$send_payload = implode('&',$payload);

		$msg = '';

		if ($this->GetOption('method','POST') == 'POST') {
			$ch = curl_init($this->GetOption('url'));
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$send_payload);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,0);
			$res = curl_exec($ch);
			if (!$res) {
				$msg = curl_error($ch);
			}
			curl_close($ch);
		} else {
			$url = $this->GetOption('url');
			if (strpos($url,'?'))
				$url .= '&'.$send_payload;
			else
				$url .= '?'.$send_payload;
			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_HTTPGET,1);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,0);
			$res = curl_exec($ch);
			if (!$res)
				$msg = curl_error($ch);
			curl_close($ch);
		}
		return array($res,$msg);
	}
}
