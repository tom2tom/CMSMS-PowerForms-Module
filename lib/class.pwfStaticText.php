<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfStaticTextField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'StaticTextField';
	}

	function GetFieldInput($id,&$params)
	{
		if($this->GetOption('smarty_eval','0') == '1')
		{
			$this->SetSmartyEval(TRUE);
		}
		return $this->GetOption('text');
	}

	function GetFieldStatus()
	{
		return $this->formdata->formsmodule->Lang('text_length',strlen($this->GetOption('text')));
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Static Text Field]';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
				array($mod->Lang('title_text'),
				$mod->CreateTextArea((get_preference(get_userid(),'use_wysiwyg')=='1'),$module_id,$this->GetOption('text'),'opt_text','pageheadtags'))
		);
		$adv = array(
				array($mod->Lang('title_smarty_eval'),
				$mod->CreateInputCheckbox($module_id,'opt_smarty_eval',
            		'1',$this->GetOption('smarty_eval','0')))
		);
		return array('main'=>$main,'adv'=>$adv);
	}
}

?>
