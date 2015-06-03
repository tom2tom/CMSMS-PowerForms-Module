<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfHTML5Email extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsInput = TRUE;
		$this->IsSortable = TRUE;
		$this->Type = 'HTML5Email';
	}

	function Populate($id,&$params)
	{
		return '<input type="email" name="'.$id.$this->formdata->current_prefix.$this->Id.
		'"'.$this->GetIdTag().$this->GetScript().' />';
	}

/*	function Validate($id)
	{
		//TODO
	}
*/
}
?>
