<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfHiddenField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasLabel = 0;
		$this->NeedsDiv = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'HiddenField';
		$this->IsSortable = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		if($this->Value !== FALSE)
		{
			$v = $this->Value;
		}
		else
		{
			$v = $this->GetOption('value');
		}

		if($this->GetOption('smarty_eval','0') == '1')
		{
			//process without cacheing
			$v = $mod->ProcessTemplateFromData($v);
		}

		if($this->GetOption('browser_edit','0') == '1' && $params['in_admin'] == 1)
		{
			$type = 'text';
		}
		else
		{
			$type = 'hidden';
		}

		return '<input type="'.$type.'" name="'.$id.'pwfp_'.$this->Id.'" value="'.$v.'"'.$this->GetCSSIdTag().' />';
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
				array($mod->Lang('title_value'),
            		$mod->CreateInputText($module_id,'opt_value',$this->GetOption('value'),25,1024))
		);
		$adv = array(
				array($mod->Lang('title_smarty_eval'),
				$mod->CreateInputHidden($module_id,'opt_smarty_eval',0).
				$mod->CreateInputCheckbox($module_id,'opt_smarty_eval',
            		1,$this->GetOption('smarty_eval',0))),
				array($mod->Lang('title_browser_edit'),
				$mod->CreateInputHidden($module_id,'opt_browser_edit',0).
				$mod->CreateInputCheckbox($module_id,'opt_browser_edit',
					1,$this->GetOption('browser_edit',0)))
		);
		return array('main'=>$main,'adv'=>$adv);
	}
	
	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray); //TODO hidden field may use logic?
	}
	
}

?>
