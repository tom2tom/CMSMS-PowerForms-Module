<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDatePickerField extends pwfFieldBase
{
	var $Months;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DatePickerField';
		$this->DisplayInForm = true;
		$mod = $formdata->pwfmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->Months = array(
			''=>'',
            $mod->Lang('date_january')=>1,
            $mod->Lang('date_february')=>2,
            $mod->Lang('date_march')=>3,
            $mod->Lang('date_april')=>4,
            $mod->Lang('date_may')=>5,
            $mod->Lang('date_june')=>6,
            $mod->Lang('date_july')=>7,
            $mod->Lang('date_august')=>8,
            $mod->Lang('date_september')=>9,
            $mod->Lang('date_october')=>10,
            $mod->Lang('date_november')=>11,
            $mod->Lang('date_december')=>12);
		$this->hasMultipleFormComponents = true;
		$this->labelSubComponents = false;
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$today = getdate();
		return $mod->Lang('date_range',array($this->GetOption('start_year',($today['year']-10)) ,
         $this->GetOption('end_year',($today['year']+10)))).
         ($this->GetOption('default_year','-1')!=='-1'?' ('.$this->GetOption('default_year','-1').')':'');
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$today = getdate();
		$Days = array(''=>'');
		for ($i=1;$i<32;$i++)
		{
			$Days[$i]=$i;
		}
		$Year = array(''=>'');
		$sty = $this->GetOption('start_year',($today['year']-10));
		if($sty == -1)
		{
			$sty = $today['year'];
		}
		for ($i=$sty;$i<$this->GetOption('end_year',($today['year']+10))+1;$i++)
		{
			$Year[$i]=$i;
		}
		if($this->HasValue())
		{
			$user_order = $this->GetOption('date_order','d-m-y');
			$arrUserOrder = explode("-", $user_order);

			$today['mday'] = $this->GetArrayValue(array_search("d", $arrUserOrder));
			$today['mon'] = $this->GetArrayValue(array_search("m", $arrUserOrder));
			$today['year'] = $this->GetArrayValue(array_search("y", $arrUserOrder));
		}
		else if($this->GetOption('default_blank','0') == '1')
		{
			$today['mday']='';
			$today['mon']='';
			$today['year']='';
		}
		else if($this->GetOption('default_year','-1') != '-1')
		{
			$today['year'] = $this->GetOption('default_year','-1');
		}

		$ret = array();
		$day = new stdClass();
		$js = $this->GetOption('javascript','');

		$day->input = $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id.'[]', $Days, -1,
			$today['mday'], $js.$this->GetCSSIdTag('_day'));
		$day->title = $mod->Lang('day');
		$day->name = '<label for="'.$this->GetCSSId('_day').'">'.$mod->Lang('day').'</label>';

		$mon = new stdClass();
		$mon->input = $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id.'[]', $this->Months, -1,
			$today['mon'], $js.$this->GetCSSIdTag('_month'));
		$mon->title = $mod->Lang('mon');
		$mon->name = '<label for="'.$this->GetCSSId('_month').'">'.$mod->Lang('mon').'</label>';

		$yr = new stdClass();
		$yr->input = $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id.'[]', $Year, -1,
			$today['year'],$js.$this->GetCSSIdTag('_year'));
		$yr->name = '<label for="'.$this->GetCSSId('_year').'">'.$mod->Lang('year').'</label>';
		$yr->title = $mod->Lang('year');

		$order = array("d" => $day, "m" => $mon, "y" => $yr);
		$user_order = $this->GetOption('date_order','d-m-y');
		$arrUserOrder = explode("-", $user_order);
		foreach($arrUserOrder as $key)
		{
			$ret[] = $order[$key];
		}

      return $ret;
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->HasValue())
		{
			// Original:  Day, Month, Year
			//$theDate = mktime (1, 1, 1, $this->GetArrayValue(1),  $this->GetArrayValue(0), $this->GetArrayValue(2));
			// Month, Day, Year
			//$theDate = mktime (1, 1, 1, $this->GetArrayValue(0),  $this->GetArrayValue(1), $this->GetArrayValue(2));
			$user_order = $this->GetOption('date_order','d-m-y');
			$arrUserOrder = explode("-", $user_order);
			$theDate = mktime (1, 1, 1,
				$this->GetArrayValue(array_search("m", $arrUserOrder)),
				$this->GetArrayValue(array_search("d", $arrUserOrder)),
				$this->GetArrayValue(array_search("y", $arrUserOrder)));
			$ret = date($this->GetOption('date_format','j F Y'), $theDate);

			$ret = str_replace(array("January","February","March","April","May","June","July","August","September","October","November","December"),
				array(
					$mod->Lang('date_january'),
					$mod->Lang('date_february'),
					$mod->Lang('date_march'),
					$mod->Lang('date_april'),
					$mod->Lang('date_may'),
					$mod->Lang('date_june'),
					$mod->Lang('date_july'),
					$mod->Lang('date_august'),
					$mod->Lang('date_september'),
					$mod->Lang('date_october'),
					$mod->Lang('date_november'),
					$mod->Lang('date_december')
					),
				$ret);

				$ret = html_entity_decode($ret, ENT_QUOTES, 'UTF-8');
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

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$today = getdate();
		$main = array(
			array($mod->Lang('title_date_format'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_date_format',
          	$this->GetOption('date_format','j F Y'),25,25),
					$mod->Lang('help_date_format')),
			array($mod->Lang('title_date_order'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_date_order',
						$this->GetOption('date_order','d-m-y'),5,5),
					$mod->Lang('help_date_order')),
		   array($mod->Lang('title_default_blank'),
					$mod->CreateInputHidden($formDescriptor,'pwfp_opt_default_blank','0').
					$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_default_blank',
            		'1',$this->GetOption('default_blank','0')),
					$mod->Lang('help_default_blank')),
		   array($mod->Lang('title_start_year'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_start_year',
						$this->GetOption('start_year',($today['year']-10)),10,10)),
		   array($mod->Lang('title_end_year'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_end_year',
						$this->GetOption('end_year',($today['year']+10)),10,10)),
		   array($mod->Lang('title_default_year'),
					$mod->CreateInputText($formDescriptor, 'pwfp_opt_default_year',
						$this->GetOption('default_year','-1'),10,10),
					$mod->Lang('help_default_year'))
		);
		return array('main'=>$main);
	}

	function HasValue($deny_blank_responses=false)
	{
		if($this->Value === false)
		{
			return false;
		}
		if(!is_array($this->Value))
		{
			return false;
		}
		if($this->GetArrayValue(1) == '' ||
			$this->GetArrayValue(0) == '' ||
			$this->GetArrayValue(2) == '')
		{
			return false;
		}
		return true;
	}

}

?>