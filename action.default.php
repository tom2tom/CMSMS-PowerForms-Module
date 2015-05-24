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
$funcs = new pwfFormOperations();
$formdata = $funcs->Load($this,$form_id,$params,TRUE); //CHECKME safely cache form data somewhere ?
unset($funcs);
if(!$formdata)
{
	echo "<!-- no form -->\n";
	return;
}

//Crash;

$fieldExpandOp = FALSE;
if(!isset($params['pwfp_callcount']))
{
	//first time
	$callcount = 0;
	$formdata->Page = 1;
	$formdata->FormPagesCount = 1; //we will count
}
else
{
	$callcount = (int)$params['pwfp_callcount'];
	$formdata->Page = (int)$params['pwfp_formpage'];
	$formdata->FormPagesCount = 1;
	//update all formdata from $params[] TODO cache these data somewhere
	foreach($params as $pKey=>$pVal)
	{
		if(strpos($pKey,'pwfp_') === 0)
		{
			$pid = substr($pKey,5);
			if(is_numeric($pid))
			{
				$fld = pwfFieldCheck::GetFieldById($formdata,$pid);
				if($fld)
					$fld->SetValue($pVal);
/*
'pwfp_13' => 
    array (size=1)
      0 => string '1' (length=1)
  'pwfp_15' => string 'AD' (length=2)
  'pwfp_16' => string 'asdas' (length=5)
 */				
			}
			elseif(strpos($pid,'FeX_') === 0 || strpos($pid,'FeD_') === 0)
			{
				// expanding or shrinking a field
				$fieldExpandOp = TRUE;
			}
		}
	}
}

$smarty->assign('form_has_validation_errors',0);
$smarty->assign('show_submission_errors',0);
$finished = FALSE;

$adbg = $params;
$adbg2 = $formdata;
//Crash;

if(!$fieldExpandOp &&
(!empty($params['pwfp_done']) || ($formdata->FormPagesCount > 1 && $formdata->Page > 0)) //TODO how Page updated
)
{
	// validate form
//Crash2;
	$allvalid = TRUE;
	$message = array();
	$formPageCount = 1;
	$valPage = $formdata->Page - 1; //TODO off by 1 ?
	$usertagops = cmsms()->GetUserTagOperations();
	$udt = pwfUtils::GetFormOption($formdata,'validate_udt');
	$unspec = pwfUtils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified'));

	foreach($formdata->Fields as &$one)
	{
		if($one->GetFieldType() == 'PageBreak')
			$formPageCount++;
/*TODO logic? if($valPage != $formPageCount)
		{
$Crash1;
			continue; //ignore pages before the current? last? one
		}
*/
		$deny_space_validation = !!$this->GetPreference('blank_invalid');
		if(// ! $one->IsNonRequirableField() &&
			$one->IsRequired() && $one->HasValue($deny_space_validation) === FALSE)
		{
$Crash2;
			$allvalid = FALSE;
			$one->validated = FALSE;
			$one->ValidationMessage = $this->Lang('please_enter_a_value',$one->GetName());
			$message[] = $one->ValidationMessage;
			$one->SetOption('is_valid',FALSE);
		}
		elseif($one->Type == 'Captcha')
		{
			$res = $one->Validate();
//$Crash4;
			if($res[0])
				$one->SetOption('is_valid',TRUE);
			else
			{
				$allvalid = FALSE;
				$message[] = $res[1];
				$one->SetOption('is_valid',FALSE);
			}

		}
		elseif($one->GetValue()) // != $this->Lang('unspecified'))
		{
//$Crash3;
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
				$alias = $othr->ForceAlias();
				$parms[$alias] = $replVal;
				$id = $othr->GetId();
				$parms['fld_'.$id] = $replVal;
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

//$Crash10;

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
