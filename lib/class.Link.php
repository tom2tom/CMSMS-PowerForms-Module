<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class Link extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->IsInput = TRUE;
		$this->Required = FALSE;
		$this->Type = 'Link';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'default_link' => 12,
		'default_link_title' => 12,
		];
	}

/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}
*/
	public function DisplayableValue($as_string=TRUE)
	{
		if (is_array($this->Value)) {
			$ret = '<a href="'.$this->Value[0].'">'.$this->Value[1].'</a>';
		} else {
			$ret = '';
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

		$main[] = [$mod->Lang('title_default_link'),
					$mod->CreateInputText($id, 'fp_default_link',
						$this->GetProperty('default_link'), 25, 128)];
		$main[] = [$mod->Lang('title_default_link_title'),
					$mod->CreateInputText($id, 'fp_default_link_title',
						$this->GetProperty('default_link_title'), 25, 128)];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$js = $this->GetScript();

		if (is_array($this->Value)) {
			$val = $this->Value;
		} else {
			$val = [$this->GetProperty('default_link'),$this->GetProperty('default_link_title')];
		}

		$ret = [];
		$oneset = new \stdClass();
		$tid = $this->GetInputId('_1');
		$oneset->title = $mod->Lang('link_destination');
		$tmp = '<label for="'.$tid.'">'.$oneset->title.'</label>';
//TODO does $val[0] need html_entity_decode()?
		$tmp = $mod->CreateInputText(
			$id, $this->formdata->current_prefix.$this->Id.'[]',
			html_entity_decode($val[0]), '', '',
			$js);
		$oneset->name = $this->SetClass($tmp);
		$tmp = preg_replace('/id="\S+"/', 'id="'.$tid.'"', $tmp);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $oneset;

		$oneset = new \stdClass();
		$tid = $this->GetInputId('_2');
		$oneset->title = $mod->Lang('link_label');
		$tmp = '<label for="'.$tid.'">'.$oneset->title.'</label>';
		$oneset->name = $this->SetClass($tmp);
//TODO ibid does $val[1] ever need html_entity_decode()?
		$tmp = $mod->CreateInputText(
			$id, $this->formdata->current_prefix.$this->Id.'[]',
			$val[1], '', '',
			$js);
		$tmp = preg_replace('/id="\S+"/', 'id="'.$tid.'"', $tmp);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $oneset;
		$this->MultiPopulate = TRUE;
		return $ret;
	}
}
