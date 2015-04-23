<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDispositionDatabase extends pwfFieldBase
{
	var $approvedBy;

	function __construct(&$form_ptr, &$params)
	{
		parent::__construct($form_ptr, $params);
		$this->Type = 'DispositionDatabase';
		$this->IsDisposition = true;
		$this->NonRequirableField = true;
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->HideLabel = 1;
		$this->NeedsDiv = 0;
		$this->approvedBy = '';
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->form_ptr->module_ptr;
		if($this->Value === false)
		{
			return '';
		}
		return $mod->CreateInputHidden($id, 'pwfp__'.$this->Id,
			$this->EncodeReqId($this->Value));
	}

	function SetApprovalName($name)
	{
		$this->approvedBy = $name;
	}

	function StatusInfo()
	{
		 return '';
	}

	function DecodeReqId($theVal)
	{
		$tmp = base64_decode($theVal);
		$tmp2 = str_replace(session_id(),'',$tmp);
		if(substr($tmp2,0,1) == '_')
		{
			return substr($tmp2,1);
		}
		else
		{
			return -1;
		}
	}

	function EncodeReqId($req_id)
	{
		return base64_encode(session_id().'_'.$req_id);
	}

	function SetValue($val)
	{
		$decval = base64_decode($val);

		if($val === false)
		{
			// no value set, so we'll leave value as false
		}
		elseif(strpos($decval,'_') === false)
		{
			// unencrypted value, coming in from previous response
			$this->Value = $val;
		}
		else
		{
			// encrypted value coming in from a form, so we'll update.
			$this->Value = $this->DecodeReqId($val);
		}
	}

	function getSortFieldVal($sortFieldNumber)
	{
		return -1;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->form_ptr->module_ptr;
		$main = array(
		array($mod->Lang('title_data_stored_in_fbr'),
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_feu_bnd','0').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_crypt','0').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_hash_sort','0').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_sortfield1','').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_sortfield2','').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_sortfield3','').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_sortfield4','').
		 $mod->CreateInputHidden($formDescriptor, 'pwfp_opt_sortfield5','')));
		return array('main'=>$main);
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$mod = $this->form_ptr->module_ptr;
		$this->HiddenDispositionFields($mainArray, $advArray);
	}

    // Write To the Database
	function DisposeForm($returnid)
	{
		$form = $this->form_ptr;
		list($res,$msg) = $form->StoreResponse(($this->Value?$this->Value:-1),$this->approvedBy,$this);
		return array($res,$msg);
	}

}

?>
