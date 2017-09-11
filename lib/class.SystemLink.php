<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class SystemLink extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->Required = FALSE;
		$this->Type = 'SystemLink';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'auto_link' => 10,
		'target_page' => 11,
		];
	}

/*	public function GetSynopsis()
	{
 		return $this->formdata->pwfmod->Lang('').': STUFF';
	}
*/
	public function DisplayableValue($as_string=TRUE)
	{
		if ($this->GetProperty('auto_link', 0)) {
			$pageinfo = \cmsms()->variables['pageinfo'];
			$ret = $this->formdata->pwfmod->CreateContentLink($pageinfo->content_id, $pageinfo->content_title);
		} else {
			$contentops = \cmsms()->GetContentOperations();
			$cobj = $contentops->LoadContentFromId($this->GetProperty('target_page', 0));
			$ret = $this->formdata->pwfmod->CreateContentLink($cobj->Id(), $cobj->Name());
		}

		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->pwfmod;
		$main[] = [$mod->Lang('title_auto_link'),
					$mod->CreateInputHidden($id, 'fp_auto_link', 0).
					$mod->CreateInputCheckbox($id, 'fp_auto_link', 1,
						$this->GetProperty('auto_link', 0)),
					$mod->Lang('help_auto_link')];
		$main[] = [$mod->Lang('title_target_page'),
					Utils::CreateHierarchyPulldown($mod, $id, 'fp_target_page',
						$this->GetProperty('target_page', 0))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		if ($this->GetProperty('auto_link', 0)) {
			$oneset = new \stdClass();
			$pageinfo = \cmsms()->variables['pageinfo'];
			$oneset->name = $pageinfo->content_title;
			$oneset->title = $oneset->name;
			$tmp = $this->formdata->pwfmod->CreateContentLink($pageinfo->content_id, $oneset->name);
			$oneset->input = $this->SetClass($tmp);
			$this->MultiPopulate = TRUE;
			return [$oneset];
		} else {
			$page = $this->GetProperty('target_page', 0);
			if ($page > 0) {
				$oneset = new \stdClass();
				$contentops = \cmsms()->GetContentOperations();
				$cobj = $contentops->LoadContentFromId($page);
				$oneset->name = $cobj->Name();
				$oneset->title = $oneset->name;
				$tmp = $this->formdata->pwfmod->CreateContentLink($cobj->Id(), $oneset->name);
				$oneset->input = $this->SetClass($tmp);
				$this->MultiPopulate = TRUE;
				return [$oneset];
			}
		}
		$this->MultiPopulate = FALSE;
		return '';
	}
}
