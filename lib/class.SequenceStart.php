<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SequenceStart extends FieldBase
{
	public $IsSequence = TRUE;
	public $Repeats = 1; //total number

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInSubmission = FALSE;
		$this->Alias = uniqid('start_'.$this->formdata->Id); //not user editable
		$this->HasLabel = TRUE;
		$this->LabelSubComponents = FALSE;
		$this->MultiPopulate = TRUE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'SequenceStart';
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		$t = $this->GetProperty('privatename',$mod->Lang('none2'));
		return $mod->Lang('identifier').': '.$t;
	}

	public function AdminPopulate($id)
	{
		$except = array(
		'title_field_alias',
		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval');
		list($main,$adv) = $this->AdminPopulateCommon($id,$except,TRUE);
		$mod = $this->formdata->formsmodule;
		//name-help
		$main[0][] = $mod->Lang('help_sequence_name','&#187;&#187;','&amp;nbsp;&amp;#187;&amp#187;');

		$def = uniqid('s'.$this->formdata->Id,FALSE);
		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'fp_privatename',
							$this->GetProperty('privatename',$def),25,50));
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

	public function PostAdminAction(&$params)
	{
		//ensure field name ends as expected
		$nm = $this->Name;
		if (strpos($nm,'&nbsp;&#187;&#187;',2) === FALSE) {
			$this->Name = $nm.'&nbsp;&#187;&#187;';
		}
	}

	public function AdminValidate($id)
	{
		$messages = array();
		$mod = $this->formdata->formsmodule;
		$ret = TRUE;
		$ref = $this->GetProperty('privatename');
		if ($ref) {
			foreach ($this->formdata->Fields as $obfld) {
				if ($obfld->Type == 'SequenceStart') {
					$p = $obfld->GetProperty('privatename');
					if ($p == $ref && $obfld != $this) {
						$ret = FALSE;
						$messages[] = $mod->Lang('err_typed',$mod->Lang('sequenceid'));
						break;
					}
				}
			}
		} else {
			$messages[] = $mod->Lang('missing_type',$mod->Lang('sequenceid')); //not fatal, warn user
		}

		$num = $this->GetProperty('mincount');
		if (!$num || $num < 1) {
			$num = 1;
			$this->SetProperty('mincount',1);
		}
		$num2 = $this->GetProperty('maxcount');
		if ($num2) {
			if ($num2 > 10) {
				$this->SetProperty('maxcount',10);
				$messages[] = $mod->Lang('err_typed',$mod->Lang('count')).': MAX';
			} elseif ($num2 < $num)
				$this->SetProperty('maxcount',$num);
		}
		$num3 = $this->GetProperty('repeatcount');
		if ($num3) {
			if ($num3 < $num || ($num2 && $num3 > $num2)) {
				$this->setProperty('repeatcount',$num);
				$messages[] = $mod->Lang('err_typed',$mod->Lang('count')).': INITIAL';
			}
		} else {
			$this->setProperty('repeatcount',$num);
			$messages[] = $mod->Lang('missing_type',$mod->Lang('count')).': INITIAL';
		}

		$msg = ($messages)?implode('<br />',$messages):'';
		return array($ret,$msg);
	}

	public function Populate($id,&$params)
	{
		$ret = array();
		$bnm = $id.$this->formdata->current_prefix.$this->Id;
		$bid = $this->GetInputId();
		//at this stage, don't know whether either/both buttons are relevant
		$propkeys = array('insert_label','delete_label');
		$nm = array('_SeX','_SeW');
		foreach ($propkeys as $i=>$key) {
			$m = $nm[$i];
			$oneset = new \stdClass();
			$oneset->name = '';
			$oneset->title = '';
			$oneset->input = '';
			$tmp = '<input type="button" name="'.$bnm.$m.'" id="'.$bid.$m.
			'" value="'.$this->GetProperty($key).'" />';
			$oneset->op = $this->SetClass($tmp);
			if ($i == 1) {
				$oneset->op .= ' &#187;&#187;';
			}
			$ret[] = $oneset;
		}
		return $ret;
	}
}
