<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$this->DoNothing();

if(!isset($params['pwfp_f']) || !isset($params['pwfp_r']) || !isset($params['pwfp_c']))
{
	echo $this->Lang('validation_param_error');
	return false;
}

$params['response_id']=$params['pwfp_r'];
$params['form_id']=$params['pwfp_f'];
$params['pwfp_user_form_validate']=true;
$funcs = new pwfUtils($this, $params, true);

if(!$funcs->CheckResponse($params['pwfp_f'], $params['pwfp_r'], $params['pwfp_c']))
{
	echo $this->Lang('validation_response_error');
	return false;
}

/* Stikki removed: Old stuff, should be removed from Form.class.php aswell
else
{
	//[#2792] DeleteResponse is never called on validation;
	//$funcs->DeleteResponse($params['pwfp_r']);
}
*/

$confirmationField = false;
foreach($funcs->GetFields() as &$thisField)
{
	if($thisField->GetFieldType() == 'DispositionEmailConfirmation')
	{
		$thisField->ApproveToGo($params['pwfp_r']);
		$results = $funcs->Dispose($returnid);
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
