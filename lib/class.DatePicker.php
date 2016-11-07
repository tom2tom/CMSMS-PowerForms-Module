<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class DatePicker extends FieldBase
{
	private $Months;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->LabelSubComponents = FALSE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'DatePicker';
		$mod = $formdata->formsmodule;
		$months = explode(',',$mod->Lang('all_months'));
		$this->Months = array_flip($months); //0-based
		foreach ($this->Months as $name=>&$val) {
			$val++;	//1-based
		}
		unset ($val);
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$today = getdate();
		return $mod->Lang('date_range',array($this->GetProperty('start_year',($today['year']-10)) ,
		 $this->GetProperty('end_year',($today['year']+10)))).
		 ($this->GetProperty('default_year','-1')!=='-1'?' ('.$this->GetProperty('default_year','-1').')':'');
	}

	public function HasValue($deny_blank_responses=FALSE)
	{
		if (!$this->Value)
			return FALSE;
		if (!is_array($this->Value))
			return FALSE;

		if ($this->GetArrayValue(1) == '' ||
			$this->GetArrayValue(0) == '' ||
			$this->GetArrayValue(2) == '')
			return FALSE;

		return TRUE;
	}

	public function DisplayableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		if ($this->HasValue()) {
			// Original:  Day,Month,Year
			//$theDate = mktime (1,1,1,$this->GetArrayValue(1), $this->GetArrayValue(0),$this->GetArrayValue(2));
			// Month,Day,Year
			//$theDate = mktime (1,1,1,$this->GetArrayValue(0), $this->GetArrayValue(1),$this->GetArrayValue(2));
			$user_order = $this->GetProperty('date_order','d-m-y');
			$arrUserOrder = explode("-",$user_order);
			$theDate = mktime (1,1,1,
				$this->GetArrayValue(array_search('m',$arrUserOrder)),
				$this->GetArrayValue(array_search('d',$arrUserOrder)),
				$this->GetArrayValue(array_search('y',$arrUserOrder)));
			$ret = date($this->GetProperty('date_format','j F Y'),$theDate);

			$ret = str_replace(array('January','February','March','April','May','June','July','August','September','October','November','December'),
					explode(',',$mod->Lang('all_months')),$ret);
				$ret = html_entity_decode($ret,ENT_QUOTES,'UTF-8');
		} else
			$ret = $this->GetFormProperty('unspecified',$mod->Lang('unspecified'));

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		$today = getdate();

		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_default_blank'),
						$mod->CreateInputHidden($id,'fp_default_blank',0).
						$mod->CreateInputCheckbox($id,'fp_default_blank',1,
							$this->GetProperty('default_blank',0)),
						$mod->Lang('help_default_today'));
		$main[] = array($mod->Lang('title_start_year'),
						$mod->CreateInputText($id,'fp_start_year',
							$this->GetProperty('start_year',($today['year']-10)),10,10));
		$main[] = array($mod->Lang('title_end_year'),
						$mod->CreateInputText($id,'fp_end_year',
							$this->GetProperty('end_year',($today['year']+10)),10,10));
		$main[] = array($mod->Lang('title_default_year'),
						$mod->CreateInputText($id,'fp_default_year',
							$this->GetProperty('default_year','-1'),10,10),
						$mod->Lang('help_default_year'));
		$adv[] = array($mod->Lang('title_date_format'),
						$mod->CreateInputText($id,'fp_date_format',
							$this->GetProperty('date_format','j F Y'),25,25),
						$mod->Lang('help_date_format'));
		$adv[] = array($mod->Lang('title_date_order'),
						$mod->CreateInputText($id,'fp_date_order',
							$this->GetProperty('date_order','d-m-y'),5,5),
						$mod->Lang('help_date_order'));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$today = getdate();

		$Days = array(''=>'');
		for ($i=1; $i<32; $i++)
			$Days[$i] = $i;

		$Years = array(''=>'');
		$sty = $this->GetProperty('start_year',($today['year']-10));
		if ($sty <= 0)
			$sty = $today['year'];
		$ndy = $this->GetProperty('end_year',($today['year']+10)) + 1;
		for ($i=$sty; $i<$ndy; $i++)
			$Years[$i] = $i;

		if ($this->HasValue()) {
			$user_order = $this->GetProperty('date_order','d-m-y');
			$arrUserOrder = explode("-",$user_order);

			$today['mday'] = $this->GetArrayValue(array_search('d',$arrUserOrder));
			$today['mon'] = $this->GetArrayValue(array_search('m',$arrUserOrder));
			$today['year'] = $this->GetArrayValue(array_search('y',$arrUserOrder));
		} else if ($this->GetProperty('default_blank',0)) {
			$today['mday']='';
			$today['mon']='';
			$today['year']='';
		} else {
			$i = $this->GetProperty('default_year',0);
			if ($i)
				$today['year'] = $i;
		}

		$mod = $this->formdata->formsmodule;
		$js = $this->GetScript();

		$day = new \stdClass();
		$day->title = $mod->Lang('day');
		$tid = $this->GetInputId('_day');
		$tmp = '<label for="'.$tid.'">'.$day->title.'</label>';
		$day->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',$Days,-1,
			$today['mday'],'id="'.$tid.'"'.$js);
		$day->input = $this->SetClass($tmp);

		$mon = new \stdClass();
		$tid = $this->GetInputId('_month');
		$mon->title = $mod->Lang('month');
		$tmp = '<label for="'.$tid.'">'.$mon->title.'</label>';
		$mon->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',$this->Months,-1,
			$today['mon'],'id="'.$tid.'"'.$js);
		$mon->input = $this->SetClass($tmp);

		$yr = new \stdClass();
		$tid = $this->GetInputId('_year');
		$yr->title = $mod->Lang('year');
		$tmp = '<label for="'.$tid.'">'.$yr->title.'</label>';
		$yr->name = $this->SetClass($tmp);
		$tmp = $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id.'[]',$Years,-1,
			$today['year'],'id="'.$tid.'"'.$js);
		$yr->input = $this->SetClass($tmp);

		$order = array('d' => $day,'m' => $mon,'y' => $yr);
		$user_order = $this->GetProperty('date_order','d-m-y');
		$arrUserOrder = explode("-",$user_order);

		$ret = array();
		foreach ($arrUserOrder as $key)
			$ret[] = $order[$key];

		return $ret;
	}
}
