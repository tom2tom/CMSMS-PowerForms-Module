<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

require_once(cms_join_path(dirname(__FILE__),'DispositionEmailBase.class.php'));

class fbDispositionEmailConfirmation extends fbDispositionEmailBase
{
	//var $validated;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'DispositionEmailConfirmation';
		$this->DisplayInForm = true;
		$this->NonRequirableField = false;
		$this->DisplayInSubmission = true;
		$this->IsDisposition = true;
		$this->ValidationTypes = array();
		$this->ValidationType = 'email';
		$this->modifiesOtherFields = true;
		$mod = $form_ptr->module_ptr;
		$form_ptr->AddTemplateVariable('confirm_url',
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
		if($this->form_ptr->GetFormState() == 'update')
		{
			$this->approvedToGo = true;
			return;
		}
		// If we haven't been approved, inhibit all other dispositions!
		$others = $this->form_ptr->GetFields();

		for($i=0;$i<count($others);$i++)
		{
			if(($this->approvedToGo && $others[$i]->GetFieldType() == 'DispositionDatabase') ||
				($this->approvedToGo && $others[$i]->GetFieldType() == 'DispositionFormBrowser'))
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
		$mod = $this->form_ptr->module_ptr;
		return $mod->fbCreateInputText($id, 'fbrp__'.$this->Id,
			htmlspecialchars($this->Value, ENT_QUOTES),25,80,$this->GetCSSIdTag(),'email');
	}

    // send emails
	function DisposeForm($returnid)
	{
		if(!$this->approvedToGo)
		{
			// create response URL
			$fbrf = null;
			list($rid,$code) = $this->form_ptr->StoreResponse(-1,'',$fbrf);

			$smarty = cmsms()->GetSmarty();
			$mod = $this->form_ptr->module_ptr;
			$smarty->assign('confirm_url',$mod->CreateFrontendLink('', $returnid,
				'validate', '', array('fbrp_f'=>$this->form_ptr->GetId(),'fbrp_r'=>$rid,'fbrp_c'=>$code), '',
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
		$mod = $this->form_ptr->module_ptr;
		$contentops = cmsms()->GetContentOperations();

		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
		$main = (isset($ret['main'])) ? $ret['main'] : array();
		$main[] = array($mod->Lang('redirect_after_approval'),
				@$contentops->CreateHierarchyDropdown('',$this->GetOption('redirect_page','0'), $formDescriptor.'fbrp_opt_redirect_page'));
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
			$mod = $this->form_ptr->module_ptr;
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
