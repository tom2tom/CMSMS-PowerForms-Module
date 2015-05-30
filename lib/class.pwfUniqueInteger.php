<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUniqueInteger extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'UniqueInteger';
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		if($this->Value)
		{
			$ret = $mod->CreateInputHidden($id,$this->formdata->current_prefix.$this->Id,$this->Value);
			if($this->GetOption('show_to_user',0))
				$ret .= $this->Value;
		}
		else if($this->GetOption('use_random_generator',0))
		{
			$times = $this->GetOption('numbers_to_generate',5);
			$number = $this->generate_numbers(0,9,$times);
			$ret = $mod->CreateInputHidden($id,$this->formdata->current_prefix.$this->Id,$number);
			if($this->GetOption('show_to_user',0))
				$ret .= $number;
		}
		else
		{
			$db = cmsms()->GetDb();
			$seq = $db->GenID(cms_db_prefix().'module_pwf_uniquefield_seq');
			$ret = $mod->CreateInputHidden($id,$this->formdata->current_prefix.$this->Id,$seq);
			if($this->GetOption('show_to_user',0))
				$ret .= $seq;
		}

		return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$main = array(
			array($mod->Lang('title_show_to_user'),$mod->CreateInputHidden($module_id,'opt_show_to_user',0).
					$mod->CreateInputCheckbox($module_id,'opt_show_to_user',1,$this->GetOption('show_to_user',0))),
			array($mod->Lang('title_use_random_generator'),$mod->CreateInputHidden($module_id,'opt_use_random_generator',0).
					$mod->CreateInputCheckbox($module_id,'opt_use_random_generator',1,$this->GetOption('use_random_generator',0))),
			array($mod->Lang('title_numbers_to_generate'),$mod->CreateInputText($module_id,'opt_numbers_to_generate',$this->GetOption('numbers_to_generate','5'),25,25))
		);

		return array('main'=>$main);
	}

	private function generate_numbers($min,$max,$times)
	{
		$output = '';
		$array = range($min,$max);
		srand ((double)microtime()*10000);
		for($x = 0; $x < $times; $x++)
		{
			$i = rand(1,count($array))-1;
			$output .= $array[$i];
		}
		return $output;
	}

}

?>
