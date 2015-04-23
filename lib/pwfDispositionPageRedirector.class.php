<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDispositionPageRedirector extends pwfFieldBase
{
	var $addressCount;
	var $addressAdd;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'DispositionPageRedirector';
		$this->DisplayInForm = true;
		$this->NonRequirableField = false;
		$this->IsDisposition = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->ValidationTypes = array();
		$this->addressAdd = 0;
	}

	function GetOptionAddButton()
	{
		$mod = $this->form_ptr->module_ptr;
		return $mod->Lang('add_destination');
	}

	function GetOptionDeleteButton()
	{
		$mod = $this->form_ptr->module_ptr;
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
				$this->RemoveOptionElement('destination_page', $thisVal - $delcount);
				$this->RemoveOptionElement('destination_subject', $thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countAddresses()
	{
		$tmp = $this->GetOptionRef('destination_page');
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
		$mod = $this->form_ptr->module_ptr;
		$js = $this->GetOption('javascript','');

		// why all this? Associative arrays are not guaranteed to preserve
		// order, except in "chronological" creation order.
		$sorted =array();
		if($this->GetOption('select_one','') != '')
		{
			$sorted[' '.$this->GetOption('select_one','')]='';
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
		$mod = $this->form_ptr->module_ptr;
		$opt = $this->GetOption('destination_page','');

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
         $ret= $mod->Lang('destination_count',$num);
        return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		global $id;
		$contentops = cmsms()->GetContentOperations();
		$mod = $this->form_ptr->module_ptr;

		$this->countAddresses();
		if($this->addressAdd > 0)
		{
			$this->addressCount += $this->addressAdd;
			$this->addressAdd = 0;
		}
//		$ret = $this->PrePopulateAdminFormBase($formDescriptor);
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
//		$main[] = array($mod->Lang('title_director_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_subject'),
			$mod->Lang('title_destination_page'),
			$mod->Lang('title_select')
			);
		$num = ($this->addressCount>1) ? $this->addressCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_subject[]',$this->GetOptionElement('destination_subject',$i),30,128),
			$contentops->CreateHierarchyDropdown('',$this->GetOptionElement('destination_page',$i), $id.'pwfp_opt_destination_page[]'),
			$mod->CreateInputCheckbox($formDescriptor, 'pwfp_sel_'.$i, $i,-1,'style="margin-left:1em;"')
			);
		}
		return array('main'=>$main,'table'=>$dests);
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$this->HiddenDispositionFields($mainArray, $advArray);
	}

	function GetHumanReadableValue($as_string)
	{
		$mod = $this->form_ptr->module_ptr;
		if($this->HasValue())
		{
			$ret = $this->GetOptionElement('destination_page',($this->Value - 1));
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
		// If needed, make sure other dispositions get run 1st.See  Dispose($returnid) in Form class.
		$mod = $this->form_ptr->module_ptr;
		$mod->RedirectContent($this->GetOptionElement('destination_page',($this->Value - 1)));
		return array(true, 'everything worked');
	}

	function AdminValidate()
	{
		$mod = $this->form_ptr->module_ptr;
		$opt = $this->GetOption('destination_page');
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
	    return array($ret,$message);
	}
}

?>
