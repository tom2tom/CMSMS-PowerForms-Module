<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class is for system-generated emails (i.e. no interaction with the user or
//form prior to its submission) to any number of destinations (as a combination of to,cc,bcc)

class pwfSystemEmail extends pwfEmailBase
{
	var $addressAdd;
	var $addressCount;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'SystemEmail';
		$this->addressAdd = FALSE;
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_address');
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_address');
	}

	function DoOptionAdd(&$params)
	{
		$this->addressAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $key=>$val)
		{
			if(substr($key,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('destination_address',$val - $delcount); //TODO
				$delcount++;
			}
		}
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ret = $mod->Lang('to').': ';
		$dests = $this->GetOption('destination_address');
		if(is_array($dests))
		{
			if(count($dests) > 1)
			{
				$ret.= count($dests).' '.$mod->Lang('recipients');
			}
			else
			{
				$pre = substr($dests[0],0,4);
				if($pre == '|cc|')
					$ret = $mod->Lang('cc').': '.substr($dests[0],4);
				elseif($pre == '|bc|')
					$ret = $mod->Lang('bcc').': '.substr($dests[0],4);
				else
					$ret.= $dests[0];
			}
		}
		elseif($dests)
		{
			$pre = substr($dests,0,4);
			if($pre == '|cc|')
				$ret = $mod->Lang('cc').': '.substr($dests,4);
			elseif($pre == '|bc|')
				$ret = $mod->Lang('bcc').': '.substr($dests,4);
			else
				$ret .= $dests;
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
			$this->addressCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->addressCount = 1;
		else
			$this->addressCount = 0;
	}

	function GetDests($id,$row,$sel)
	{
		$id = cms_htmlentities($id);
		$name = $id.$this->formdata->current_prefix.'mailto_'.$row; //must be distinct for each address
		$totypes = array ('to','cc','bc');
		$btns = array();
		for ($i=0; $i<3; $i++)
		{
			$text = '<input type="radio" id="'.$id.'mailto_'.$row.$i. //'pwfp_' removed from id
			'" class="cms_radio" style="margin-left:5px;" name="'.
			$name.'" value="'.$totypes[$i].'"';
			if($sel == $totypes[$i])
				$text .= ' checked="checked"';
			$text .= ' />';
			$btns[] = $text;
		}
		return $btns;
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;

		$this->countAddresses();

		if($this->addressAdd)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = FALSE;
		}

		$ret = $this->PrePopulateAdminFormCommonEmail($id);
//		$ret['main'][] = array($mod->Lang('title_destination_address'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_destination_address'),
			$mod->Lang('to'),
			$mod->Lang('cc'),
			$mod->Lang('bcc'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount > 1) ? $this->addressCount:1;
		for($i=0; $i<$num; $i++)
		{
			$addr = $this->GetOptionElement('destination_address',$i);
			if($addr)
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
			$btns = self::GetDests($id,$i,$totype);

			$dests[] = array(
			$mod->CreateInputText($id,'opt_destination_address[]',$addr,50,128),
			array_shift ($btns),
			array_shift ($btns),
			array_shift ($btns),
			$mod->CreateInputCheckbox($id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}
		$ret['table']= $dests;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = parent::AdminValidate($id);
		if(!ret)
			$messages[] = $msg;

		$mod = $this->formdata->formsmodule;
		$opt = $this->GetOption('email_from_address');
		if($opt)
		{
			list($rv,$msg) = $this->validateEmailAddr($opt);
			if(!$rv)
			{
				$ret = FALSE;
				$messages[] = $msg;
			}
		}
		else
		{
			$ret = FALSE;
			$messages[] = $mod->Lang('must_specify_TODO');
		}

    	$dests = $this->GetOptionRef('destination_address');
		if($dests)
		{
			if(!is_array($dests))
				$dests = array($dests);
			$num = count($dests);
			for($i=0; $i<$num; $i++)
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
			$messages[] = $mod->Lang('must_specify_one_destination');
		}

		$msg = ($ret)? '' : implode('<br />',$messages);
    	return array($ret,$msg);
	}

	function SetFromAddress()
	{
		return FALSE;
	}

	function SetReplyToName()
	{
		return FALSE;
	}

	function SetReplyToAddress()
	{
		return FALSE;
	}

	function Dispose($id,$returnid)
	{
		$dests = $this->GetOptionRef('destination_address');
		return $this->SendForm($dests,$this->GetOption('email_subject'));
	}

}

?>
