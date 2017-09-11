<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class PageRedirector extends FieldBase
{
	private $addressAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = TRUE;
		$this->IsInput = TRUE;
		$this->MultiChoice = TRUE;
		$this->Type = 'PageRedirector';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		$ret = parent::GetMutables($nobase) + [
		'select_label' => 12,
		];

		$mkey1 = 'destination_page';
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
		$opt = $this->GetPropArray('destination_page');
		$num = ($opt) ? count($opt) : 0;
		return $this->formdata->pwfmod->Lang('destination_count', $num);
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
		return $this->GetPropArray('destination_page') != FALSE;
	}

	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('destination_page', $indx);
				$this->RemovePropIndexed('destination_subject', $indx);
			}
		}
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			$ret = $this->GetPropIndexed('destination_page', $this->Value);
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
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;

		$main[] = [$mod->Lang('title_select_one_message'),
					$mod->CreateInputText($id, 'fp_select_label',
						$this->GetProperty('select_label', $mod->Lang('select_one')), 30, 128)];
		if ($this->addressAdd) {
			$this->AddPropIndexed('destination_page', '');
			$this->AddPropIndexed('destination_subject', '');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetPropArray('destination_page');
		if ($opt) {
			$dests = [];
			$dests[] = [
				$mod->Lang('title_selection_subject'),
				$mod->Lang('title_destination_page'),
				$mod->Lang('title_select')
				];
			foreach ($opt as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = [
					$mod->CreateInputText($id, 'fp_destination_subject'.$arf,
						$this->GetPropIndexed('destination_subject', $i), 30, 128),
					Utils::CreateHierarchyPulldown($mod, $id, 'fp_destination_page'.$arf, $one),
					$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
			unset($one);
			$this->MultiComponent = TRUE;
			return ['main'=>$main,'adv'=>$adv,'table'=>$dests];
		} else {
			$this->MultiComponent = FALSE;
			$main[] = ['','',$mod->Lang('missing_type', $mod->Lang('page'))];
			return ['main'=>$main,'adv'=>$adv];
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$pages = $this->GetPropArray('destination_page');
		if ($pages) {
			foreach ($pages as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('destination_subject', $i)) {
					$this->RemovePropIndexed('destination_page', $i);
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

		if (!$this->GetProperty('destination_page')) {
			$ret = FALSE;
			$mod = $this->formdata->pwfmod;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('page'));
		}
		$msg = ($ret)?'':implode('<br />', $messages);
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$pages = $this->GetPropArray('destination_subject');
		if ($pages) {
			$mod = $this->formdata->pwfmod;
			$choices = [' '.$this->GetProperty('select_label', $mod->Lang('select_one')) => -1]
				+ array_flip($pages);
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Dispose($id, $returnid)
	{
		//TODO ensure all other dispositions are run before this
//		$this->formdata->pwfmod->RedirectContent($this->GetPropIndexed('destination_page',$this->Value));
		$page = $this->GetPropIndexed('destination_page', $this->Value);
		if ($page >= 0) {
			$this->formdata->XtraProps['redirect_page'] = $page;
			$this->formdata->XtraProps['submit_action'] = 'redir';
			return [TRUE,''];
		}
		$mod = $this->formdata->pwfmod;
		return [FALSE,$mod->Lang('missing_type', $mod->Lang('page'))];
	}
}
