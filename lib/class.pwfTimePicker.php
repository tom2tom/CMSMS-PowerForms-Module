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
			if($this->GetOption('24_hour',0))
				$ret = $this->GetArrayValue(0).':'.
					$this->GetArrayValue(1);
			else
				$ret = $this->GetArrayValue(0).':'.
					$this->GetArrayValue(1).' '.
					$this->GetArrayValue(2);
		}
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_24_hour'),
				$mod->CreateInputHidden($id,'opt_24_hour',0).
            	$mod->CreateInputCheckbox($id,'opt_24_hour',1,
					$this->GetOption('24_hour',0))));
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetScript();

		$now = localtime(time(),TRUE);
		$Mins = array();
		$Hours = array();
		$ret = array();
		for ($i=0; $i<60; $i++)
		{
			$mo = sprintf("%02d",$i);
			$Mins[$mo] = $mo;
		}
		if($this->GetOption('24_hour',0))
		{
			for($i=0; $i<24; $i++)
			{
				$mo = sprintf("%02d",$i);
				$Hours[$mo] = $mo;
			}

			if($this->HasValue())
			{
				$now['tm_hour'] = $this->GetArrayValue(0);
				$now['tm_min'] = $this->GetArrayValue(1);
			}
			$oneset = new stdClass();
			$tid = $this->GetInputId('_hour');
			$oneset->title = $mod->Lang('hour');
			$oneset->name = '<label for="'.$tid.'">'.$oneset->title.'</label>';
			$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',
				$Hours,-1,$now['tm_hour'],$js);
			$oneset->input = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$ret[] = $oneset;

			$oneset = new stdClass();
			$tid = $this->GetInputId('_min');
			$oneset->title = $mod->Lang('min');
			$oneset->name = '<label for="'.$tid.'">'.$oneset->title.'</label>';
			$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',
				$Mins,-1,$now['tm_min'],$js);
			$oneset->input = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$ret[] = $oneset;

			return $ret;
		}
		else
		{
			for($i=1; $i<13; $i++)
			{
				$mo = sprintf("%02d",$i);
				$Hours[$mo]=$mo;
			}
			if($this->HasValue())
			{
				$now['tm_hour'] = $this->GetArrayValue(0);
				$now['tm_min'] = $this->GetArrayValue(1);
				$now['merid'] = $this->GetArrayValue(2);
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

			$oneset = new stdClass();
			$tid = $this->GetInputId('_hour');
			$oneset->title = $mod->Lang('hour');
			$oneset->name = '<label for="'.$tid.'">'.$oneset->title.'</label>';
			$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',
				$Hours,-1,$now['tm_hour'],$js);
			$oneset->input = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$ret[] = $oneset;

			$oneset = new stdClass();
			$tid = $this->GetInputId('_min');
			$oneset->title = $mod->Lang('min');
			$oneset->name = '<label for="'.$tid.'">'.$oneset->title.'</label>';
			$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',
				$Mins,-1,$now['tm_min'],$js);
			$oneset->input = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$ret[] = $oneset;

			$oneset = new stdClass();
			$tid = $this->GetInputId('_meridian');
			$oneset->title = $mod->Lang('merid');
			$oneset->name = '<label for="'.$tid.'">'.$oneset->title.'</label>';
			$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',
				$this->flag12hour,-1,$now['merid'],$js);
			$oneset->input = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
			$ret[] = $oneset;
			return $ret;
		}
	}

}

?>
