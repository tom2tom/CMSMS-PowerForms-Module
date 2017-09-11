<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class allows sending an email to a destination selected from a pulldown

namespace PWForms;

class EmailDirector extends EmailBase
{
	private $addressAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->MultiChoice = TRUE;
		$this->IsDisposition = TRUE;
//		$this->IsInput = TRUE; no preservation of input data
		$this->Type = 'EmailDirector';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		$ret = parent::GetMutables($nobase) + [
		'select_label' => 12,
		'subject_override' => 10,
		];

		$mkey1 = 'destination_address';
		$mkey2 = 'destination_subject';
		if ($actual) {
			$opt = $this->GetPropArray($mkey1);
			if ($opt) {
				$suff = array_keys($opt);
			} else {
				return $ret;
			}
		} else {
			$suff = range(1, 10);
		}
		foreach ($suff as $one) {
			$ret[$mkey1.$one] = 12;
		}
		foreach ($suff as $one) {
			$ret[$mkey2.$one] = 12;
		}
		return $ret;
	}

	public function GetSynopsis()
	{
		$opt = $this->GetPropArray('destination_address');
		$num = ($opt) ? count($opt) : 0;

		$mod = $this->formdata->pwfmod;
		$ret = $mod->Lang('destination_count', $num);
		$status = $this->TemplateStatus();
		if ($status) {
			$ret .= '<br />'.$status;
		}
		return $ret;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			$ret = $this->GetPropIndexed('destination_subject', $this->Value);
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->pwfmod->Lang('unspecified'));
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->pwfmod->Lang('add_destination');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->pwfmod->Lang('delete_destination');
	}

	public function HasComponentAdd()
	{
		return TRUE;
	}

	public function ComponentAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	public function HasComponentDelete()
	{
		return $this->GetPropArray('destination_address') != FALSE;
	}

	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('destination_address', $indx);
				$this->RemovePropIndexed('destination_subject', $indx);
			}
		}
	}

	public function AdminPopulate($id)
	{
//		$this->SetEmailJS(); TODO
		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, 'title_email_subject');
		$mod = $this->formdata->pwfmod;
		// remove the "email subject" field
		$main[] = [$mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id, 'fp_select_label',
			$this->GetProperty('select_label', $mod->Lang('select_one')), 25, 128)];
		$main[] = [$mod->Lang('title_allow_subject_override'),
			$mod->CreateInputHidden($id, 'fp_subject_override', 0).
			$mod->CreateInputCheckbox($id, 'fp_subject_override', 1,
				$this->GetProperty('subject_override', 0)),
			$mod->Lang('help_allow_subject_override')];
		if ($this->addressAdd) {
			$this->AddPropIndexed('destination_subject', '');
			$this->AddPropIndexed('destination_address', '');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			$dests = [];
			$dests[] = [
				$mod->Lang('title_selection_subject'),
				$mod->Lang('title_destination_address'),
				$mod->Lang('title_select')
				];
			foreach ($opt as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = [
				$mod->CreateInputText($id, 'fp_destination_subject'.$arf,
					$this->GetPropIndexed('destination_subject', $i), 40, 128),
				$mod->CreateInputText($id, 'fp_destination_address'.$arf, $one, 50, 128),
				$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
			unset($one);
			return ['main'=>$main,'adv'=>$adv,'table'=>$dests,'extra'=>$extra];
		} else {
			$main[] = ['','',$mod->Lang('missing_type', $mod->Lang('destination'))];
			return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$addrs = $this->GetPropArray('destination_address');
		if ($addrs) {
			foreach ($addrs as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('destination_subject', $i)) {
					$this->RemovePropIndexed('destination_address', $i);
					$this->RemovePropIndexed('destination_subject', $i);
				}
			}
			unset($one);
		}
	}

	public function AdminValidate($id)
	{
		$messages = [];
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}

		$mod = $this->formdata->pwfmod;
		list($rv, $msg) = $this->validateEmailAddr($this->GetProperty('email_from_address'));
		if (!$rv) {
			$ret = FALSE;
			$messages[] = $msg;
		}
		$dests = $this->GetProperty('destination_address');
		$c = count($dests);
		if ($c) {
			for ($i=0; $i<$c; $i++) {
				list($rv, $msg) = $this->validateEmailAddr($dests[$i]);
				if (!$rv) {
					$ret = FALSE;
					$messages[] = $msg;
				}
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('destination'));
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$subjects = $this->GetPropArray('destination_subject');
		if ($subjects) {
			$mod = $this->formdata->pwfmod;
			$choices = [' '.$this->GetProperty('select_label', $mod->Lang('select_one'))=>-1]
				+ array_flip($subjects);
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Validate($id)
	{
		if ($this->Value) {
			$val = TRUE;
			$this->ValidationMessage = '';
		} else {
			$val = FALSE;
			$mod = $this->formdata->pwfmod;
			$this->ValidationMessage = $mod->Lang('missing_type', $mod->Lang('destination'));
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}

	public function Dispose($id, $returnid)
	{
		if ($this->GetProperty('subject_override', 0) && $this->GetProperty('email_subject')) {
			$subject = $this->GetProperty('email_subject');
		} else {
			$subject = $this->GetPropIndexed('destination_subject', $this->Value);
		}

		return $this->SendForm($this->GetPropIndexed('destination_address', $this->Value), $subject);
	}
}
