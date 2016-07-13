<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class HTML5URL extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->IsSortable = TRUE;
		$this->Type = 'HTML5URL';
	}

	public function Populate($id,&$params)
	{
		$tmp = '<input type="url" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.'"'.$this->GetScript().' />';
		return $this->SetClass($tmp);
	}

/*	public function Validate($id)
	{
		//TODO
	}
*/
}