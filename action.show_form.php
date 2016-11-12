<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
/*
'form' => alias, first-time opened
'form_id' => enum or -1
'action' => 'default'
'captcha_input' (maybe)

'preload' => set first-time field values, array, keys=field id or alias, values=what to set
'resume' (maybe) => action name or 'modulename,actionname' for cancellation redirect
'passthru' => scalar data to be provided as a parameter to the 'resume' action
'exclude' => singleton or array of field id(s) or alias(es) to be omitted from the form
'excludetype' => singleton or array of field-type(s) to be omitted from the form

TEMPLATE-SPECIFIC PARAMS E.G. TODO prefix needed
'in_admin' (maybe, deprecated)
'in_browser' (maybe, deprecated)

after first-pass, many with prefix: 'pwfp_\d{3}_'
 including
		...field id => field-value from UI
		...Fe[DX]_ => expand or shrink a multi-element field
		...previous => pageback clicked
		...continue => pagenext clicked
		...done => submit clicked
		...cancel => cancel clicked
		...formdata => cache key
		...formpage => displayed-page index
*/

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
$pkeys = array_keys($params);
$matched = preg_grep('/^pwfp_\d{3}_/',$pkeys);
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

if (isset($params[$prefix.'cancel'])) {
	if (isset($params[$prefix.'passthru'])) {
		$newparms = array('passthru'=>$params[$prefix.'passthru']);
	} else
		$newparms = array();
	if (strpos($params[$prefix.'resume'],',') === FALSE) {
		$this->Redirect($id,$params[$prefix.'resume'],$returnid,$newparms);
	} else {
		list($module,$action) = explode(',',$params[$prefix.'resume']);
		$mod = cms_utils::get_module($module);
		$this->LoadRedirectMethods();
		cms_module_Redirect($mod,$id,$action,$returnid,$newparms);
		exit;
	}
}

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

if (isset($params[$prefix.'datakey'])) {
	$firsttime = FALSE; //this is a return-visit
	$tplvars = array(); //TODO members to preserve c.f. 1st-pass

	$cache_key = $params[$prefix.'datakey'];
	$formdata = $cache->get($cache_key);
	if (is_null($formdata)) {
		echo PWForms\Utils::ProcessTemplate($this,'message.tpl',array(
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('err_data'),
			'error'=>1));
		return;
	}
	$formdata->formsmodule = &$this;

	$adjust = preg_grep('/^pwfp_\d{3}_Fe[DX]_/',$pkeys) //expanding or shrinking a field
			|| preg_grep('/^pwfp_\d{3}_Se[DIWX]_/',$pkeys); //adding or deleting a sequence
	if (!$adjust) {
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
//								$val = '-.-'; //ensure invalid-value if empty
						}
						$fld->SetValue($val);
					}
				}
			}
		}

		if ($donekey) {
			$limit = PWForms\Utils::GetFormProperty($formdata,'submit_limit',0);
			if ($limit > 0) { // rate-limiting applies
				if (!empty($_SERVER['REMOTE_ADDR'])) {
					$src = $_SERVER['REMOTE_ADDR'];
					$t = time();
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
			foreach ($formdata->FieldOrders as $key) {
				$obfld = $formdata->Fields[$key];
				if ($obfld->GetFieldType() == 'PageBreak')
					$formPageCount++;
/*TODO logic? if ($valPage != $formPageCount) {
$Crash1;
					continue; //ignore pages before the current? last? one
				}
*/
				$deny_space_validation = !!$this->GetPreference('blank_invalid');
				if (// $obfld->GetChangeRequirement() &&
					$obfld->IsRequired() && !$obfld->HasValue($deny_space_validation)) {
$this->Crash2();
					$allvalid = FALSE;
					$obfld->valid = FALSE;
					$obfld->ValidationMessage = $this->Lang('please_enter_a_value',$obfld->GetName());
					$message[] = $obfld->ValidationMessage;
				} elseif ($obfld->GetValue()) {
					$res = $obfld->Validate($id);
					if ($res[0])
						$obfld->valid = TRUE;
					else {
						$allvalid = FALSE;
						$obfld->valid = FALSE;
						$message[] = $res[1];
					}
				}
			}

			if ($allvalid) {
				$udt = PWForms\Utils::GetFormProperty($formdata,'validate_udt');
				if (!empty($udt)) {
					$usertagops = cmsms()->GetUserTagOperations(); //TODO ok here ?
					$unspec = PWForms\Utils::GetFormProperty($formdata,'unspecified',$this->Lang('unspecified'));

					$parms = $params;
					foreach ($formdata->FieldOrders as $key) {
						$obfld = $formdata->Fields[$key];
						if ($obfld->DisplayInSubmission()) {
							$val = $obfld->DisplayableValue();
							if ($val == '')
								$val = $unspec;
						} else
							$val = '';
						$name = $obfld->GetVariableName();
						$parms[$name] = $val;
						$alias = $obfld->ForceAlias();
						$parms[$alias] = $val;
						$id = $obfld->GetId();
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
				foreach ($formdata->FieldOrders as $key) {
					$obfld = $formdata->Fields[$key];
					$obfld->PreDisposeAction();
					if ($obfld->ComputeOnSubmission())
						$computes[$key] = $obfld->ComputeOrder();
				}

				if ($computes) {
					asort($computes);
					foreach ($computes as $key=>$val)
						$formdata->Fields[$key]->Compute();
				}

				$alldisposed = TRUE;
				$message = array();
				// dispose TODO handle 'blocked' notices
				foreach ($formdata->FieldOrders as $key) {
					$obfld = $formdata->Fields[$key];
					if ($obfld->IsDisposition() && $obfld->IsDisposable()) {
						$res = $obfld->Dispose($id,$returnid);
						if (!$res[0]) {
							$alldisposed = FALSE;
							$message[] = $res[1];
						}
					}
				}
				// cleanups
				foreach ($formdata->FieldOrders as $key) {
					$obfld = $formdata->Fields[$key];
					$obfld->PostDisposeAction();
				}

				$parms = array();
				$parms['form_id'] = $form_id;
				$parms['form_name'] = PWForms\Utils::GetFormNameFromID($form_id);

				$tplvars['form_done'] = 1;
				if ($alldisposed) {
					//TODO how to handle $params['resume'] c.f. cancellation
					$cache->delete($cache_key);
					$act = PWForms\Utils::GetFormProperty($formdata,'submit_action','text');
					switch ($act) {
					 case 'text':
						$this->SendEvent('OnFormSubmit',$parms);
						PWForms\Utils::SetupFormVars($formdata,$tplvars);
						PWForms\Utils::ProcessTemplateFromDatabase($this,'pwf_sub_'.$form_id,$tplvars,TRUE);
						return;
					 case 'redir':
						$this->SendEvent('OnFormSubmit',$parms);
						$ret = PWForms\Utils::GetFormProperty($formdata,'redirect_page',0);
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
	} // !field expand/shrink
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

	if (isset($params['excludetype'])) {
	//TODO singleton or array of field-type(s) to be omitted from the form
	}
	if (isset($params['exclude'])) {
	//TODO singleton or array of field id(s) or alias(es) to be omitted from the form
	}

	//make initiator-supplied parameters available TODO may also be needed for later pass(es)
	$tplvars = array_diff_key($params, array(
	'action' => 1,
	'cancel' => 1,
	'exclude' => 1,
	'excludetype' => 1,
	'form' => 1,
	'form_id' => 1,
	'passthru' => 1,
	'preload' => 1,
	'resume' => 1
	));

	if (isset($params['preload'])) {
		//set fields' value from externally-supplied values
		foreach ($params['preload'] as $key=>$val) {
			if (is_numeric($key)) {
				$field_id = (int)$key;
			} else {
				$field_id = PWForms\Utils::GetFieldIDFromAlias($key);
				if ($field_id == -1) {
//TODO warning
					continue;
				}
			}
			if (isset($formdata->Fields[$field_id])) {
				$obfld = $formdata->Fields[$field_id];
				$obfld->SetValue($val);
			} else {
//TODO warning
			}
		}
	}

	$firsttime = TRUE;
	$formdata->Page = 1;
	$formdata->PagesCount = 1; //we will count

	$formdata->FieldOrders = array_keys($formdata->Fields);
	$funcs->Arrange($formdata->Fields,$formdata->FieldOrders);

	//construct cache key (more random than backend keys)
	if (!empty($_SERVER['SERVER_ADDR']))
		$token = $_SERVER['SERVER_ADDR'];
	else
		$token = mt_rand(0,999999).'.'.mt_rand(0,999999);
	$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100,2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
	$cache_key = 'pwf'.md5($token);
}

$tplvars['form_has_validation_errors'] = $validerr;
$tplvars['show_submission_errors'] = 0;

$udtonce = $firsttime && PWForms\Utils::GetFormProperty($formdata,'predisplay_udt');
$udtevery = PWForms\Utils::GetFormProperty($formdata,'predisplay_each_udt');
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

require __DIR__.DIRECTORY_SEPARATOR.'populate.show.php';

$cache->set($cache_key,$formdata,84600);

$styler = '<link rel="stylesheet" type="text/css" href="'.$baseurl.'/css/showform.css" />';
$t = PWForms\Utils::GetFormProperty($formdata,'css_file','');
if ($t) {
	$fp = ''.PWForms\Utils::GetUploadsPath($this).DIRECTORY_SEPARATOR.$t;
	if (is_file($fp)) {
		$url = PWForms\Utils::GetUploadURL($this,$t);
		$styler .= '\n<link rel="stylesheet" type="text/css" href="'.$url.'" />';
	}
}
$t = <<<EOS
var linkadd = '{$styler}',
 \$head = $('head'),
 \$linklast = \$head.find('link[rel="stylesheet"]:last');
if (\$linklast.length) {
 \$linklast.after(linkadd);
} else {
 \$head.append(linkadd);
}
EOS;
$jsall = NULL;
PWForms\Utils::MergeJS(FALSE,array($t),FALSE,$jsall);
echo $jsall;

echo $form_start.$hidden;
PWForms\Utils::ProcessTemplateFromDatabase($this,'pwf_'.$form_id,$tplvars,TRUE);
echo $form_end;
//inject constructed js after other content (pity we can't get to </body> or </html> from here)
$jsall = NULL;
PWForms\Utils::MergeJS($formdata->jsincs,$formdata->jsfuncs,$formdata->jsloads,$jsall);
if ($jsall)
	echo $jsall;
