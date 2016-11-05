<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file by Jeremy Bass <jeremyBass@cableone.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class supplies content from a template

namespace PWForms;

class ByTemplate extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
//		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
//		$this->HasLabel = FALSE;
//		$this->IsInput = TRUE; TODO runtime property
		$this->NeedsDiv = FALSE;
		$this->Type = 'ByTemplate';
	}

	//?SetValue()

	public function DisplayableValue($as_string=TRUE)
	{
		$formdata = $this->formdata;

		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				if ($as_string)
					return implode($this->GetFormProperty('list_delimiter',','),$this->Value);
				else {
					$ret = $this->Value;
					return $ret; //a copy
				}
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_tag'),
						$mod->CreateInputText($id,'fp_value',$this->GetProperty('value'),100,1024),
						$mod->Lang('help_tag'));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$tplvars = array();
		$tplvars['FBid'] = $id.$this->formdata->current_prefix.$this->Id;
		// for selected... what to do here TODO
		// for things like checked="checked" on the back page
		$tplvars['FBvalue'] = $this->Value;

		$val = $this->GetProperty('value');
		return Utils::ProcessTemplateFromData($this->formdata->formsmodule,$val,$tplvars);
	}
}
