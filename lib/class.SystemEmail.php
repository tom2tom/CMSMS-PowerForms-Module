<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for system-generated emails (i.e. no interaction with the user or
//form prior to its submission) to any number of destinations (as a combination of to,cc,bcc)

namespace PWForms;

class SystemEmail extends EmailBase
{
	var $addressAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'SystemEmail';
	}

	public function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_address');
	}

	public function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_address');
	}

	public function DoOptionAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	public function DoOptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemoveOptionElement('destination_address',$indx);
			}
		}
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('to').': ';
		$dests = $this->GetOption('destination_address');
		if (is_array($dests)) {
			if (count($dests) > 1) {
				$ret.= count($dests).' '.$mod->Lang('recipients');
			} else {
				$pre = substr($dests[0],0,4);
				if ($pre == '|cc|')
					$ret = $mod->Lang('cc').': '.substr($dests[0],4);
				elseif ($pre == '|bc|')
					$ret = $mod->Lang('bcc').': '.substr($dests[0],4);
				else
					$ret.= $dests[0];
			}
		} elseif ($dests) {
			$pre = substr($dests,0,4);
			if ($pre == '|cc|')
				$ret = $mod->Lang('cc').': '.substr($dests,4);
			elseif ($pre == '|bc|')
				$ret = $mod->Lang('bcc').': '.substr($dests,4);
			else
				$ret .= $dests;
		} else {
			$ret.= $mod->Lang('unspecified');
		}
		$status = $this->TemplateStatus();
		if ($status)
			$ret.='<br />'.$status;
		return $ret;
	}

	public function GetDests($id,$row,$sel)
	{
		$id = \cms_htmlentities($id);
		$name = $id.$this->formdata->current_prefix.'mailto_'.$row; //must be distinct for each address
		$totypes = array ('to','cc','bc');
		$btns = array();
		for ($i=0; $i<3; $i++)
		{
			$text = '<input type="radio" id="'.$id.'mailto_'.$row.$i. //'pwfp_' removed from id
			'" class="cms_radio" style="margin-left:5px;" name="'.
			$name.'" value="'.$totypes[$i].'"';
			if ($sel == $totypes[$i])
				$text .= ' checked="checked"';
			$text .= ' />';
			$btns[] = $text;
		}
		return $btns;
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id,FALSE,FALSE);
		if ($this->addressAdd) {
			$this->AddOptionElement('destination_address','');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetOptionRef('destination_address');
		if ($opt) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_destination_address'),
				$mod->Lang('to'),
				$mod->Lang('cc'),
				$mod->Lang('bcc'),
				$mod->Lang('title_select')
				);
			foreach ($opt as $i=>&$one) {
				if (strncmp($one,'|cc|',4) == 0) {
					$totype = 'cc';
					$addr = substr($one,4);
				} elseif (strncmp($one,'|bc|',4) == 0) {
					$totype = 'bc';
					$addr = substr($one,4);
				} else {
					$totype = 'to';
					$addr = $one; //maybe empty
				}
				$btns = self::GetDests($id,$i,$totype);

				$dests[] = array(
					$mod->CreateInputText($id,'opt_destination_address'.$i,$addr,50,128),
					array_shift($btns),
					array_shift($btns),
					array_shift($btns),
					$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_destination_address'),$dests);
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests,'funcs'=>$funcs,'extra'=>$extra);
		} else {
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('destination')));
			return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$addrs = $this->GetOptionRef('destination_address');
		if ($addrs) {
			foreach ($addrs as $i=>&$one) {
				if (!$one)
					$this->RemoveOptionElement('destination_address',$i);
			}
			unset($one);
		}
		$this->PostAdminActionEmail($params);
	}

	public function AdminValidate($id)
	{
		$messages = array();
		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		$opt = $this->GetOption('email_from_address');
		if ($opt) {
			list($rv,$msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('source'));
		}

		$dests = $this->GetOptionRef('destination_address');
		if ($dests) {
			foreach ($dests as &$one) {
				list($rv,$msg) = $this->validateEmailAddr($one);
			 	if (!$rv) {
					$ret = FALSE;
					$messages[] = $msg;
				}
			}
			unset($one);
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('destination'));
		}

		$msg = ($ret)? '' : implode('<br />',$messages);
		return array($ret,$msg);
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

	public function Dispose($id,$returnid)
	{
		$dests = $this->GetOptionRef('destination_address');
		return $this->SendForm($dests,$this->GetOption('email_subject'));
	}

}

