<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSystemLinkField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->NonRequirableField = TRUE;
		$this->Required = FALSE;
		$this->Type = 'SystemLinkField';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
        $this->HasMultipleFormComponents = TRUE;
        $this->IsSortable = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$thisLink = new stdClass();
		$gCms = cmsms();
		if($this->GetOption('auto_link','0') == '1')
		{
			$pageinfo = $gCms->variables['pageinfo'];
			$thisLink->input = $this->formdata->formsmodule->CreateContentLink($pageinfo->content_id,$pageinfo->content_title);
			$thisLink->name = $pageinfo->content_title;
			$thisLink->title = $pageinfo->content_title;
		}
		else
		{
			$contentops = $gCms->GetContentOperations();
			$cobj = $contentops->LoadContentFromId($this->GetOption('target_page','0'));
			$thisLink->input = $this->formdata->formsmodule->CreateContentLink($cobj->Id(),$cobj->Name());
			$thisLink->name = $cobj->Name();
			$thisLink->title = $cobj->Name();
		}
		return array($thisLink);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->GetOption('auto_link',0))
		{
			$pageinfo = cmsms()->variables['pageinfo'];
			$ret = $this->formdata->formsmodule->CreateContentLink($pageinfo->content_id,$pageinfo->content_title);
		}
		else
		{
			$contentops = cmsms()->GetContentOperations();
    		$cobj = $contentops->LoadContentFromId($this->GetOption('target_page','0'));
			$ret = $this->formdata->formsmodule->CreateContentLink($cobj->Id(),$cobj->Name());
		}

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$contentops = cmsms()->GetContentOperations();

		$main = array(
				array($mod->Lang('title_link_autopopulate'),
					 $mod->CreateInputCheckbox($module_id,'opt_auto_link',
            		'1',$this->GetOption('auto_link','0')),
					 $mod->Lang('help_link_autopopulate')),
             array($mod->Lang('title_link_to_sitepage'),
				 	$contentops->CreateHierarchyDropdown('',$this->GetOption('target_page'),$module_id.'opt_target_page'))
		);
		return array('main'=>$main);
	}

}

?>
