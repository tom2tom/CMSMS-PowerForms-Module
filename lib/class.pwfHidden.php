<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfHidden extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasLabel = 0;
		$this->NeedsDiv = 0;
		$this->NonRequirableField = TRUE;
		$this->Type = 'Hidden';
		$this->IsSortable = FALSE;
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

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		if($this->Value !== FALSE)
			$val = $this->Value;
		else
			$val = $this->GetOption('value');

		if($this->GetOption('smarty_eval',0))
			$val = $mod->ProcessTemplateFromData($val);

		if($this->GetOption('browser_edit',0) && !empty($params['in_admin'])) //TODO deprecated
			$type = 'text';
		else
			$type = 'hidden';

		return '<input type="'.$type.'" name="'.$id.$this->formdata->current_prefix.$this->Id.'" value="'.$val.'"'.$this->GetCSSIdTag().' />';
	}

}

?>
