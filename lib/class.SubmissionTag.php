<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SubmissionTag extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'SubmissionTag';
	}

	public function GetFieldStatus()
	{
		return $this->GetProperty('udtname',$this->formdata->formsmodule->Lang('unspecified'));
	}

	public function AdminPopulate($id)
	{
		$usertags = \cmsms()->GetUserTagOperations()->ListUserTags();
		$choices = array();
		foreach ($usertags as $key => $value)
			$choices[$value] = $key;

		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE,FALSE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_udt_name'),
						$mod->CreateInputDropdown($id,'pdt_udtname',$choices,-1,
							$this->GetProperty('udtname')));
		$main[] = array($mod->Lang('title_export_form_to_udt'),
						$mod->CreateInputHidden($id,'pdt_export_form',0).
						$mod->CreateInputCheckbox($id,'pdt_export_form',1,
							$this->GetProperty('export_form',0)));

		return array('main'=>$main,'adv'=>$adv);
	}

	public function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		$unspec = $this->GetFormProperty('unspecified',$mod->Lang('unspecified'));

		$params = array();
		if ($this->GetProperty('export_form',0))
			$params['FORM'] = $this->formdata;

		foreach ($this->formdata->Fields as &$one) {
			$val = '';
			if ($one->DisplayInSubmission()) {
				$val = $one->GetDisplayableValue();
				if (!$val)
					$val = $unspec;
			}
			$name = $one->GetVariableName();
			$params[$name] = $val;
			$alias = $one->ForceAlias();
			$params[$alias] = $val;
			$id = $others[$i]->GetId();
			$params['fld_'.$id] = $val;
		}
		unset($one);

		$tplvars = array();
		Utils::SetupFormVars($this->formdata,$tplvars);
		if ($tplvars) {
			$smarty = \cmsms()->GetSmarty();
			$smarty->assign($tplvars);
		}
		$usertagops = \cmsms()->GetUserTagOperations();
		$res = $usertagops->CallUserTag($this->GetProperty('udtname'),$params);

		if ($res === FALSE)
			return array(FALSE,$mod->Lang('err_usertag'));
		return array(TRUE,'');
	}
}
