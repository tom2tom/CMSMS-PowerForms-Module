<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSubmitForm extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'SubmitForm';
	}

	function GetFieldStatus()
	{
		return $this->GetOption('method').' '.$this->GetOption('url');
	}

	function PrePopulateAdminForm($id)
	{
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;
		$main = array();
		if(function_exists('curl_init'))
		{
			$adv = array();
			$methods = array('POST'=>'POST','GET'=>'GET');
			$main[] = array($mod->Lang('title_method'),
				$mod->CreateInputDropdown($id,'opt_method',$methods,-1,
					$this->GetOption('method')));
			$main[] = array($mod->Lang('title_url'),
				$mod->CreateInputText($id,'opt_url',$this->GetOption('url'),40,255),
				$mod->Lang('help_url'));
			foreach($formdata->Fields as &$one)
			{
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
			return array('main'=>$main,'adv'=>$adv);
		}
		else
		{
			$main[] = array('','',$mod->Lang('title_install_curl'));
			return array('main'=>$main);
		}
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminVisible($mainArray,$advArray);
	}

	function Dispose($id,$returnid)
	{
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;
		$msg = '';
		$submission = $this->GetOption('url');
		$payload = array();
		$fields = $formdata->Fields;
		$unspec = $this->GetFormOption('unspecified',$mod->Lang('unspecified'));

		foreach($fields as &$one)
		{
			if($this->GetOption('sub_'.$one->GetId(),0))
			{
				$payload[] = urlencode($this->GetOption('fld_'.$one->GetId())).'='.
				urlencode($one->GetHumanReadableValue());
			}
		}
		unset($one);
		if($this->GetOption('additional'))
			$payload[] = $this->GetOption('additional');

		$send_payload = implode('&',$payload);
		if($this->GetOption('method','POST') == 'POST')
		{
			$ch = curl_init($this->GetOption('url'));
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$send_payload);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,0);
			$res = curl_exec($ch);
			if(!$res)
			{
				$msg = curl_error($ch);
			}
			curl_close($ch);
		}
		else
		{
			$url = $this->GetOption('url');
			if(strpos($url,'?'))
			{
				$url .= '&'.$send_payload;
			}
			else
			{
				$url .= '?'.$send_payload;
			}
			$ch = curl_init($url);
			curl_setopt($ch,CURLOPT_HTTPGET,1);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,0);
			$res = curl_exec($ch);
			if(!$res)
			{
				$msg = curl_error($ch);
			}
			curl_close($ch);
		}
		return array($res,$msg);
	}

}

?>
