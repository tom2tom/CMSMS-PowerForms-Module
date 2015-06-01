<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCaptcha extends pwfFieldBase
{
	var $defaulttemplate = '{$prompt}<br />{$captcha}';
	var $RealName = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->IsRequired = TRUE;
		$this->Type = 'Captcha';
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('captcha',$captcha->getCaptcha());
		$smarty->assign('prompt',$this->GetOption('prompt',$mod->Lang('captcha_prompt')));
		$test = method_exists($captcha,'NeedsInputField') ? $captcha->NeedsInputField() : TRUE;
		if($test)
		{
			//for captcha validation, input-object name must be as shown, not e.g. $this->formdata->current_prefix.$this->Id
//			$input = $mod->CustomCreateInputType($id,'captcha_input','',10,10,$this->GetCSSIdTag());
			$input = $mod->CustomCreateInputType($id,$this->formdata->current_prefix.$this->Id,'',10,10,$this->GetCSSIdTag());
			$smarty->assign('captcha_input',$input);
		}
		else
		{
			$hidden = $mod->CreateInputHidden($id,$this->formdata->current_prefix.$this->Id,1); //include field in post-submit walk
			$smarty->assign('captcha_input',$hidden);
		}
		$tpl = $this->GetOption('captcha_template',$this->defaulttemplate);
		if($this->GetOption('aslabel',0))
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

	function PrePopulateAdminForm($id)
	{
		$main = array();
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if($captcha)
		{
			unset($captcha);
			$main[] = array($mod->Lang('title_captcha_prompt'),
				$mod->CreateInputText($id,'opt_prompt',
					$this->GetOption('prompt',$mod->Lang('captcha_prompt')),60,120));
			$main[] = array($mod->Lang('title_captcha_wrong'),
				$mod->CreateInputText($id,'opt_wrongtext',
					$this->GetOption('wrongtext',$mod->Lang('captcha_wrong')),60,120));

			$adv[] = array($mod->Lang('title_captcha_label'),
				$mod->CreateInputHidden($id,'opt_aslabel',0).
				$mod->CreateInputCheckbox($id,'opt_aslabel',1,$this->GetOption('aslabel',0)),
				$mod->Lang('help_captcha_label')
				);
			$adv[] = array($mod->Lang('title_captcha_template'),
				$mod->CreateTextArea(FALSE,$id,$this->GetOption('captcha_template',$this->defaulttemplate),
					'opt_captcha_template','pwf_shortarea','','','',50,5),
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
		unset($mainArray[4]); //no helptext
		unset($advArray[0]); //no hide label
		unset($advArray[2]); //no field javascript
		unset($advArray[3]); //no field logic
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;
		$opt = $this->GetOption('captcha_template');
		if(!$opt)
		{
			$ret = FALSE;
			$messages[] = $this->formdata->formsmodule->Lang('TODO must supply');
		}
		$opt = $this->GetOption('prompt');
		if(!$opt)
		{
			$ret = FALSE;
			$messages[] = $this->formdata->formsmodule->Lang('TODO must supply');
		}
		$opt = $this->GetOption('wrongtext');
		if(!$opt)
		{
			$ret = FALSE;
			$messages[] = $this->formdata->formsmodule->Lang('TODO must supply');
		}
		$msg = ($ret)? '' : implode('<br />',$messages);
    	return array($ret,$msg);
	}

	function Validate($id)
	{
		//now it's safe to restore fieldname
		if($this->RealName)
		{
			$this->Name = $this->RealName;
			$this->RealName = FALSE;
		}
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		$captcha = $mod->getModuleInstance('Captcha');
		if(!$captcha) //should never happen
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('error_module_captcha');
		}
		elseif(!$captcha->CheckCaptcha($this->Value)) //upstream migrated any $params['captcha_input] to this
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $this->GetOption('wrongtext',
				$mod->Lang('captcha_wrong'));
		}
		return array($this->validated,$this->ValidationMessage);
	}
}

?>
