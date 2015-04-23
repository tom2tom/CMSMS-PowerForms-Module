<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

require_once(cms_join_path(dirname(__FILE__),'DispositionEmailBase.class.php'));

class pwfDispositionEmail extends pwfDispositionEmailBase
{
	var $addressCount;
	var $addressAdd;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'DispositionEmail';
		$this->DisplayInForm = false;
		$this->DisplayInSubmission = false;
		$this->NonRequirableField = true;
		$this->IsDisposition = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->ValidationTypes = array();
	}

	function GetOptionAddButton()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('add_address');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('delete_address');
	}

	function DoOptionAdd(&$params)
	{
		$this->addressAdd = 1;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,9) == 'pwfp_sel_')
			{
				$this->RemoveOptionElement('destination_address', $thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function StatusInfo()
	{
		$mod = $this->form_ptr->module_ptr;
		$ret= $mod->Lang('to').': ';
		$opt = $this->GetOption('destination_address','');
		if(is_array($opt))
		{
			if(count($opt)>1)
			{
				$ret.= count($opt).' '.$mod->Lang('recipients');
			}
			else
			{
				$pref = substr($opt[0],0,4);
				if($pref == '|cc|')
					$ret = $mod->Lang('cc').': '.substr($opt[0],4);
				elseif($pref == '|bc|')
					$ret = $mod->Lang('bcc').': '.substr($opt[0],4);
				else
					$ret.= $opt[0];
			}
		}
		elseif($opt != '')
		{
			$pref = substr($opt,0,4);
			if($pref == '|cc|')
				$ret = $mod->Lang('cc').': '.substr($opt,4);
			elseif($pref == '|bc|')
				$ret = $mod->Lang('bcc').': '.substr($opt,4);
			else
				$ret .= $opt;
		}
		else
		{
			$ret.= $mod->Lang('unspecified');
		}
		$status = $this->TemplateStatus();
		if($status)
			$ret.='<br />'.$status;
        return $ret;
	}

	function countAddresses()
	{
		$tmp = $this->GetOptionRef('destination_address');
		if(is_array($tmp))
		{
			$this->addressCount = count($tmp);
		}
		elseif($tmp !== false)
		{
			$this->addressCount = 1;
		}
		else
		{
			$this->addressCount = 0;
		}
	}

    // send emails
	function DisposeForm($returnid)
	{
		$tmp = $this->GetOptionRef('destination_address');
		return $this->SendForm($tmp,$this->GetOption('email_subject'));
	}

	function GetDests ($formDescriptor, $row, $sel)
	{
		$id = cms_htmlentities($formDescriptor);
		$name = $id.'pwfp_aef_to_'.$row; //must be distinct for each address
		$totypes = array ('to','cc','bc');
		$btns = array();
		for ($i=0;$i<3;$i++)
		{
			$text = '<input class="cms_radio" style="margin-left:5px;" type="radio" name="'.$name.'" id="'.$id.'pwfp_aef_to" value="'.$totypes[$i].'"';
			if($sel == $totypes[$i])
				$text .= ' checked="checked"';
			$text .= ' />';
			$btns[] = $text;
		}
		return $btns;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;

		$this->countAddresses();

		if($this->addressAdd > 0)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = 0;
		}

		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
//		$main[] = array($mod->Lang('title_destination_address'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_destination_address'),
			$mod->Lang('to'),
			$mod->Lang('cc'),
			$mod->Lang('bcc'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$addr = $this->GetOptionElement('destination_address',$i);
			if($addr != '')
			{
				switch (substr($addr,0,4))
				{
					case '|cc|':
					$totype = 'cc';
					$addr = substr($addr,4);
					break;
					case '|bc|':
					$totype = 'bc';
					$addr = substr($addr,4);
					break;
					default:
					$totype = 'to';
					break;
				}
			}
			else
				$totype = 'to';
			$btns = self::GetDests($formDescriptor, $i, $totype);

			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_address[]',$addr,50,128),
			array_shift ($btns),
			array_shift ($btns),
			array_shift ($btns),
			$mod->CreateInputCheckbox($formDescriptor, 'pwfp_sel_'.$i, $i,-1,'style="margin-left:1em;"')
			);
		}
		$ret['table']= $dests;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$this->HiddenDispositionFields($mainArray, $advArray);
	}

	function AdminValidate()
	{
		$mod = $this->form_ptr->module_ptr;

  		list($ret, $message) = $this->DoesFieldHaveName();
		if($ret)
		{
			list($ret, $message) = $this->DoesFieldNameExist();
		}
		list($rv,$mess) = $this->validateEmailAddr($this->GetOption('email_from_address'));
		if(!$rv)
		{
			$ret = false;
			$message .= $mess;
		}
    	$opt = $this->GetOptionRef('destination_address');
		if($opt == false || count($opt) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('must_specify_one_destination').'<br />';
		}
		else
		{
			if(!is_array($opt))
				$opt = array($opt);
			$num = count($opt);
			for($i=0;$i<$num;$i++)
			{
				list($rv,$mess) = $this->validateEmailAddr($opt[$i]);
			 	if(!$rv)
				{
					$ret = false;
					$message .= $mess;
				}
			}
		}
    	return array($ret,$message);
	}

}

?>
