<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class allows sending an email with subject, sender-name, sender-email
//and receiver(s) taken from (specified) other fields

namespace PWForms;

class CustomEmail extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'CustomEmail';
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$opt = $this->GetPropArray('destination_address');
		if ($opt)
			$ret = $mod->Lang('to').': '.count($opt).' '.$mod->Lang('fields');
		else
			$ret = $mod->Lang('missing_type',$mod->Lang('destination'));
		$status = $this->TemplateStatus();
		if ($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	public function AdminPopulate($id)
	{
		$displayfields = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInForm)
				$displayfields[$one->GetName()] = $one->GetId();
		}
		unset($one);
		$destfields = array();
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			foreach ($displayfields as $k=>$v) {
				if (in_array($v,$opt))
					$destfields[$k] = $v;
			}
		}
		$mod = $this->formdata->formsmodule;
		$choices = array($mod->Lang('select_one') => '') + $displayfields;

		list($main,$adv,$jsfuncs,$extra) = $this->AdminPopulateCommonEmail($id,FALSE,TRUE,FALSE);
		$waslast = array_pop($main); //keep only the default to-type selector
		$main[] = array($mod->Lang('title_subject_field'),
						$mod->CreateInputDropdown($id,'fp_email_subject',$choices,-1,
						$this->GetProperty('email_subject')));
		$main[] = array($mod->Lang('title_from_field'),
						$mod->CreateInputDropdown($id,'fp_email_from_name',$choices,-1,
						$this->GetProperty('email_from_name',$mod->Lang('friendly_name'))));
		$main[] = array($mod->Lang('title_from_address_field'),
						$mod->CreateInputDropdown($id,'fp_email_from_address',$choices,-1,
						$this->GetProperty('email_from_address')));
		$main[] = array($mod->Lang('title_destination_field'),
						$mod->CreateInputSelectList($id,'fp_destination_address'.$i,$displayfields,
						$destfields,5));
		$main[] = $waslast;
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$jsfuncs,'extra'=>$extra);
	}

	public function PostAdminAction(&$params)
	{
		$this->PostAdminActionEmail($params);
	}

	public function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		if (!$this->GetProperty('email_subject')) {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_subject'));
		}
		if (!$this->GetProperty('email_from_name')) {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_name'));
		}
		$opt = $this->GetProperty('email_from_address');
		if ($opt) {
			list($rv,$msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_email_from_address'));
		}
		$opt = $this->GetPropArray('destination_address');
		if ($opt) {
			list($rv,$msg) = $this->validateEmailAddr($opt);
			if (!$rv) {
				$ret = FALSE;
				$messages[] = $msg;
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('no_field_assigned',$mod->Lang('title_destination_address'));
		}
		$msg = $this->TemplateStatus();
		if ($msg) {
			$ret = FALSE;
			$messages[] = $msg;
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	public function Dispose($id, $returnid)
	{
		$dests = $this->GetPropArray('destination_address'); //TODO in this case, field id's ?
		if ($dests) {
			$formdata = $this->formdata;

			$senderfld = $this->GetProperty('email_from_name'); //TODO confirm this is field_id?
			$fld = $formdata->Fields[$senderfld];
			$this->SetProperty('email_from_name',$fld->GetDisplayableValue());

			$fromfld = $this->GetProperty('email_from_address');
			$fld = $formdata->Fields[$fromfld];
			$this->SetProperty('email_from_address',$fld->GetDisplayableValue());

			$addrs = array();
			foreach ($dests as $field_id) {
				$fld = $formdata->Fields[$field_id];
				$value = $fld->GetDisplayableValue();
				if (strpos($value,',') !== FALSE)
					$addrs = $addrs + explode(',',$value);
				else
					$addrs[] = $value;
			}

/*			$subjectfld = $this->GetProperty('email_subject');
			$fld = $formdata->Fields[$subjectfld];
			$this->SetProperty('email_subject',$fld->GetDisplayableValue());

			$ret = $this->SendForm($addrs,$this->GetProperty('email_subject'));

			$this->SetProperty('email_subject',$subjectfld);
*/
			$fld = $formdata->Fields[$this->GetProperty('email_subject')];
			$ret = $this->SendForm($addrs,$fld->GetDisplayableValue()); //TODO check value(subject) is ok

			$this->SetProperty('email_from_name',$senderfld);
			$this->SetProperty('email_from_address',$fromfld);

			return $ret;
		}
		return array(FALSE,$this->formdata->formsmodule->Lang('err_address',''));
	}
}
