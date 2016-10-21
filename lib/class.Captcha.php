<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Captcha extends FieldBase
{
	private $defaulttemplate = '{$prompt}<br />{$captcha}';
	private $RealName = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->Required = TRUE;
		$this->MultiPopulate = FALSE;
		$this->Type = 'Captcha';
	}

	public function GetFieldStatus()
	{
		return $this->ValidationMessage; //TODO useless
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = '[Captcha]';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if ($captcha) {
			unset($captcha);
		} else {
			return array('main'=>array($this->GetErrorMessage('err_module_captcha')));
		}

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$this->RemoveAdminField($main,$mod->Lang('title_field_helptext'));
		$main[] = array($mod->Lang('title_captcha_prompt'),
						$mod->CreateInputText($id,'pdt_prompt',
							$this->GetProperty('prompt',$mod->Lang('captcha_prompt')),60,120));
		$main[] = array($mod->Lang('title_captcha_wrong'),
						$mod->CreateInputText($id,'pdt_wrongtext',
							$this->GetProperty('wrongtext',$mod->Lang('captcha_wrong')),60,120));
		$this->RemoveAdminField($adv,$mod->Lang('title_hide_label'));
		$this->RemoveAdminField($adv,$mod->Lang('title_field_helptoggle'));
		$this->RemoveAdminField($adv,$mod->Lang('title_field_javascript'));
		$this->RemoveAdminField($adv,$mod->Lang('title_field_resources'));
		$adv[] = array($mod->Lang('title_captcha_label'),
						$mod->CreateInputHidden($id,'pdt_aslabel',0).
						$mod->CreateInputCheckbox($id,'pdt_aslabel',1,
							$this->GetProperty('aslabel',0)),
						$mod->Lang('help_captcha_label'));
		//setup to revert to default (a.k.a. 'sample') template
		list($button,$jsfunc) = Utils::CreateTemplateAction($mod,$id,
			'pdt_captcha_template',$mod->Lang('title_create_sample_template'),
			$this->defaulttemplate);
		$adv[] = array($mod->Lang('title_captcha_template'),
						$mod->CreateTextArea(FALSE,$id,$this->GetProperty('captcha_template',$this->defaulttemplate),
							'pdt_captcha_template','pwf_shortarea','','','',50,5),
						$mod->Lang('help_captcha_template').'<br /><br />'.$button);
		return array('main'=>$main,'adv'=>$adv,'funcs'=>array($jsfunc));
	}

	public function AdminValidate($id)
	{
		$mod = $this->formdata->formsmodule;
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;
		$pt = $this->GetProperty('captcha_template');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('captchatemplateTODO'));
		}
		$pt = $this->GetProperty('prompt');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('captchapromptTODO'));
		}
		$pt = $this->GetProperty('wrongtext');
		if (!$pt) {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('captchamsgTODO'));
		}
		$msg = ($ret) ? '' : implode('<br />',$messages);
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		$tplvars = array(
			'captcha' => $captcha->getCaptcha(),
			'prompt' => $this->GetProperty('prompt',$mod->Lang('captcha_prompt')).
				$this->GetFormProperty('required_field_symbol','*')
		);
		$test = method_exists($captcha,'NeedsInputField') ? $captcha->NeedsInputField() : TRUE;
		if ($test) {
			//for captcha validation, input-object name must be as shown, not e.g. $this->formdata->current_prefix.$this->Id
//			$tmp = $mod->CreateInputText($id,'captcha_input','',10,10);
			$tmp = $mod->CreateInputText($id,$this->formdata->current_prefix.$this->Id,'',10,10);
			$tplvars['captcha_input'] = preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		} else {
			$tplvars['captcha_input'] = $mod->CreateInputHidden($id,$this->formdata->current_prefix.$this->Id,1); //include field in post-submit walk
		}
		$tpl = $this->GetProperty('captcha_template',$this->defaulttemplate);
		if ($this->GetProperty('aslabel',0)) {
			$this->HideLabel = FALSE;
			$this->RealName = $this->Name;
			$tmp = Utils::ProcessTemplateFromData($mod,$tpl,$tplvars);
			$this->Name = $this->SetClass($tmp);
			return '';
		} else {
			$this->HideLabel = TRUE;
			$this->RealName = FALSE;
			$tmp = Utils::ProcessTemplateFromData($mod,$tpl,$tplvars);
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
		$this->valid = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if (!$captcha) { //should never happen
			$this->valid = FALSE;
			$this->ValidationMessage = $mod->Lang('err_module_captcha');
		} elseif (!$captcha->CheckCaptcha($this->Value)) { //upstream migrated any $params['captcha_input] to this
			$this->valid = FALSE;
			$this->ValidationMessage = $this->GetProperty('wrongtext',
				$mod->Lang('captcha_wrong'));
		}
		return array($this->valid,$this->ValidationMessage);
	}
}
