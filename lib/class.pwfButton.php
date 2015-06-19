<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfButton extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->Type = 'Button';
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_button_text'),
						$mod->CreateInputText($id,'opt_text',
							$this->GetOption('text'),40));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$tmp = '<input type="button" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.
			'" value="'.$this->GetOption('text').'"'.$this->GetScript().' />';
		return $this->SetClass($tmp);
	}

}

?>
