<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class EmailSender extends EmailBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'EmailSender';
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE);
		$mod = $this->formdata->formsmodule;
		$choices = array($mod->Lang('option_from')=>'f',$mod->Lang('option_reply')=>'r',$mod->Lang('option_both')=>'b');
		
		$main[] = array($mod->Lang('title_headers_to_modify'),
						$mod->CreateInputDropdown($id, 'fp_headers_to_modify', $choices, -1,
							$this->GetProperty('headers_to_modify', 'b')));
		$adv[] = array($mod->Lang('title_field_default_value'),
						$mod->CreateInputText($id, 'fp_default',
							$this->GetProperty('default'), 25, 1024));
		$adv[] = array($mod->Lang('title_clear_default'),
						$mod->CreateInputHidden($id, 'fp_clear_default', 0).
						$mod->CreateInputCheckbox($id, 'fp_clear_default', 1,
							$this->GetProperty('clear_default', 0)),
						$mod->Lang('help_clear_default'));
		$adv[] = array($mod->Lang('title_html5'),
						$mod->CreateInputHidden($id, 'fp_html5', 0).
						$mod->CreateInputCheckbox($id, 'fp_html5', 1,
							$this->GetProperty('html5', 0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id, &$params)
	{
		$this->SetEmailJS();
		if ($this->GetProperty('html5', 0)) {
			$addr = ($this->HasValue()) ? $this->Value : '';
			$place = 'placeholder="'.$this->GetProperty('default').'"';
		} else {
			$addr = ($this->HasValue()) ? $this->Value : $this->GetProperty('default');
			$place = '';
		}
		$tmp = $this->formdata->formsmodule->CreateInputEmail(
			$id, $this->formdata->current_prefix.$this->Id,
			htmlspecialchars($addr, ENT_QUOTES), 25, 128,
			$place.$this->GetScript());
		$tmp = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		return $this->SetClass($tmp, 'emailaddr');
	}

	public function PreDisposeAction()
	{
		if ($this->Value) {
			$htm = $this->GetProperty('headers_to_modify', 'b');
			foreach ($this->formdata->Fields as &$one) {
				if ($one->IsDisposition() && is_subclass_of($one, 'EmailBase')) {
					if ($htm == 'f' || $htm == 'b') {
						$one->SetProperty('email_from_name', $this->Value);
					}
					if ($htm == 'r' || $htm == 'b') { //TODO check 'b' can't match here
						$one->SetProperty('email_reply_to_name', $this->Value);
					}
				}
			}
			unset($one);
		}
	}
}
