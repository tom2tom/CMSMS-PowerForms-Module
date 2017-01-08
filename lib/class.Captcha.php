<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Captcha extends FieldBase
{
	private $defaulttemplate = '{$prompt}<br />{$captcha}';
	private $RealName = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->Required = TRUE;
		$this->Type = 'Captcha';
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Captcha]';
		if ($as_string) {
			return $ret;
		} else {
			return array($ret);
		}
	}

	public function GetSynopsis()
	{
		$captcha = \cms_utils::get_module('Captcha');
		if ($captcha) {
			return '';
		}
		return $this->formdata->formsmodule->Lang('missing_module', 'Captcha');
	}

	public function AdminPopulate($id)
	{
		$captcha = \cms_utils::get_module('Captcha');
		if ($captcha) {
			unset($captcha);
		} else {
			return array('main'=>array($this->GetErrorMessage('err_module', 'Captcha')));
		}

		$except = array(
		'title_field_helptext',
		'title_field_helptoggle',
		'title_field_javascript',
		'title_field_resources',
		'title_hide_label'
		);
		list($main, $adv) = $this->AdminPopulateCommon($id, $except);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_captcha_prompt'),
						$mod->CreateInputText($id, 'fp_prompt',
							$this->GetProperty('prompt', $mod->Lang('captcha_prompt')), 60, 120));
		$main[] = array($mod->Lang('title_captcha_wrong'),
						$mod->CreateInputText($id, 'fp_wrongtext',
							$this->GetProperty('wrongtext', $mod->Lang('captcha_wrong')), 60, 120));
		$adv[] = array($mod->Lang('title_captcha_label'),
						$mod->CreateInputHidden($id, 'fp_aslabel', 0).
						$mod->CreateInputCheckbox($id, 'fp_aslabel', 1,
							$this->GetProperty('aslabel', 0)),
						$mod->Lang('help_captcha_label'));
		//setup to revert to default (a.k.a. 'sample') template
		list($button, $jsfunc) = Utils::CreateTemplateAction($mod, $id,
			'fp_captcha_template', $mod->Lang('title_create_sample_template'),
			$this->defaulttemplate);
		$this->jsfuncs[] = $jsfunc;

		$adv[] = array($mod->Lang('title_captcha_template'),
						$mod->CreateTextArea(FALSE, $id, $this->GetProperty('captcha_template', $this->defaulttemplate),
							'fp_captcha_template', 'pwf_shortarea', '', '', '', 50, 5),
						$mod->Lang('help_captcha_template').'<br /><br />'.$button);
		return array('main'=>$main,'adv'=>$adv);
	}

	public function AdminValidate($id)
	{
		$messages = array();
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}
		$mod = $this->formdata->formsmodule;
		$pt = $this->GetProperty('captcha_template');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('captchatemplateTODO'));
		}
		$pt = $this->GetProperty('prompt');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('captchapromptTODO'));
		}
		$pt = $this->GetProperty('wrongtext');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type', $mod->Lang('captchamsgTODO'));
		}
		$msg = ($ret) ? '' : implode('<br />', $messages);
		return array($ret,$msg);
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		$captcha = \cms_utils::get_module('Captcha');
		if (!$captcha) {
			return $mod->Lang('err_module', 'Captcha');
		}

		$tplvars = array(
			'captcha' => $captcha->getCaptcha(),
			'prompt' => $this->GetProperty('prompt', $mod->Lang('captcha_prompt')).
				$this->GetFormProperty('required_field_symbol', '*')
		);
		$test = method_exists($captcha, 'NeedsInputField') ? $captcha->NeedsInputField() : TRUE;
		if ($test) {
			//for captcha validation, input-object name must be as shown, not e.g. $this->formdata->current_prefix.$this->Id
//			$tmp = $mod->CreateInputText($id,'captcha_input','',10,10);
			$tmp = $mod->CreateInputText($id, $this->formdata->current_prefix.$this->Id, '', 10, 10);
			$tplvars['captcha_input'] = preg_replace('/id="\S+"/', 'id="'.$this->GetInputId().'"', $tmp);
		} else {
			$tplvars['captcha_input'] = $mod->CreateInputHidden($id, $this->formdata->current_prefix.$this->Id, 1); //include field in post-submit walk
		}
		$tpl = $this->GetProperty('captcha_template', $this->defaulttemplate);
		if ($this->GetProperty('aslabel', 0)) {
			$this->HideLabel = FALSE;
			$this->RealName = $this->Name;
			$tmp = Utils::ProcessTemplateFromData($mod, $tpl, $tplvars);
			$this->Name = $this->SetClass($tmp);
			return '';
		} else {
			$this->HideLabel = TRUE;
			$this->RealName = FALSE;
			$tmp = Utils::ProcessTemplateFromData($mod, $tpl, $tplvars);
			return $this->SetClass($tmp);
		}
	}

	public function Validate($id)
	{
		//now it's safe to restore fieldname TODO reinstate when no text is input (so no validation)
		if ($this->RealName) {
			$this->Name = $this->RealName;
			$this->RealName = FALSE;
		}

		$mod = $this->formdata->formsmodule;
		$captcha = \cms_utils::get_module('Captcha');
		if (!$captcha) { //should never happen
			$this->valid = FALSE;
			$this->ValidationMessage = $mod->Lang('err_module', 'Captcha');
		} elseif ($captcha->CheckCaptcha($this->Value)) { //upstream migrated $params['captcha_input] to $this->Value
			$this->valid = TRUE;
			$this->ValidationMessage = '';
		} else {
			$this->valid = FALSE;
			$this->ValidationMessage = $this->GetProperty('wrongtext',
				$mod->Lang('captcha_wrong'));
		}
		return array($this->valid,$this->ValidationMessage);
	}
}
