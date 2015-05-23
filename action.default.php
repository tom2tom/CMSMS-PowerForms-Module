<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!isset($params['form_id']) && isset($params['form'])) // got the form by alias
	$params['form_id'] = pwfUtils::GetFormIDFromAlias($params['form']);
if(empty($params['form_id']) || $params['form_id'] == -1)
{
	echo "<!-- no form -->\n";
	return;
}
$form_id = (int)$params['form_id'];

if(isset($params['pwfp_form_data']))
	$formdata = $params['pwfp_form_data'];
else
{
	$funcs = new pwfFormOperations();
	$formdata = $funcs->Load($this,$form_id,$params,TRUE);
	unset($funcs);
	if($formdata)
		$formdata->Page = 1;
	else
	{
		echo "<!-- no form -->\n";
		return;
	}
}

$fieldExpandOp = FALSE;
foreach($params as $pKey=>$pVal)
{
	if(substr($pKey,0,9) == 'pwfp_FeX_' || substr($pKey,0,9) == 'pwfp_FeD_')
	{
		// expanding or shrinking a field
		$fieldExpandOp = TRUE;
	}
}

$smarty->assign('form_has_validation_errors',0);
$smarty->assign('show_submission_errors',0);

$finished = FALSE;
if(!$fieldExpandOp &&
(!empty($params['pwfp_done']) || $formdata->FormPagesCount > 1 && $formdata->Page > 0))
{
	// validate form
	$allvalid = TRUE;
	$message = array();
	$formPageCount = 1;
	$valPage = $formdata->Page - 1;
	$usertagops = cmsms()->GetUserTagOperations();
	$udt = pwfUtils::GetFormOption($formdata,'validate_udt');
	$unspec = pwfUtils::GetFormOption($formdata,'unspecified',$mod->Lang('unspecified'));

	foreach($formdata->Fields as &$one)
	{
		if($one->GetFieldType() == 'PageBreakField')
			$formPageCount++;
		if($valPage != $formPageCount)
			continue; //ignore pages before the current one

		$deny_space_validation = !!$mod->GetPreference('blank_invalid');
		if(// ! $one->IsNonRequirableField() &&
			$one->IsRequired() && $one->HasValue($deny_space_validation) === FALSE)
		{
			$allvalid = FALSE;
			$one->validated = FALSE;
			$one->ValidationMessage = $mod->Lang('please_enter_a_value',$one->GetName());
			$message[] = $one->ValidationMessage;
			$one->SetOption('is_valid',FALSE);
		}
		elseif($one->GetValue() != $mod->Lang('unspecified'))
		{
			$res = $one->Validate();
			if($res[0])
				$one->SetOption('is_valid',TRUE);
			else
			{
				$allvalid = FALSE;
				$message[] = $res[1];
				$one->SetOption('is_valid',FALSE);
			}
		}

		if($allvalid && !empty($udt))
		{
			$parms = $params;
			foreach($formdata->Fields as &$othr)
			{
				$replVal = '';
				if($othr->DisplayInSubmission())
				{
					$replVal = $othr->GetHumanReadableValue();
					if($replVal == '')
					{
						$replVal = $unspec;
					}
				}
				$name = $othr->GetVariableName();
				$parms[$name] = $replVal;
				$id = $othr->GetId();
				$parms['fld_'.$id] = $replVal;
				$alias = $othr->GetAlias();
				if(!empty($alias))
					$parms[$alias] = $replVal;
			}
			unset($othr);
			$res = $usertagops->CallUserTag($udt,$parms);
			if(!$res[0])
			{
				$allvalid = FALSE;
				$message[] = $res[1];
			}
		}
	}
	unset($one);

    if(!$allvalid)
    {
		// validation error(s)
		$smarty->assign('form_has_validation_errors',1);
		$smarty->assign('form_validation_errors',$message);
		$formdata->Page--;
	}
	elseif(!empty($params['pwfp_done']))
	{
		$finished = TRUE;
		// run all field methods that modify other fields
		$computes = array();
		$i = 0; //don't assume anything about fields-array key
		foreach($formdata->Fields as &$one)
		{
			if($one->ModifiesOtherFields())
				$one->ModifyOtherFields();
			if($one->ComputeOnSubmission())
				$computes[$i] = $one->ComputeOrder();
			$i++;
		}

		asort($computes);
		foreach($computes as $cKey=>$cVal)
			$formdata->Fields[$cKey]->Compute();

		$alldisposed = TRUE;
		$message = array();
		// dispose TODO handle 'blocked' notices
		foreach($formdata->Fields as &$one)
		{
			if($one->IsDisposition() && $one->DispositionIsPermitted())
			{
				$res = $one->DisposeForm($returnid);
				if(!$res[0])
				{
					$alldisposed = FALSE;
					$message[] = $res[1];
				}
			}
		}
		// cleanups
		foreach($formdata->Fields as &$one)
			$one->PostDispositionAction();
		unset($one);
	}
}

if(isset($params['pwfp_callcount']))
	$callcount = (int)$params['pwfp_callcount'];
else
	$callcount = 0;

$parms = array();
$parms['form_id'] = $form_id;
$parms['form_name'] = pwfUtils::GetFormNameFromID($form_id);

if($finished)
{
	$smarty->assign('form_done',1);
	if($alldisposed)
	{
		$this->SendEvent('OnFormSubmit',$parms);
		$act = pwfUtils::GetFormOption($formdata,'submit_action','text');
		if($act == 'text')
		{
			$message = pwfUtils::GetFormOption($formdata,'submission_template','');
			pwfUtils::setFinishedFormSmarty($formdata,TRUE);
			//process via smarty (no cacheing)
			echo $this->ProcessTemplateFromData($message);
			return;
		}
		elseif($act == 'redir')
		{
			$ret = pwfUtils::GetFormOption($formdata,'redirect_page',-1);
			if($ret != -1)
				$this->RedirectContent($ret);
		}
	}
	else
	{
		$this->SendEvent('OnFormSubmitError',$parms);
		$params['pwfp_error'] = '';
		$smarty->assign('submission_error',$this->Lang('submission_error'));
		$smarty->assign('submission_error_list',$message);
		$smarty->assign('show_submission_errors',!$this->GetPreference('hide_errors'));
	}
}
else
{
	$udtonce = pwfUtils::GetFormOption($formdata,'predisplay_udt');
	$udtevery = pwfUtils::GetFormOption($formdata,'predisplay_each_udt');
	if($udtonce || $udtevery)
	{
		$parms2 = $params;
		$parms2['FORM'] =& $formdata;
		$usertagops = cmsms()->GetUserTagOperations();
		if($udtonce && $callcount == 0)
			/*$tmp = */$usertagops->CallUserTag($udtonce,$parms2);
		if($udtevery)
			/*$tmp = */$usertagops->CallUserTag($udtevery,$parms2);
		unset($parms2);
		unset($usertagops);
	}
}

$smarty->assign('form_done',0);
$this->SendEvent('OnFormDisplay',$parms);
unset($parms);

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.default.php';

echo $this->ProcessTemplateFromDatabase('pwf_'.$form_id);

?>
