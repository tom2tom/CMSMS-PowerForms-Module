<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfOperate
{
/*EXPORTED function FormRender($id,&$params,$returnid)
	{
		// Check if form id given
	TODO	$mod = $this->formsmodule;

		if($this->Id == -1)
		{
			return "<!-- no form -->\n";
		}

		// Check if show full form
		if($this->loaded != 'full')
		{
			$this->Load($this->Id,$params,TRUE);
		}

		// Usual crap
		$reqSymbol = $this->GetAttr('required_field_symbol','*');
		$smarty = cmsms()->GetSmarty();

		$smarty->assign('title_page_x_of_y',$mod->Lang('title_page_x_of_y',array($this->Page,$this->FormPagesCount)));

		$smarty->assign('css_class',$this->GetAttr('css_class',''));
		$smarty->assign('total_pages',$this->FormPagesCount);
		$smarty->assign('this_page',$this->Page);
		$smarty->assign('form_name',$this->Name);
		$smarty->assign('form_id',$this->Id);
		$smarty->assign('actionid',$id);

		// Build hidden
		$hidden = $mod->CreateInputHidden($id, 'form_id', $this->Id);
		if(isset($params['lang']))
		{
			$hidden .= $mod->CreateInputHidden($id, 'lang', $params['lang']);
		}
		$hidden .= $mod->CreateInputHidden($id, 'pwfp_continue', ($this->Page + 1));
		if(isset($params['pwfp_browser_id']))
		{
			$hidden .= $mod->CreateInputHidden($id,'pwfp_browser_id',$params['pwfp_browser_id']);
		}
		if(isset($params['response_id']))
		{
			$hidden .= $mod->CreateInputHidden($id,'response_id',$params['response_id']);
		}
		if($this->Page > 1)
		{
			$hidden .= $mod->CreateInputHidden($id, 'pwfp_previous', ($this->Page - 1));
		}
		if($this->Page == $this->FormPagesCount)
		{
			$hidden .= $mod->CreateInputHidden($id, 'pwfp_done', 1);
		}

		// Start building fields
		$fields = array();
		$prev = array();
		$formPageCount = 1;

		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'PageBreakField')
			{
				$formPageCount++;
			}
			if($formPageCount != $this->Page)
			{
				$testIndex = 'pwfp__'.$fld->GetId();

				// Ryan's ugly fix for Bug 4307
				// We should figure out why this field wasn't populating its Smarty variable
	TODO		if($fld->GetFieldType() == 'FileUploadField')
				{
					$smarty->assign('fld_'.$fld->GetId(),$fld->GetHumanReadableValue());
					$hidden .= $mod->CreateInputHidden($id,
						$testIndex,
						pwfUtils::unmy_htmlentities($fld->GetHumanReadableValue()));
					$thisAtt = $fld->GetHumanReadableValue(FALSE);
					$smarty->assign('test_'.$fld->GetId(), $thisAtt);
					$smarty->assign('value_fld'.$fld->GetId(), $thisAtt[0]);
				}

				if(!isset($params[$testIndex]))
				{
					// do we need to write something?
				}
				elseif(is_array($params[$testIndex]))
				{
					foreach($params[$testIndex] as $val)
					{
						$hidden .= $mod->CreateInputHidden($id,
									$testIndex.'[]',
									pwfUtils::unmy_htmlentities($val));
					}
				}
				else
				{
					$hidden .= $mod->CreateInputHidden($id,
							   $testIndex,
							   pwfUtils::unmy_htmlentities($params[$testIndex]));
				}

				if($formPageCount < $this->Page && $fld->DisplayInSubmission())
				{
					$oneset = new stdClass();
					$oneset->value = $fld->GetHumanReadableValue();

					$smarty->assign($fld->GetName(),$oneset);

					if($fld->GetAlias() != '')
					{
						$smarty->assign($fld->GetAlias(),$oneset);
					}

					$prev[] = $oneset;
				}
				continue;
			}
			$oneset = new stdClass();
			
			if($fld->GetAlias() != '')
			{
				$smarty->assign($fld->GetAlias(),$oneset);
				$oneset->alias = $fld->GetAlias();
			}
			else
			{
				$oneset->alias = $name_alias;
			}
			$oneset->css_class = $fld->GetOption('css_class');
			$oneset->display = $fld->DisplayInForm()?1:0;
			$oneset->error = $fld->GetOption('is_valid',TRUE)?'':$fld->validationErrorText;
			$oneset->field_helptext_id = 'pwfp_ht_'.$fld->GetID();
			$oneset->has_label = $fld->HasLabel();
			$oneset->helptext = $fld->GetOption('helptext');
			if(((!$fld->HasLabel()) || $fld->HideLabel()) && ($fld->GetOption('fbr_edit','0') == '0' || $params['in_admin'] != 1))
				$oneset->hide_name = 1;
			else
				$oneset->hide_name = 0;
			$oneset->id = $fld->GetId();
			$oneset->input = $fld->GetFieldInput($id, $params, $returnid);
			$oneset->input_id = $fld->GetCSSId();
			$oneset->label_parts = $fld->LabelSubComponents()?1:0;
			$oneset->logic = $fld->GetFieldLogic();
			$oneset->multiple_parts = $fld->HasMultipleFormComponents()?1:0;
			$oneset->name = $fld->GetName();
			$oneset->needs_div = $fld->NeedsDiv();
			$oneset->required = $fld->IsRequired()?1:0;
			$oneset->required_symbol = $fld->IsRequired()?$reqSymbol:'';
			$oneset->smarty_eval = $fld->GetSmartyEval()?1:0;
			$oneset->type = $fld->GetDisplayType();
			$oneset->valid = $fld->validated?1:0;
			$oneset->values = $fld->GetAllHumanReadableValues();
	//		$oneset->valid = $fld->GetOption('is_valid',TRUE)?1:0;

			$name_alias = $fld->GetName();
			$name_alias = str_replace($toreplace, $replacement, $name_alias);
			$name_alias = strtolower($name_alias);
			$name_alias = preg_replace('/[^a-z0-9]+/i','_',$name_alias);
			$smarty->assign($name_alias,$oneset);

			$fields[$oneset->input_id] = $oneset;
	//		$fields[] = $oneset;
		}
		unset ($fld);

		$smarty->assign('hidden',$hidden);
		$smarty->assign_by_ref('fields',$fields);
		$smarty->assign_by_ref('previous',$prev);

		$jsStr = '';
		$jsTrigger = '';
		if($this->GetAttr('input_button_safety','0') == '1')
		{
			$jsStr = <<<EOS
<script type="text/javascript">
//<![CDATA[
var submitted = 0;
function LockButton () {
 var ret = false;
 if(!submitted) {
  var item = document.getElementById("{$id}submit");
  if(item != NULL) {
   setTimeout(function() {item.disabled = true}, 0);
  }
  submitted = 1;
  ret = true;
 }
 return ret;
}
//]]>
</script>
EOS;
			$jsTrigger = ' onclick="return LockButton()"';
		}

		$js = $this->GetAttr('submit_javascript');

		if($this->Page > 1)
		{
			$smarty->assign('prev','<input class="cms_submit submit_prev" name="'.$id.'pwfp_prev" id="'.$id.'pwfp_prev" value="'.$this->GetAttr('prev_button_text').'" type="submit" '.$js.' />');
		}
		else
		{
			$smarty->assign('prev','');
		}

		$smarty->assign('has_captcha',0);
		if($this->Page < $formPageCount)
		{
			$smarty->assign('submit','<input class="cms_submit submit_next" name="'.$id.'submit" id="'.$id.'submit" value="'.$this->GetAttr('next_button_text').'" type="submit" '.$js.' />');
		}
		else
		{
			$captcha = $mod->getModuleInstance('Captcha');
			if($this->GetAttr('use_captcha','0') == '1' && $captcha != NULL)
			{
				$smarty->assign('graphic_captcha',$captcha->getCaptcha());
				$smarty->assign('title_captcha',$this->GetAttr('title_user_captcha',$mod->Lang('title_user_captcha')));
				$smarty->assign('input_captcha',$mod->CreateInputText($id, 'pwfp_captcha_phrase',''));
				$smarty->assign('has_captcha',1);
			}

			$smarty->assign('submit','<input class="cms_submit submit_current" name="'.$id.'submit" id="'.$id.'submit" value="'.$this->GetAttr('submit_button_text').'" type="submit" '.$js.' />');
		}
		return $mod->ProcessTemplateFromDatabase('pwf_'.$this->Id);
	 }

*/
	// returns array, element 0 is TRUE for success, FALSE for failure
	 // element 1 is an array of reasons, upon failure
	 function FormValidate()
	 {
		$validated = TRUE;
		$message = array();
		$formPageCount=1;
		$valPage = $this->Page - 1;
		$usertagops = cmsms()->GetUserTagOperations();
		$mod = $this->formsmodule;
		$udt = $this->GetAttr('validate_udt','');
		$unspec = $this->GetAttr('unspecified',$mod->Lang('unspecified'));

		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'PageBreakField')
				$formPageCount++;
			if($valPage != $formPageCount)
				continue;

			$deny_space_validation = !!$mod->GetPreference('blank_invalid');
			if(// ! $fld->IsNonRequirableField() &&
				$fld->IsRequired() && $fld->HasValue($deny_space_validation) === FALSE)
			{
				$message[] = $mod->Lang('please_enter_a_value',$fld->GetName());
				$validated = FALSE;
				$fld->SetOption('is_valid',FALSE);
				$fld->validationErrorText = $mod->Lang('please_enter_a_value',$fld->GetName());
				$fld->validated = FALSE;
			}
			else if($fld->GetValue() != $mod->Lang('unspecified'))
			{
				$res = $fld->Validate();
				if($res[0] != TRUE)
				{
					$message[] = $res[1];
					$validated = FALSE;
					$fld->SetOption('is_valid',FALSE);
				}
				else
					$fld->SetOption('is_valid',TRUE);
			}

			if($validated == TRUE && !empty($udt) && "-1" != $udt)
			{
				$parms = $params;
				foreach($this->Fields as &$othr)
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
					{
						$parms[$alias] = $replVal;
					}
				}
				unset ($othr);
				$res = $usertagops->CallUserTag($udt,$parms);
				if($res[0] != TRUE)
				{
					$message[] = $res[1];
					$validated = FALSE;
				}
			}
		}
		unset ($fld);
		return array($validated, $message);
	 }

	 // returns array, element 0 is TRUE for success, FALSE for failure
	 // element 1 is an array of reasons, upon failure
	 function FormDispose($returnid)
	 {
		$suppress_email=FALSE //TODO
		// first, we run all field methods that will modify other fields
		$computes = array();
		$i = 0; //don't assume anything about fields-array key
		foreach($this->Fields as &$fld)
		{
			if($fld->ModifiesOtherFields())
			{
				$fld->ModifyOtherFields();
			}
			if($fld->ComputeOnSubmission())
			{
				$computes[$i] = $fld->ComputeOrder();
			}
			$i++;
		}

		asort($computes);
		foreach($computes as $cKey=>$cVal)
		{
			$this->Fields[$cKey]->Compute();
		}

		$resArray = array();
		$retCode = TRUE;
		// for each form disposition pseudo-field, dispose the form results
		foreach($this->Fields as &$fld)
		{
			if($fld->IsDisposition() && $fld->DispositionIsPermitted())
			{
				if(!($suppress_email && $fld->IsEmailDisposition()))
				{
					$res = $fld->DisposeForm($returnid);
					if($res[0] == FALSE)
					{
						$retCode = FALSE;
						$resArray[] = $res[1];
					}
				}
			}
		}
		// handle any last cleanup functions
		foreach($this->Fields as &$fld)
		{
			$fld->PostDispositionAction();
		}
		unset ($fld);
		return array($retCode,$resArray);
	 }

	 function ManageFileUploads()
	 {
		$config = cmsms()->GetConfig();
		$mod = $this->formsmodule;

		// build rename map
		$mapId = array();
		$eval_string = FALSE;
		$i = 0;
		foreach($this->Fields as &$fld)
		{
			$mapId[$fld->GetId()] = $i;
			$i++;
		}

		foreach($this->Fields as &$fld)
		{
			if(strtolower(get_class($fld)) == 'pwffileuploadfield')
			{
				// Handle file uploads
				// if the uploads module is found, and the option is checked in
				// the field, then the file is added to the uploads module
				// and a link is added to the results
				// if the option is not checked, then the file is merely uploaded
				// to the "uploads" directory
	TODO ID			$_id = 'm1_pwfp__'.$fld->GetId();
				if(isset($_FILES[$_id]) && $_FILES[$_id]['size'] > 0)
				{
					$thisFile =& $_FILES[$_id];
					$thisExt = substr($thisFile['name'],strrpos($thisFile['name'],'.'));

					if($fld->GetOption('file_rename','') == '')
					{
						$destination_name = $thisFile['name'];
					}
					else
					{
						$flds = array();
						$destination_name = $fld->GetOption('file_rename');
						preg_match_all('/\$fld_(\d+)/', $destination_name, $flds);
						foreach($flds[1] as $tF)
						{
							if(isset($mapId[$tF]))
							{
								$ref = $mapId[$tF];
								$destination_name = str_replace('$fld_'.$tF,
									 $this->Fields[$ref]->GetHumanReadableValue(),$destination_name);
							}
						}
						$destination_name = str_replace('$ext',$thisExt,$destination_name);
					}

					if($fld->GetOption('sendto_uploads'))
					{
						// we have a file we can send to the uploads
						$uploads = $mod->GetModuleInstance('Uploads');
						if(!$uploads)
						{
							// no uploads module
							audit(-1, $mod->GetName(), $mod->Lang('submit_error'),$mail->GetErrorInfo());
							return array($res, $mod->Lang('error_module_upload'));
						}

						$parms = array();
						$parms['input_author'] = $mod->Lang('anonymous');
						$parms['input_summary'] = $mod->Lang('title_uploadmodule_summary');
						$parms['category_id'] = $fld->GetOption('uploads_category');
						$parms['field_name'] = $_id;
						$parms['input_destname'] = $destination_name;
						if($fld->GetOption('allow_overwrite','0') == '1')
						{
							$parms['input_replace'] = 1;
						}
						$res = $uploads->AttemptUpload(-1,$parms,-1);

						if($res[0] == FALSE)
						{
							// failed upload kills the send.
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',$res[1]));
							return array($res[0], $mod->Lang('uploads_error',$res[1]));
						}

						$uploads_destpage = $fld->GetOption('uploads_destpage');
						$url = $uploads->CreateLink ($parms['category_id'], 'getfile', $uploads_destpage, '',
							array ('upload_id' => $res[1]), '', TRUE);

						$url = str_replace('admin/moduleinterface.php?','index.php?',$url);

						$fld->ResetValue();
						$fld->SetValue($url);
					}
					else
					{
						// Handle the upload ourselves
						$src = $thisFile['tmp_name'];
						$dest_path = $fld->GetOption('file_destination',$config['uploads_path']);

						// validated message before, now do it for the file itself
						$valid = TRUE;
						$ms = $fld->GetOption('max_size');
						$exts = $fld->GetOption('permitted_extensions','');
						if($ms != '' && $thisFile['size'] > ($ms * 1024))
						{
							$valid = FALSE;
						}
						else if($exts != '')
						{
							$match = FALSE;
							$legalExts = explode(',',$exts);
							foreach($legalExts as $thisExt)
							{
								if(preg_match('/\.'.trim($thisExt).'$/i',$thisFile['name']))
								{
									$match = TRUE;
								}
								else if(preg_match('/'.trim($thisExt).'/i',$thisFile['type']))
								{
									$match = TRUE;
								}
							}
							if(!$match)
							{
								$valid = FALSE;
							}
						}
						if(!$valid)
						{
							unlink($src);
							audit(-1, $mod->GetName(), $mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
							return array(FALSE, '');
						}
						$dest = $dest_path.DIRECTORY_SEPARATOR.$destination_name;
						if(file_exists($dest) && $fld->GetOption('allow_overwrite','0')=='0')
						{
							unlink($src);
							return array(FALSE,$mod->Lang('error_file_exists', array($destination_name)));
						}
						if(!move_uploaded_file($src,$dest))
						{
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',''));
							return array(FALSE, $mod->Lang('uploads_error',''));
						}
						else
						{
							if(strpos($dest_path,$config['root_path']) !== FALSE)
							{
								$url = str_replace($gCms->config['root_path'],'',$dest_path).DIRECTORY_SEPARATOR.$destination_name;
							}
							else
							{
								$url = $mod->Lang('uploaded_outside_webroot',$destination_name);
							}
							//$fld->ResetValue();
							//$fld->SetValue(array($dest,$url));
						}
					}
				}
			}
		}
		unset ($fld);
		return array(TRUE,'');
	}

}

?>
