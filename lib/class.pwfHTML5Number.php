<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfHTML5Number extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsInput = TRUE;
		$this->IsSortable = TRUE;
		$this->Type = 'HTML5Number';
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_min_number'),
			$mod->CreateInputText($id,'opt_min_number',$this->GetOption('min_number',0)));
		$main[] = array($mod->Lang('title_max_number'),
			$mod->CreateInputText($id,'opt_max_number',$this->GetOption('max_number',500)));
		$main[] = array($mod->Lang('title_step_number'),
			$mod->CreateInputText($id,'opt_step_number',$this->GetOption('step_number',50)));
		return array('main'=>$main);
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		$min = $this->GetOption('min_number');
		if(!$min || !is_numeric($min))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('error_number',$mod->Lang('minumum')); //TODO translate
		}
		$max = $this->GetOption('max_number');
		if(!$max || !is_numeric($max) || $max <= $min))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('error_number',$mod->Lang('maximum'));
		}
		$step = $this->GetOption('step_number');
		if(!$step || !is_numeric($step) || $step >= $max))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('error_number',$mod->Lang('step'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	function Populate($id,&$params)
	{
		$min = $this->GetOption('min_number',0);
		$max = $this->GetOption('max_number',500);
		$step = $this->GetOption('step_number',50);

		return '<input type="number" name="'.$id.$this->formdata->current_prefix.$this->Id.
		'" min="'.$min.'" max="'.$max.'" step="'.$step.'"'.$this->GetIdTag().$this->GetScript().' />';
	}
}

?>
