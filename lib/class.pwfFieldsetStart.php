<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldsetStart extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->IsSortable = FALSE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'FieldsetStart';
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Begin Fieldset: '.$this->Value.']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_legend'),
						$mod->CreateInputText($id,'opt_legend',
							$this->GetOption('legend'),50));
		return array('main'=>$main,'adv'=>$adv);
	}

	function Populate($id,&$params)
	{
		$str = '<fieldset id="'.$this->GetInputId().'"';
//TODO in general, class applied to parent (div, table-cell etc)
/*		$opt = $this->GetOption('css_class');
		if($opt)
			$str .= ' class="'.$opt.'"';
*/
		$opt = $this->GetScript();
		if($opt)
			$str .= ' '.$opt;
		$str .= '>';
		$opt = $this->GetOption('legend');
		if($opt)
			$str .= '<legend>'.$opt.'</legend>';
		return $str;
	}

}

?>
