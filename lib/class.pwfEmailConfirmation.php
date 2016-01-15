<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
/*
Property $approvedToGo - set FALSE in Validate(), set TRUE in ApproveToGo(),
which is called from action.validate.php
PreDisposeAction() disables or enables all other disposition-fields, per $approvedToGo
Dispose() called only when $approvedToGo = FALSE, caches form data in record-table,
sends email requesting confirmation, setup to show 'email-sent' message
When confirmation-link opened, action initiated get form data from record-table,
process it as if on-screen
*/

class pwfEmailConfirmation extends pwfEmailBase
{
	private $approvedToGo = FALSE;
	private $blocked; //array of disposition-fields blocked here

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
//TODO advice about ? return $this->TemplateStatus();
	}

	function ApproveToGo($record_id)
	{
		$this->approvedToGo = TRUE;
	}

	function AdminPopulate($id)
	{
		//log extra tag for use in template-help
		pwfUtils::AddTemplateVariable($this->formdata,'confirm_url','title_confirmation_url');

		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id);
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
	}

	function Populate($id,&$params)
	{
		$this->formdata->jscripts['mailcheck'] = 'construct'; //flag to generate & include js for this type of field
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id,$this->formdata->current_prefix.$this->Id,
			htmlspecialchars($this->Value,ENT_QUOTES),25,128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		return $this->SetClass($tmp,'emailaddr');
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

	//assumes this field is first disposition on the form (sorted at runtime)
	function PreDisposeAction()
	{
		$val = $this->approvedToGo; //FALSE prior to confirmation
		if($val)
		{
			//unblock dispositions
			foreach($this->blocked as $fid)
			{
				$one = $this->formdata->Fields[$fid];
				$one->DispositionPermitted = TRUE;
			}
		}
		else
		{
			//block relevant dispositions (some may already be blocked for other reasons)
			$this->blocked = array();
			foreach($this->formdata->Fields as &$one)
			{
				if($one->IsDisposition && $one->DispositionPermitted)
				{
					$this->blocked[] = $one->Id;
					$one->DispositionPermitted = FALSE;
				}
			}
			unset($one);
		}
		$this->DispositionPermitted = !$val; //re-enable/inhibit this disposition
	}

	//only called when $this->approvedToGo is FALSE
	function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		//cache form data, pending confirmation
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$record_id = $db->GenID($pre.'module_pwf_record_seq');
		$t = time();
		$pub = substr(md5(session_id().$t),0,12);
		$pw = $pub.pwfUtils::Unfusc($mod->GetPreference('masterpass'));
		$when = $db->DbTimeStamp($t);
		$this->formdata->formsmodule = NULL; //exclude module-data from the record
		$cont = pwfUtils::Encrypt($this->formdata,$pw);
		$db->Execute('INSERT INTO '.$pre.
		'module_pwf_record (record_id,pubkey,submitted,contents) VALUES (?,?,?,?)',
			array($record_id,$pub,$when,$cont));
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
