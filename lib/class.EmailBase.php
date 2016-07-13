<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class EmailBase extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->IsEmailDisposition = TRUE;
	}

	public function TemplateStatus()
	{
		if ($this->GetOption('email_template'))
			return '';
		return $this->formdata->formsmodule->Lang('email_template_not_set');
	}

	public function AdminPopulateCommonEmail($id,$totype=FALSE,$visible=TRUE)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,$visible);

		$mod = $this->formdata->formsmodule;
		$message = $this->GetOption('email_template');

		//additional main-tab items
		$main[] = array($mod->Lang('title_email_subject'),
						$mod->CreateInputText($id,'opt_email_subject',
							$this->GetOption('email_subject'),50),$mod->Lang('canuse_smarty'));
		$main[] = array($mod->Lang('title_email_from_name'),
						$mod->CreateInputText($id,'opt_email_from_name',
							$this->GetOption('email_from_name',$mod->Lang('friendly_name')),40,128));
		$main[] = array($mod->Lang('title_email_from_address'),
						$mod->CreateInputText($id,'opt_email_from_address',
							$this->GetOption('email_from_address'),50,128),
						$mod->Lang('email_from_addr_help',$_SERVER['SERVER_NAME']));
		//abandoned here: 'opt_email_cc_address','opt_use_bcc'
		//code elsewhere assumes this is last in $main[]
		if ($totype)
			$main[] = array(
				$mod->Lang('title_send_using'),
				$mod->CreateInputRadioGroup($id,'opt_send_using',
					array($mod->Lang('to')=>'to',$mod->Lang('cc')=>'cc',$mod->Lang('bcc')=>'bc'),
					$this->getOption('send_using','to'),'','&nbsp;&nbsp;'),
					$mod->Lang('email_to_help'));

		//additional advanced-tab items
		$adv[] = array($mod->Lang('title_html_email'),
					$mod->CreateInputHidden($id,'opt_html_email',0).
					$mod->CreateInputCheckbox($id,'opt_html_email',1,
						$this->GetOption('html_email',0)));
		$adv[] = array($mod->Lang('title_email_encoding'),
					$mod->CreateInputText($id,'opt_email_encoding',
						$this->GetOption('email_encoding','utf-8'),15,128));
		//setup sample-template buttons and scripts
		$ctldata = array();
		$ctldata['opt_email_template']['html_button'] = TRUE;
		$ctldata['opt_email_template']['text_button'] = TRUE;
		$ctldata['opt_email_template']['is_email'] = TRUE;
		list($buttons,$scripts) = Utils::TemplateActions($this->formdata,$id,$ctldata);
		$adv[] = array($mod->Lang('title_email_template'),
						$mod->CreateTextArea(FALSE,$id,
						//($this->GetOption('html_email',0)?$message:htmlspecialchars($message))
						$message,'opt_email_template','pwf_tallarea','','','',50,15,'','html'),
						'<br /><br />'.$buttons[0].'&nbsp'.$buttons[1]);
		//show variables-help on advanced tab
		return array($main,$adv,$scripts,'varshelpadv');
	}

	public function PostAdminActionEmail(&$params)
	{
		if (!is_array($params['opt_destination_address']))
			$params['opt_destination_address'] = array($params['opt_destination_address']);

		foreach ($params['opt_destination_address'] as $i => $to) {
$mod->Crash;
			if (isset($params['mailto_'.$i])) {
				$totype = $params['mailto_'.$i];
				switch ($totype) {
				 case 'cc';
					$params['opt_destination_address'][$i] = '|cc|'.$to;
					break;
				 case 'bc':
					$params['opt_destination_address'][$i] = '|bc|'.$to;
					break;
				}
//TODO ?? somewhere $this->SetOptionElement('destination_address',[$i or other index],[adjusted]parameter) ??
				unset($params[$totype]);
			}
		}
	}

	// override as necessary, return TRUE to include sender-address header in email 
	public function SetFromAddress()
	{
		return TRUE;
	}

	// override as necessary, return TRUE to include sender header in email 
	public function SetFromName()
	{
		return TRUE;
	}

	// override as necessary, return TRUE to include reply-to header in email 
	public function SetReplyToName()
	{
		return TRUE;
	}

	// override as necessary, return TRUE to include reply-to header in email 
	public function SetReplyToAddress()
	{
		return TRUE;
	}

	public function validateEmailAddr($email)
	{
		$mod = $this->formdata->formsmodule;
		$ret = TRUE;
		$messages = array();
		if (strpos($email,',') !== FALSE)
			$ta = explode(',',$email);
		else
			$ta = array($email);
		foreach ($ta as $to) {
			$to = trim($to);
			$totype = substr($to,0,4);
			if ($totype == '|cc|' || $totype == '|bc|')
				$to = substr($to,4);

			if (!preg_match($mod->email_regex,$to)) {
				$ret = FALSE;
				$messages[] = $mod->Lang('error_email_address',$to);
			}
			//TODO c.f. mailcheck.js for frontend addresses
		}
		$msg = ($ret) ? '':implode('<br />',$messages);
		return array($ret,$msg);
	}

	/*
	send email(s)
	$subject is processed via smarty
	message body is generated from field-option 'email_template' (or a default template)
	*/
	public function SendForm($destination_array,$subject,$tplvars=array())
	{
		$mod = $this->formdata->formsmodule;
		if ($destination_array == FALSE || $subject == FALSE)
			return array(FALSE,$mod->Lang('missing_type',$mod->Lang('destination'))); //TODO if subject

		$mail = $mod->GetModuleInstance('CMSMailer');
		if (!$mail)
			return array(FALSE,$mod->Lang('missing_cms_mailer'));

		$mail->reset();

		$defto = $this->GetOption('send_using','to');
		if (!is_array($destination_array))
			$destination_array = array($destination_array);
		foreach ($destination_array as $thisDest) {
			if (strpos($thisDest,',') !== FALSE) {
				$res = FALSE;
				$sub_ads = explode(',',$thisDest);
				foreach ($sub_ads as $this_ad) {
					$bare = trim($this_ad);
					if ($bare) {
						$totype = substr($bare,0,4);
						switch ($totype) {
						 case '|cc|':
							$mail->AddCC(substr($bare,4));
							break;
						 case '|bc|':
							$mail->AddBCC(substr($bare,4));
							break;
						 default:
							switch ($defto) {
							 case 'cc':
								$mail->AddCC($bare);
								break;
							 case 'bc':
							 case 'bcc':
								$mail->AddBCC($bare);
								break;
							 default:
								$mail->AddAddress($bare);
//								$haveto = TRUE;
								break;
							}
							break;
						}
						$res = TRUE;
					}
				}
				if ($res == FALSE) {
					$mail->reset();
					return array(FALSE,$mod->Lang('error_address',$this_ad));
				}
			} else {
				$bare = trim($thisDest);
				if ($bare) {
					$totype = substr($bare,0,4);
					switch ($totype) {
					 case '|cc|':
						$mail->AddCC(substr($bare,4));
						break;
					 case '|bc|':
						$mail->AddBCC(substr($bare,4));
						break;
					 default:
						switch ($defto) {
						 case 'cc':
							$mail->AddCC($bare);
							break;
						 case 'bc':
						 case 'bcc':
							$mail->AddBCC($bare);
							break;
						 default:
							$mail->AddAddress($bare);
//							$haveto = TRUE;
							break;
						}
						break;
					}
				} else {
					$mail->reset();
					return array(FALSE,$mod->Lang('error_address',$thisDest));
				}
			}
		}

		if ($this->SetFromName())
			$mail->SetFromName($this->GetOption('email_from_name'));

		if ($this->SetFromAddress())
			$mail->SetFrom($this->GetOption('email_from_address'));

		$rt = $this->GetOption('email_reply_to_address');
		if ($rt && $this->SetReplyToAddress()) {
			if ($this->SetFromName()) {
				$rn = $this->GetOption('email_reply_to_name');
				if (!$rn)
					$rn = $this->GetOption('email_from_name');
			} else
				$rn = '';
			$mail->AddReplyTo($rt,$rn);
		}

		$mail->SetCharSet($this->GetOption('email_encoding','utf-8'));

		$htmlemail = $this->GetOption('html_email',0);

		Utils::SetupFormVars($this->formdata,$htmlemail,$tplvars);

		$subject = Utils::ProcessTemplateFromData($mod,$subject,$tplvars);
		$mail->SetSubject($subject);

		$message = $this->GetOption('email_template');
		if ($message) {
			if ($htmlemail)
				$message2 = $message;
		} else {
			$message = Utils::CreateDefaultTemplate($this->formdata,FALSE);
			if ($htmlemail)
				$message2 = Utils::CreateDefaultTemplate($this->formdata,TRUE);
		}
		$message = Utils::ProcessTemplateFromData($mod,$message,$tplvars);

		if ($htmlemail) {
			$mail->IsHTML(TRUE);
			$message2 = Utils::ProcessTemplateFromData($mod,$message2,$tplvars);
			$mail->SetAltBody(strip_tags(html_entity_decode($message)));
			$mail->SetBody($message2);
		} else {
			$mail->SetBody(html_entity_decode($message));
		}

		foreach ($this->formdata->Fields as &$one) {
	 		if ($one->Type == 'FileUpload' &&
 			  !$one->GetOption('suppress_attachment') && !$one->GetOption('sendto_uploads'))
			{
				// file(s) to be attached to email
				$ud = Utils::GetUploadsPath();
				if (!$ud) {
					$mail->reset();
					return array(FALSE,$mod->Lang('error_uploads_dir'));
				}

				$thisAtt = $one->GetDisplayableValue(FALSE);

				if (is_array($thisAtt)) {
					foreach ($thisAtt as $onefile) {
						$filepath = $ud.DIRECTORY_SEPARATORY.$onefile;
						if (function_exists('finfo_open')) {
							$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
							$thisType = finfo_file($finfo,$filepath);
							finfo_close($finfo);
						} else if (function_exists('mime_content_type'))
							$thisType = mime_content_type($filepath);
						else
							$thisType = 'application/octet-stream';

						$thisNames = split('[/:\\]',$filepath);
						$thisName = array_pop($thisNames);
						if (!$mail->AddAttachment($filepath,$thisName,'base64',$thisType)) {
							$mail->reset();
							return array(FALSE,$mod->Lang('error_attach',
									array($filepath,$filepath,$onefile)));
						}
					}
				} elseif ($thisAtt) {
					$filepath = $ud.DIRECTORY_SEPARATORY.$thisAtt;
					if (function_exists('finfo_open')) {
						$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
						$thisType = finfo_file($finfo,$filepath);
						finfo_close($finfo);
					} else if (function_exists('mime_content_type'))
						$thisType = mime_content_type($filepath);
					else
						$thisType = 'application/octet-stream';

					$thisNames = split('[/:\\]',$filepath);
					$thisName = array_pop($thisNames);

					if (!$mail->AddAttachment($filepath,$thisName,'base64',$thisType)) {
						$mail->reset();
						return array(FALSE,$mod->Lang('error_attach',
							array($filepath,$filepath,$thisType)));
					}
				}
			}
		}
		unset($one);

		// send the message
		if ($mail->Send() !== FALSE)
			$toReturn = array(TRUE,'');
		else
			$toReturn = array(FALSE,$mail->GetErrorInfo());

		$mail->reset();
		return $toReturn;
	}
}
