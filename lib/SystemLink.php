<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PowerForms;

class SystemLink extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->IsSortable = FALSE;
		$this->MultiPopulate = TRUE;
		$this->Required = FALSE;
		$this->Type = 'SystemLink';
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_none')=>'none');
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		if ($this->GetOption('auto_link',0)) {
			$pageinfo = cmsms()->variables['pageinfo'];
			$ret = $this->formdata->formsmodule->CreateContentLink($pageinfo->content_id,$pageinfo->content_title);
		} else {
			$contentops = cmsms()->GetContentOperations();
			$cobj = $contentops->LoadContentFromId($this->GetOption('target_page',0));
			$ret = $this->formdata->formsmodule->CreateContentLink($cobj->Id(),$cobj->Name());
		}

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_auto_link'),
						$mod->CreateInputHidden($id,'opt_auto_link',0).
						$mod->CreateInputCheckbox($id,'opt_auto_link',1,
							$this->GetOption('auto_link',0)),
						$mod->Lang('help_auto_link'));
		$main[] = array($mod->Lang('title_target_page'),
						Utils::CreateHierarchyPulldown($mod,$id,'opt_target_page',
							$this->GetOption('target_page',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		if ($this->GetOption('auto_link',0)) {
			$oneset = new stdClass();
			$pageinfo = cmsms()->variables['pageinfo'];
			$oneset->name = $pageinfo->content_title;
			$oneset->title = $oneset->name;
			$tmp = $this->formdata->formsmodule->CreateContentLink($pageinfo->content_id,$oneset->name);
			$oneset->input = $this->SetClass($tmp);
			$this->MultiPopulate = TRUE;
			return array($oneset);
		} else {
			$page = $this->GetOption('target_page',0);
			if ($page > 0) {
				$oneset = new stdClass();
				$contentops = cmsms()->GetContentOperations();
				$cobj = $contentops->LoadContentFromId($page);
				$oneset->name = $cobj->Name();
				$oneset->title = $oneset->name;
				$tmp = $this->formdata->formsmodule->CreateContentLink($cobj->Id(),$oneset->name);
				$oneset->input = $this->SetClass($tmp);
				$this->MultiPopulate = TRUE;
				return array($oneset);
			}
		}
		$this->MultiPopulate = FALSE;
		return '';
	}

}

