<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfEmailConfirmation extends pwfEmailBase
{
	var $approvedToGo = FALSE;
	var $realAction;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->Type = 'EmailConfirmation';
		$this->ValidationType = 'email';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_email_address')=>'email');
	}

	function GetFieldStatus()
	{
        return $this->TemplateStatus();
	}

	function ApproveToGo($record_id)
	{
		$this->approvedToGo = TRUE;
	}

	function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		//log extra tag for use in template-help
		pwfUtils::AddTemplateVariable($this->formdata,'confirm_url','title_confirmation_url');

		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id);

		$adv[] = array($mod->Lang('redirect_after_approval'),
						pwfUtils::CreateHierarchyPulldown($mod,$id,'opt_redirect_page',
						$this->GetOption('redirect_page',0)));
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
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
		//sneak this in, ahead of PreDisposeAction()
		$this->approvedToGo = FALSE;

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

	//we assume (correctly) this field is first disposition on the form
	function PreDisposeAction()
	{
		$val = $this->approvedToGo;
		if($val)
		{
			$page = $this->GetOption('redirect_page',0);
			if($page > 0)
			{
				$this->formdata->Options['redirect_page'] = $page;
				$this->formdata->Options['submit_action'] = 'redir';
			}
			else
				$this->formdata->Options['submit_action'] = $this->realAction;
		}
		else
		{
			$this->realAction = $this->GetFormOption('submit_action','text');
			$this->formdata->Options['submit_action'] = 'confirm';
		}
		//inhibit/enable all dispositions
		foreach($this->formdata->Fields as &$one)
		{
			if($one->IsDisposition())
				$one->SetDispositionPermission($val);
		}
		unset($one);
		$this->SetDispositionPermission(!$val); //re-enable/inhibit this disposition
	}

	//only called when $this->approvedToGo is FALSE
	function Dispose($id,$returnid)
	{
		//cache form data, pending confirmation
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$record_id = $db->GenID($pre.'module_pwf_record_seq');
		$t = time();
		$code = substr(md5(session_id().$t),0,12);
		$when = $db->DbTimeStamp($t);
		$mod = $this->formdata->formsmodule;
		$this->formdata->formsmodule = NULL; //exclude module-data from the record
		$cont = pwfUtils::Encrypt($this->formdata,$code.$mod->GetPreference('default_phrase'));
		$db->Execute('INSERT INTO '.$pre.
		'module_pwf_record (record_id,code,submitted,contents) VALUES (?,?,?,?)',
			array($record_id,$code,$when,$cont));
		$this->formdata->formsmodule = $mod; //reinstate
		//set url variable for email template
		$smarty = cmsms()->GetSmarty();
		$pref = $this->formdata->current_prefix;
		$smarty->assign('confirm_url',
			$this->formdata->formsmodule->CreateFrontendLink('',$returnid,'validate','',
			array(
				$pref.'c'=>$code,
				$pref.'d'=>$this->Id,
//				$pref.'f'=>$this->formdata->Id,
				$pref.'r'=>$record_id),
			'',TRUE,FALSE,'',TRUE));
		return $this->SendForm($this->GetValue(),$this->GetOption('email_subject'));
	}
}

?>
