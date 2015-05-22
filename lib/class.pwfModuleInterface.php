<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This class written by Jeremy Bass <jeremyBass@cableone.net> */

//class supplies content from a template

class pwfModuleInterfaceField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasLabel = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'ModuleInterfaceField';
		$this->IsSortable = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('FBid',$id.'pwfp_'.$this->Id);
		// for selected... what to do here
		// for things like checked="checked" on the back page
		$smarty->assign('FBvalue',$this->Value);

		$v = $this->GetOption('value');
		return $this->formdata->formsmodule->ProcessTemplateFromData($v);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
				array($mod->Lang('title_tag'),
            		$mod->CreateInputText($module_id,'opt_value',$this->GetOption('value'),100,1024),
            		$mod->Lang('help_tag')),
					)
		);
		return array('main'=>$main);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$formdata = $this->formdata;

		if($this->HasValue())
		{
			if(is_array($this->Value))
			{
				if($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$this->Value);
				else
				{
					$ret = $this->Value;
					return $ret; //a copy
				}
			}
			$ret = $this->Value;
		}
		else
		{
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

}

?>
