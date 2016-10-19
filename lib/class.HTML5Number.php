<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class HTML5Number extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->IsSortable = TRUE;
		$this->Type = 'HTML5Number';
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_min_number'),
						$mod->CreateInputText($id,'pdt_min_number',
							$this->GetProperty('min_number',0)));
		$main[] = array($mod->Lang('title_max_number'),
						$mod->CreateInputText($id,'pdt_max_number',
							$this->GetProperty('max_number',500)));
		$main[] = array($mod->Lang('title_step_number'),
						$mod->CreateInputText($id,'pdt_step_number',
							$this->GetProperty('step_number',50)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		$min = $this->GetProperty('min_number');
		if (!$min || !is_numeric($min)) {
			$ret = FALSE;
			$messages[] = $mod->Lang('error_typed',$mod->Lang('minumum'));
		}
		$max = $this->GetProperty('max_number');
		if (!$max || !is_numeric($max) || $max <= $min) {
			$ret = FALSE;
			$messages[] = $mod->Lang('error_typed',$mod->Lang('maximum'));
		}
		$step = $this->GetProperty('step_number');
		if (!$step || !is_numeric($step) || $step >= $max) {
			$ret = FALSE;
			$messages[] = $mod->Lang('error_typed',$mod->Lang('increment'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		$min = $this->GetProperty('min_number',0);
		$max = $this->GetProperty('max_number',500);
		$step = $this->GetProperty('step_number',50);

		$tmp = '<input type="number" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.
			'" min="'.$min.'" max="'.$max.'" step="'.$step.'"'.$this->GetScript().' />';
		return $this->SetClass($tmp);
	}
}
