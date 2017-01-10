<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FieldsetStart extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'FieldsetStart';
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Begin Fieldset: '.$this->Value.']';
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, TRUE, FALSE);
		$mod = $this->formdata->formsmodule;
		$main[] = [$mod->Lang('title_legend'),
						$mod->CreateInputText($id, 'fp_legend',
							$this->GetProperty('legend'), 50)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$tmp = '<fieldset id="'.$this->GetInputId().'"';
		$ret = $this->SetClass($tmp);
		$opt = $this->GetScript();
		if ($opt) {
			$ret .= ' '.$opt;
		}
		$ret .= '>';
		$opt = $this->GetProperty('legend');
		if ($opt) {
			$ret .= '<legend>'.$opt.'</legend>';
		}
		return $ret;
	}
}
