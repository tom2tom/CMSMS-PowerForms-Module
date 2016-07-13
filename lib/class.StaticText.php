<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class StaticText extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->Type = 'StaticText';
	}

	public function GetFieldStatus()
	{
		return $this->formdata->formsmodule->Lang('text_length',strlen($this->GetOption('text')));
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = '[Static Text Field]';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);

		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_text'),
						$mod->CreateTextArea((get_preference(get_userid(),'use_wysiwyg')),$id,
							$this->GetOption('text'),'opt_text','pageheadtags'));
		$adv[] = array($mod->Lang('title_smarty_eval'),
						$mod->CreateInputHidden($id,'opt_smarty_eval',0).
						$mod->CreateInputCheckbox($id,'opt_smarty_eval',1,
							$this->GetOption('smarty_eval',0)));
		return array('main'=>$main,'adv'=>$adv);
	}
	
	public function Populate($id,&$params)
	{
		if ($this->GetOption('smarty_eval',0))
			$this->SetSmartyEval(TRUE);

		return $this->GetOption('text');
	}

}