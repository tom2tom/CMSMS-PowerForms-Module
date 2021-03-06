<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2015-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class SequenceStart extends FieldBase
{
	public $IsSequence = TRUE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInSubmission = FALSE;
		$this->Alias = uniqid('start_'.$this->formdata->Id); //not user editable
		$this->LabelSubComponents = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'SequenceStart';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'change_count' => 10,
		'maxcount' => 11,
		'mincount' => 11,
		'repeatcount' => 11,
		'delete_label' => 12,
		'insert_label' => 12,
		'privatename' => 12,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		$t = $this->GetProperty('privatename', $mod->Lang('none2'));
		$ret = $mod->Lang('identifier').': '.$t;
		$ret .= ','.$this->GetProperty('repeatcount').' time(s)'; //TODO lang
		return $ret;
	}

	public function AdminPopulate($id)
	{
		$nm = $this->Name;
		if ($nm && ($p=strpos($nm, '&nbsp;&#187;&#187;', 2)) !== FALSE) {
			$this->Name = substr($nm, 0, $p);
		}
		$except = [
		'title_field_alias',
		'title_field_javascript',
		'title_field_resources',
		'title_smarty_eval'];
		list($main, $adv) = $this->AdminPopulateCommon($id, $except, FALSE);
		$mod = $this->formdata->pwfmod;
		//name-help
		$main[0][] = $mod->Lang('help_sequence_name', '&#187;&#187;');

		$def = uniqid('s'.$this->formdata->Id, FALSE);
		$main[] = [$mod->Lang('title_privatename'),
						$mod->CreateInputText($id, 'fp_privatename',
							$this->GetProperty('privatename', $def), 25, 50)];
		$main[] = [$mod->Lang('title_initial_count'),
						$mod->CreateInputText($id, 'fp_repeatcount',
							$this->GetProperty('repeatcount', 1), 2, 2),
						$mod->Lang('help_initial_count')];
		$main[] = [$mod->Lang('title_change_count'),
						$mod->CreateInputHidden($id, 'fp_change_count', 0).
						$mod->CreateInputCheckbox($id, 'fp_change_count', 1,
							$this->GetProperty('change_count', 1))];
		$main[] = [$mod->Lang('title_min_count'),
						$mod->CreateInputText($id, 'fp_mincount',
							$this->GetProperty('mincount', 1), 2, 2),
						$mod->Lang('help_min_count')];
		$main[] = [$mod->Lang('title_max_count'),
						$mod->CreateInputText($id, 'fp_maxcount',
							$this->GetProperty('maxcount', 0), 2, 2),
						$mod->Lang('help_limit_count')];
		$main[] = [$mod->Lang('title_add_button_seq'),
						$mod->CreateInputText($id, 'fp_insert_label',
							$this->GetProperty('insert_label', $mod->Lang('insert')), 25, 30)];
		$main[] = [$mod->Lang('title_del_button_seq'),
						$mod->CreateInputText($id, 'fp_delete_label',
							$this->GetProperty('delete_label', $mod->Lang('delete')), 25, 30)];

		return ['main'=>$main,'adv'=>$adv];
	}

	public function PostAdminAction(&$params)
	{
		//ensure field name ends as expected
		$this->Name .= '&nbsp;&#187;&#187;';
	}

	public function AdminValidate($id)
	{
		$messages = [];
		$mod = $this->formdata->pwfmod;
		$ret = TRUE;
		$ref = $this->GetProperty('privatename');
		if ($ref) {
			foreach ($this->formdata->Fields as $obfld) {
				if ($obfld->Type == 'SequenceStart') {
					$p = $obfld->GetProperty('privatename');
					if ($p == $ref && $obfld != $this) {
						$ret = FALSE;
						$messages[] = $mod->Lang('err_typed', $mod->Lang('sequenceid'));
						break;
					}
				}
			}
		} else {
			$messages[] = $mod->Lang('missing_type', $mod->Lang('sequenceid')); //not fatal, warn user
		}

		$num = $this->GetProperty('mincount');
		if (!$num || $num < 1) {
			$num = 1;
			$this->SetProperty('mincount', 1);
		}
		$num2 = $this->GetProperty('maxcount');
		if ($num2) {
			if ($num2 > 10) {
				$this->SetProperty('maxcount', 10);
				$messages[] = $mod->Lang('err_typed', $mod->Lang('count')).': MAX';
			} elseif ($num2 < $num) {
				$this->SetProperty('maxcount', $num);
			}
		}
		$num3 = $this->GetProperty('repeatcount');
		if ($num3) {
			if ($num3 < $num || ($num2 && $num3 > $num2)) {
				$this->SetProperty('repeatcount', $num);
				$messages[] = $mod->Lang('err_typed', $mod->Lang('count')).': INITIAL';
			}
		} else {
			$this->SetProperty('repeatcount', $num);
			$messages[] = $mod->Lang('missing_type', $mod->Lang('count')).': INITIAL';
		}
		//TODO conform 'change_count' property with SequenceEnd
		$msg = ($messages)?implode('<br />', $messages):'';
		return [$ret,$msg];
	}

	public function Populate($id, &$params)
	{
		if (!$this->GetProperty('change_count', 1)) {
			$this->MultiPopulate = FALSE;
			return '';
		}
		$ret = [];
		$bnm = $id.$this->formdata->current_prefix;
		$bid = $this->GetInputId();
		//at this stage, don't know whether either/both buttons are relevant, can't tailor
		$propkeys = ['insert_label','delete_label'];
		$nm = ['SeX_','SeW_'];
		foreach ($propkeys as $i=>$key) {
			$m = $nm[$i];
			$oneset = new \stdClass();
			$oneset->name = '';
			$oneset->title = '';
			$oneset->input = '';
			$tmp = '<input type="submit" name="'.$bnm.$m.$this->Id.'" id="'.$bid.$m.
			'" value="'.$this->GetProperty($key).' &darr;"';
			if ($i == 0) {
				$t = $this->formdata->pwfmod->Lang('tip_sequence_add');
				$tmp .= ' title="'.$t.'" />';
			} else {
				$t = $this->formdata->pwfmod->Lang('tip_sequence_del');
				$tmp .= ' title="'.$t.'" onclick="return confirm(\''.$this->formdata->pwfmod->Lang('confirm').'\');" />';
			}
			$oneset->op = $this->SetClass($tmp);
			if ($i == 1) {
				$oneset->op .= ' &#187;&#187;';
			}
			$ret[] = $oneset;
		}
		$this->MultiPopulate = TRUE;
		return $ret;
	}
}
