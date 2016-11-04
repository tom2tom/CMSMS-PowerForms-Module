<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class Link extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->ChangeRequirement = FALSE;
		$this->IsInput = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Required = FALSE;
		$this->Type = 'Link';
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if (is_array($this->Value))
			$ret = '<a href="'.$this->Value[0].'">'.$this->Value[1].'</a>';
		else
			$ret = '';

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE);
		$mod = $this->formdata->formsmodule;

		$main[] = array($mod->Lang('title_default_link'),
						$mod->CreateInputText($id,'fp_default_link',
							$this->GetProperty('default_link'),25,128));
		$main[] = array($mod->Lang('title_default_link_title'),
						$mod->CreateInputText($id,'fp_default_link_title',
							$this->GetProperty('default_link_title'),25,128));
		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetScript();

		if (is_array($this->Value))
			$val = $this->Value;
		else
			$val = array($this->GetProperty('default_link'),$this->GetProperty('default_link_title'));

		$ret = array();
		$oneset = new \stdClass();
		$tid = $this->GetInputId('_1');
		$oneset->title = $mod->Lang('link_destination');
		$tmp = '<label for="'.$tid.'">'.$oneset->title.'</label>';
//TODO does $val[0] need html_entity_decode()?
		$tmp = $mod->CreateInputText(
			$id,$this->formdata->current_prefix.$this->Id.'[]',
			html_entity_decode($val[0]),'','',
			$js);
		$oneset->name = $this->SetClass($tmp);
		$tmp = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $oneset;

		$oneset = new \stdClass();
		$tid = $this->GetInputId('_2');
		$oneset->title = $mod->Lang('link_label');
		$tmp = '<label for="'.$tid.'">'.$oneset->title.'</label>';
		$oneset->name = $this->SetClass($tmp);
//TODO ibid does $val[1] ever need html_entity_decode()?
		$tmp = $mod->CreateInputText(
			$id,$this->formdata->current_prefix.$this->Id.'[]',
			$val[1],'','',
			$js);
		$tmp = preg_replace('/id="\S+"/','id="'.$tid.'"',$tmp);
		$oneset->input = $this->SetClass($tmp);
		$ret[] = $oneset;
		return $ret;
	}
}
