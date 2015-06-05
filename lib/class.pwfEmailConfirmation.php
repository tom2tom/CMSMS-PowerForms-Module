<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailConfirmation extends pwfEmailBase
{
	var $approvedToGo;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->ModifiesOtherFields = TRUE;
		$this->Type = 'EmailConfirmation';
		$this->ValidationType = 'email';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
		$this->approvedToGo = FALSE;
	}

	function GetFieldStatus()
	{
        return $this->TemplateStatus();
	}

	function ApproveToGo($response_id)
	{
		$this->approvedToGo = TRUE;
//TODO 'REALLY' dispose the whole form (without further confirmation)
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$contentops = cmsms()->GetContentOperations();

//TODO where should this be?
		pwfUtils::AddTemplateVariable($this->formdata,'confirm_url',$mod->Lang('title_confirmation_url'));

		$ret = $this->PrePopulateAdminFormCommonEmail($id);
		$ret['main'][] = array($mod->Lang('redirect_after_approval'),
				@$contentops->CreateHierarchyDropdown('',$this->GetOption('redirect_page','0'),$id.'opt_redirect_page'));
		return $ret;
	}

	function ModifyOtherFields()
	{
		if($this->formdata->FormState == 'update')
		{
			$this->approvedToGo = TRUE;
			return;
		}
		// If we haven't been approved,inhibit all other dispositions!
		foreach($this->formdata->Fields as &$one)
		{
			if($this->approvedToGo && $one->GetFieldType() == 'FormBrowser')
				$one->SetApprovalName($this->GetValue());
			elseif(!$this->approvedToGo && $one->IsDisposition())
				$one->SetDispositionPermission(FALSE);
		}
		unset($one);
		$this->SetDispositionPermission(TRUE);
	}

	function Populate($id,&$params)
	{
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,
			$this->GetScript());
		return preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
	}

	function Validate($id)
	{
  		$this->validated = TRUE;
  		$this->ValidationMessage = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			if($this->Value)
			{
				list($rv,$msg) = $this->validateEmailAddr($this->Value);
				if(!$rv)
				{
					$this->validated = FALSE;
					$this->ValidationMessage = $msg;
				}
			}
			else
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $this->formdata->formsmodule->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function Dispose($id,$returnid)
	{
		if($this->approvedToGo)
		{
			return array(TRUE,'');
		}
		else
		{
//TODO cache form data, & abort disposition, pending confirmation
			// create response URL
			$handler = NULL;
//TODO response store??? 
			list($response_id,$code) = pwfUtils::StoreResponse($this->formdata,-1,'',$handler);
			$smarty = cmsms()->GetSmarty();
			$mod = $this->formdata->formsmodule;
//TODO actually achieves anything?
			pwfUtils::AddTemplateVariable($this->formdata,'confirm_url',$mod->Lang('title_confirmation_url'));
			$pref = $this->formdata->current_prefix;
//TODO setting URL actually achieves anything?
			$smarty->assign('confirm_url',$mod->CreateFrontendLink('',$returnid,
				'validate','',array(
					$pref.'c'=>$code,
					$pref.'d'=>$this->Id,
					$pref.'f'=>$this->formdata->Id,
					$pref.'r'=>$response_id),
				'',TRUE,FALSE,'',TRUE));
			return $this->SendForm($this->GetValue(),$this->GetOption('email_subject'));
		}
	}
}

?>
