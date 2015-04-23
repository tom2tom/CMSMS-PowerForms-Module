<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!isset($params['form_id']) && isset($params['form']))
{
    // get the form by name, not ID
    $params['form_id'] = $this->GetFormIDFromAlias($params['form']);
}

$inline = false;
if((isset($params['inline'])) && preg_match('/t(rue)*|y(yes)*|1/i',$params['inline']))
{
	$inline = true;
}

$fbrp_callcount = 0;
$funcs = new pwfUtils($this,$params,true,true);

$fld = $funcs->GetFormBrowserField();
if($fld !== false && $fld->GetOption('feu_bind','0')=='1')
{
	$feu = $this->GetModuleInstance('FrontEndUsers');
	if($feu == false)
	{
		debug_display("FAILED to instatiate FEU!");
		return;
	}
	if($feu->LoggedInId() === false)
	{
		echo $this->Lang('please_login');
		return;
	}
}

if(!($inline || ($funcs->GetAttr('inline','0')== '1'))) $id = 'cntnt01';

$smarty->assign('fb_form_has_validation_errors',0);
$smarty->assign('fb_show_submission_errors',0);
$smarty->assign('fb_form_header', $funcs->RenderFormHeader());
$smarty->assign('fb_form_footer',$funcs->RenderFormFooter());

$finished = false;
$fieldExpandOp = false;

if(isset($params['fbrp_callcount']))
{
    $fbrp_callcount = (int)$params['fbrp_callcount'];
}

foreach($params as $pKey=>$pVal)
{
	if(substr($pKey,0,9) == 'fbrp_FeX_' || substr($pKey,0,9) == 'fbrp_FeD_')
	{
		// expanding or shrinking a field
		$fieldExpandOp = true;
	}
}

if(!$fieldExpandOp && (($funcs->GetPageCount() > 1 && $funcs->GetPageNumber() > 0) || (isset($params['fbrp_done'])&& $params['fbrp_done']==1)))
{
	// Validate form
	$res = $funcs->Validate();

	// We have validate errors
    if($res[0] === false)
    {
		$smarty->assign('fb_form_validation_errors',$res[1]);
		$smarty->assign('fb_form_has_validation_errors',1);

		$funcs->PageBack();

		// No validate errors, proceed
	}
	else if(isset($params['fbrp_done']) && $params['fbrp_done']==1)
	{
		// Check captcha, if installed
		$ok = true;
		$captcha = $this->getModuleInstance('Captcha');
		if($funcs->GetAttr('use_captcha','0') == '1' && $captcha != null)
		{
			if(!$captcha->CheckCaptcha($params['fbrp_captcha_phrase']))
			{
				$smarty->assign('captcha_error',$funcs->GetAttr('captcha_wrong',$this->Lang('wrong_captcha')));

				$funcs->PageBack();
				$ok = false;
			}
		}

		// All ok, dispose form and manage fileuploads
		if($ok)
		{
			$finished = true;
			$funcs->manageFileUploads();
			$results = $funcs->Dispose($returnid);
		}
	}
}

if(!$finished)
{
	$parms = array();
	$parms['form_name'] = $funcs->GetName();
	$parms['form_id'] = $funcs->GetId();
	$this->SendEvent('OnFormBuilderFormDisplay',$parms);

    if(isset($params['fb_from_fb'])) //CHECKME never used
	{
		$smarty->assign('fb_form_start',
			$this->CreateFormStart($id, 'user_edit_resp', $returnid, 'POST',
				'multipart/form-data',
				($funcs->GetAttr('inline','0') == '1'), '',
				array('fbrp_callcount'=>$fbrp_callcount+1)).
				$this->CreateInputHidden($id,'response_id',isset($params['response_id'])?$params['response_id']:'-1'));
	}
	else
	{
     	$smarty->assign('fb_form_start',
			   $this->CreateFormStart($id, 'default', $returnid, 'POST',
				'multipart/form-data',
				($funcs->GetAttr('inline','0') == '1'), '',
				array('fbrp_callcount'=>$fbrp_callcount+1)));
	}

	$smarty->assign('fb_form_end',$this->CreateFormEnd());
	$smarty->assign('fb_form_done',0);
}
else
{
	$smarty->assign('fb_form_done',1);
	if($results[0] == true)
	{
		$parms = array();
		$parms['form_name'] = $funcs->GetName();
		$parms['form_id'] = $funcs->GetId();
		$this->SendEvent('OnFormBuilderFormSubmit',$parms);

		$act = $funcs->GetAttr('submit_action','text');
		if($act == 'text')
		{
			$message = $funcs->GetAttr('submission_template','');
			$funcs->setFinishedFormSmarty(true);
			//process via smarty without cacheing (smarty->fetch() fails)
			echo $this->ProcessTemplateFromData($message);
			return;
		}
		else if($act == 'redir')
		{
			$ret = $funcs->GetAttr('redirect_page','-1');
			if($ret != -1)
			{
				$this->RedirectContent($ret);
			}
		}
	}
	else
	{
		$parms = array();
		$params['fbrp_error']='';
		$smarty->assign('fb_submission_error',$this->Lang('submission_error'));

		$show = $this->GetPreference('hide_errors','1');
		$smarty->assign('fb_submission_error_list',$results[1]);
		$smarty->assign('fb_show_submission_errors',$show);

		$parms['form_name'] = $funcs->GetName();
		$parms['form_id'] = $funcs->GetId();
		$this->SendEvent('OnFormBuilderFormSubmitError',$parms);
	}
}

$udtonce = $funcs->GetAttr('predisplay_udt','');
$udtevery = $funcs->GetAttr('predisplay_each_udt','');
if(!$finished &&
   ((!empty($udtonce) && $udtonce != '-1') ||
    (!empty($udtevery) && $udtevery != '-1')))
{
	$usertagops = $gCms->GetUserTagOperations();
	$parms = $params;
	$parms['FORM'] =& $funcs;

	if(isset($fbrp_callcount) && $fbrp_callcount == 0 &&
		!empty($udtonce) && "-1" != $udtonce)
	{
		$tmp = $usertagops->CallUserTag($udtonce,$parms);
	}
	if(!empty($udtevery) && "-1" != $udtevery)
	{
		$tmp = $usertagops->CallUserTag($udtevery,$parms);
	}
}
echo $funcs->RenderForm($id, $params, $returnid);
?>
