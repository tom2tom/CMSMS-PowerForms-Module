<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$this->DoNothing();

if(!isset($params['fbrp_f']) || !isset($params['fbrp_r']) || !isset($params['fbrp_c']))
{
	echo $this->Lang('validation_param_error');
	return false;
}

$params['response_id']=$params['fbrp_r'];
$params['form_id']=$params['fbrp_f'];
$params['fbrp_user_form_validate']=true;
$aeform = new pwfForm($this, $params, true);

if(!$aeform->CheckResponse($params['fbrp_f'], $params['fbrp_r'], $params['fbrp_c']))
{
	echo $this->Lang('validation_response_error');
	return false;
}

/* Stikki removed: Old stuff, should be removed from Form.class.php aswell
else
{
	//[#2792] DeleteResponse is never called on validation;
	//$aeform->DeleteResponse($params['fbrp_r']);
}
*/

$confirmationField = false;
foreach($aeform->GetFields() as &$thisField)
{
	if($thisField->GetFieldType() == 'DispositionEmailConfirmation')
	{
		$thisField->ApproveToGo($params['fbrp_r']);
		$results = $aeform->Dispose($returnid);
		if($results[0] == true)
		{
			$ret = $thisField->GetOption('redirect_page','-1');
			if($ret != -1)
			{
				unset ($thisfield);
				$this->RedirectContent($ret);
			}
		}
		else
		{
			echo 'Error!: '; //TODO translate
			foreach($results[1] as $thisRes)
			{
				echo $thisRes . '<br />';
			}
		}
		$confirmationField = true;
		break;
	}
}
unset ($thisfield);

if(!$confirmationField)
	echo $this->Lang('validation_no_field_error');
?>
