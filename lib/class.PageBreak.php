<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class PageBreak extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->Required = FALSE;
		$this->Type = 'PageBreak';
	}

/*	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [];
	}
*/
	public function AdminPopulate($id)
	{
		$except = [
//		'title_field_javascript',
//		'title_field_resources',
		'title_smarty_eval',
		];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except, FALSE, FALSE);
		return ['main'=>$main,'adv'=>$adv];
	}
}
