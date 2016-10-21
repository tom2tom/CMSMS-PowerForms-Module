<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!function_exists('BlockSource')) {
 function BlockSource()
 {
	if (!empty($_SERVER['REMOTE_ADDR'])) {
		$t = $time();
		$t2 = trim($db->DBTimeStamp($t-900),"'"); //after 900 more seconds, it will be erased
		$src = $_SERVER['REMOTE_ADDR'];
	 	global $db;
		$pre = cms_db_prefix();
		$sql = array('UPDATE '.$pre.'module_pwf_ip_log SET howmany=255,basetime=? WHERE src=?');
		$args = array(array($t2,$src));
		$sql[] = 'INSERT INTO '.$pre.'module_pwf_ip_log
(src,howmany,basetime) SELECT ?,255,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM '.$pre.'module_pwf_ip_log T WHERE T.src=?)';
		$args[] = array($src,$t2,$src);
		PWForms\Utils::SafeExec($sql,$args);
	}
 }
}

if (!isset($params['form_id']) && isset($params['form'])) // got the form by alias
	$params['form_id'] = PWForms\Utils::GetFormIDFromAlias($params['form']);
if (empty($params['form_id']) || $params['form_id'] == -1) {
	echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
		'title'=>$this->Lang('title_aborted'),
		'message'=>$this->Lang('err_data'),
		'error'=>1));
	return;
}
list($current,$prior) = $this->_GetTokens(); //fresh pair of fieldname-prefixes
//check that we're current
$matched = preg_grep('/^pwfp_\d{3}_/',array_keys($params));
if ($matched) {
	$key = reset($matched);
	if (strpos($key,$current) === 0)
		$prefix = $current;
	elseif (strpos($key,$prior) === 0)
		$prefix = $prior;
	else {
		BlockSource();
		echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('comeback_expired')));
		return;
	}
	while ($key = next($matched)) {
		if (strpos($key,$prefix) !== 0) {
			BlockSource();
			echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
				'title'=>$this->Lang('title_aborted'),
				'message'=>$this->Lang('comeback_expired')));
			return;
		}
	}
} else
	$prefix = $current;

$form_id = (int)$params['form_id'];
$validerr = 0; //default no validation error
try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
		'title'=>$this->Lang('title_aborted'),
		'message'=>$this->Lang('err_system').' NO CACHE MECHANISM',
		'error'=>1));
	return;
}
/*QUEUE
try {
	$mx = PWForms\Utils::GetMutex($this);
} catch (Exception $e) {
	echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
		'title'=>$this->Lang('title_aborted'),
		'message'=>$this->Lang('err_system').' NO MUTEX MECHANISM',
		'error'=>1));
	return;
}
*/
$tplvars = array();

if (isset($params[$prefix.'formdata'])) {
	$firsttime = FALSE; //this is a return-visit

	$cache_key = $params[$prefix.'formdata'];
	$formdata = $cache->get($cache_key);
	if (is_null($formdata)) {
		echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('err_data'),
			'error'=>1));
		return;
	}
	$formdata->formsmodule = &$this;

	$matched = preg_grep('/^pwfp_\d{3}_Fe[DX]_/',array_keys($params)); //expanding or shrinking a field
	if (!$matched) {
		$donekey = (isset($params[$prefix.'done'])) ? $prefix.'done' : FALSE;

		if (isset($params[$prefix.'continue']))
			$formdata->Page++;
		elseif (isset($params[$prefix.'previous'])) {
			$formdata->Page--;
			if ($donekey) {
				unset($params[$donekey]);
				$donekey = FALSE;
			}
		}

		//update cached field data from $params[]
		foreach ($params as $key=>$val) {
			if (strncmp($key,'pwfp_',5) == 0) {
				$pid = substr($key,9); //ignore 'pwfp_NNN_' prefix
				if (is_numeric($pid)) {
					if (isset($formdata->Fields[$pid])) {
						$fld = $formdata->Fields[$pid];
						if ($fld->Type == 'Captcha') {
							if (isset($params['captcha_input']))
								$val = $params['captcha_input'];
//							if (!$val)
//								$val = -.-; //ensure invalid-value if empty
						}
						$fld->SetValue($val);
					}
				}
			}
		}

		if ($donekey) {
/*			// rate-limit?
			$num = PWForms\Utils::GetFormOption($formdata,'submit_limit',0);
			if ($num > 0) {
				if (!empty($_SERVER['REMOTE_ADDR'])) {
					$src = $_SERVER['REMOTE_ADDR'];
					$t = $time();
					$t2 = trim($db->DBTimeStamp($t-3600),"'");
					$t = trim($db->DBTimeStamp($t),"'");

					$pre = cms_db_prefix();
					$sql = array();
					$sql[] = <<<EOS
DELETE FROM {$pre}module_pwf_ip_log WHERE src=? AND basetime<?
EOS;
					$args = array(array($src,$t2));
					$sql[] = <<<EOS
UPDATE {$pre}module_pwf_ip_log SET howmany=howmany+1 WHERE src=? AND howmany<?
EOS;
					$args[] = array($src,$num+1);
					$sql[] = <<<EOS
INSERT INTO {$pre}module_pwf_ip_log (src,howmany,basetime)
SELECT ?,1,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pre}module_pwf_ip_log T WHERE T.src=?)
EOS;
					$args[] = array($src,$t,$src);
					PWForms\Utils::SafeExec($sql,$args);

					$sql = 'SELECT COUNT(src_ip) AS sent FROM '.$pre.'module_pwf_ip_log WHERE src_ip=?';
					$sent = PWForms\Utils::SafeGet($sql,array($src),'one');
					if ($sent > $num) {
						EarlyExit($this,2);
						return;
					}
				}
			} else {
				//TODO check for blocked after EarlyExit::expired
			}
*/
			if (!empty($_SERVER['REMOTE_ADDR'])) {
				$src = $_SERVER['REMOTE_ADDR'];
				$t = $time();
				$t2 = trim($db->DBTimeStamp($t-3600),"'");
				$t = trim($db->DBTimeStamp($t),"'");
				$num = 0;
				$pre = cms_db_prefix();
				$sql = 'DELETE FROM '.$pre.'module_pwf_ip_log WHERE src=? AND basetime<?';
				$args = array($src,$t2);
				$sql2 = 'SELECT COUNT(*) AS num FROM '.$pre.'module_pwf_ip_log WHERE src=? AND basetime<=?';
				$args2 = array(array($src,$t));

				$nt = 10;
				while ($nt > 0) {
					$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
					$db->StartTrans();
					$db->Execute($sql,$args);
					$num = $db->GetOne($sql2,$args2);
					if ($db->CompleteTrans())
						break;
					else {
						$nt--;
						usleep(50000);
					}
				}
				if ($nt == 0) {
					echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
						'title'=>$this->Lang('title_aborted'),
						'message'=>$this->Lang('system_data'),
						'error'=>1));
					return;
				}
				if ($num) {
					if ($num == 255) { //blocked due to expiry
						BlockSource(); //again!
						echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
							'title'=>$this->Lang('title_aborted'),
							'message'=>$this->Lang('comeback_expired')));
						return;
					}
					//rate-limit?
					$limit = PWForms\Utils::GetFormOption($formdata,'submit_limit',0);
					if ($limit) {
						if ($num <= $limit) {
							$sql = array();
							$sql[] = <<<EOS
UPDATE {$pre}module_pwf_ip_log SET howmany=howmany+1 WHERE src=? AND howmany<?
EOS;
							$args = array(array($src,$num+1));
							$sql[] = <<<EOS
INSERT INTO {$pre}module_pwf_ip_log (src,howmany,basetime)
SELECT ?,1,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pre}module_pwf_ip_log T WHERE T.src=?)
EOS;
							$args[] = array($src,$t,$src);
							PWForms\Utils::SafeExec($sql,$args);
						} else {
							echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
								'title'=>$this->Lang('title_aborted'),
								'message'=>$this->Lang('comeback_toomany')));
							return;
						}
					}
				}
			}

			// validate form
			$allvalid = TRUE;
			$message = array();
			$formPageCount = 1;
			$valPage = $formdata->Page - 1; //TODO off by 1 ?
			foreach ($formdata->FieldOrders as $one) {
				$one = $formdata->Fields($one);
				if ($one->GetFieldType() == 'PageBreak')
					$formPageCount++;
/*TODO logic? if ($valPage != $formPageCount) {
$Crash1;
					continue; //ignore pages before the current? last? one
				}
*/
				$deny_space_validation = !!$this->GetPreference('blank_invalid');
				if (// $one->GetChangeRequirement() &&
					$one->IsRequired() && !$one->HasValue($deny_space_validation)) {
$this->Crash2();
					$allvalid = FALSE;
					$one->SetProperty('is_valid',FALSE);
					$one->valid = FALSE;
					$one->ValidationMessage = $this->Lang('please_enter_a_value',$one->GetName());
					$message[] = $one->ValidationMessage;
				} elseif ($one->GetValue()) {
					$res = $one->Validate($id);
					if ($res[0])
						$one->SetProperty('is_valid',TRUE);
					else {
						$allvalid = FALSE;
						$one->SetProperty('is_valid',FALSE);
						$message[] = $res[1];
					}
				}
			}

			if ($allvalid) {
				$udt = PWForms\Utils::GetFormOption($formdata,'validate_udt');
				if (!empty($udt)) {
					$usertagops = cmsms()->GetUserTagOperations(); //TODO ok here ?
					$unspec = PWForms\Utils::GetFormOption($formdata,'unspecified',$this->Lang('unspecified'));

					$parms = $params;
					foreach ($formdata->FieldOrders as $one) {
						$one = $formdata->Fields($one);
						if ($one->DisplayInSubmission()) {
							$val = $one->GetDisplayableValue();
							if ($val == '')
								$val = $unspec;
						} else
							$val = '';
						$name = $one->GetVariableName();
						$parms[$name] = $val;
						$alias = $one->ForceAlias();
						$parms[$alias] = $val;
						$id = $one->GetId();
						$parms['fld_'.$id] = $val;
					}

					$res = $usertagops->CallUserTag($udt,$parms);
					if (!$res[0]) {
						$allvalid = FALSE;
						$message[] = $res[1];
					}
				}
			}

			if ($allvalid) {
/*QUEUE (php with async post-callback is bogus !?
				$token = abs(crc32($this->GetName().'Qmutex')); //same token as in action.run_queue.php
				if (!$mx->lock($token)) {
					echo $this->Lang('err_lock');
					exit;
				}
				$queue = $cache->get('pwfQarray');
				if (!$queue)
					$queue = array();
				unset($formdata->formsmodule); //no need to cache this
				$queue[] = array(
					'data' => $formdata, //CHECKME encrypted?
					'submitted' => time(),
					'pageid' => $id);
				$cache->set('pwfQarray',$queue,0); //no expiry
				$formdata->formsmodule = &$this;
				$mx->unlock($token);
				if (!$cache->get('pwfQrunning')) {
					//initiate async queue processing
					if ($this->ch) {
						while (curl_multi_info_read($this->mh))
							usleep(20000);
						curl_multi_remove_handle($this->mh,$this->ch);
						curl_close($this->ch);
						$this->ch = FALSE;
					}

					$ch = curl_init($this->Qurl);
					curl_setopt($ch,CURLOPT_FAILONERROR,TRUE);
					curl_setopt($ch,CURLOPT_FOLLOWLOCATION,TRUE);
					curl_setopt($ch,CURLOPT_FORBID_REUSE,TRUE);
					curl_setopt($ch,CURLOPT_FRESH_CONNECT,TRUE);
					curl_setopt($ch,CURLOPT_HEADER,FALSE);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
					curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);	//in case ...

					curl_multi_add_handle($this->mh,$ch);
					$runcount = 0;
					do
					{
						$mrc = curl_multi_exec($this->mh,$runcount);
					} while ($mrc == CURLM_CALL_MULTI_PERFORM); //irrelevant for curl 7.20.0+ (2010-02-11)
//					if ($mrc != CURLM_OK) i.e. CURLM_OUT_OF_MEMORY, CURLM_INTERNAL_ERROR
					if ($runcount) {
						$this->ch = $ch; //cache for later cleanup
					} else {
						curl_multi_remove_handle($this->mh,$ch);
						curl_close($ch);
					}
				}
*/
				// run all field methods that modify other fields
				$computes = array();
				foreach ($formdata->FieldOrders as $one) {
					$obfld = $formdata->Fields($one);
					$obfld->PreDisposeAction();
					if ($obfld->ComputeOnSubmission())
						$computes[$one] = $obfld->ComputeOrder();
				}

				if ($computes) {
					asort($computes);
					foreach ($computes as $fid=>$one)
						$formdata->Fields[$fid]->Compute();
				}

				$alldisposed = TRUE;
				$message = array();
				// dispose TODO handle 'blocked' notices
				foreach ($formdata->FieldOrders as $one) {
					$one = $formdata->Fields($one);
					if ($one->IsDisposition() && $one->IsDisposable()) {
						$res = $one->Dispose($id,$returnid);
						if (!$res[0]) {
							$alldisposed = FALSE;
							$message[] = $res[1];
						}
					}
				}
				// cleanups
				foreach ($formdata->FieldOrders as $one) {
					$one = $formdata->Fields($one);
					$one->PostDisposeAction();
				}

				$parms = array();
				$parms['form_id'] = $form_id;
				$parms['form_name'] = PWForms\Utils::GetFormNameFromID($form_id);

				$tplvars['form_done'] = 1;
				if ($alldisposed) {
					$cache->delete($cache_key);
					$act = PWForms\Utils::GetFormOption($formdata,'submit_action','text');
					switch ($act) {
					 case 'text':
						$this->SendEvent('OnFormSubmit',$parms);
						PWForms\Utils::setFinishedFormSmarty($formdata,TRUE);
						PWForms\Utils::ProcessTemplateFromDatabase($this,'pwf_sub_'.$form_id,$tplvars,TRUE);
						return;
					 case 'redir':
						$this->SendEvent('OnFormSubmit',$parms);
						$ret = PWForms\Utils::GetFormOption($formdata,'redirect_page',0);
						if ($ret > 0)
							$this->RedirectContent($ret);
						else {
							echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
								'title'=>$this->Lang('missing_type',$this->Lang('page')),
								'message'=>$this->Lang('cannot_show_TODO'),
								'error'=>1));
							return;
						}
					 case 'confirm':
					 	//confirmation needed before submission
						//after confirmation, formdata will be different
						echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
							'title'=>$this->Lang('title_confirm'),
							'message'=>$this->Lang('help_confirm')));
						return;
					 default:
						exit;
					}
				} else {
					$this->SendEvent('OnFormSubmitError',$parms);
					$tplvars = $tplvars + array(
						'submission_error' => $this->Lang('err_submission'),
						'submission_error_list' => $message,
						'show_submission_errors' => !$this->GetPreference('hide_errors')
					);
				}
				unset($parms);
// end of synchronous processing
			} else { // validation error(s)
				$validerr = 1;
				$tplvars['form_validation_errors'] = $message;
				$formdata->Page--; //TODO why
			}
		}
	}
} else { //first time
	$funcs = new PWForms\FormOperations();
	$formdata = $funcs->Load($this,$form_id,$id,$params);
	if (!$formdata) {
		unset($funcs);
		echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('err_data'),
			'error'=>1));
		return;
	}
	$firsttime = TRUE;
	$formdata->Page = 1;
	$formdata->PagesCount = 1; //we will count

/*	if ($params['action'] == 'show_form') {
		import & store field data
	}
*/
	$formdata->FieldOrders = array_keys($formdata->Fields);
	$funcs->Arrange($formdata->Fields,$formdata->FieldOrders);

	//construct sufficiently-unique cache key
	if (!empty($_SERVER['SERVER_ADDR']))
		$token = $_SERVER['SERVER_ADDR'];
	else
		$token = mt_rand(0,999999).'.'.mt_rand(0,999999);
	$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100,2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
	$cache_key = md5($token);
}

$tplvars['form_has_validation_errors'] = $validerr;
$tplvars['show_submission_errors'] = 0;

$udtonce = $firsttime && PWForms\Utils::GetFormOption($formdata,'predisplay_udt');
$udtevery = PWForms\Utils::GetFormOption($formdata,'predisplay_each_udt');
if ($udtonce || $udtevery) {
	$parms = $params;
	$parms['FORM'] =& $formdata;
	$usertagops = cmsms()->GetUserTagOperations();
	if ($udtonce)
		$usertagops->CallUserTag($udtonce,$parms);
	if ($udtevery)
		$usertagops->CallUserTag($udtevery,$parms);
	unset($parms);
	unset($usertagops);
}

$this->SendEvent('OnFormDisplay',array(
 'form_id'=>$form_id,
 'form_name'=>PWForms\Utils::GetFormNameFromID($form_id)));

$tplvars['form_done'] = 0;

require __DIR__.DIRECTORY_SEPARATOR.'populate.default.php';

$cache->set($cache_key,$formdata,84600);

echo $form_start.$hidden;
PWForms\Utils::ProcessTemplateFromDatabase($this,'pwf_'.$form_id,$tplvars,TRUE);
echo $form_end;
//inject constructed js after other content (pity we can't get to </body> or </html> from here)
$jsall = NULL;
PWForms\Utils::MergeJS($formdata->jsincs,$formdata->jsfuncs,$formdata->jsloads,$jsall);
if ($jsall)
	echo $jsall;
