<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class is for system-generated emails (i.e. no interaction with the user or
//form prior to its submission) to any number of destinations (as a combination of to,cc,bcc)

namespace PWForms;

class SystemEmail extends EmailBase
{
	private $addressAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->IsDisposition = TRUE;
		$this->MultiComponent = TRUE;
		$this->Type = 'SystemEmail';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		$ret = parent::GetMutables($nobase);
		$mkey1 = 'destination_address';
		$mkey2 = 'address_type';
		if ($actual) {
			$opt = $this->GetPropArray($mkey1);
			if ($opt) {
				$suff = array_keys($opt);
			} else {
				return $ret;
			}
		} else {
			$suff = ['*']; //range(1, 10); //'any' relevant match
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
		$mod = $this->formdata->pwfmod;
		$ret = $mod->Lang('to').': ';
		$dests = $this->GetPropArray('destination_address');
		if ($dests) {
			$c = count($dests);
			if ($c > 1) {
				$ret .= $c.' '.$mod->Lang('recipients');
			} else {
				$type = $this->GetPropIndexed('address_type', 1, 'to');
				if ($type == 'cc') {
					$ret = $mod->Lang('cc').': ';
				} elseif ($type == 'bc') {
					$ret = $mod->Lang('bcc').': ';
				}
				$ret .= $dests[1];
			}
		} else {
			$ret .= $mod->Lang('unspecified');
		}
		$status = $this->TemplateStatus();
		if ($status) {
			$ret .= '<br />'.$status;
		}
		return $ret;
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->pwfmod->Lang('add_address');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->pwfmod->Lang('delete_address');
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
				$this->RemovePropIndexed('address_type', $indx);
			}
		}
	}

	public function AdminPopulate($id)
	{
$adbg = $this; //DEBUG
		$mod = $this->formdata->pwfmod;
		list($main, $adv, $extra) = $this->AdminPopulateCommonEmail($id, FALSE, FALSE, FALSE);

		if ($this->addressAdd) {
			$this->AddPropIndexed('destination_address', '');
			$this->AddPropIndexed('address_type', 'to');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			$totypes = ['to', 'cc', 'bc'];
			$dests = [];
			$dests[] = [
				$mod->Lang('title_destination_address'),
				$mod->Lang('to'),
				$mod->Lang('cc'),
				$mod->Lang('bcc'),
				$mod->Lang('title_select')
				];
			foreach ($opt as $i=>&$one) {
				$arf = '['.$i.']';

				$totype = $this->GetPropIndexed('address_type', $i, 'to');
				$btns = [];
				for ($c=0; $c<3; $c++) {
					$t = '<input type="radio" class="cms_radio" name="'.$id.'fp_address_type'.$arf.
					'" id="'.$id.'address_type'.$i.$c.'" value="'.$totypes[$c].'"';
					if ($totype == $totypes[$c]) {
						$t .= ' checked="checked"';
					}
					$t .= ' style="margin-left:5px;" />';
					$btns[] = $t;
				}
				$dests[] = [
					$mod->CreateInputText($id, 'fp_destination_address'.$arf, $one, 50, 128),
					$btns[0],
					$btns[1],
					$btns[2],
					$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
			unset($one);
			$this->MultiComponent = TRUE;
			return ['main'=>$main,'adv'=>$adv,'table'=>$dests,'extra'=>$extra];
		} else {
			$this->MultiComponent = FALSE;
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
				if (!$one) {
					$this->RemovePropIndexed('destination_address', $i);
					$this->RemovePropIndexed('address_type', $i);
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
		$opt = $this->GetProperty('email_from_address');
		if ($opt !== '') {
			$opt = filter_var(trim($opt), FILTER_SANITIZE_EMAIL);
			list($rv, $msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('source'));
		}

		$dests = $this->GetPropArray('destination_address');
		if ($dests) {
			foreach ($dests as &$one) {
				if ($one !== '') {
					$one = filter_var(trim($one), FILTER_SANITIZE_EMAIL);
				}
				list($rv, $msg) = $this->validateEmailAddr($one);
				if (!$rv) {
					$ret = FALSE;
					$messages[] = $msg;
				}
			}
			unset($one);
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('destination'));
		}

		$msg = ($ret)? '' : implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function SetFromAddress()
	{
		return FALSE;
	}

	public function SetReplyToName()
	{
		return FALSE;
	}

	public function SetReplyToAddress()
	{
		return FALSE;
	}

	public function Dispose($id, $returnid)
	{
		$dests = $this->GetPropArray('destination_address');
		return $this->SendForm($dests, $this->GetProperty('email_subject'));
	}
}
