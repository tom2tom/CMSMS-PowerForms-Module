<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class RadioGroup extends FieldBase
{
	private $optionAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'RadioGroup';
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	public function ComponentAdd(&$params)
	{
		$this->optionAdd = TRUE;
	}

	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('button_name',$indx);
				$this->RemovePropIndexed('button_checked',$indx);
				$this->RemovePropIndexed('button_is_set',$indx);
			}
		}
	}

	public function SetValue($newvalue)
	{
		if (is_array($newvalue)) { //group-member selected in form
			$this->Value = reset($newvalue); //1-based index?
		} elseif ($newvalue) {
			$all = $this->GetPropArray('button_checked');
			if ($all) {
				$i = array_search($newvalue,$all); //1-based index
				if ($i === FALSE)
					$i = 0;
			} else {
				$i = 0; //unknown
			}
			$this->Value = $i;
		} else { //probably no group-member selected
			$this->Value = 0;
		}
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->Value) {
			$ret = $this->GetPropIndexed('button_checked',$this->Value);
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetSynopsis()
	{
		$def = '';
		$opt = $this->GetPropArray('button_is_set');
		if ($opt) {
			$optionCount = count($opt);
			foreach ($opt as $i=>$val) {
				if ($val == 'y') {
					$def = ',default value '.$this->GetPropIndexed('button_checked',$i); //TODO $lang[]
					break;
				}
			}
		} else {
			$optionCount = 0;
		}
		$ret = $this->formdata->formsmodule->Lang('options',$optionCount).$def;
		return $ret;
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_radio_separator'),
						$mod->CreateInputText($id,'fp_radio_separator',
							$this->GetProperty('radio_separator','&nbsp;&nbsp;'),15,25),
						$mod->Lang('help_radio_separator'));
		if ($this->optionAdd) {
			$this->AddPropIndexed('button_name','');
			$this->AddPropIndexed('button_checked','');
			$this->AddPropIndexed('button_is_set','y');
			$this->optionAdd = FALSE;
		}
		$names = $this->GetPropArray('button_name');
		if ($names) {
			$boxes = array();
			$boxes[] = array(
				$mod->Lang('title_default_sel'),
				$mod->Lang('title_radio_label'),
				$mod->Lang('title_selected_value'),
				$mod->Lang('title_select')
				);
			$fieldclass = 'field'.$this->Id;
			foreach ($names as $i=>$one) {
				$arf = '['.$i.']';
				$tmp = $mod->CreateInputCheckbox($id,'fp_button_is_set'.$arf,'y',$this->GetPropIndexed('button_is_set',$i),
					'style="display:block;margin:auto;"');
				$boxes[] = array(
					$mod->CreateInputHidden($id,'fp_button_is_set'.$arf,'n').
						str_replace('class="','class="'.$fieldclass.' ',$tmp),
					$mod->CreateInputText($id,'fp_button_name'.$arf,$one,25,128),
					$mod->CreateInputText($id,'fp_button_checked'.$arf,$this->GetPropIndexed('button_checked',$i),25,128),
					$mod->CreateInputCheckbox($id,'selected'.$arf,1,-1,'style="display:block;margin:auto;"')
				 );
			}
			$this->jsfuncs['checkone'] = <<<'EOS'
function select_only(cb,fclass) {
 if (cb.checked) {
  $('input.'+fclass).attr('checked',false);
  cb.checked = true;
 }
}
EOS;
			$this->jsloads[] = <<<EOS
 $('input.{$fieldclass}').change(function(){
  select_only(this,'{$fieldclass}');
 });
EOS;
	  		$this->MultiComponent = TRUE;
			$this->HasDeleteOp = TRUE;
			return array('main'=>$main,'adv'=>$adv,'table'=>$boxes);
		} else {
			$this->MultiComponent = FALSE;
			$this->HasDeleteOp = FALSE;
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('member')));
			return array('main'=>$main,'adv'=>$adv);
		}
	}

	public function AdminValidate($id)
	{
		$messages = array();
		list($ret,$msg) = parent::AdminValidate($id);
		if (!$ret)
			$messages[] = $msg;
		$mod = $this->formdata->formsmodule;
		$sets = $this->GetPropArray('button_is_set');
		if ($sets) {
			foreach ($sets as $val) {
				if ($val != 'n') {
					break;
				}
			}
			if ($val == 'n')
				$messages[] = $mod->Lang('validation_selected');
		} else {
			$messages[] = $mod->Lang('missing_type',$mod->Lang('member'));
		}
		if ($messages) {
			return array(FALSE,implode('<br />',$messages));
		} else {
			return array(TRUE,'');
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('button_name');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!($one || $this->GetPropIndexed('button_checked',$i))) {
					$this->RemovePropIndexed('button_name',$i); //should be ok in loop
					$this->RemovePropIndexed('button_checked',$i);
					$this->RemovePropIndexed('button_is_set',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$names = $this->GetPropArray('button_name');
		if ($names) {
			$ret = array();
			$mod = $this->formdata->formsmodule;
			$sep = $this->GetProperty('radio_separator','&nbsp;&nbsp;');
			$cnt = count($names);
			$b = 1;
			foreach ($names as $i=>&$one) {
				$oneset = new \stdClass();
				if ($one) {
					$oneset->title = $one;
					if ($b == $cnt) //last button
						$sep = '';
					$tmp = '<label for="'.$this->GetInputId('_'.$i).'">'.$one.'</label>'.$sep;
					$oneset->name = $this->SetClass($tmp);
				} else {
					$oneset->title = '';
					$oneset->name = '';
				}

 				$tmp = '<input type="radio" id="'.$this->GetInputId('_'.$i).'" name="'.
					$id.$this->formdata->current_prefix.$this->Id.'[]" value="'.$i.'"';

				if (($this->Value || is_numeric($this->Value)) && $i == $this->Value) {
					$checked = TRUE;
				} elseif ($this->GetPropIndexed('button_is_set',$i) == 'y') {
					$checked = TRUE;
				} else {
					$checked = FALSE;
				}
				if ($checked)
					$tmp .= ' checked="checked"';
				$tmp .= $this->GetScript().' />';
				$oneset->input = $this->SetClass($tmp);
				$ret[] = $oneset;
				$b++;
			}
			unset($one);
			$this->MultiPopulate = TRUE;
			return $ret;
		}
		$this->MultiPopulate = FALSE;
		return '';
	}
}
