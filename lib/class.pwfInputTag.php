<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class supplies content from a UDT

class pwfInputTag extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
//		$this->NeedsDiv = FALSE;
		$this->Type = 'InputTag';
	}

	function GetFieldStatus()
	{
		return $this->GetOption('udtname',$this->formdata->formsmodule->Lang('unspecified'));
	}

	function PrePopulateAdminForm($id)
	{
		$usertagops = cmsms()->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;

		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_udt_name'),
			$mod->CreateInputDropdown($id,'opt_udtname',$usertaglist,-1,
			  $this->GetOption('udtname')));
		$main[] = array($mod->Lang('title_export_form_to_udt'),
			$mod->CreateInputHidden($id,'opt_export_form',0).
			$mod->CreateInputCheckbox($id,'opt_export_form',1,
				$this->GetOption('export_form',0)));
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		//setup variables for use in template
		$params = array();
		if($this->GetOption('export_form',0))
			$params['FORM'] = $this->formdata;

		$mod = $this->formdata->formsmodule;
		$unspec = $this->GetFormOption('unspecified',$mod->Lang('unspecified'));
		foreach($this->formdata->Fields as &$one)
		{
			$val = '';
			if($one->DisplayInSubmission())
			{
				$val = $one->GetHumanReadableValue();
				if(!$val)
					$val = $unspec;
			}
			$params[$name] = $val;
			$alias = $one->ForceAlias();
			$params[$alias] = $val;
			$id = $one->GetId();
			$params['fld_'.$id] = $val;
		}
		unset($one);

		$usertagops = cmsms()->GetUserTagOperations();
		$udt = $this->GetOption('udtname');
		$res = $usertagops->CallUserTag($udt,$params);
		if($res !== FALSE)
			return $res;
		return $mod->Lang('error_usertag_named',$udt);
	}

}

?>
