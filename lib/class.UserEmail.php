<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class allows the form user to initiate an email, with customised sender
//and replyto, to a specified destination with optional copy to the form user

namespace PWForms;

class UserEmail extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = TRUE;
		$this->IsInput = TRUE;
		$this->Type = 'UserEmail';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'send_user_copy' => 12,
		'send_user_label' => 12,
		'headers_to_modify' => 12,
		];
	}

	public function GetSynopsis()
	{
		return $this->TemplateStatus();
	}

	public function HasValue($deny_blank_response=FALSE)
	{
		return !empty($this->Value[0]);
	}

	public function GetValue()
	{
		return $this->Value[0];
	}

	//c.f. parent::SetValue() which calls html_myentities_decode()
	public function SetValue($newvalue)
	{
		if (!is_array($newvalue)) {
			$newvalue = [$newvalue];
		}
		foreach ($newvalue as &$tmp) {
			$tmp = filter_var($tmp, FILTER_SANITIZE_EMAIL);
		}
		unset($tmp);
		$this->Value = $newvalue;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if (is_array($this->Value)) {
			if ($as_string) {
				return implode($this->GetFormProperty('list_delimiter', ','), $this->Value);
			} else {
				$ret = $this->Value;
				return $ret; //a copy
			}
		} elseif ($this->Value) {
			$ret = $this->Value;
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

	public function AdminPopulate($id)
	{
		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, 'title_email_from_address');
		$mod = $this->formdata->pwfmod;

		$choices = [
			$mod->Lang('option_never')=>'n',
			$mod->Lang('option_user_choice')=>'c',
			$mod->Lang('option_always')=>'a'];
		$main[] = [$mod->Lang('title_send_user_copy'),
						$mod->CreateInputDropdown($id, 'fp_send_user_copy', $choices, -1,
						$this->GetProperty('send_user_copy', 'n'))];
		$main[] = [$mod->Lang('title_send_user_label'),
						$mod->CreateInputText($id, 'fp_send_user_label',
						$this->GetProperty('send_user_label', $mod->Lang('title_send_me_a_copy')), 25, 125)];
		$choices = [
			$mod->Lang('option_from')=>'f',
			$mod->Lang('option_reply')=>'r',
			$mod->Lang('option_both')=>'b'];
		$main[] = [$mod->Lang('title_headers_to_modify'),
						$mod->CreateInputDropdown($id, 'fp_headers_to_modify', $choices, -1,
						$this->GetProperty('headers_to_modify', 'f'))];
		return ['main'=>$main,'adv'=>$adv,'extra'=>$extra];
	}

	public function Populate($id, &$params)
	{
		$this->SetEmailJS();
		$toself = ($this->GetProperty('send_user_copy', 'n') == 'c');
//		$multi = ($toself) ? '[]':'';
//TODO check this logic
		$sf = ($toself) ? '_1':'';
//		$val = ($toself) ? $this->$this->Value[0] : $this->Value;
		$mod = $this->formdata->pwfmod;

		//returned value always array, even if 1 member(i.e. not $toself)
		$tmp = $mod->CreateInputEmail(
			$id, $this->formdata->current_prefix.$this->Id.'[]',
			htmlspecialchars($this->Value[0], ENT_QUOTES), 25, 128,
			$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId($sf).'"', $tmp);
		$ret = $this->SetClass($tmp, 'emailaddr');
		if ($toself) {
			$tid = $this->GetInputId('_2');
			$tmp = $mod->CreateInputCheckbox($id, $this->formdata->current_prefix.$this->Id.'[]', 1, 0, 'id="'.$tid.'"');
			$ret .= '<br />'.$this->SetClass($tmp);
			$tmp = '<label class ="" for="'.$tid.'">'.
				$this->GetProperty('send_user_label', $mod->Lang('title_send_me_a_copy')).'</label>';
			$ret .= '&nbsp;'.$this->SetClass($tmp);
		}
		return $ret;
	}

	public function Validate($id)
	{
		if ($this->ValidationType != 'none') {
			return parent::Validate($id);
		}
		$this->SetProperty('valid', TRUE);
		$this->ValidationMessage = '';
		return [TRUE,$this->ValidationMessage];
	}

	public function PreDisposeAction()
	{
		if ($this->HasValue()) {
			$htm = $this->GetProperty('headers_to_modify', 'f');
			foreach ($this->formdata->Fields as &$one) {
				if ($one->IsDisposition() && is_subclass_of($one, 'EmailBase')) {
					if ($htm == 'f' || $htm == 'b') {
						$one->SetProperty('email_from_address', $this->Value[0]);
					}
					if ($htm == 'r' || $htm == 'b') {
						$one->SetProperty('email_reply_to_address', $this->Value[0]);
					}
				}
			}
			unset($one);
		}
	}

	public function Dispose($id, $returnid)
	{
		if ($this->HasValue() &&
		($this->GetProperty('send_user_copy', 'n') == 'a' ||
		($this->GetProperty('send_user_copy', 'n') == 'c' && isset($this->Value[1]) && $this->Value[1] == 1))
		) {
			return $this->SendForm($this->Value[0], $this->GetProperty('email_subject'));
		} else {
			return [TRUE,''];
		}
	}
}
