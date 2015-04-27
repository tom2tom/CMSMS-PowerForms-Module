<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* Class for emailing results based on pulldown */

//require_once(cms_join_path(dirname(__FILE__),'class.pwfDispositionEmailBase.php'));

class pwfDispositionDirector extends pwfDispositionEmailBase
{
	var $addressCount;
	var $addressAdd;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionDirector';
		$this->DisplayInForm = true;
		$this->IsDisposition = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->ValidationTypes = array();
		$this->addressAdd = 0;
	}

	function GetOptionAddButton()
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->Lang('add_destination');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->Lang('delete_destination');
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
				$this->RemoveOptionElement('destination_subject', $thisVal - $delcount);
				$delcount++;
			}
		}
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

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');

		// why all this? Associative arrays are not guaranteed to preserve
		// order, except in "chronological" creation order.
		$sorted = array();
		if($this->GetOption('select_one','') != '')
		{
			$sorted[' '.$this->GetOption('select_one','')] = '';
		}
		else
		{
			$sorted[' '.$mod->Lang('select_one')]='';
		}
		$subjects = $this->GetOptionRef('destination_subject');

		if(count($subjects) > 1)
		{
			for($i=0;$i<count($subjects);$i++)
			{
			$sorted[$subjects[$i]]=($i+1);
			}
		}
		else
		{
			$sorted[$subjects] = '1';
		}
		return $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id, $sorted, -1, $this->Value, $js.$this->GetCSSIdTag());
	}


	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$opt = $this->GetOption('destination_address','');

		if(is_array($opt))
		{
			$num = count($opt);
		}
		elseif($opt != '')
		{
			$num = 1;
		}
		else
		{
			$num = 0;
        }
		$ret = $mod->Lang('destination_count',$num);
		$status = $this->TemplateStatus();
		if($status) $ret .= '<br />'.$status;
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$this->countAddresses();
		if($this->addressAdd > 0)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = 0;
		}

		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
		$main = (isset($ret['main'])) ? $ret['main'] : array();

		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
		$main[] = array($mod->Lang('title_allow_subject_override'),
			$mod->CreateInputHidden($formDescriptor,'pwfp_opt_subject_override','0').
			$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_subject_override',
                '1',$this->GetOption('subject_override','0')),
			$mod->Lang('help_allow_subject_override'));
//		$main[] = array($mod->Lang('title_director_details'),$dests);
		$ret['main'] = $main;

		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_subject'),
			$mod->Lang('title_destination_address'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_subject[]',$this->GetOptionElement('destination_subject',$i),40,128),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_address[]',$this->GetOptionElement('destination_address',$i),50,128),
			$mod->CreateInputCheckbox($formDescriptor, 'pwfp_sel_'.$i, $i,-1,'style="margin-left:1em;"')
			);
		}
		$ret['table'] = $dests;
		return $ret;
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->formdata->pwfmodule;
		// remove the "email subject" field
		$this->RemoveAdminField($mainArray, $mod->Lang('title_email_subject'));
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->HasValue())
		{
			$ret = $this->GetOptionElement('destination_subject',($this->Value - 1));
		}
		else
		{
			$ret = $mod->Lang('unspecified');
		}
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	function DisposeForm($returnid)
	{
		if($this->GetOption('subject_override','0') == '1' && $this->GetOption('email_subject','') != '')
		{
			$subject = $this->GetOption('email_subject');
		}
		else
		{
			$subject = $this->GetOptionElement('destination_subject',($this->Value - 1));
		}
		return $this->SendForm($this->GetOptionElement('destination_address',($this->Value - 1)),$subject);
	}

	function AdminValidate()
	{
		$mod = $this->formdata->pwfmodule;
    	$opt = $this->GetOption('destination_address');
  		list($ret, $message) = $this->DoesFieldHaveName();
		if($ret)
		{
			list($ret, $message) = $this->DoesFieldNameExist();
		}

		if(count($opt) == 0)
		{
			$ret = false;
			$message .= $mod->Lang('must_specify_one_destination').'<br />';
		}
		list($rv,$mess) = $this->validateEmailAddr($this->GetOption('email_from_address'));
		if(!$rv)
		{
    	    $ret = false;
            $message .= $mess;
		}
        for($i=0;$i<count($opt);$i++)
		{
			list($rv,$mess) = $this->validateEmailAddr($opt[$i]);
			if(!$rv)
			{
				$ret = false;
				$message .= $mess;
			}
		}

        return array($ret,$message);
	}

	function Validate()
	{
		$mod = $this->formdata->pwfmodule;
		$result = true;
		$message = '';

		if($this->Value == false)
		{
			$result = false;
			$message = $mod->Lang('must_specify_one_destination').'<br />';
		}
		return array($result,$message);
	}

}

?>
