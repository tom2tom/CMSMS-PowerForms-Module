<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailConfirmation extends pwfEmailBase
{
	$approvedToGo;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Type = 'EmailConfirmation';
		$this->ValidationType = 'email';
		$this->approvedToGo = FALSE;
		$this->ModifiesOtherFields = TRUE;
		$mod = $formdata->formsmodule;
		pwfUtils::AddTemplateVariable($formdata,'confirm_url',$mod->Lang('title_confirmation_url'));
	}

	function GetFieldStatus()
	{
        return $this->TemplateStatus();
	}

	function ApproveToGo($response_id)
	{
		$this->approvedToGo = TRUE;
	}

	function ModifyOtherFields()
	{
		if($this->formdata->FormState == 'update')
		{
			$this->approvedToGo = TRUE;
			return;
		}
		// If we haven't been approved,inhibit all other dispositions!
		$others = $this->formdata->Fields;

		for($i=0; $i<count($others); $i++)
		{
			if($this->approvedToGo && $others[$i]->GetFieldType() == 'DispositionFormBrowser')
			{
				$others[$i]->SetApprovalName($this->GetValue());
			}
			elseif(!$this->approvedToGo && $others[$i]->IsDisposition())
			{
				$others[$i]->SetDispositionPermission(FALSE);
			}
		}
		$this->SetDispositionPermission(TRUE);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		return $mod->CustomCreateInputType($id,'pwfp_'.$this->Id,htmlspecialchars($this->Value,ENT_QUOTES),25,80,$this->GetCSSIdTag(),'email');
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$contentops = cmsms()->GetContentOperations();

		$ret = $this->PrePopulateAdminFormBase($module_id);
		$main = (isset($ret['main'])) ? $ret['main'] : array();
		$main[] = array($mod->Lang('redirect_after_approval'),
				@$contentops->CreateHierarchyDropdown('',$this->GetOption('redirect_page','0'),$module_id.'opt_redirect_page'));
		$ret['main'] = $main;
		return $ret;
	}

	function Validate()
	{
  		$this->validated = TRUE;
  		$this->ValidationMessage = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			$mod = $this->formdata->formsmodule;
			if($this->Value)
			{
				if(!preg_match($mod->email_regex,$this->Value))
				{
					$this->validated = FALSE;
					$this->ValidationMessage = $mod->Lang('invalid_email',$this->Name); //TODO translate
				}
			}
			else
			{
				$this->validated = FALSE;
				$this->ValidationMessage = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated,$this->ValidationMessage);
	}

    // send emails
	function DisposeForm($returnid)
	{
		if(!$this->approvedToGo)
		{
			// create response URL
			$handler = NULL;
TODO			list($rid,$code) = pwfUtils::StoreResponse($formdata,-1,'',$handler);

			$smarty = cmsms()->GetSmarty();
			$mod = $this->formdata->formsmodule;
			$smarty->assign('confirm_url',$mod->CreateFrontendLink('',$returnid,
				'validate','',array('pwfp_f'=>$this->formdata->Id,'pwfp_r'=>$rid,'pwfp_c'=>$code),'',
				TRUE,FALSE,'',TRUE));
			return $this->SendForm($this->GetValue(),$this->GetOption('email_subject'));
		}
		else
		{
			return array(TRUE,'');
		}
	}

}

?>
