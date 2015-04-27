<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldsetStart extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'FieldsetStart';
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->ValidationTypes = array();
		$this->HasLabel = 0;
		$this->NeedsDiv = 0;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$js = $this->GetOption('javascript','');
		$str = '<fieldset';
		$class = $this->GetOption('css_class');
		$legend = $this->GetOption('legend');
		$str .= $this->GetCSSIdTag();
		if($class != '')
		{
			$str .= " class=\"$class\"";
		}
		if($js != '')
		{
			$str .= ' '.$js;
		}
		$str .= '>';
		if($legend != '')
		{
			$str .= '<legend>'.$legend.'</legend>';
		}
		return $str;
	}

	function StatusInfo()
	{
		return '';
	}

	function GetHumanReadableValue($as_string=true)
	{
		// there's nothing human readable about a fieldset.
		$ret = '[Begin Fieldset: '.$this->Value.']';
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array(
			  array($mod->Lang('title_legend'),
					$mod->CreateInputText($formDescriptor,'pwfp_opt_legend',
					  $this->GetOption('legend',''), 50)));
		return array('main'=>$main);
	}

}

?>
