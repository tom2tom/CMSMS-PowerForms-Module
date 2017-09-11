<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/
/*
Property $approvedToGo - set FALSE in Validate(), set TRUE in ApproveToGo(),
which is called from action.validate.php
PreDisposeAction() disables or enables all other disposition-fields, per $approvedToGo
Dispose() called only when $approvedToGo = FALSE, caches form data in record-table,
sends email requesting confirmation, setup to show 'email-sent' message
When confirmation-link opened, action initiated get form data from record-table,
process it as if on-screen
*/

namespace PWForms;

class EmailConfirmation extends EmailBase
{
	private $approvedToGo = FALSE;
	private $blocked; //array of disposition-fields blocked here

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = TRUE;
//		$this->IsInput = TRUE; no preservation of input value
		$this->Type = 'EmailConfirmation';
	}

/*	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [];
	}
*/
/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}
*/
	public function ApproveToGo($sid)
	{
		$this->approvedToGo = TRUE;
	}

	public function AdminPopulate($id)
	{
		//log extra tag for use in template-help
		Utils::AddTemplateVariable($this->formdata, 'confirm_url', 'title_confirmation_url');

		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id);
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
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
		//sneak this in, ahead of PreDisposeAction()
		$this->approvedToGo = FALSE;

		$val = TRUE;
		$this->ValidationMessage = '';
		switch ($this->ValidationType) {
		 case 'email':
			if ($this->Value) {
				list($rv, $msg) = $this->validateEmailAddr($this->Value);
				if (!$rv) {
					$val = FALSE;
					$this->ValidationMessage = $msg;
				}
			} else {
				$val = FALSE;
				$this->ValidationMessage = $this->formdata->pwfmod->Lang('enter_an_email', $this->Name);
			}
			break;
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	//assumes this field is first disposition on the form (sorted at runtime)
	public function PreDisposeAction()
	{
		$val = $this->approvedToGo; //FALSE prior to confirmation
		if ($val) {
			//unblock dispositions
			foreach ($this->blocked as $fid) {
				$one = $this->formdata->Fields[$fid];
				$one->SetDisposable(TRUE);
			}
		} else {
			//block relevant dispositions (some may already be blocked for other reasons)
			$this->blocked = [];
			foreach ($this->formdata->Fields as &$one) {
				if ($one->IsDisposition() && $one->IsDisposable()) {
					$this->blocked[] = $one->Id;
					$one->SetDisposable(FALSE);
				}
			}
			unset($one);
		}
		$this->SetDisposable(!$val); //re-enable/inhibit this disposition
	}

	//only called when $this->approvedToGo is FALSE
	public function Dispose($id, $returnid)
	{
		$mod = $this->formdata->pwfmod;
		//cache form data, pending confirmation
		$pre = \cms_db_prefix();
		$db = \cmsms()->GetDb();

		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$pref = str_repeat('0', 20);
		for ($i = 0; $i < 20; $i++) {
			$pref[$i] = $chars[mt_rand(0, 71)];
		}
		$pub = sha1(uniqid($pref, TRUE)); //easy 40-byte hash
		$cfuncs = new Crypter($mod);
		$pw = $pub.$cfuncs->decrypt_preference(Crypter::MKEY);
		$when = time();
		$cont = $cfuncs->encrypt_value(serialize($this->formdata), $pw);
		$db->Execute('INSERT INTO '.$pre.'module_pwf_session (pubkey,submitted,contents) VALUES (?,?,?)', [$pub, $when, $cont]);
		$sid = $db->Insert_Id();
		$this->formdata->pwfmod = $mod; //reinstate
		//set url variable for email template
		$tplvars = [];
		$pref = $this->formdata->current_prefix;
		$tplvars['confirm_url'] =
			$this->formdata->pwfmod->CreateFrontendLink('', $returnid, 'validate', '',
			[$pref.'c'=>$pub,
			 $pref.'d'=>$this->Id,
//			 $pref.'f'=>$this->formdata->Id,
			 $pref.'s'=>$sid],
			'', TRUE, FALSE, '', TRUE);
		return $this->SendForm($this->GetValue(), $this->GetProperty('email_subject'), $tplvars);
	}
}
