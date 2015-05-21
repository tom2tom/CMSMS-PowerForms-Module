<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//require_once(cms_join_path(dirname(__FILE__),'class.pwfDispositionEmailBase.php'));

class pwfDispositionEmailConfirmation extends pwfDispositionEmailBase
{
	//var $validated;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionEmailConfirmation';
		$this->DisplayInForm = true;
		$this->NonRequirableField = false;
		$this->DisplayInSubmission = true;
		$this->IsDisposition = true;
		$this->ValidationTypes = array();
		$this->ValidationType = 'email';
		$this->modifiesOtherFields = true;
		$mod = $formdata->pwfmodule;
		$formdata->AddTemplateVariable('confirm_url',
       		$mod->Lang('title_confirmation_url'));
		$this->approvedToGo = false;
	}

	function StatusInfo()
	{
        return $this->TemplateStatus();
	}

	function ApproveToGo($response_id)
	{
		$this->approvedToGo = true;
	}

	function ModifyOtherFields()
	{
		if($this->formdata->GetFormState() == 'update')
		{
			$this->approvedToGo = true;
			return;
		}
		// If we haven't been approved, inhibit all other dispositions!
		$others = $this->formdata->GetFields();

		for($i=0;$i<count($others);$i++)
		{
			if($this->approvedToGo && $others[$i]->GetFieldType() == 'DispositionFormBrowser')
			{
				$others[$i]->SetApprovalName($this->GetValue());
			}
			elseif(!$this->approvedToGo && $others[$i]->IsDisposition())
			{
				$others[$i]->SetDispositionPermission(false);
			}
		}
		$this->SetDispositionPermission(true);
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->CustomCreateInputText($id, 'pwfp__'.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES),25,80,$this->GetCSSIdTag(),'email');
	}

    // send emails
	function DisposeForm($returnid)
	{
		if(!$this->approvedToGo)
		{
			// create response URL
			$handler = null;
			list($rid,$code) = $this->formdata->StoreResponse(-1,'',$handler);

			$smarty = cmsms()->GetSmarty();
			$mod = $this->formdata->pwfmodule;
			$smarty->assign('confirm_url',$mod->CreateFrontendLink('', $returnid,
				'validate', '', array('pwfp_f'=>$this->formdata->GetId(),'pwfp_r'=>$rid,'pwfp_c'=>$code), '',
				true,false,'',true));
			return $this->SendForm($this->GetValue(),$this->GetOption('email_subject'));
		}
		else
		{
			return array(true,'');
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$contentops = cmsms()->GetContentOperations();

		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
		$main = (isset($ret['main'])) ? $ret['main'] : array();
		$main[] = array($mod->Lang('redirect_after_approval'),
				@$contentops->CreateHierarchyDropdown('',$this->GetOption('redirect_page','0'), $formDescriptor.'pwfp_opt_redirect_page'));
		$ret['main'] = $main;
		return $ret;
	}

	function Validate()
	{
  		$this->validated = true;
  		$this->validationErrorText = '';
		switch ($this->ValidationType)
		{
		 case 'email':
			$mod = $this->formdata->pwfmodule;
			if($this->Value !== false &&
				!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $this->Value))
			{
				$this->validated = false;
				$this->validationErrorText = $mod->Lang('please_enter_an_email',$this->Name);
			}
			break;
		}
		return array($this->validated, $this->validationErrorText);
	}
}

?>
