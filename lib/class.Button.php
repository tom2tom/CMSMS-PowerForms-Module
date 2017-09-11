<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Button extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->Type = 'Button';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + ['text' => 12];
	}

/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}
*/
	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_button_text'),
						$mod->CreateInputText($id, 'fp_text',
							$this->GetProperty('text'), 40)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$tmp = '<input type="button" id="'.$this->GetInputId().'" name="'.
			$id.$this->formdata->current_prefix.$this->Id.
			'" value="'.$this->GetProperty('text').'"'.$this->GetScript().' />';
		return $this->SetClass($tmp);
	}
}
