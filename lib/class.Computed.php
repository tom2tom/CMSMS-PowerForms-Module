<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Computed extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsComputedOnSubmission = TRUE;
		$this->IsSortable = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'Computed';
		$this->ValidationTypes = array();
	}

	public function ComputeOrder()
	{
		return $this->GetProperty('order',1); //user-supplied number
	}

	public function Compute()
	{
		$fids = array();
		$procstr = $this->GetProperty('value');
		//TODO if fields not named like '$fld_N' in the string ?
		preg_match_all('/\$fld_(\d+)/',$procstr,$fids);

		$etype = $this->GetProperty('string_or_number_eval','numeric');
		switch ($etype) {
		 case 'numeric':
			foreach ($fids[1] as $field_id) {
				if (array_key_exists($field_id,$this->formdata->Fields)) {
					$val = $this->formdata->Fields[$field_id]->GetDisplayableValue();
					if (!is_numeric($val))
						$val = '0';
					$procstr = str_replace('$fld_'.$field_id,$val,$procstr);
				}
			}
			$eval_string = TRUE;
			break;
		 case 'compute':
			foreach ($fids[1] as $field_id) {
				if (array_key_exists($field_id,$this->formdata->Fields)) {
					$val = $this->formdata->Fields[$field_id]->GetDisplayableValue();
					// strip any PHP function from submitted string
					$arr = get_defined_functions(); // internal and user
					$val = str_replace($arr['internal'],'',$val);
					$val = str_replace($arr['user'],'',$val);
					$procstr = str_replace('$fld_'.$field_id,$val,$procstr);
				}
			}
			$eval_string = TRUE;
			break;
		 default:
			$this->Value = '';
			foreach ($fids[1] as $field_id) {
				if (array_key_exists($field_id,$this->formdata->Fields)) {
					$this->Value .= $this->formdata->Fields[$field_id]->GetValue();
					if ($etype != 'unstring')
						$this->Value .= ' ';
				}
			}
			$eval_string = FALSE;
			break;
		}

		if ($eval_string) {
			// see if we can trap an error
			// this is vulnerable to an evil form designer, but not an evil form user
			ob_start();
			if (eval('function testcfield'.mt_rand(100,200).'() {\$this->Value=$procstr;}') === FALSE)
				$this->Value = $this->formdata->formsmodule->Lang('title_bad_function',$procstr);
			else
				eval($val);
			ob_end_clean();
		}
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		$help = Utils::FormFieldsHelp($this->formdata). //TODO Compute() expects $fld_N, not field alias
			'<br /><br />'.$mod->Lang('help_operators');
		$choices = array(
			$mod->Lang('title_numeric')=>'numeric',
			$mod->Lang('title_string')=>'string',
			$mod->Lang('title_string_unspaced')=>'unstring',
			$mod->Lang('title_compute')=>'compute');

		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE);
		$main[] = array($mod->Lang('title_compute_value'),
						$mod->CreateInputText($id,'pdt_value',$this->GetProperty('value'),35,1024),
						$help);
		$main[] = array($mod->Lang('title_string_or_number_eval'),
						$mod->CreateInputRadioGroup($id,'pdt_string_or_number_eval',$choices,
						$this->GetProperty('string_or_number_eval','numeric'),'&nbsp;&nbsp;'));
		$main[] = array($mod->Lang('title_compute_order'),
						$mod->CreateInputText($id,'pdt_order',$this->GetProperty('order',1),3),
						$mod->Lang('help_compute_order'));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if (!ret)
			$messages[] = $msg;
		$val = $this->GetProperty('value');
		if ($val) {
			//PROCESSING ARBITRARY INPUT WITH EVAL() IS NOT SAFE!!!
			// but throw in a few checks for $val sanity
			$se = new SaferEval();
			$errs = $se->checkScript($val,FALSE);
			if ($errs) {
				$ret = FALSE;
				foreach ($errs as $edata) {
					$msg = $edata['name'];
					if (!empty($edata['line']))
						$msg .= ': line '.$edata['line'];
					$messages[] = $msg;
				}
			}
		} else {
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('TODO_eval'));
		}
		$val = $this->GetProperty('compute_order');
		if (!is_numeric($val) || $val < 1) {
			$ret = FALSE;
			$messages[] = $mod->Lang('error_typed',$mod->Lang('TODO_order'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}
}
