<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

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

	public function AdminPopulate($id)
	{
		$except = array(); //TODO omit irrelevant objects
		list($main, $adv) = $this->AdminPopulateCommon($id, $except, TRUE, FALSE);
		return array('main'=>$main,'adv'=>$adv);
	}
}
