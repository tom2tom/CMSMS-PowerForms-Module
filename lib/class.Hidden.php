<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Hidden extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->HasLabel = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'Hidden';
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE,FALSE); //TODO hidden field may use logic?

		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_value'),
						$mod->CreateInputText($id,'fp_value',
							$this->GetProperty('value'),25,1024));
		$adv[] = array($mod->Lang('title_smarty_eval'),
						$mod->CreateInputHidden($id,'fp_smarty_eval',0).
						$mod->CreateInputCheckbox($id,'fp_smarty_eval',1,
							$this->GetProperty('smarty_eval',0))); //TODO check whether to treat hidden as sub-template
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;

		if ($this->Value || is_numeric($this->Value)) {
			if (!is_numeric($this->Value) && $this->GetProperty('smarty_eval',0)) {
				$tplvars = array();
				$val = Utils::ProcessTemplateFromData($mod,$this->Value,$tplvars);
			} else {
				$val = $this->Value;
			}
		} else {
			$val = '';
		}

//		if ($this->GetProperty('browser_edit',0) && !empty($params['in_admin'])) //TODO deprecated
//			$type = 'text';
//		else
			$type = 'hidden';

		$tmp = '<input type="'.$type.'" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.'" value="'.$val.'" />';
		return $this->SetClass($tmp);
	}
}
