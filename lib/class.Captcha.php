<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

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

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'aslabel' => 10,
		'prompt' => 12,
		'wrongtext' => 12,
		'captcha_template' => 13,
		];
	}

	public function GetSynopsis()
	{
		$captcha = \cms_utils::get_module('Captcha');
		if ($captcha) {
			return '';
		}
		return $this->formdata->pwfmod->Lang('missing_module', 'Captcha');
	}

	public function GetDefaultTemplate()
	{
		return $this->defaulttemplate;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Captcha]';
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		$captcha = \cms_utils::get_module('Captcha');
		if ($captcha) {
			unset($captcha);
		} else {
			return ['main'=>[$this->GetErrorMessage('err_module', 'Captcha')]];
		}

		$except = [
		'title_field_helptext',
		'title_field_helptoggle',
		'title_field_javascript',
		'title_field_resources',
		'title_hide_label'
		];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_captcha_prompt'),
					$mod->CreateInputText($id, 'fp_prompt',
						$this->GetProperty('prompt', $mod->Lang('captcha_prompt')), 60, 120)];
		$main[] = [$mod->Lang('title_captcha_wrong'),
					$mod->CreateInputText($id, 'fp_wrongtext',
						$this->GetProperty('wrongtext', $mod->Lang('captcha_wrong')), 60, 120)];
		$adv[] = [$mod->Lang('title_captcha_label'),
					$mod->CreateInputHidden($id, 'fp_aslabel', 0).
					$mod->CreateInputCheckbox($id, 'fp_aslabel', 1,
						$this->GetProperty('aslabel', 0)),
					$mod->Lang('help_captcha_label')];

		$button = Utils::SetTemplateButton('captcha_template',
			$mod->Lang('title_create_sample_template'));
		$adv[] = [$mod->Lang('title_captcha_template'),
					$mod->CreateTextArea(FALSE, $id, $this->GetProperty('captcha_template', $this->defaulttemplate),
						'fp_captcha_template', 'pwf_shortarea', '', '', '', 50, 5),
					$mod->Lang('help_captcha_template').'<br /><br />'.$button];
		$this->Jscript->jsloads[] = <<<EOS
 $('#get_captcha_template').click(function () {
  populate_template('{$id}fp_captcha_template');
 });
EOS;
		$this->Jscript->jsfuncs[] = Utils::SetTemplateScript($mod, $id, ['type'=>'captcha', 'field_id'=>$this->Id]);

		return ['main'=>$main,'adv'=>$adv];
	}

	public function AdminValidate($id)
	{
		$messages = [];
		list($ret, $msg) = parent::AdminValidate($id);
		if (!$ret) {
			$messages[] = $msg;
		}
		$mod = $this->formdata->pwfmod;
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
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$captcha = \cms_utils::get_module('Captcha');
		if (!$captcha) {
			return $mod->Lang('err_module', 'Captcha');
		}

		$tplvars = [
			'captcha' => $captcha->getCaptcha(),
			'prompt' => $this->GetProperty('prompt', $mod->Lang('captcha_prompt')).
				$this->GetFormProperty('required_field_symbol', '*')
		];
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

		$mod = $this->formdata->pwfmod;
		$captcha = \cms_utils::get_module('Captcha');
		if (!$captcha) { //should never happen
			$val = FALSE;
			$this->ValidationMessage = $mod->Lang('err_module', 'Captcha');
		} elseif ($captcha->CheckCaptcha($this->Value)) { //upstream migrated $params['captcha_input] to $this->Value
			$val = TRUE;
			$this->ValidationMessage = '';
		} else {
			$val = FALSE;
			$this->ValidationMessage = $this->GetProperty('wrongtext',
				$mod->Lang('captcha_wrong'));
		}
		$this->SetProperty('valid', $val);
		return [$val, $this->ValidationMessage];
	}
}
