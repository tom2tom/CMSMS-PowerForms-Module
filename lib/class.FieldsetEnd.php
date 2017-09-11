<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class FieldsetEnd extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HideLabel = TRUE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'FieldsetEnd';
	}

/*	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase);
	}
*/
	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[End Fieldset: '.$this->Value.']';
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		$except = [
//		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval',
		];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except, FALSE, FALSE);
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		return '</fieldset>';
	}
}
