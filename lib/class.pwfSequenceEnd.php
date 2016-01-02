<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSequenceEnd extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE; //all handled by SequenceStart
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->IsSortable = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'SequenceEnd';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[End FieldSequence: '.$this->GetOption('starter').']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$this->RemoveAdminField($main,$mod->Lang('title_field_alias'));
		$this->RemoveAdminField($main,$mod->Lang('title_field_validation'));
		$this->RemoveAdminField($main,$mod->Lang('title_field_helptext'));
/*		$choices = array of current starter fields + notyet
		$main[] = array($mod->Lang(''),
						$mod->CreateInputDropdown($id,'opt_starter',$choices,
							-1,$this->GetOption('starter')));
*/
		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'opt_privatename',
							$this->GetOption('privatename',htmlentities($this->Name)),20,50));

		$main[] = array($mod->Lang('title_startername'),
						$mod->CreateInputText($id,'opt_starter',
							$this->GetOption('starter'),20,50));
		return array('main'=>$main,'adv'=>array());
	}

/*	function PostAdminAction(&$params)
	{
		'starter' option = that_field ->Options['name'], not ->Name
		conform any starterfield::opt_ender
	}

	function AdminValidate($id)
	{
		unique privatenamename
		warn if startername not found
		warn if order is not after corresponding sequence start
	}
*/
/*	function Populate($id,&$params)
	{
		return '</div><!-- end sequence '.$this->GetOption('starter').' -->';
	}
*/
}

?>
