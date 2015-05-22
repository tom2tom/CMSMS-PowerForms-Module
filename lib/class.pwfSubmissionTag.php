<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSubmissionTagField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'SubmissionTagField';
		$this->IsDisposition = TRUE;
		$this->NonRequirableField = TRUE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
	}

	function GetFieldStatus()
	{
		return $this->GetOption('udtname',$this->formdata->formsmodule->Lang('unspecified'));
	}

	function DisposeForm($returnid)
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
		$unspec = $this->GetFormOption('unspecified',$mod->Lang('unspecified'));
		$params = array();
		if($this->GetOption('export_form',0))
			$params['FORM'] = $this->formdata;

		for($i=0; $i<count($others); $i++)
		{
			$replVal = '';
			if($others[$i]->DisplayInSubmission())
			{
				$replVal = $others[$i]->GetHumanReadableValue();
				if($replVal == '')
				{
					$replVal = $unspec;
				}
			}
			$name = $others[$i]->GetVariableName();
			$params[$name] = $replVal;
			$id = $others[$i]->GetId();
			$params['fld_'.$id] = $replVal;
			$alias = $others[$i]->GetAlias();
			if(!empty($alias))
				$params[$alias] = $replVal;
		}

		pwfUtils::SetFinishedFormSmarty($this->formdata);
		$usertagops = cmsms()->GetUserTagOperations();
		$res = $usertagops->CallUserTag($this->GetOption('udtname'),$params);

		if($res === FALSE)
			return array(FALSE,$mod->Lang('error_usertag'));
		return array(TRUE,'');
	}

	function PrePopulateAdminForm($module_id)
	{
		$usertagops = cmsms()->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;

		$mod = $this->formdata->formsmodule;
		$main = array();
		$main[] = array($mod->Lang('title_udt_name'),
			$mod->CreateInputDropdown($module_id,'opt_udtname',$usertaglist,-1,$this->GetOption('udtname')));
		$main[] = array($mod->Lang('title_export_form_to_udt'),
			$mod->CreateInputHidden($module_id,'opt_export_form',0).
			$mod->CreateInputCheckbox($module_id,'opt_export_form',1,$this->GetOption('export_form',0)));
		return array('main'=>$main);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}
}

?>

