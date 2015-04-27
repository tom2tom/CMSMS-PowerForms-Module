<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDispositionEmailBase extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsDisposition = true;
		$this->IsEmailDisposition = true;
		$this->ValidationTypes = array();
	}

	//override this
	function StatusInfo()
	{
		return '';
	}

	//override this
	function DisposeForm($returnid)
	{
		return array(true,'');
	}

	function TemplateStatus()
	{
		if($this->GetOption('email_template','') == '')
		{
			$mod = $this->formdata->pwfmodule;
			return $mod->Lang('email_template_not_set');
		}
	}

	// send emails
	function SendForm($destination_array, $subject)
	{
		if($destination_array == false || $subject == false)
			return array(false,'');

		$form = $this->formdata;
		$mod = $form->pwfmodule;
		$db =$mod->dbHandle;

		if($mod->GetPreference('enable_antispam'))
		{
			if(!empty($_SERVER['REMOTE_ADDR']))
			{
				$query = 'select count(src_ip) as sent from '.cms_db_prefix().
				'module_pwf_ip_log where src_ip=? AND sent_time > ?';

				$dbresult = $db->GetOne($query, array($_SERVER['REMOTE_ADDR'],
					   trim($db->DBTimeStamp(time() - 3600),"'")));

				if($dbresult && isset($dbresult['sent']) && $dbresult['sent'] > 9)
				{
					// too many from this IP address. Kill it.
					$msg = '<hr />'.$mod->Lang('suspected_spam').'<hr />';
					audit(-1, $mod->GetName(),$mod->Lang('log_suspected_spam',$_SERVER['REMOTE_ADDR']));
					return array(false,$msg);
				}
			}
		}

		$mail = $mod->GetModuleInstance('CMSMailer');
		if($mail == FALSE)
		{
			$msg = '';
			if(!$mod->GetPreference('hide_errors',0))
			{
				$msg = '<hr />'.$mod->Lang('missing_cms_mailer'). '<hr />';
			}
			audit(-1, $mod->GetName(),$mod->Lang('missing_cms_mailer'));
			return array(false,$msg);
		}
		$mail->reset();

		$rt = $this->GetOption('email_reply_to_address','');
		$rn = $this->GetOption('email_reply_to_name','');
		if(empty($rn))
		{
			$rn = $this->GetOption('email_from_name','');
		}
		if($this->SetReplyToAddress() && !empty($rt))
		{
			$mail->AddReplyTo($rt,$this->SetFromName()?$rn:'');
		}

		if($this->SetFromAddress())
		{
			$mail->SetFrom($this->GetOption('email_from_address'));
		}
		if($this->SetFromName())
		{
			$mail->SetFromName($this->GetOption('email_from_name'));
		}

		$mail->SetCharSet($this->GetOption('email_encoding','utf-8'));

		$message = $this->GetOption('email_template','');
		$htmlemail = ($this->GetOption('html_email','0') == '1');
		if($this->GetFieldType() == 'DispositionEmailConfirmation')
		{
			$form->AddTemplateVariable('confirm_url',$mod->Lang('title_confirmation_url'));
		}
		if($htmlemail)
		{
			$mail->IsHTML(true);
		}
		if(strlen($message) < 1)
		{
			$message = $form->createSampleTemplate(false);
			if($htmlemail)
			{
				$message2 = $form->createSampleTemplate(true);
			}
		}
		elseif($htmlemail)
		{
			$message2 = $message;
		}
		$form->setFinishedFormSmarty($htmlemail);

		$theFields = $form->GetFields();

		for($i=0;$i<count($theFields);$i++)
		{
	 		if(strtolower(get_class($theFields[$i])) == 'pwffileuploadfield')
    		{
				if(!$theFields[$i]->GetOption('suppress_attachment'))
				{
					if(!$theFields[$i]->GetOption('sendto_uploads'))
					{
						// we have a file we wish to attach
						$thisAtt = $theFields[$i]->GetHumanReadableValue(false);

						if(is_array($thisAtt))
						{
							if(function_exists('finfo_open'))
							{
								$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
								$thisType = finfo_file($finfo, $thisAtt[0]);
								finfo_close($finfo);
							}
							else if(function_exists('mime_content_type'))
							{
								$thisType = mime_content_type($thisAtt[0]);
							}
							else
							{
								$thisType = 'application/octet-stream';
							}
							$thisNames = split('[/:\\]',$thisAtt[0]);
							$thisName = array_pop($thisNames);
							if(!$mail->AddAttachment($thisAtt[0], $thisName, "base64", $thisType))
							{
								// failed upload kills the send.
								audit(-1, $mod->GetName(), $mod->Lang('submit_error',$mail->GetErrorInfo()));
								return array($res, $mod->Lang('upload_attach_error',
										array($thisAtt[0],$thisAtt[0] ,$thisType)));
							}
						}
						else if(strlen($thisAtt) > 0)
						{	// Fix for Bug 4307
							//Filepath can't be relative to CWD dir
							$filepath = $theFields[$i]->GetOption('file_destination');
							$filepath = cms_join_path($filepath, $thisAtt);

							if(function_exists('finfo_open'))
							{
								$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
								$thisType = finfo_file($finfo, $filepath);
								finfo_close($finfo);
							}
							else if(function_exists('mime_content_type'))
							{
								$thisType = mime_content_type($filepath);
							}
							else
							{
								$thisType = 'application/octet-stream';
							}

							$thisNames = split('[/:\\]',$filepath);
							$thisName = array_pop($thisNames);

							if(!$mail->AddAttachment($filepath, $thisName, "base64", $thisType))
							{
								// failed upload kills the send.
								audit(-1, $mod->GetName(), $mod->Lang('submit_error',$mail->GetErrorInfo()));
								return array($res, $mod->Lang('upload_attach_error',
									array($filepath,$filepath ,$thisType)));
							}
						}
					}
				}
     		}
    	}
		//process without cacheing
		$message = $mod->ProcessTemplateFromData($message);
		$subject = $mod->ProcessTemplateFromData($subject);
		$mail->SetSubject($subject);
		if($htmlemail)
		{
			$message2 = $mod->ProcessTemplateFromData($message2);
			$mail->SetAltBody(strip_tags(html_entity_decode($message)));
			$mail->SetBody($message2);
		}
		else
		{
			$mail->SetBody(html_entity_decode($message));
		}

//		$haveto = false;
		$defto = $this->GetOption('send_using','to');
		if(!is_array($destination_array))
		{
			$destination_array = array($destination_array);
		}

		foreach($destination_array as $thisDest)
		{
			if(strpos($thisDest,',') !== false)
			{
				$res = false;
				$sub_ads = explode(',',$thisDest);
				foreach($sub_ads as $this_ad)
				{
					$bare = trim($this_ad);
					if($bare)
					{
						$totype = substr($bare,0,4);
						switch ($totype)
						{
						 case '|cc|':
							$mail->AddCC(substr($bare,4));
							break;
						 case '|bc|':
							$mail->AddBCC(substr($bare,4));
							break;
						 default:
							switch ($defto)
							{
							 case 'cc':
								$mail->AddCC($bare);
								break;
							 case 'bc':
							 case 'bcc':
								$mail->AddBCC($bare);
								break;
							 default:
								$mail->AddAddress($bare);
//								$haveto = true;
								break;
							}
							break;
						}
						$res = true;
					}
				}
				if($res == false)
				{
					audit(-1, $mod->GetName(), $mod->Lang('error_address', $this_ad));
					$toReturn = array(false, $mod->Lang('error_address', $this_ad));
				}
			}
			else
			{
				$bare = trim($thisDest);
				if($bare)
				{
					$totype = substr($bare,0,4);
					switch ($totype)
					{
					 case '|cc|':
						$mail->AddCC(substr($bare,4));
						break;
					 case '|bc|':
						$mail->AddBCC(substr($bare,4));
						break;
					 default:
						switch ($defto)
						{
						 case 'cc':
							$mail->AddCC($bare);
							break;
						 case 'bc':
						 case 'bcc':
							$mail->AddBCC($bare);
							break;
						 default:
							$mail->AddAddress($bare);
//							$haveto = true;
							break;
						}
						break;
					}
					$res = true;
				}
				else
				{
					audit(-1, $mod->GetName(), $mod->Lang('error_address', $thisDest));
					$toReturn = array(false, $mod->Lang('error_address', $thisDest));
					$res = false;
				}
			}
		}

		if($res != false)
		{
//			if($haveto == false)
//			$res = $mail->AddAddress(''); adding '' or null generates error
			// send the message...
			$res = $mail->Send();
			if($res === false)
			{
				audit(-1, $mod->GetName(), $mod->Lang('submit_error',$mail->GetErrorInfo()));
				$toReturn = array(false, $mail->GetErrorInfo());
			}
			else
			{
				if($mod->GetPreference('enable_antispam'))
				{
					if(!empty($_SERVER['REMOTE_ADDR']))
					{
						$rec_id = $db->GenID(cms_db_prefix().'module_pwf_ip_log_seq');
						$query = 'INSERT INTO '.cms_db_prefix().
						'module_pwf_ip_log (sent_id, src_ip, sent_time) VALUES (?, ?, ?)';

						$dbresult = $db->Execute($query, array($rec_id, $_SERVER['REMOTE_ADDR'],
						   trim($db->DBTimeStamp(time()),"'")));
					}
				}
				$toReturn = array(true, '');
			}
		}

		$mail->reset();
		return $toReturn;
	}

	function PrePopulateAdminFormBase($formDescriptor, $totype = false)
	{
		$mod = $this->formdata->pwfmodule;
		$message = $this->GetOption('email_template','');

		if($this->GetFieldType() == 'DispositionEmailConfirmation')
		{
			$this->formdata->AddTemplateVariable('confirm_url',$mod->Lang('title_confirmation_url'));
		}
		/* main-tab items */
		$main = array(
				array($mod->Lang('title_email_subject'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_email_subject',
						$this->GetOption('email_subject',''),50),$mod->Lang('canuse_smarty')),

				array($mod->Lang('title_email_from_name'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_email_from_name',
						$this->GetOption('email_from_name',$mod->Lang('friendly_name')),40,128)),

				array($mod->Lang('title_email_from_address'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_email_from_address',
						$this->GetOption('email_from_address',''),50,128),
						$mod->Lang('email_from_addr_help',$_SERVER['SERVER_NAME']))
			  );
		//abandoned here: 'pwfp_opt_email_cc_address', 'pwfp_opt_use_bcc'
		//code elsewhere assumes this is last in $main[]
		if($totype)
			$main[] = array(
				$mod->Lang('title_send_using'),
				$mod->CreateInputRadioGroup($formDescriptor,'pwfp_opt_send_using',
					array($mod->Lang('to')=>'to',$mod->Lang('cc')=>'cc',$mod->Lang('bcc')=>'bc'),
					$this->getOption('send_using','to'),'','&nbsp;&nbsp;'),
					$mod->Lang('email_to_help'));

		$parm = array();
		$parm['opt_email_template']['html_button'] = true;
		$parm['opt_email_template']['text_button'] = true;
		$parm['opt_email_template']['is_email'] = true;
		list ($funcs, $buttons) = $this->formdata->AdminTemplateActions($formDescriptor,$parm);

		/* advanced-tab items */
		$adv = array(
				array($mod->Lang('title_html_email'),
					$mod->CreateInputHidden($formDescriptor,'pwfp_opt_html_email','0').
					$mod->CreateInputCheckbox($formDescriptor,'pwfp_opt_html_email','1',
						$this->GetOption('html_email','0'))),

				array($mod->Lang('title_email_encoding'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_email_encoding',
					$this->GetOption('email_encoding','utf-8'),15,128)),

				array($mod->Lang('title_email_template'),
					$mod->CreateTextArea(false, $formDescriptor,
					/*($this->GetOption('html_email','0')=='1'?$message:htmlspecialchars($message))*/
					$message,'pwfp_opt_email_template', 'pwf_tallarea', '','','',80,15,'','html').
					'<br /><br />'.$buttons[0].'&nbsp'.$buttons[1])
			  );
		/*show variables-help on advanced tab*/
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

	function validateEmailAddr($email)
	{
		$mod = $this->formdata->pwfmodule;
		$ret = true;
		$message = '';
		if(strpos($email,',') !== false)
		{
			$ta = explode(',',$email);
		}
		else
		{
			$ta = array($email);
		}
		foreach($ta as $to)
		{
			$to = trim($to);

			$totype = substr($to,0,4);
			if($totype == '|cc|' || $totype == '|bc|')
				$to = substr($to,4);

			if(!preg_match(($mod->GetPreference('relaxed_email_regex','0')==0?$mod->email_regex:$mod->email_regex_relaxed), $to))
			{
				$ret = false;
				$message .= $mod->Lang('not_valid_email',$to).'<br />';
			}
		}
		return array($ret, $message);
	}

}

?>
