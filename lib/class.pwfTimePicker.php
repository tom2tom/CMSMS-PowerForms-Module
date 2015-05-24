<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfTimePicker extends pwfFieldBase
{
	var $flag12hour;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasMultipleFormComponents = TRUE;
		$this->IsInput = TRUE;
		$this->LabelSubComponents = FALSE;
		$this->Type = 'TimePicker';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->flag12hour = array(
			$mod->Lang('title_before_noon')=>$mod->Lang('title_before_noon'),
			$mod->Lang('title_after_noon')=>$mod->Lang('title_after_noon'));
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		return ($this->GetOption('24_hour','0') == '0'?$mod->Lang('12_hour'):$mod->Lang('24_hour'));
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
		{
			if($this->GetOption('24_hour',0) == 0)
				$ret = $this->GetArrayValue(0).':'.
					$this->GetArrayValue(1).' '.
					$this->GetArrayValue(2);
			else
				$ret = $this->GetArrayValue(0).':'.
					$this->GetArrayValue(1);
		}
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		$now = localtime(time(),TRUE);
		$Mins = array();
		$Hours = array();
		$ret = array();
		for ($i=0; $i<60; $i++)
		{
			$mo = sprintf("%02d",$i);
			$Mins[$mo]=$mo;
		}
		if($this->GetOption('24_hour','0') == '0')
		{
			for ($i=1; $i<13; $i++)
			{
				$mo = sprintf("%02d",$i);
				$Hours[$mo]=$mo;
			}
			if($this->HasValue())
			{
				$now['tm_hour'] = $this->GetArrayValue(0);
				$now['merid'] = $this->GetArrayValue(2);
				$now['tm_min'] = $this->GetArrayValue(1);
			}
			else
			{
				$now['merid'] = $mod->Lang('title_before_noon');
				if($now['tm_hour'] > 12)
				{
					$now['tm_hour'] -= 12;
					$now['merid'] = $mod->Lang('title_after_noon');
				}
				elseif($now['tm_hour'] == 0)
				{
					$now['tm_hour'] = 12;
				}
			}

			$hr = new stdClass();
			$hr->input = $mod->CreateInputDropdown($id,'pwfp_'.$this->Id.'[]',
				$Hours,-1,$now['tm_hour'],$js.$this->GetCSSIdTag('_hour'));
			$hr->title = $mod->Lang('hour');
			$hr->name = '<label for="'.$this->GetCSSId('_hour').'">'.$mod->Lang('hour').'</label>';
			$ret[] = $hr;

			$min = new stdClass();
			$min->input = $mod->CreateInputDropdown($id,'pwfp_'.$this->Id.'[]',
				$Mins,-1,$now['tm_min'],$js.$this->GetCSSIdTag('_min'));
			$min->title = $mod->Lang('min');
			$min->name = '<label for="'.$this->GetCSSId('_min').'">'.$mod->Lang('min').'</label>';
			$ret[] = $min;

			$mer = new stdClass();
			$mer->input = $mod->CreateInputDropdown($id,'pwfp_'.$this->Id.'[]',
				$this->flag12hour,-1,$now['merid'],$js.$this->GetCSSIdTag('_meridian'));
			$mer->name = '<label for="'.$this->GetCSSId('_meridian').'">'.$mod->Lang('merid').'</label>';
			$mer->title = $mod->Lang('merid');
			$ret[] = $mer;
			return $ret;
		}
		else
		{
			for ($i=0; $i<24; $i++)
			{
				$mo = sprintf("%02d",$i);
				$Hours[$mo]=$mo;
			}

			if($this->HasValue())
			{
				$now['tm_hour'] = $this->GetArrayValue(0);
				$now['tm_min'] = $this->GetArrayValue(1);
			}
			$hr = new stdClass();
			$hr->input = $mod->CreateInputDropdown($id,'pwfp_'.$this->Id.'[]',
				$Hours,-1,$now['tm_hour'],$js.$this->GetCSSIdTag('_hour'));
			$hr->title = $mod->Lang('hour');
			$hr->name = '<label for="'.$this->GetCSSId('_hour').'">'.$mod->Lang('hour').'</label>';
			$ret[] = $hr;

			$min = new stdClass();
			$min->input = $mod->CreateInputDropdown($id,'pwfp_'.$this->Id.'[]',
				$Mins,-1,$now['tm_min'],$js.$this->GetCSSIdTag('_min'));
			$min->title = $mod->Lang('min');
			$min->name = '<label for="'.$this->GetCSSId('_min').'">'.$mod->Lang('min').'</label>';
			$ret[] = $min;

			return $ret;
		}
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_24_hour'),
            		$mod->CreateInputCheckbox($module_id,'opt_24_hour',
            		'1',$this->GetOption('24_hour','0'))));
		return array('main'=>$main);
	}

}

?>
