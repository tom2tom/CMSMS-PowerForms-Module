<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCaptcha extends pwfFieldBase
{
	$defaulttemplate;
	$RealName = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'Captcha';
		$this->defaulttemplate = '{$captcha_image}<br />{$captcha_prompt}'; //TODO
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		$smarty->assign('captcha_image',$captcha->getCaptcha());
		$smarty->assign('captcha_prompt',$this->GetOption('captcha_prompt',$mod->Lang('captcha_prompt'));
		$test = method_exists($captcha,'NeedsInputField') ? $captcha->NeedsInputField() : TRUE;
		if($test)
		{
			//for captcha validation, input-object name must be as shown, not e.g. 'pwfp_'.$this->Id
			$input = $mod->CustomCreateInputType($id,'captcha_input',10,10,$this->GetCSSIdTag());
			$smarty->assign('captcha_input',$input);
		}
		else
			$smarty->assign('captcha_input','');
		$tpl = $this->GetOption('captcha_template',$this->defaulttemplate);
		if($this->GetOption('captcha_label',0))
		{
			$this->HideLabel = FALSE;
			$this->RealName = $this->Name;
			$this->Name = $mod->ProcessTemplateFromData($tpl);
			return '';
		}
		else
		{
			$this->HideLabel = TRUE;
			$this->RealName = FALSE;
			return $mod->ProcessTemplateFromData($tpl);
		}
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Captcha]';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function GetFieldStatus()
	{
		return $this->ValidationMessage;
	}

	function PrePopulateAdminForm($module_id)
	{
		$main = array();
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if($captcha)
		{
			unset($captcha);
			$main[] = array($mod->Lang('title_captcha_prompt'),
				$mod->CreateInputText($module_id,'opt_captcha_prompt',
					$this->GetOption('captcha_prompt',$mod->Lang('captcha_prompt')),60,120));
			$main[] = array($mod->Lang('title_captcha_wrong'),
				$mod->CreateInputText($module_id,'opt_captcha_wrong',
					$this->GetOption('captcha_wrong',$mod->Lang('captcha_wrong')),60,120));

			$adv[] = array($mod->Lang('title_captcha_label'),
				$mod->CreateInputHidden($module_id,'opt_captcha_label',0).
				$mod->CreateInputCheckbox($module_id,'opt_captcha_label',1,$this->GetOption('captcha_label',0)),
				$mod->Lang('help_captcha_label')
				);
			$adv[] = array($mod->Lang('title_captcha_template'),
				$mod->CreateTextArea(FALSE,$module_id,$this->GetOption('captcha_template',$this->defaulttemplate),
					'opt_captcha_template','pwf_shortarea','','','',50,8),
				$mod->Lang('help_captcha_template')
				);
			return array('main'=>$main,'adv'=>$adv);
		}
		else //should never happen
		{
			$main[] = array($mod->Lang('error_module_captcha'),
				$mod->Lang('captcha_not_installed'));
			return array('main'=>$main);
		}
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		unset($mainArray[3]); //no helptext
		unset($advArray[0]); //no hide label
		unset($advArray[3]); //no field javascript
		unset($advArray[4]); //no field logic
	}

	function PostFieldSaveProcess(&$params)
	{
		if($this->RealName)
			$this->Name = this->RealName;
	}

	function Validate()
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if(!$captcha) //should never happen
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('error_module_captcha');
		}
		elseif(!$captcha->CheckCaptcha($this->Value)) //TODO $params['captcha_input]
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $this->GetOption('captcha_wrong',
				$mod->Lang('captcha_wrong'));
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
