<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfPageRedirector extends pwfFieldBase
{
	var $addressAdd = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'PageRedirector';
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_destination');
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_destination');
	}

	function DoOptionAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		if(isset($params['selected']))
		{
			foreach($params['selected'] as $indx)
			{
				$this->RemoveOptionElement('destination_page',$indx);
				$this->RemoveOptionElement('destination_subject',$indx);
			}
		}
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOption('destination_page');
		if(is_array($opt))
			$num = count($opt);
		elseif($opt)
			$num = 1;
		else
			$num = 0;
		return $this->formdata->formsmodule->Lang('destination_count',$num);
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
			$ret = $this->GetOptionElement('destination_page',$this->Value);
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;

		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,'opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
//		$main[] = array($mod->Lang('title_director_details'),$dests);
		$dests = array();

		if($this->addressAdd)
		{
			$this->AddOptionElement('destination_page','');
			$this->AddOptionElement('destination_subject','');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetOptionRef('destination_page');
		if($opt)
		{
			$dests[] = array(
				$mod->Lang('title_selection_subject'),
				$mod->Lang('title_destination_page'),
				$mod->Lang('title_select')
				);
			$contentops = cmsms()->GetContentOperations();
			foreach($opt as $i=>&$one)
			{
				$dests[] = array(
				$mod->CreateInputText($id,'opt_destination_subject'.$i,$this->GetOptionElement('destination_subject',$i),30,128),
				$contentops->CreateHierarchyDropdown('',$one,$i),$id.'opt_destination_page[]'),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminVisible($mainArray,$advArray);
	}

	function PostAdminSubmitCleanup(&$params)
	{
		//cleanup empties
		$pages = $this->GetOptionRef('destination_page');
		if($pages)
		{
			foreach($pages as $i=>&$one)
			{
				if(!$one || !$this->GetOptionElement('destination_subject',$i))
				{
					$this->RemoveOptionElement('destination_page',$i);
					$this->RemoveOptionElement('destination_subject',$i);
				}
			}
			unset($one);
		}
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		if(!$this->GetOption('destination_page'))
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('must_specify_one_destination');
		}
		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	function Populate($id,&$params)
	{
		$pages = $this->GetOptionRef('destination_subject');
		if($pages)
		{
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one')) => '')
				+ array_flip($pages);
			return $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
		}
		return '';
	}

	function Dispose($id,$returnid)
	{
		//TODO ensure all other dispositions are run before this
		$mod = $this->formdata->formsmodule;
		$mod->RedirectContent($this->GetOptionElement('destination_page',$this->Value));
		return array(TRUE,'');
	}

}

?>
