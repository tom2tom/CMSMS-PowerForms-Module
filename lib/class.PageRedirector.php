<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class PageRedirector extends FieldBase
{
	var $addressAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'PageRedirector';
	}

	public function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_destination');
	}

	public function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_destination');
	}

	public function DoOptionAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	public function DoOptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemoveOptionElement('destination_page',$indx);
				$this->RemoveOptionElement('destination_subject',$indx);
			}
		}
	}

	public function GetFieldStatus()
	{
		$opt = $this->GetOption('destination_page');
		if (is_array($opt))
			$num = count($opt);
		elseif ($opt)
			$num = 1;
		else
			$num = 0;
		return $this->formdata->formsmodule->Lang('destination_count',$num);
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		if ($this->HasValue())
			$ret = $this->GetOptionElement('destination_page',$this->Value);
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id,'opt_select_one',
							$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
		if ($this->addressAdd) {
			$this->AddOptionElement('destination_page','');
			$this->AddOptionElement('destination_subject','');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetOptionRef('destination_page');
		if ($opt) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_selection_subject'),
				$mod->Lang('title_destination_page'),
				$mod->Lang('title_select')
				);
			foreach ($opt as $i=>&$one) {
				$dests[] = array(
					$mod->CreateInputText($id,'opt_destination_subject'.$i,
						$this->GetOptionElement('destination_subject',$i),30,128),
					Utils::CreateHierarchyPulldown($mod,$id,'opt_destination_page'.$i,$one),
					$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_director_details'),$dests);
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('page')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$pages = $this->GetOptionRef('destination_page');
		if ($pages) {
			foreach ($pages as $i=>&$one) {
				if (!$one || !$this->GetOptionElement('destination_subject',$i)) {
					$this->RemoveOptionElement('destination_page',$i);
					$this->RemoveOptionElement('destination_subject',$i);
				}
			}
			unset($one);
		}
	}

	public function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;

		if (!$this->GetOption('destination_page')) {
			$ret = FALSE;
			$mod = $this->formdata->formsmodule;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('page'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		$pages = $this->GetOptionRef('destination_subject');
		if ($pages) {
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one')) => -1)
				+ array_flip($pages);
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Dispose($id,$returnid)
	{
		//TODO ensure all other dispositions are run before this
//		$this->formdata->formsmodule->RedirectContent($this->GetOptionElement('destination_page',$this->Value));
		$page = $this->GetOptionElement('destination_page',$this->Value);
		if ($page >= 0) {
			$this->formdata->Options['redirect_page'] = $page;
			$this->formdata->Options['submit_action'] = 'redir';
			return array(TRUE,'');
		}
		$mod = $this->formdata->formsmodule;
		return array(FALSE,$mod->Lang('missing_type',$mod->Lang('page')));
	}

}
