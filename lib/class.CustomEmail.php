<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class allows sending an email with subject, sender-name, sender-email
//and receiver(s) taken from (specified) other fields

namespace PWForms;

class CustomEmail extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsDisposition = TRUE;
//		$this->MultiChoice = TRUE;
		$this->Type = 'CustomEmail';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		$ret = parent::GetMutables($nobase) + [
		'email_subject' => 12,
		'email_from_name' => 12,
		'email_from_address' => 12,
		];

		$mkey1 = 'destination_address';
		if ($actual) {
			$opt = $this->GetPropArray($mkey1);
			if ($opt) {
				$suff = array_keys($opt);
			} else {
				return $ret;
			}
		} else {
			$suff = ['*']; //any numeric suffix
		}
		foreach ($suff as $one) {
			$ret[$mkey1.$one] = 12;
		}
		return $ret;
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			$ret = $mod->Lang('to').': '.count($opt).' '.$mod->Lang('fields');
		} else {
			$ret = $mod->Lang('missing_type', $mod->Lang('destination'));
		}
		$status = $this->TemplateStatus();
		if ($status) {
			$ret .= '<br />'.$status;
		}
		return $ret;
	}

	public function AdminPopulate($id)
	{
		$displayfields = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInForm) {
				$displayfields[$one->GetName()] = $one->GetId();
			}
		}
		unset($one);
		$destfields = [];
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			foreach ($displayfields as $k=>$v) {
				if (in_array($v, $opt)) {
					$destfields[$k] = $v;
				}
			}
		}
		$mod = $this->formdata->pwfmod;
		$choices = [$mod->Lang('select_one') => ''] + $displayfields;

		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, FALSE, FALSE, FALSE);
		$waslast = array_pop($main); //keep only the default to-type selector
		$main[] = [$mod->Lang('title_subject_field'),
					$mod->CreateInputDropdown($id, 'fp_email_subject', $choices, -1,
					$this->GetProperty('email_subject'))];
		$main[] = [$mod->Lang('title_from_field'),
					$mod->CreateInputDropdown($id, 'fp_email_from_name', $choices, -1,
					$this->GetProperty('email_from_name', $mod->Lang('friendly_name')))];
		$main[] = [$mod->Lang('title_from_address_field'),
					$mod->CreateInputDropdown($id, 'fp_email_from_address', $choices, -1,
					$this->GetProperty('email_from_address'))];
		$main[] = [$mod->Lang('title_destination_field'),
					$mod->CreateInputSelectList($id, 'fp_destination_address', $displayfields,
					$destfields, 5)];
		$main[] = $waslast;
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
	}

	public function AdminValidate($id)
	{
		$messages = [];
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}

		$mod = $this->formdata->pwfmod;
		if (!$this->GetProperty('email_subject')) {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned', $mod->Lang('title_email_subject'));
		}
		if (!$this->GetProperty('email_from_name')) {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned', $mod->Lang('title_email_from_name'));
		}
		$opt = $this->GetProperty('email_from_address');
		if ($opt) {
			list($rv, $msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned', $mod->Lang('title_email_from_address'));
		}
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			list($rv, $msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned', $mod->Lang('title_destination_address'));
		}
		$msg = $this->TemplateStatus();
		if ($msg) {
			$ret = FALSE;
			$messages[] = $msg;
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Dispose($id, $returnid)
	{
		$dests = $this->GetPropArray('destination_address'); //TODO in this case, field id's ?
		if ($dests) {
			$formdata = $this->formdata;

			$senderfld = $this->GetProperty('email_from_name'); //TODO confirm this is field_id?
			$fld = $formdata->Fields[$senderfld];
			$this->SetProperty('email_from_name', $fld->DisplayableValue());

			$fromfld = $this->GetProperty('email_from_address');
			$fld = $formdata->Fields[$fromfld];
			$this->SetProperty('email_from_address', $fld->DisplayableValue());

			$addrs = [];
			foreach ($dests as $field_id) {
				$fld = $formdata->Fields[$field_id];
				$value = $fld->DisplayableValue();
				if (strpos($value, ',') !== FALSE) {
					$addrs = $addrs + explode(',', $value);
				} else {
					$addrs[] = $value;
				}
			}

/*			$subjectfld = $this->GetProperty('email_subject');
			$fld = $formdata->Fields[$subjectfld];
			$this->SetProperty('email_subject',$fld->DisplayableValue());

			$ret = $this->SendForm($addrs,$this->GetProperty('email_subject'));

			$this->SetProperty('email_subject',$subjectfld);
*/
			$fld = $formdata->Fields[$this->GetProperty('email_subject')];
			$ret = $this->SendForm($addrs, $fld->DisplayableValue()); //TODO check value(subject) is ok

			$this->SetProperty('email_from_name', $senderfld);
			$this->SetProperty('email_from_address', $fromfld);

			return $ret;
		}
		return [FALSE,$this->formdata->pwfmod->Lang('err_address', '')];
	}
}
