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
		$this->DisplayInForm = FALSE;
		$this->IsComputedOnSubmission = TRUE;
		$this->IsSortable = FALSE;
		$this->NeedsDiv = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'Computed';
		$this->ValidationTypes = array();
	}

	function ComputeOrder()
	{
		return $this->GetOption('order','1');
	}

	function Compute()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;

		$mapId = array();
		$eval_string = FALSE;

		for($i=0; $i<count($others); $i++)
		{
			$mapId[$others[$i]->GetId()] = $i;
		}

		$flds = array();
		$procstr = $this->GetOption('value');
		preg_match_all('/\$fld_(\d+)/',$procstr,$flds);

		if($this->GetOption('string_or_number_eval','numeric') == 'numeric')
		{
			foreach($flds[1] as $tF)
			{
				if(isset($mapId[$tF]))
				{
					$ref = $mapId[$tF];
					if(is_numeric($others[$ref]->GetHumanReadableValue()))
					{
						$procstr = str_replace('$fld_'.$tF,
							$others[$ref]->GetHumanReadableValue(),$procstr);
					}
					else
					{
						$procstr = str_replace('$fld_'.$tF,
							'0',$procstr);
					}
				}
			}
			$eval_string = TRUE;
		}
		else if($this->GetOption('string_or_number_eval','numeric') == 'compute')
		{
			foreach($flds[1] as $tF)
			{
				if(isset($mapId[$tF]))
				{
					$ref = $mapId[$tF];
					$procstr = str_replace('$fld_'.$tF,
					 $this->sanitizeValue($others[$ref]->GetHumanReadableValue()),$procstr);
				}
			}
			$eval_string = TRUE;
		}
		else
		{
			$thisValue = '';
			foreach($flds[1] as $tF)
			{
				if(isset($mapId[$tF]))
				{
					$ref = $mapId[$tF];
					$this->Value .= $others[$ref]->GetValue();
					if($this->GetOption('string_or_number_eval','numeric') != 'unstring')
					{
						$this->Value .= ' ';
					}
				}
			}
		}

		if($eval_string)
		{
			$strToEval = "\$this->Value=$procstr;";
			// see if we can trap an error
			// this is all vulnerable to an evil form designer, but
			// not an evil form user.
			ob_start();
			if(eval('function testcfield'.rand().'() {'.$strToEval.'}') === FALSE)
			{
				$this->Value = $mod->Lang('title_bad_function',$procstr);
			}
			else
			{
				eval($strToEval);
			}
			ob_end_clean();
		}
	}

	// strip any possible PHP function from submitted string
	function sanitizeValue($val)
	{
		$arr = get_defined_functions(); // internal and user
		$val = str_replace($arr['internal'],'',$val);
		$val = str_replace($arr['user'],'',$val);
		return $val;
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$help = pwfUtils::FormFieldsHelp($this->formdata).'<br /><br />'.
			$mod->Lang('help_operators');
		$processType = array(
			$mod->Lang('title_numeric')=>'numeric',
		    $mod->Lang('title_string')=>'string',
			$mod->Lang('title_string_unspaced')=>'unstring',
			$mod->Lang('title_compute')=>'compute');

		$main = array(
			 array(
				$mod->Lang('title_compute_value'),
				$mod->CreateInputText($id,'opt_value',$this->GetOption('value'),35,1024),
				$help),
			 array(
				$mod->Lang('title_string_or_number_eval'),
				$mod->CreateInputRadioGroup($id,'opt_string_or_number_eval',
			    	$processType,
			    	$this->GetOption('string_or_number_eval','numeric'),'&nbsp;&nbsp;')),
			 array(
				$mod->Lang('title_order'),
				$mod->CreateInputText($id,'opt_order',$this->GetOption('order',1),5,10),
				$mod->Lang('help_computed_order'))
		);
		return array('main'=>$main);
	}

}

?>
