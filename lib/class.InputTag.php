<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class supplies content from a UDT

namespace PWForms;

class InputTag extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsSortable = FALSE;
//		$this->NeedsDiv = FALSE;
		$this->Type = 'InputTag';
	}

	public function GetFieldStatus()
	{
		return $this->GetOption('udtname',$this->formdata->formsmodule->Lang('unspecified'));
	}

	public function AdminPopulate($id)
	{
		$usertags = cmsms()->GetUserTagOperations()->ListUserTags();
		$choices = array();
		foreach ($usertags as $key => $value)
			$choices[$value] = $key;

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_udt_name'),
						$mod->CreateInputDropdown($id,'opt_udtname',$choices,-1,
							$this->GetOption('udtname')));
		$adv[] = array($mod->Lang('title_export_form_to_udt'),
						$mod->CreateInputHidden($id,'opt_export_form',0).
						$mod->CreateInputCheckbox($id,'opt_export_form',1,
							$this->GetOption('export_form',0)));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		//setup variables for use in template
		$params = array();
		if ($this->GetOption('export_form',0))
			$params['FORM'] = $this->formdata;

		$mod = $this->formdata->formsmodule;
		$unspec = $this->GetFormOption('unspecified',$mod->Lang('unspecified'));
		foreach ($this->formdata->Fields as &$one) {
			$val = '';
			if ($one->DisplayInSubmission()) {
				$val = $one->GetDisplayableValue();
				if (!$val)
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
		$ret = $usertagops->CallUserTag($udt,$params);
		if ($ret !== FALSE)
			return $this->SetClass($tmp);
		return $mod->Lang('error_usertag_named',$udt);
	}

}
