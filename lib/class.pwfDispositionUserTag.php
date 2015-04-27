<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDispositionUserTag extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionUserTag';
		$this->IsDisposition = true;
		$this->NonRequirableField = true;
		$this->DisplayInForm = false;
		$this->DisplayInSubmission = false;
		$this->sortable = false;
	}

	function StatusInfo()
	{
		$mod=$this->formdata->pwfmodule;
		return $this->GetOption('udtname',$mod->Lang('unspecified'));
	}

	function DisposeForm($returnid)
	{
		$mod=$this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		$unspec = $this->formdata->GetAttr('unspecified',$mod->Lang('unspecified'));
		$params = array();
		if($this->GetOption('export_form','0') == '1')
		{
			$params['FORM'] = $this->formdata;
		}
		for($i=0;$i<count($others);$i++)
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
			{
				$params[$alias] = $replVal;
			}
		}

		$this->formdata->setFinishedFormSmarty();
		$usertagops = cmsms()->GetUserTagOperations();
		$res = $usertagops->CallUserTag($this->GetOption('udtname'), $params);

		if($res === FALSE)
		{
			return array(false,$mod->Lang('error_usertag_disposition'));
		}
		return array(true,'');
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$main = array();

		$usertagops = cmsms()->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		foreach($usertags as $key => $value)
		{
			$usertaglist[$value] = $key;
		}
		$main[] = array($mod->Lang('title_udt_name'),
				$mod->CreateInputDropdown($formDescriptor,
							  'pwfp_opt_udtname',$usertaglist,-1,
							  $this->GetOption('udtname')));
		$main[] = array($mod->Lang('title_export_form_to_udt'),
				  $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_export_form','0').
					$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_export_form',
					'1',$this->GetOption('export_form','0')));
		return array('main'=>$main);
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$this->HiddenDispositionFields($mainArray, $advArray);
	}
}

?>
