<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class allows sending an email to a destination selected from a pulldown

class pwfEmailDirector extends pwfEmailBase
{
	var $addressAdd = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'EmailDirector';
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
				$this->RemoveOptionElement('destination_address',$indx);
				$this->RemoveOptionElement('destination_subject',$indx);
			}
		}
	}

	function GetFieldStatus()
	{
		$opt = $this->GetOption('destination_address');
		if(is_array($opt))
			$num = count($opt);
		elseif($opt)
			$num = 1;
		else
			$num = 0;

		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('destination_count',$num);
		$status = $this->TemplateStatus();
		if($status)
			$ret .= '<br />'.$status;
		return $ret;
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->HasValue())
			$ret = $this->GetOptionElement('destination_subject',$this->Value);
		else
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));

		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv,$funcs,$extra) = $this->AdminPopulateCommonEmail($id);
		$mod = $this->formdata->formsmodule;
		// remove the "email subject" field
		$this->RemoveAdminField($main,
			$mod->Lang('title_email_subject'));
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,'opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_allow_subject_override'),
			$mod->CreateInputHidden($id,'opt_subject_override',0).
			$mod->CreateInputCheckbox($id,'opt_subject_override',1,
				$this->GetOption('subject_override',0)),
			$mod->Lang('help_allow_subject_override'));
		if($this->addressAdd)
		{
			$this->AddOptionElement('destination_subject','');
			$this->AddOptionElement('destination_address','');
			$this->addressAdd = FALSE;
		}
		$opt = $this->GetOptionRef('destination_address');
		if($opt)
		{
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_selection_subject'),
				$mod->Lang('title_destination_address'),
				$mod->Lang('title_select')
				);
			foreach($opt as $i=>&$one)
			{
				$dests[] = array(
				$mod->CreateInputText($id,'opt_destination_subject'.$i,
					$this->GetOptionElement('destination_subject',$i),40,128),
				$mod->CreateInputText($id,'opt_destination_address'.$i,$one,50,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_director_details'),$dests);
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests,'funcs'=>$funcs,'extra'=>$extra);
		}
		else
		{
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('destination')));
			return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>$extra);
		}
	}

	function PostAdminAction(&$params)
	{
		//cleanup empties
		$addrs = $this->GetOptionRef('destination_address');
		if($addrs)
		{
			foreach($addrs as $i=>&$one)
			{
				if(!$one || !$this->GetOptionElement('destination_subject',$i))
				{
					$this->RemoveOptionElement('destination_address',$i);
					$this->RemoveOptionElement('destination_subject',$i);
				}
			}
			unset($one);
		}
		$this->PostAdminActionEmail($params);
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;
	
		$mod = $this->formdata->formsmodule;
		list($rv,$msg) = $this->validateEmailAddr($this->GetOption('email_from_address'));
		if(!$rv)
		{
			$ret = FALSE;
			$messages[] = $msg;
		}
		$dests = $this->GetOption('destination_address');
		$c = count($dests);
		if($c)
		{
			for($i=0; $i<$c; $i++)
			{
				list($rv,$msg) = $this->validateEmailAddr($dests[$i]);
				if(!$rv)
				{
					$ret = FALSE;
					$messages[] = $msg;
				}
			}
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('missing_type',$mod->Lang('destination'));
		}
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	function Populate($id,&$params)
	{
		$subjects = $this->GetOptionRef('destination_subject');
		if($subjects)
		{
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one'))=>-1)
				+ array_flip($subjects);
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	function Validate($id)
	{
		if($this->Value)
		{
	  		$this->validated = TRUE;
  			$this->ValidationMessage = '';
		}
		else
		{
	  		$this->validated = FALSE;
			$mod = $this->formdata->formsmodule;
  			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('destination'));
		}
		return array($this->validated,$this->ValidationMessage);
	}

	function Dispose($id,$returnid)
	{
		if($this->GetOption('subject_override',0) && $this->GetOption('email_subject'))
			$subject = $this->GetOption('email_subject');
		else
			$subject = $this->GetOptionElement('destination_subject',$this->Value);

		return $this->SendForm($this->GetOptionElement('destination_address',$this->Value),$subject);
	}

}

?>
