<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class StaticText extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->Type = 'StaticText';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + ['text'=>12];
	}

	public function GetSynopsis()
	{
		return $this->formdata->pwfmod->Lang('text_length', strlen($this->GetProperty('text')));
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Static Text Field]';
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		$except = [
		'title_field_helptoggle',
		'title_hide_label',
		'title_field_javascript',
		];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except, FALSE, TRUE);
		$mod = $this->formdata->pwfmod;

		$main[] = [$mod->Lang('title_text'),
						$mod->CreateTextArea((get_preference(get_userid(), 'use_wysiwyg')), $id,
							$this->GetProperty('text'), 'fp_text', 'pageheadtags')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		return $this->GetProperty('text');
	}
}
