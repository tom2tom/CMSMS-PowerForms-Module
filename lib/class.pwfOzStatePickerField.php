<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfOzStatePickerField extends pwfFieldBase
{
	var $states;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'OzStatePickerField';
		$this->DisplayInForm = true;
		$this->ValidationTypes = array();

		$this->states = array(
		"No Default"=>'',
		"Australian Capital Territory"=>"ACT",
		"New South Wales"=>"NSW",
		"Northern Territory"=>"NT",
		"Queensland"=>"Qld",
		"South Australia"=>"SA",
		"Tasmania"=>"Tas",
		"Victoria"=>"Vic",
		"Western Australia"=>"WA"
		);
	}


	function StatusInfo()
	{
		return '';
	}

	function GetHumanReadableValue($as_string=true)
	{
		$ret = array_search($this->Value,$this->states);
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}


	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');

		unset($this->states[$mod->Lang('no_default')]);
		if($this->GetOption('select_one','') != '')
		{
			$this->states = array_merge(array($this->GetOption('select_one','')=>''),$this->states);
		}
		else
		{
			$this->states = array_merge(array($mod->Lang('select_one')=>''),$this->states);
		}

		if(!$this->HasValue() && $this->GetOption('default_state','') != '')
		{
			$this->SetValue($this->GetOption('default_state',''));
		}

		return $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id, $this->states, -1, $this->Value,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		ksort($this->states);

		$main = array(
			array($mod->Lang('title_select_default_state'),
            		$mod->CreateInputDropdown($formDescriptor, 'pwfp_opt_default_state',
            		$this->states, -1, $this->GetOption('default_state',''))),
			array($mod->Lang('title_select_one_message'),
            		$mod->CreateInputText($formDescriptor, 'pwfp_opt_select_one',
            		$this->GetOption('select_one',$mod->Lang('select_one'))))
		);
		return array('main'=>$main);
	}
}

?>
