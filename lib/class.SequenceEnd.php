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
			$this->LastBreak = FALSE;
		}
		$this->SetLast($this->LastBreak);
		$this->Type = 'SequenceEnd';
	}

	public function SetLast($state=TRUE)
	{
		$this->LastBreak = $state;
		if ($state) {
			$flag = '&#171;&#171;&nbsp;'; //left angle quotes
			$pre = 'end_';
		} else {
			$flag = '&#166;&#166;&nbsp;'; //broken vertical bars
			$pre = 'break_';
		}
		if ($this->Name) { //not a fresh load
			$l = strpos($this->Name,'&nbsp;');
			$post = substr($this->Name,$l+6);
			$this->Name = $flag.$post;
		}
		if ($this->Alias) {
			$post = explode('_',$this->Alias);
			$this->Alias = $pre.end($post);
		} else {
			$this->Alias = uniqid($pre.$this->formdata->Id);
		}
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$t = $this->GetProperty('privatename',$mod->Lang('none2'));
		return $mod->Lang('identifier').': '.$t;
	}

	public function AdminPopulate($id)
	{
		$nm = $this->Name;
		$pre = '&#171;&#171;&nbsp;';
		$p = strlen($pre);
		if ($nm && (strncmp($nm,$pre,$p)) == 0) {
			$this->Name = substr($nm,$p);
		}
		$except = array(
		'title_field_alias',
		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval');
		list($main,$adv) = $this->AdminPopulateCommon($id,$except,TRUE);
		$mod = $this->formdata->formsmodule;
		//name-help
		$main[0][] = $mod->Lang('help_sequence_name2','&#171;&#171;');

		//TODO MAYBE a picklist of available names
		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'fp_privatename',
							$this->GetProperty('privatename',''),25,50),
						$mod->Lang('help_privatename'));
		$main[] = array($mod->Lang('title_change_count'),
						$mod->CreateInputHidden($id,'fp_change_count',0).
						$mod->CreateInputCheckbox($id,'fp_change_count',1,
							$this->GetProperty('change_count',1)));
		$main[] = array($mod->Lang('title_add_button_seqpre'),
						$mod->CreateInputText($id,'fp_insertpre_label',
							$this->GetProperty('insertpre_label',$mod->Lang('insert')),25,30));
		$main[] = array($mod->Lang('title_del_button_seqpre'),
						$mod->CreateInputText($id,'fp_deletepre_label',
							$this->GetProperty('deletepre_label',$mod->Lang('delete')),25,30));
		$main[] = array($mod->Lang('title_add_button_seq'),
						$mod->CreateInputText($id,'fp_insert_label',
							$this->GetProperty('insert_label',$mod->Lang('insert')),25,30));
		$main[] = array($mod->Lang('title_del_button_seq'),
						$mod->CreateInputText($id,'fp_delete_label',
							$this->GetProperty('delete_label',$mod->Lang('delete')),25,30));

		return array('main'=>$main,'adv'=>$adv);
	}

	public function PostAdminAction(&$params)
	{
		//ensure field name begins as expected
		$this->Name = '&#171;&#171;&nbsp;'.$this->Name;
	}

	public function AdminValidate($id)
	{
		$mod = $this->formdata->formsmodule;
		$ret = TRUE;
		$msg = '';
		$ref = $this->GetProperty('privatename');
		if ($ref) {
			//check property is ok
			$starter = FALSE;
			$multi = FALSE;
			foreach ($this->formdata->Fields as $obfld) {
				$t = $obfld->Type;
				if ($t == 'SequenceStart' || ($t == 'SequenceEnd' && $obfld != $this)) {
					$p = $obfld->GetProperty('privatename');
					if ($p == $ref) {
						if ($t == 'SequenceStart') {
							$starter = TRUE;
						} else {
							$multi = TRUE;
						}
					}
				}
			}
			if ($multi) {
				$ret = FALSE;
				$msg = $mod->Lang('err_typed',$mod->Lang('sequenceid'));
			} elseif (!$starter) {
				$msg = $mod->Lang('missing_type',$mod->Lang(''));
			}
		} else {
			$msg = $mod->Lang('missing_type',$mod->Lang('sequenceid')); //not fatal, warn user
		}
		//TODO conform 'change_count' property with SequenceStart, if any
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		if (!$this->GetProperty('change_count',1)) {
			$this->MultiPopulate = FALSE;
			return '';
		}
		//at this stage, don't know whether all buttons are relevant, can't tailor them
		if ($this->LastBreak) {
			$propkeys = array('insertpre_label','deletepre_label','insert_label');
			$nm = array('SeI_','SeD_','SeX_');
		} else {
			$propkeys = array('insertpre_label','deletepre_label','insert_label','delete_label');
			$nm = array('SeI_','SeD_','SeX_','SeW_');
		}
		$ret = array();
		$bnm = $id.$this->formdata->current_prefix;
		$bid = $this->GetInputId();
		if ($this->LastBreak) {
			$pre = '&#171;&#171;';
		} else {
			$pre = '&#166;&#166;';
		}

		foreach ($propkeys as $i=>$key) {
			$m = $nm[$i];
			$oneset = new \stdClass();
			$oneset->name = '';
			$oneset->title = '';
			$oneset->input = '';
			$tmp = '<input type="submit" name="'.$bnm.$m.$this->Id.'" id="'.$bid.$m.
			'" value="'.$this->GetProperty($key);
			if ($i < 2) {
				$tmp .= ' &uarr;"';
			} else {
				$tmp .= ' &darr;"';
			}
			if ($i%2 == 0) {
				$t = $this->formdata->formsmodule->Lang('tip_sequence_add');
 				$tmp .= ' title="'.$t.'" />';
			} else {
				$t = $this->formdata->formsmodule->Lang('tip_sequence_del');
				$tmp .= ' title="'.$t.'" onclick="return confirm(\''.$this->formdata->formsmodule->Lang('confirm').'\');" />';
			}
			if ($i == 0) {
				$oneset->op = $pre.' '.$this->SetClass($tmp);
			} else {
				$oneset->op = $this->SetClass($tmp);
			}
			$ret[] = $oneset;
		}
		return $ret;
	}
}
