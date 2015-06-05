<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file by Jeremy Bass <jeremyBass@cableone.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class supplies content from a template

class pwfInputTemplate extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
//		$this->HasLabel = FALSE;
		$this->IsSortable = FALSE;
//		$this->NonRequirableField = TRUE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'InputTemplate';
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

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$main = array(
			array($mod->Lang('title_tag'),
				$mod->CreateInputText($id,'opt_value',$this->GetOption('value'),100,1024),
            	$mod->Lang('help_tag')),
					);
		return array('main'=>$main);
	}

	function Populate($id,&$params)
	{
		$smarty = cmsms()->GetSmarty();
		$smarty->assign('FBid',$id.$this->formdata->current_prefix.$this->Id);
		// for selected... what to do here TODO
		// for things like checked="checked" on the back page
		$smarty->assign('FBvalue',$this->Value);

		$val = $this->GetOption('value');
		return $this->formdata->formsmodule->ProcessTemplateFromData($val);
	}

}

?>