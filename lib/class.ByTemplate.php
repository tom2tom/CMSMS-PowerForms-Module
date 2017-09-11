<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Derived in part from FormBuilder-module file by Jeremy Bass <jeremyBass@cableone.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class supplies content from a template

namespace PWForms;

class ByTemplate extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
//		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
//		$this->IsInput = TRUE; TODO runtime property
		$this->NeedsDiv = FALSE;
		$this->Type = 'ByTemplate';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + ['value' => 12];
	}

/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}

	public function SetValue()
	{
	}
*/
	public function DisplayableValue($as_string=TRUE)
	{
		$formdata = $this->formdata;

		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				if ($as_string) {
					return implode($this->GetFormProperty('list_delimiter', ','), $this->Value);
				} else {
					$ret = $this->Value;
					return $ret; //a copy?
				}
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->pwfmod->Lang('unspecified'));
		}
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_tag'),
				$mod->CreateInputText($id, 'fp_value', $this->GetProperty('value'), 30, 100),
				$mod->Lang('help_tag')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$tplvars = [];
		$tplvars['PFid'] = $id.$this->formdata->current_prefix.$this->Id; //TODO varname
		// for selected... what to do here TODO
		// for things like checked="checked" on the back page
		$tplvars['PFvalue'] = $this->Value; //TODO varname

		$val = $this->GetProperty('value');
		return Utils::ProcessTemplateFromData($this->formdata->pwfmod, $val, $tplvars);
	}
}
