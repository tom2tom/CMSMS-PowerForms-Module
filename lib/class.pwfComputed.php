<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfComputed extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
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

	function ComputeOrder()
	{
		return $this->GetOption('order',1); //user-supplied number
	}

	function Compute()
	{
		$fids = array();
		$procstr = $this->GetOption('value');
		//TODO if fields not named like '$fld_N' in the string ?
		preg_match_all('/\$fld_(\d+)/',$procstr,$fids);
		$mapId = array_flip(array_keys($this->formdata->Fields)); //keys are field id's

		$etype = $this->GetOption('string_or_number_eval','numeric');
		switch($etype)
		{
		 case 'numeric':
			foreach($fids[1] as $field_id)
			{
				if(array_key_exists($field_id,$mapId))
				{
					$val = $this->formdata->Fields[$field_id]->GetHumanReadableValue();
					if(!is_numeric($val))
						$val = '0';
					$procstr = str_replace('$fld_'.$field_id,$val,$procstr);
				}
			}
			$eval_string = TRUE;
			break;
		 case 'compute':
			foreach($fids[1] as $field_id)
			{
				if(array_key_exists($field_id,$mapId))
				{
					$val = $this->formdata->Fields[$field_id]->GetHumanReadableValue();
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
			foreach($fids[1] as $field_id)
			{
				if(array_key_exists($field_id,$mapId))
				{
					$this->Value .= $this->formdata->Fields[$field_id]->GetValue();
					if($etype != 'unstring')
						$this->Value .= ' ';
				}
			}
			$eval_string = FALSE;
			break
		}

		if($eval_string)
		{
			$val = "\$this->Value=$procstr;";
			// see if we can trap an error
			// this is vulnerable to an evil form designer, but not an evil form user
			ob_start();
			if(eval('function testcfield'.rand().'() {'.$val.'}') === FALSE)
				$this->Value = $this->formdata->formsmodule->Lang('title_bad_function',$procstr);
			else
				eval($val);
			ob_end_clean();
		}
	}

	function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		$help = pwfUtils::FormFieldsHelp($this->formdata). //TODO Compute() expects $fld_N, not field alias
			'<br /><br />'.$mod->Lang('help_operators');
		$choices = array(
			$mod->Lang('title_numeric')=>'numeric',
			$mod->Lang('title_string')=>'string',
			$mod->Lang('title_string_unspaced')=>'unstring',
			$mod->Lang('title_compute')=>'compute');

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$main[] = array($mod->Lang('title_compute_value'),
						$mod->CreateInputText($id,'opt_value',$this->GetOption('value'),35,1024),
						$help);
		$main[] = array($mod->Lang('title_string_or_number_eval'),
						$mod->CreateInputRadioGroup($id,'opt_string_or_number_eval',$choices,
						$this->GetOption('string_or_number_eval','numeric'),'&nbsp;&nbsp;'));
		$main[] = array($mod->Lang('title_compute_order'),
						$mod->CreateInputText($id,'opt_order',$this->GetOption('order',1),3),
						$mod->Lang('help_compute_order'));
		return array('main'=>$main,'adv'=>$adv);
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;
		$val = $this->GetOption('value');
		if($val)
		{
			//TODO check $val sanity
//			$ret = FALSE;
//			$messages[] = $mod->Lang('error_typed',$mod->Lang'TODO_eval'));
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang'TODO_eval'));
		}
		$val = $this->GetOption('compute_order');
		if(!is_numeric($val) || $val < 1)
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('error_typed',$mod->Lang'TODO_order'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

}

?>
