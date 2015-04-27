<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This class written by Tapio "Stikki" Löytty <tapsa@blackmilk.fi> */

class pwfYearPulldownField extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'YearPullDownField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array();
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$sorted =array();

		if($this->GetOption('year_start','') != '')
		{
			$count_from = $this->GetOption('year_start','');
		}
		else
		{
			$count_from = 1900;
		}

		for ($i=date("Y"); $i>=$count_from; $i--)
		{
			$sorted[$i]=$i;
		}

		if($this->GetOption('sort') == '1')
		{
			ksort($sorted);
		}

		if($this->GetOption('select_one','') != '')
		{
			$sorted = array(' '.$this->GetOption('select_one','')=>'') + $sorted;
		}
		else
		{
			$sorted = array(' '.$mod->Lang('select_one')=>'') + $sorted;
		}
		return $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id, $sorted, -1, $this->Value,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_select_one',
						  $this->GetOption('select_one',$mod->Lang('select_one')),25,128));

		$main[] = array($mod->Lang('title_year_end_message'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_year_start',
						  $this->GetOption('year_start',1900),25,128));

		$main[] = array($mod->Lang('sort_options'),
					$mod->CreateInputDropdown($formDescriptor,'pwfp_opt_sort',
						  array('Yes'=>1,'No'=>0),-1,
						  $this->GetOption('sort',0)));

		return array('main'=>$main);
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->HasValue())
		{
			$ret = $this->Value;
		}
		else
		{
			$ret = $this->formdata->GetAttr('unspecified',$mod->Lang('unspecified'));
		}
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}
}

?>
