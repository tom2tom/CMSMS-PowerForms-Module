<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!function_exists('EarlyExit'))
{
 function EarlyExit(&$mod,&$smarty,$mode=0)
 {
 	switch($mode)
	{
	 case 1:
		SubmitLog(1000);
		$smarty->assign('message',$mod->Lang('comeback_expired'));
		break;
	 case 2:
		SubmitLog(1000);
		$smarty->assign('message',$mod->Lang('comeback_toomany'));
		break;
	}
	$smarty->assign('title',$mod->Lang('title_aborted'));
	echo $mod->ProcessTemplate('message.tpl');
 }

 function SubmitLog($number=0)
 {
	if(empty($_SERVER['REMOTE_ADDR']))
	{
		//TODO
		return FALSE;
	}
	$db = cmsms()->GetDb();
	$pre = cms_db_prefix();
	//try to update
	if($number)
	{
		$sql = 'UPDATE '.$pre.
			'module_pwf_ip_log SET basetime=?,howmany=? WHERE src=?'; //TODO fields
		$when = trim($db->DBTimeStamp(time()+900),"'");
		$res = $db->Execute($sql,array($when,$number,$_SERVER['REMOTE_ADDR']));
	}
	else
	{
		$sql = 'UPDATE '.$pre.
			'module_pwf_ip_log SET basetime=?,howmany=howmany+1 WHERE src=?'; //TODO fields
		$when = trim($db->DBTimeStamp(time()),"'");
		$res = $db->Execute($sql,array($when,$_SERVER['REMOTE_ADDR']));
	}
	if(!$res)
	{
		//revert to insert
		if($number < 1)
			$number = 1;
//		$id = $db->GenID($pre.'module_pwf_ip_log_seq');
		$sql = 'INSERT INTO '.$pre.
			'module_pwf_ip_log (src,howmany,basetime) VALUES (?,?,?)'; //TODO fields
		$db->Execute($sql,array($_SERVER['REMOTE_ADDR'],$number,$when));
	}
	return TRUE;
 }
}

if(!isset($params['form_id']) && isset($params['form'])) // got the form by alias
	$params['form_id'] = pwfUtils::GetFormIDFromAlias($params['form']);
if(empty($params['form_id']))
{
	echo "<!-- no form -->\n";
	return;
}
list($current,$prior) = $this->GetTokens(); //fresh pair of fieldname-prefixes
//check that we're current
$matched = preg_grep('/^pwfp_\d{3}_/',array_keys($params));
if($matched)
{
	$key = reset($matched);
	if(strpos($key,$current) === 0)
		$prefix = $current;
	elseif(strpos($key,$prior) === 0)
		$prefix = $prior;
	else
	{
		EarlyExit($this,$smarty,1);
		return;
	}
	while($key = next($matched))
	{
		if(strpos($key,$prefix) !== 0)
		{
			EarlyExit($this,$smarty,1);
			return;
		}
	}
}
else
	$prefix = $current;

$form_id = (int)$params['form_id'];
$validerr = 0; //default no validation error
$cache = pwfCache::Get($this);

if(isset($params[$prefix.'formdata']))
{
	$firsttime = FALSE; //this is a return-visit

	$cache_key = $params[$prefix.'formdata'];
	$formdata = unserialize($cache->driver_get($cache_key));
	$formdata->formsmodule =& $this;
//	$formdata = $this->cache[$cache_key];

	$matched = preg_grep('/^pwfp_\d{3}_Fe[DX]_/',array_keys($params)); //expanding or shrinking a field
	if(!$matched)
	{
		$donekey = (isset($params[$prefix.'done'])) ? $prefix.'done' : FALSE;

		if(isset($params[$prefix.'continue']))
			$formdata->Page++;
		elseif(isset($params[$prefix.'previous']))
		{
			$formdata->Page--;
			if($donekey)
			{
				unset($params[$donekey]);
				$donekey = FALSE;
			}
		}

		//update cached field data from $params[]
		foreach($params as $key=>$val)
		{
			if(strncmp($key,'pwfp_',5) == 0)
			{
				$pid = substr($key,9); //ignore 'pwfp_NNN_' prefix
				if(is_numeric($pid))
				{
					$fld = $formdata->Fields[$pid];
					if($fld)
					{
						if($fld->Type == 'Captcha')
						{
							if(isset($params['captcha_input']))
								$val = $params['captcha_input'];
//							if(!$val)
//								$val = -.-; //ensure invalid-value if empty
						}
						$fld->SetValue($val);
					}
				}
			}
		}

		if($donekey)
		{
/* TODO police spam	
		if($mod->GetPreference('enable_antispam'))
		{
			if(!empty($_SERVER['REMOTE_ADDR']))
			{
				$db = cmsms()->GetDb();
				$query = 'SELECT COUNT(src_ip) AS sent FROM '.cms_db_prefix().
				'module_pwf_ip_log WHERE src_ip=? AND sent_time > ?';

				$sent = $db->GetOne($query,array($_SERVER['REMOTE_ADDR'],
					   trim($db->DBTimeStamp(time() - 3600),"'")));

				if($sent > 9)
				{
					// too many from this IP address. Kill it.
					$msg = '<hr />'.$mod->Lang('suspected_spam').'<hr />';
//					audit(-1,$mod->GetName(),$mod->Lang('log_suspected_spam',$_SERVER['REMOTE_ADDR']));
					return array(FALSE,$msg);
				}
			}
		}

TODO this is not just an email thing!!
				if($mod->GetPreference('enable_antispam'))
				{
					if(!empty($_SERVER['REMOTE_ADDR']))
					{
//						$rec_id = $db->GenID(cms_db_prefix().'module_pwf_ip_log_seq');
						$query = 'INSERT INTO '.cms_db_prefix().
						'module_pwf_ip_log (sent_id,src_ip,sent_time) VALUES (?,?,?)';

						$res = $db->Execute($query,array(
							$rec_id,
							$_SERVER['REMOTE_ADDR'],
							trim($db->DBTimeStamp(time()),"'")
							));
					}
				}
*/
 
			if(!empty($_SERVER['REMOTE_ADDR']))
			{
				$sql = 'SELECT howmany FROM '.cms_db_prefix().
				'module_pwf_ip_log where src=? AND basetime > ?';

				$num = $db->GetOne($sql,array(
					$_SERVER['REMOTE_ADDR'],
					trim($db->DBTimeStamp(time() - 3600),"'")
					));

				if($num > X)
				{
					EarlyExit($this,$smarty,2);
					return;
				}
			}
*/	
			// validate form
			$allvalid = TRUE;
			$message = array();
			$formPageCount = 1;
			$valPage = $formdata->Page - 1; //TODO off by 1 ?
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
				if(// !$one->IsNonRequirableField() &&
					$one->IsRequired() && !$one->HasValue($deny_space_validation))
				{
$this->Crash2();
					$allvalid = FALSE;
					$one->SetOption('is_valid',FALSE);
					$one->validated = FALSE;
					$one->ValidationMessage = $this->Lang('please_enter_a_value',$one->GetName());
					$message[] = $one->ValidationMessage;
				}
				elseif($one->GetValue())
				{
					$res = $one->Validate($id);
					if($res[0])
						$one->SetOption('is_valid',TRUE);
					else
					{
						$allvalid = FALSE;
						$one->SetOption('is_valid',FALSE);
						$message[] = $res[1];
					}
				}
			}
			unset($one);

			if($allvalid)
			{
				$udt = pwfUtils::GetFormOption($formdata,'validate_udt');
				if(!empty($udt))
				{
					$usertagops = cmsms()->GetUserTagOperations(); //TODO ok here ?
					$unspec = pwfUtils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified'));

					$parms = $params;
					foreach($formdata->Fields as &$one)
					{
						if($one->DisplayInSubmission())
						{
							$val = $one->GetHumanReadableValue();
							if($val == '')
								$val = $unspec;
						}
						else
							$val = '';
						$name = $one->GetVariableName();
						$parms[$name] = $val;
						$alias = $one->ForceAlias();
						$parms[$alias] = $val;
						$id = $one->GetId();
						$parms['fld_'.$id] = $val;
					}
					unset($one);
					$res = $usertagops->CallUserTag($udt,$parms);
					if(!$res[0])
					{
						$allvalid = FALSE;
						$message[] = $res[1];
					}
				}
			}

			if($allvalid)
			{
				// run all field methods that modify other fields
				$computes = array();
				$i = 0; //don't assume anything about fields-array key
				foreach($formdata->Fields as &$one)
				{
					$one->PreDispositionAction();
					if($one->ComputeOnSubmission())
						$computes[$i] = $one->ComputeOrder();
					$i++;
				}

				asort($computes);
				foreach($computes as $cKey=>$cVal)
					$formdata->Fields[$cKey]->Compute(); //TODO ensure $cKey is field_id

				$alldisposed = TRUE;
				$message = array();
				// dispose TODO handle 'blocked' notices
				foreach($formdata->Fields as &$one)
				{
					if($one->IsDisposition() && $one->DispositionIsPermitted())
					{
						$res = $one->Dispose($id,$returnid);
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

				$parms = array();
				$parms['form_id'] = $form_id;
				$parms['form_name'] = pwfUtils::GetFormNameFromID($form_id);

				$smarty->assign('form_done',1);
				if($alldisposed)
				{
					$this->SendEvent('OnFormSubmit',$parms);
					$cache->driver_delete($cache_key);
					$act = pwfUtils::GetFormOption($formdata,'submit_action','text');
					if($act == 'text')
					{
						$message = pwfUtils::GetFormOption($formdata,'submission_template','');
						pwfUtils::setFinishedFormSmarty($formdata,TRUE);
						echo $this->ProcessTemplateFromData($message);
						return;
					}
					elseif($act == 'redir')
					{
						$ret = pwfUtils::GetFormOption($formdata,'redirect_page',-1);
						if($ret != -1)
							$this->RedirectContent($ret);
						else
						{
$this->Crash3();
							exit;
						}
					}
					else
					{
$this->Crash4();
						exit;
					}
				}
				else
				{
					$this->SendEvent('OnFormSubmitError',$parms);
//					$params['pwfp_error'] = ''; TODO what for?
					$smarty->assign('submission_error',$this->Lang('error_submission'));
					$smarty->assign('submission_error_list',$message);
					$smarty->assign('show_submission_errors',!$this->GetPreference('hide_errors'));
				}
				unset($parms);
			}
			else // validation error(s)
			{
				$validerr = 1;
				$smarty->assign('form_validation_errors',$message);
				$formdata->Page--; //TODO why
			}
		}
	//$Crash10;
	}
}
else //first time
{
	$funcs = new pwfFormOperations();
	$formdata = $funcs->Load($this,$id,$params,$form_id);
	unset($funcs);
	if(!$formdata)
	{
		echo "<!-- no form -->\n";
		return;
	}
	$firsttime = TRUE;
	$formdata->Page = 1;
	$formdata->FormPagesCount = 1; //we will count

	//TODO if $in_browser && $form_edit, import & store field data
	
	//construct sufficiently-unique cache key
	if(!empty($_SERVER['SERVER_ADDR']))
		$token = $_SERVER['SERVER_ADDR'];
	else
		$token = mt_rand(0,999999).'.'.mt_rand(0,999999);
	$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100,2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
	$cache_key = md5($token);
}

$smarty->assign('form_has_validation_errors',$validerr);
$smarty->assign('show_submission_errors',0);

$udtonce = $firsttime && pwfUtils::GetFormOption($formdata,'predisplay_udt');
$udtevery = pwfUtils::GetFormOption($formdata,'predisplay_each_udt');
if($udtonce || $udtevery)
{
	$parms = $params;
	$parms['FORM'] =& $formdata;
	$usertagops = cmsms()->GetUserTagOperations();
	if($udtonce)
		$usertagops->CallUserTag($udtonce,$parms);
	if($udtevery)
		$usertagops->CallUserTag($udtevery,$parms);
	unset($parms);
	unset($usertagops);
}

$parms = array();
$parms['form_id'] = $form_id;
$parms['form_name'] = pwfUtils::GetFormNameFromID($form_id);
$this->SendEvent('OnFormDisplay',$parms);
unset($parms);

$smarty->assign('form_done',0);

require dirname(__FILE__).DIRECTORY_SEPARATOR.'method.default.php';

//$adbg = $this->cache;
//$this->Crash();

echo $this->ProcessTemplateFromDatabase('pwf_'.$form_id);

?>
