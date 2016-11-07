<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SequenceStart extends FieldBase
{
	public $IsSequence = TRUE;
	public $Repeats = 1;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = TRUE;
		//name/label not user-modifiable for this class
		$this->Name = $formdata->formsmodule->Lang('sequence_start').' &#187;&#187;'; //right angle quotes
		$this->NeedsDiv = FALSE;
		$this->Type = 'SequenceStart';
	}

	protected function CountSequences(){
		$nm = $this->GetProperty('privatename'); //sequence identifier
		$count = 0;
		$ender = NULL;
		foreach ($this->formdata->Fields as $obfld) { //TODO per field-orders
			if ($obfld->Type == 'SequenceEnd') {
				if ($obfld->GetProperty('privatename') == $nm) { //belongs to this sequence
					$ender = $obfld;
					$obfld->SetLast(FALSE);
					$count++;
				}
			}
		}
		if ($ender) {
			$ender->SetLast(TRUE);
		}
		$this->Repeats = $count;
		return $count;
	}

	protected function CopySequenceFields()
	{
		$c = $this->GetProperty('maxcount');
		if ($c > 0) {
			$cn = $this->CountSequences();
			if ($cn > $c)
				return; //TODO advice for user
		}
		foreach ($this->formdata->Fields as $obfld) {
			if ($obfld == $this) {
/*
	note position
  find next break
  get all intemediate fields
  append break field
  'insert' after/before this position (append?)
  update $LastBreak's
  update orders
*/
			}
		}
	}

	private function DeleteSequenceFields()
	{
		$c = $this->GetProperty('mincount');
		if ($c > 1) {
			$cn = $this->CountSequences();
			if ($cn <= $c)
				return; //TODO advice for user
		}
		foreach ($this->formdata->Fields as $obfld) {
			if ($obfld == $this) {
/*
	note position
  find next break
  get all intemediate fields
    delete them
   if following break field
    delete that
   update $LastBreak's
   update orders 
*/
			}
		}
	}

	public function GetSynopsis()
	{
		$t = $this->formdata->formsmodule->Lang('title_privatename');
		return $t.': '.$this->GetProperty('privatename');
	}

	public function AdminPopulate($id)
	{
		$except = array(
		'title_field_name',
		'title_field_alias',
		'title_field_helptext',
		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval');
		list($main,$adv) = $this->AdminPopulateCommon($id,$except,TRUE); // !$visible?
		$mod = $this->formdata->formsmodule;

		$alias = $this->ForceAlias(htmlentities($this->Name));
		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'fp_privatename',
							$this->GetProperty('privatename',$alias),20,50));
		$main[] = array($mod->Lang('title_initial_count'),
						$mod->CreateInputText($id,'fp_repeatcount',
							$this->GetProperty('repeatcount',1),2,2),
						$mod->Lang('help_initial_count'));
		$main[] = array($mod->Lang('title_min_count'),
						$mod->CreateInputText($id,'fp_mincount',
							$this->GetProperty('mincount',1),2,2),
						$mod->Lang('help_min_count'));
		$main[] = array($mod->Lang('title_max_count'),
						$mod->CreateInputText($id,'fp_maxcount',
							$this->GetProperty('maxcount',0),2,2),
						$mod->Lang('help_limit_count'));
		$main[] = array($mod->Lang('title_add_button_seq'),
						$mod->CreateInputText($id,'fp_insert_label',
							$this->GetProperty('insert_label',$mod->Lang('insert_sequence')),25,30));
		$main[] = array($mod->Lang('title_del_button_seq'),
						$mod->CreateInputText($id,'fp_delete_label',
							$this->GetProperty('delete_label',$mod->Lang('delete_sequence')),25,30));

		return array('main'=>$main,'adv'=>$adv);
	}

/*	public function AdminValidate($id)
	{
		TODO
		unique privatename
		max adds 0..20?
		repeatcount 1..Max
	}
*/
	public function Populate($id,&$params)
	{
		$html = '';
		$bnm = $id.$this->formdata->current_prefix.$this->Id;
		$bid = $this->GetInputId();
//TODO no 'insert_label' sometimes, no 'delete_label' sometimes
		foreach (array('insert_label','delete_label') as $key) {
			$c = $TODO;
			$tmp = '<input type="button" name="'.$bnm.$c.'" id="'.$bid.$c.
			'" value="'.$this->GetProperty($key).'" />';
			$html .= $this->SetClass($tmp).' ';
		}
		$html .= $this->Name;
		//no js
		return $html;
	}
}
