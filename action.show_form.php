<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/
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
	...Se[DIWX]_ => expand or shrink a multi-element field
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
			$t2 = $t-900; //after 900 more seconds, it will be erased
			$src = $_SERVER['REMOTE_ADDR'];
			global $db;
			$pre = cms_db_prefix();
			$sql = ['UPDATE '.$pre.'module_pwf_ip_log SET howmany=255,basetime=? WHERE src=?'];
			$args = [[$t2,$src]];
			$sql[] = 'INSERT INTO '.$pre.'module_pwf_ip_log
(src,howmany,basetime) SELECT ?,255,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM '.$pre.'module_pwf_ip_log T WHERE T.src=?)';
			$args[] = [$src,$t2,$src];
			PWForms\Utils::SafeExec($sql, $args);
		}
	}

	function ClearFormCache($cache, $params)
	{
		$matches = preg_grep('/datakey$/', array_keys($params));
		if ($matches) {
			$cache_key = reset($matches);
			$cache->delete($cache_key);
		}
	}
}

if (!isset($params['form_id']) && isset($params['form'])) { // got the form by alias
	$params['form_id'] = PWForms\Utils::GetFormIDFromAlias($params['form']);
}
if (empty($params['form_id']) || $params['form_id'] == -1) {
	echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
		'title'=>$this->Lang('title_aborted'),
		'message'=>$this->Lang('err_data') . ' (1)',
		'error'=>1]);
	return;
}

try {
	$cache = PWForms\Utils::GetCache($this);
} catch (Exception $e) {
	echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
		'title'=>$this->Lang('title_aborted'),
		'message'=>$this->Lang('err_system').' NO CACHE MECHANISM',
		'error'=>1]);
	return;
}

list($current, $prior) = $this->_GetTokens(); //fresh pair of fieldname-prefixes

//check that we're current
$matched = preg_grep('/^pwfp_\d{3}_/', array_keys($params));
if ($matched) {
	$key = reset($matched);
	if (strpos($key, $current) === 0) {
		$prefix = $current;
	} elseif (strpos($key, $prior) === 0) {
		$prefix = $prior;
	} else {
		BlockSource();
		ClearFormCache($cache, $params);
		echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('comeback_expired')]);
		return;
	}
	while ($key = next($matched)) {
		if (strpos($key, $prefix) !== 0) {
			BlockSource();
			ClearFormCache($cache, $params);
			echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
				'title'=>$this->Lang('title_aborted'),
				'message'=>$this->Lang('comeback_expired')]);
			return;
		}
	}
} else {
	$prefix = $current;
}

if (isset($params[$prefix.'cancel'])) {
	ClearFormCache($cache, $params);
	if (isset($params[$prefix.'passthru'])) {
		$newparms = ['passthru'=>$params[$prefix.'passthru']];
	} else {
		$newparms = [];
	}
	if (strpos($params[$prefix.'resume'], ',') === FALSE) {
		$this->Redirect($id, $params[$prefix.'resume'], $returnid, $newparms);
	} else {
		list($module, $action) = explode(',', $params[$prefix.'resume']);
		$mod = cms_utils::get_module($module);
		$this->LoadRedirectMethods();
		cms_module_Redirect($mod, $id, $action, $returnid, $newparms);
		exit;
	}
}

$form_id = (int)$params['form_id'];
$validerr = 0; //default no validation error

if (isset($params[$prefix.'datakey'])) {
	$firsttime = FALSE; //this is a return-visit
	$tplvars = []; //TODO members to preserve c.f. 1st-pass

	$cache_key = $params[$prefix.'datakey'];
	$formdata = $cache->get($cache_key);
	if (!$formdata) {
		echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('err_data') . ' (2)',
			'error'=>1]);
		return;
	}
	$formdata->pwfmod = &$this;

	//update cached field data from $params[]
	foreach ($matched as $key) {
		$pid = substr($key, 9); //ignore 'pwfp_NNN_' prefix
		if (is_numeric($pid)) {
			if (isset($formdata->Fields[$pid])) {
				$obfld = $formdata->Fields[$pid];
				if ($obfld->Type != 'Captcha') {
					$val = $params[$key];
				} else {
					if (isset($params['captcha_input'])) {
						$val = $params['captcha_input'];
					}
					if (!$val) {
						$val = '-.-';
					} //ensure invalid-value if empty
				}
				$obfld->SetValue($val);
			}
		}
	}

	if (isset($params[$prefix.'done'])) { //form finally-submitted
		$limit = PWForms\Utils::GetFormProperty($formdata, 'submit_limit', 0);
		if ($limit > 0) { // rate-limiting applies
			if (!empty($_SERVER['REMOTE_ADDR'])) {
				$src = $_SERVER['REMOTE_ADDR'];
				$t = time();
				$t2 = $t-3600;
				$num = 0;
				$pre = cms_db_prefix();
				$sql = 'DELETE FROM '.$pre.'module_pwf_ip_log WHERE src=? AND basetime<?';
				$args = [$src,$t2];
				$sql2 = 'SELECT COUNT(*) AS num FROM '.$pre.'module_pwf_ip_log WHERE src=? AND basetime<=?';
				$args2 = [[$src,$t]];

				$nt = 10;
				while ($nt > 0) {
					$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE'); //this isn't perfect!
					$db->StartTrans();
					$db->Execute($sql, $args);
					$num = $db->GetOne($sql2, $args2);
					if ($db->CompleteTrans()) {
						break;
					} else {
						--$nt;
						usleep(50000);
					}
				}
				if ($nt == 0) {
					$cache->delete($cache_key);
					echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
						'title'=>$this->Lang('title_aborted'),
						'message'=>$this->Lang('system_data'),
						'error'=>1]);
					return;
				}
				if ($num) {
					if ($num == 255) { //blocked due to expiry
						BlockSource(); //again!
						$cache->delete($cache_key);
						echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
							'title'=>$this->Lang('title_aborted'),
							'message'=>$this->Lang('comeback_expired')]);
						return;
					}
					if ($num <= $limit) {
						$sql = [];
						$sql[] = <<<EOS
UPDATE {$pre}module_pwf_ip_log SET howmany=howmany+1 WHERE src=? AND howmany<?
EOS;
						$args = [[$src,$num+1]];
						$sql[] = <<<EOS
INSERT INTO {$pre}module_pwf_ip_log (src,howmany,basetime)
SELECT ?,1,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pre}module_pwf_ip_log T WHERE T.src=?)
EOS;
						$args[] = [$src,$t,$src];
						PWForms\Utils::SafeExec($sql, $args);
					} else {
						$cache->delete($cache_key);
						echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
							'title'=>$this->Lang('title_aborted'),
							'message'=>$this->Lang('comeback_toomany')]);
						return;
					}
				}
			}
		}

		// validate form fields
		$allvalid = TRUE;
		$notempty = PWForms\Utils::GetFormProperty($formdata, 'blank_invalid',
			$this->GetPreference('blank_invalid'));
		$message = [];
//			$formPage = 1;
//			$valPage = $formdata->Page - 1; //TODO off by 1 ?

		foreach ($formdata->FieldOrders as $field_id) {
			$obfld = $formdata->Fields[$field_id];
/*TODO multi-page-form field validation and feedback
			if ($obfld->GetFieldType() == 'PageBreak')
				++$formPage;
			if ($valPage != $formPage) {
				continue;
			}
*/
			$val = TRUE;
			if (// $obfld->GetChangeRequirement() &&
				$obfld->IsRequired() && !$obfld->HasValue($notempty)) {
				$allvalid = $val = FALSE;
				$obfld->ValidationMessage = $this->Lang('enter_a_value', $obfld->GetName());
				$message[] = $obfld->ValidationMessage;
			} elseif ($obfld->GetValue()) {
				$res = $obfld->Validate($id);
				if (!$res[0]) {
					$allvalid = $val = FALSE;
					$message[] = $res[1];
				}
			}
			$obfld->SetProperty('valid', $val);
		}

		if ($allvalid) {
			$udt = PWForms\Utils::GetFormProperty($formdata, 'validate_udt');
			if (!empty($udt)) {
				$udtops = cmsms()->GetUserTagOperations(); //TODO ok here ?
				if ($udtops->UserTagExists($udt)) {
					$unspec = PWForms\Utils::GetFormProperty($formdata, 'unspecified', $this->Lang('unspecified'));

					$parms = $params;
					foreach ($formdata->FieldOrders as $field_id) {
						$obfld = $formdata->Fields[$field_id];
						if ($obfld->DisplayInSubmission()) {
							$val = $obfld->DisplayableValue();
							if ($val == '') {
								$val = $unspec;
							}
						} else {
							$val = '';
						}
						$name = $obfld->GetVariableName();
						$parms[$name] = $val;
						$alias = $obfld->ForceAlias();
						$parms[$alias] = $val;
						$id = $obfld->GetId();
						$parms['fld_'.$id] = $val;
					}

					$res = $udtops->CallUserTag($udt, $parms); //mixed result
					if (is_array($res)) {
						if (!$res[0]) {
							$allvalid = FALSE;
							$message[] = $res[1];
						}
					} elseif (!$res) {
						$allvalid = FALSE;
						$message[] = $this->Lang('err_usertag');
					}
				}
			}
		}

		if ($allvalid) {
			// run all field methods that modify other fields
			$computes = [];
			foreach ($formdata->FieldOrders as $field_id) {
				$obfld = $formdata->Fields[$field_id];
				$obfld->PreDisposeAction();
				if ($obfld->ComputeOnSubmission()) {
					$computes[$field_id] = $obfld->ComputeOrder();
				}
			}

			if ($computes) {
				asort($computes);
				foreach ($computes as $field_id=>$val) {
					$formdata->Fields[$field_id]->Compute();
				}
			}

			$alldisposed = TRUE;
			$message = [];
			// dispose TODO handle 'blocked' notices
			foreach ($formdata->FieldOrders as $field_id) {
				$obfld = $formdata->Fields[$field_id];
				if ($obfld->IsDisposition() && $obfld->IsDisposable()) {
					$res = $obfld->Dispose($id, $returnid);
					if (!$res[0]) {
						$alldisposed = FALSE;
						$message[] = $res[1];
					}
				}
			}
			// cleanups
			foreach ($formdata->FieldOrders as $field_id) {
				$obfld = $formdata->Fields[$field_id];
				$obfld->PostDisposeAction();
			}

			$parms = [];
			$parms['form_id'] = $form_id;
			$parms['form_name'] = PWForms\Utils::GetFormNameFromID($form_id);

			$tplvars['form_done'] = 1;
			if ($alldisposed) {
				//TODO how to handle $params['resume'] c.f. cancellation
				$cache->delete($cache_key);
				$act = PWForms\Utils::GetFormProperty($formdata, 'submit_action', 'text');
				switch ($act) {
				 case 'text':
					$this->SendEvent('OnFormSubmit', $parms);
					PWForms\Utils::SetupFormVars($formdata, $tplvars);
					PWForms\Utils::ProcessTemplateFromDatabase($this, 'pwf_sub_'.$form_id, $tplvars, TRUE);
					return;
				 case 'redir':
					$this->SendEvent('OnFormSubmit', $parms);
					$ret = PWForms\Utils::GetFormProperty($formdata, 'redirect_page', 0);
					if ($ret > 0) {
						$this->RedirectContent($ret);
					} else {
						echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
							'title'=>$this->Lang('missing_type', $this->Lang('page')),
							'message'=>$this->Lang('cannot_show_TODO'),
							'error'=>1]);
						return;
					}
				 case 'confirm':
					//'external' confirmation needed before acceptance
					//after confirmation, formdata will be different
					echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
						'title'=>$this->Lang('title_confirm'),
						'message'=>$this->Lang('help_confirm')]);
					return;
				 default:
					exit;
				}
			} else {
				$this->SendEvent('OnFormSubmitError', $parms);
				$tplvars += [
					'submission_error' => $this->Lang('err_submission'),
					'submission_error_list' => $message,
					'show_submission_errors' => !$this->GetPreference('hide_errors')
				];
			}
			unset($parms);
		} else { // validation error(s)
			$validerr = 1;
			$tplvars['form_validation_errors'] = $message;
			$formdata->Page--; //TODO why
		}
	} elseif (isset($params[$prefix.'continue'])) { //not submitted/done
			++$formdata->Page;
	} elseif (isset($params[$prefix.'previous'])) {
		--$formdata->Page;
		if ($formdata->Page < 1) {
			$formdata->Page = 1;
		}
	} elseif ($matched && ($matches=preg_grep('/_Se[DIWX]_\d+$/', $matched))) { //add or delete a sequence
		$key = reset($matches);
		preg_match('/_Se([DIWX])_(\d+)/', $key, $matches);
		if (array_key_exists($matches[2], $formdata->Fields)) { //may be deleted already
			switch ($matches[1]) {
			 case 'D': //delete before
				$del = TRUE;
				$after = FALSE;
				break;
			 case 'I': //insert before
				$del = FALSE;
				$after = FALSE;
				break;
			 case 'W': //delete after
				$del = TRUE;
				$after = TRUE;
				break;
			 case 'X': //insert after
				$del = FALSE;
				$after = TRUE;
				break;
			}
			$seqs = new PWForms\SeqOperations();
			$obfld = $formdata->Fields[$matches[2]];
			if ($del) {
				$seqs->DeleteSequenceFields($obfld, $after);
			} else {
				$seqs->CopySequenceFields($obfld, $after);
			}
			$adjust = TRUE;
		}
		unset($params[$key]);
	}
	//any textfield expansion/contraction handled in the relevant field
	//$matched && preg_grep('/_Fe[DX]_/',$matched);

/*TODO make initiator-supplied parameters available as per 1st-pass
	$tplvars = array_diff_key($params, [
	'action' => 1,
	'cancel' => 1,
	'exclude' => 1,
	'excludetype' => 1,
	'form' => 1,
	'form_id' => 1,
	'passthru' => 1,
	'preload' => 1,
	'resume' => 1
	]);
*/
} else { //first time
	$funcs = new PWForms\FormOperations();
	$formdata = $funcs->Load($this, $form_id, $id, $params);
	if (!$formdata) {
		unset($funcs);
		echo PWForms\Utils::ProcessTemplate($this, 'message.tpl', [
			'title'=>$this->Lang('title_aborted'),
			'message'=>$this->Lang('err_data') . ' (3)',
			'error'=>1]);
		return;
	}

	$formdata->FieldOrders = array_keys($formdata->Fields);
	$funcs->Arrange($formdata->Fields, $formdata->FieldOrders);

	if (isset($params['excludetype'])) {
		if (is_array($params['excludetype'])) {
			$extypes = $params['excludetype'];
		} else {
			$extypes = [$params['excludetype']];
		}
	} else {
		$extypes = FALSE;
	}
	if (isset($params['exclude'])) {
		if (is_array($params['exclude'])) {
			$exfields = $params['exclude'];
		} else {
			$exfields = [$params['exclude']];
		}
	} else {
		$exfields = FALSE;
	}

	$seqs = FALSE;
	$total = count($formdata->FieldOrders);
	for ($o=0; $o<$total; $o++) { //NOT foreach, cuz array content may change during loop
		$field_id = $formdata->FieldOrders[$o];
		$obfld = $formdata->Fields[$field_id];
		$type = $obfld->GetFieldType();
		if ($extypes && in_array($type, $extypes)) {
			unset($formdata->Fields[$field_id]);
			unset($formdata->FieldOrders[$o]); //TODO collapse array indices: splice?
		} elseif ($exfields && 0) { //TODO in_array(??,$exfields)
			unset($formdata->Fields[$field_id]);
			unset($formdata->FieldOrders[$o]);
		} elseif ($type == 'SequenceStart') {
			$times = $obfld->GetProperty('repeatcount');
			if ($times > 1) {
				if (!$seqs) {
					$seqs = new PWForms\SeqOperations();
				}
				$seqs->CopySequenceFields($obfld, TRUE, $times-1); //adjusts various parameters
				$total = count($formdata->FieldOrders);
			}
		}
	}

	//make initiator-supplied parameters available TODO may also be needed for later pass(es)
	$tplvars = array_diff_key($params, [
	'action' => 1,
	'cancel' => 1,
	'exclude' => 1,
	'excludetype' => 1,
	'form' => 1,
	'form_id' => 1,
	'passthru' => 1,
	'preload' => 1,
	'resume' => 1
	]);

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

	list($formdata->current_prefix, $formdata->prior_prefix) = $this->_GetTokens();

	//construct cache key (more random than backend keys)
	if (!empty($_SERVER['SERVER_ADDR'])) {
		$token = $_SERVER['SERVER_ADDR'];
	} else {
		$token = mt_rand(0, 999999).'.'.mt_rand(0, 999999);
	}
	$token .= 'SERVER_ADDR'.uniqid().mt_rand(1100, 2099).reset($_SERVER).key($_SERVER).end($_SERVER).key($_SERVER);
	$cache_key = 'pwf'.md5($token);
}

$tplvars['form_has_validation_errors'] = $validerr;
$tplvars['show_submission_errors'] = 0;

$udtonce = ($firsttime) ? PWForms\Utils::GetFormProperty($formdata, 'predisplay_udt') : FALSE;
$udtevery = PWForms\Utils::GetFormProperty($formdata, 'predisplay_each_udt');
if ($udtonce || $udtevery) {
	$parms = $params;
	$parms['FORM'] =& $formdata;
	$udtops = cmsms()->GetUserTagOperations();
	if ($udtonce) {
		$udtops->CallUserTag($udtonce, $parms);
	}
	if ($udtevery) {
		$udtops->CallUserTag($udtevery, $parms);
	}
	unset($parms);
	unset($udtops);
}

$this->SendEvent('OnFormDisplay', [
 'form_id'=>$form_id,
 'form_name'=>PWForms\Utils::GetFormNameFromID($form_id)]);

$tplvars['form_done'] = 0;

//fresh start for js accumulators
$ob = new \stdClass();
$ob->jsincs = []; //'include' directives
$ob->jsfuncs = []; //funcs and/or instructions
$ob->jsloads = []; //document-ready funcs and/or instructions
$formdata->Jscript = &$ob;

require __DIR__.DIRECTORY_SEPARATOR.'populate.show.php';

if ($ob->jsincs) {
	$jsincs = array_values($ob->jsincs);
	$ob->jsincs = NULL;
} else {
	$jsincs = NULL;
}
if ($ob->jsfuncs) {
	$jsfuncs = array_values($ob->jsfuncs);
	$ob->jsfuncs = NULL;
} else {
	$jsfuncs = NULL;
}
if ($ob->jsloads) {
	$jsloads = array_values($ob->jsloads);
	$ob->jsloads = NULL;
} else {
	$jsloads = NULL;
}
unset($formdata->Jscript);

$cache->set($cache_key, $formdata, 84600);

$styler = '<link rel="stylesheet" type="text/css" href="'.$baseurl.'/css/showform.css" />';
$t = PWForms\Utils::GetFormProperty($formdata, 'css_file', '');
if ($t) {
	$fp = ''.PWForms\Utils::GetUploadsPath($this).DIRECTORY_SEPARATOR.$t;
	if (is_file($fp)) {
		$url = PWForms\Utils::GetUploadURL($this, $t);
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
PWForms\Utils::MergeJS(FALSE, [$t], FALSE, $jsall);
echo $jsall;

echo $form_start.$hidden;
PWForms\Utils::ProcessTemplateFromDatabase($this, 'pwf_'.$form_id, $tplvars, TRUE);
echo $form_end;
//inject constructed js after other content (pity we can't get to </body> or </html> from here)
$jsall = NULL;
PWForms\Utils::MergeJS($jsincs, $jsfuncs, $jsloads, $jsall);
if ($jsall) {
	echo $jsall;
}
