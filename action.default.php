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
$formdata = $funcs->Load($this,$form_id,$params,TRUE);
unset($funcs);
if(!$formdata)
{
	echo "<!-- no form -->\n";
	return;
}

$inline = (isset($params['inline']) && preg_match('/t(rue)?|y(es)?|1/i',$params['inline']));
if(!($inline || (pwfUtils::GetAttr($formdata,'inline','0') == '1')))
	$id = 'cntnt01'; //TODO generalise

if(isset($params['pwfp_callcount']))
    $pwfp_callcount = (int)$params['pwfp_callcount'];
else
	$pwfp_callcount = 0;

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
	$ops = new pwfOperate();
	// validate form
	$res = $ops->FormValidate($this,$formdata,$params);
    if($res[0] === FALSE)
    {
		// validation error(s)
		$smarty->assign('form_has_validation_errors',1);
		$smarty->assign('form_validation_errors',$res[1]);
		$formdata->Page--;
	}
	else if(!empty($params['pwfp_done']))
	{
		$finished = TRUE;
		$results = $ops->FormDispose($formdata,$returnid);
	}
	unset($ops);
}

$parms = array();
$parms['form_id'] = $form_id;
$parms['form_name'] = pwfUtils::GetFormNameFromID($form_id);

if($finished)
{
	$smarty->assign('form_done',1);
	if($results[0] == TRUE)
	{
		$this->SendEvent('OnFormSubmit',$parms);
		$act = pwfUtils::GetAttr($formdata,'submit_action','text');
		if($act == 'text')
		{
			$message = pwfUtils::GetAttr($formdata,'submission_template','');
			pwfUtils::setFinishedFormSmarty($formdata,TRUE);
			//process via smarty (no cacheing)
			echo $this->ProcessTemplateFromData($message);
			return;
		}
		elseif($act == 'redir')
		{
			$ret = pwfUtils::GetAttr($formdata,'redirect_page',-1);
			if($ret != -1)
				$this->RedirectContent($ret);
		}
	}
	else
	{
		$this->SendEvent('OnFormSubmitError',$parms);
		$params['pwfp_error'] = '';
		$smarty->assign('submission_error',$this->Lang('submission_error'));
		$smarty->assign('submission_error_list',$results[1]);
		$smarty->assign('show_submission_errors',!$this->GetPreference('hide_errors'));
	}
}
else
{
	$smarty->assign('form_done',0);
	$this->SendEvent('OnFormDisplay',$parms);
	$smarty->assign('form_start',
		$this->CreateFormStart($id,'default',$returnid,'POST','multipart/form-data',
		(pwfUtils::GetAttr($formdata,'inline','0') == '1'), '',
		array('pwfp_callcount'=>$pwfp_callcount+1)));
	$smarty->assign('form_end',$this->CreateFormEnd());
}

$udtonce = pwfUtils::GetAttr($formdata,'predisplay_udt','');
$udtevery = pwfUtils::GetAttr($formdata,'predisplay_each_udt','');
if(!$finished && (!empty($udtonce) || !empty($udtevery)))
{
	$parms = $params;
	$parms['FORM'] =& $formdata;
	$usertagops = $gCms->GetUserTagOperations();
	if(isset($pwfp_callcount) && $pwfp_callcount == 0 && !empty($udtonce))
		/*$tmp = */$usertagops->CallUserTag($udtonce,$parms);

	if(!empty($udtevery))
		/*$tmp = */$usertagops->CallUserTag($udtevery,$parms);
	unset($parms);
	unset($usertagops);
}

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.default.php';

echo $this->ProcessTemplateFromDatabase('pwf_'.$form_id);

?>
