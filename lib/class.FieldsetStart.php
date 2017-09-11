<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class FieldsetStart extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'FieldsetStart';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + ['legend' => 12];
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
		$except = [
		'title_field_validation',
		'title_field_helptext',
		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval',
		'title_hide_label',
		];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_legend'),
					$mod->CreateInputText($id, 'fp_legend',
						$this->GetProperty('legend'), 40)];
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
