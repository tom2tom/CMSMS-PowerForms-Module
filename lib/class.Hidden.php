<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Hidden extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'Hidden';
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE, FALSE); //TODO hidden field may use logic?

		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_value'),
						$mod->CreateInputText($id, 'fp_value',
							$this->GetProperty('value'), 25, 1024)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;

		if ($this->Value || is_numeric($this->Value)) {
			if (!is_numeric($this->Value) && $this->GetProperty('smarty_eval', 0)) {
				$tplvars = [];
				$val = Utils::ProcessTemplateFromData($mod, $this->Value, $tplvars);
			} else {
				$val = $this->Value;
			}
		} else {
			$val = '';
		}

		$tmp = '<input type="hidden" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.'" value="'.$val.'" />';
		return $this->SetClass($tmp);
	}
}
