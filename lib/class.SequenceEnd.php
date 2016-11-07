<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SequenceEnd extends SequenceStart
{
	public $LastBreak = TRUE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		if (0) {
			$this->LastBreak = $X;
		}
		$this->SetLast($this->LastBreak);
		$this->Type = 'SequenceEnd';
	}

	public function SetLast($state=TRUE)
	{
		$this->LastBreak = $state;
		$this->Name = ($state) ?
			$this->formdata->formsmodule->Lang('sequence_end').' &#171;&#171;': //left angle quotes
			$this->formdata->formsmodule->Lang('sequence_break').' &#166;&#166;'; //broken vertical bars
	}

	//TODO method to set all $LastBreak properties in the form

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

		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'fp_privatename',
							$this->GetProperty('privatename',$alias),20,50));
		$main[] = array($mod->Lang('title_add_button_seqpre'),
						$mod->CreateInputText($id,'fp_insertpre_label',
							$this->GetProperty('insertpre_label',$mod->Lang('insert_seqpre')),25,30));
		$main[] = array($mod->Lang('title_del_button_seqpre'),
						$mod->CreateInputText($id,'fp_deletepre_label',
							$this->GetProperty('deletepre_label',$mod->Lang('delete_seqpre')),25,30));
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
		TODO relevant $this->GetProperty('privatename') or else warn user;
	}
*/
	public function Populate($id,&$params)
	{
	//TODO no 'insert_label'(s) sometimes, no 'delete_label'(s) sometimes
		if ($this->LastBreak) {
			$propkeys = array('insertpre_label','deletepre_label');
		} else {
			$propkeys = array('insertpre_label','deletepre_label','insert_label','delete_label');
		}
		$html = '';
		$bnm = $id.$this->formdata->current_prefix.$this->Id;
		$bid = $this->GetInputId();

		foreach ($propkeys as $key) {
			$c = $TODO;
			$tmp = '<input type="button" name="'.$bnm.$c.'" id="'.$bid.$c.
			'" value="'.$this->GetProperty($key).'" />';
			$html .= $this->SetClass($tmp).' ';
		}
		if ($this->LastBreak) {
			$html .= '&#171;&#171;';
		} else {
			$html .= '&#166;&#166;';
		}
		//no js
		return $html;
	}
}
