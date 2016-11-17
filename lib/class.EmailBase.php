<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class EmailBase extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata,$params);
		$this->IsDisposition = TRUE;
		$this->IsEmailDisposition = TRUE;
	}

	public function TemplateStatus()
	{
		if ($this->GetProperty('email_template'))
			return '';
		return $this->formdata->formsmodule->Lang('email_template_not_set');
	}

	/**
	AdminPopulateCommonEmail:
	@id: id given to the PWForms module on execution
	@except: optional lang-key, or array of them, to be excluded from the setup, default FALSE
	@totype: optional bool, whether to include a to/cc/bcc selector dropdown, default=FALSE
	@visible: optional bool, whether to include some options irrelevant to non-displayed
	 disposition-fields, default=TRUE
	Returns: 3-member array of stuff for use ultimately in method.open_field.php
	 [0] = array of things for 'main' tab
	 [1] = (possibly empty) array of things for 'adv' tab
	 [2] = something?? for upstream 'extra' parameter
	*/
	public function AdminPopulateCommonEmail($id, $except=FALSE, $totype=FALSE, $visible=TRUE)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,$except,FALSE,$visible);

		$mod = $this->formdata->formsmodule;
		$message = $this->GetProperty('email_template');

		//additional main-tab items
		$main[] = array($mod->Lang('title_email_subject'),
						$mod->CreateInputText($id,'fp_email_subject',
							$this->GetProperty('email_subject'),50),$mod->Lang('canuse_smarty'));
		$main[] = array($mod->Lang('title_email_from_name'),
						$mod->CreateInputText($id,'fp_email_from_name',
							$this->GetProperty('email_from_name',$mod->Lang('friendly_name')),40,128));
		$main[] = array($mod->Lang('title_email_from_address'),
						$mod->CreateInputText($id,'fp_email_from_address',
							$this->GetProperty('email_from_address'),50,128),
						$mod->Lang('email_from_addr_help',$_SERVER['SERVER_NAME']));
		//abandoned here: 'fp_email_cc_address','fp_use_bcc'
		//code elsewhere assumes this is last in $main[]
		if ($totype)
			$main[] = array(
				$mod->Lang('title_send_using'),
				$mod->CreateInputRadioGroup($id,'fp_send_using',
					array($mod->Lang('to')=>'to',$mod->Lang('cc')=>'cc',$mod->Lang('bcc')=>'bc'),
					$this->GetProperty('send_using','to'),'','&nbsp;&nbsp;'),
					$mod->Lang('email_to_help'));

		//additional advanced-tab items
		$adv[] = array($mod->Lang('title_html_email'),
					$mod->CreateInputHidden($id,'fp_html_email',0).
					$mod->CreateInputCheckbox($id,'fp_html_email',1,
						$this->GetProperty('html_email',0)));
		$adv[] = array($mod->Lang('title_email_encoding'),
					$mod->CreateInputText($id,'fp_email_encoding',
						$this->GetProperty('email_encoding','utf-8'),15,128));
		//setup sample-template buttons and scripts
		$ctldata = array();
		$ctldata['fp_email_template']['html_button'] = TRUE;
		$ctldata['fp_email_template']['text_button'] = TRUE;
		$ctldata['fp_email_template']['is_email'] = TRUE;
		list($buttons,$jsfuncs) = Utils::TemplateActions($this->formdata,$id,$ctldata);
		$this->jsfuncs = array_merge($this->jsfuncs,$jsfuncs);

		$adv[] = array($mod->Lang('title_email_template'),
						$mod->CreateTextArea(FALSE,$id,
						//($this->GetProperty('html_email',0)?$message:htmlspecialchars($message))
						$message,'fp_email_template','pwf_tallarea','','','',50,15,'','html'),
						'<br /><br />'.$buttons[0].'&nbsp;'.$buttons[1]);
		//show variables-help on advanced tab
		return array($main,$adv,'varshelpadv');
	}

	public function PostAdminActionEmail(&$params)
	{
		if (!is_array($params['fp_destination_address']))
			$params['fp_destination_address'] = array($params['fp_destination_address']);

		foreach ($params['fp_destination_address'] as $i => $to) {
			if (isset($params['mailto_'.$i])) {
				$totype = $params['mailto_'.$i];
				switch ($totype) {
				 case 'cc';
					$params['fp_destination_address'][$i] = '|cc|'.$to;
					break;
				 case 'bc':
					$params['fp_destination_address'][$i] = '|bc|'.$to;
					break;
				}
//TODO ?? somewhere $this->SetPropIndexed('destination_address',[$i or other index],[adjusted]parameter) ??
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

	private function ConvertDomains($pref)
	{
	   if (!$pref)
		   return '""';
	   $v3 = array();
	   $v2 = explode(',',$pref);
	   foreach ($v2 as $one) {
		   $v3[] = '\''.trim($one).'\'';
	   }
	   return implode(',',$v3);
	}

	protected function SetEmailJS()
	{
		if (isset($this->formdata->jsincs['mailcheck'])) {
			return;
		}
		$mod = $this->formdata->formsmodule;
		$baseurl = $mod->GetModuleURLPath();
		$this->formdata->jsincs['mailcheck'] = <<<EOS
<script type="text/javascript" src="{$baseurl}/include/mailcheck.min.js"></script>
<script type="text/javascript" src="{$baseurl}/include/levenshtein.min.js"></script>
EOS;

		$pref = $mod->GetPreference('email_topdomains');
		$topdomains = $this->ConvertDomains($pref);
		if ($topdomains)
			$topdomains = '  topLevelDomains: ['.$topdomains.'],'.PHP_EOL;
		else
			$topdomains = '';
		$pref = $mod->GetPreference('email_domains');
		$domains = $this->ConvertDomains($pref);
		if ($domains)
			$domains = '  domains: ['.$domains.'],'.PHP_EOL;
		else
			$domains = '';
		$pref = $mod->GetPreference('email_subdomains');
		$l2domains = $this->ConvertDomains($pref);
		if ($l2domains)
			$l2domains = '  secondLevelDomains: ['.$l2domains.'],'.PHP_EOL;
		else
			$l2domains = '';
		$intro = $mod->Lang('suggest');
		$empty = $mod->Lang('missing_type',$mod->Lang('destination'));
		$this->formdata->jsloads['mailcheck'] = <<<EOS
 $('.emailaddr').blur(function() {
  $(this).mailcheck({
{$domains}{$l2domains}{$topdomains}
   distanceFunction: function(string1,string2) {
    var lv = Levenshtein;
    return lv.get(string1,string2);
   },
   suggested: function(element,suggestion) {
    if (confirm('{$intro} <strong><em>' + suggestion.full + '</em></strong>?')) {
     element.innerHTML = suggestion.full;
    } else {
     element.focus();
    }
   },
   empty: function(element) {
    alert('{$empty}');
    element.focus();
   }
  });
 });
EOS;
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
				$messages[] = $mod->Lang('err_email_address',$to);
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
	public function SendForm($destination_array, $subject, $tplvars=array())
	{
		$mod = $this->formdata->formsmodule;
		if (!$subject)
			return array(FALSE,$mod->Lang('missing_type',$mod->Lang('TODO')));
		if (!$destination_array)
			return array(FALSE,$mod->Lang('missing_type',$mod->Lang('destination')));

		if ($mod->before20) {
			$mail = \cms_utils::get_module('CMSMailer');
			if (!$mail)
				return array(FALSE,$mod->Lang('err_module','CMSMailer'));
			$mail->reset();
		} else {
			$mail = new \cms_mailer();
		}

		$defto = $this->GetProperty('send_using','to');
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
				if (!$res) {
					$mail->reset();
					return array(FALSE,$mod->Lang('err_address',$this_ad));
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
					return array(FALSE,$mod->Lang('err_address',$thisDest));
				}
			}
		}

		if ($this->SetFromName())
			$mail->SetFromName($this->GetProperty('email_from_name'));

		if ($this->SetFromAddress())
			$mail->SetFrom($this->GetProperty('email_from_address'));

		$rt = $this->GetProperty('email_reply_to_address');
		if ($rt && $this->SetReplyToAddress()) {
			if ($this->SetFromName()) {
				$rn = $this->GetProperty('email_reply_to_name');
				if (!$rn)
					$rn = $this->GetProperty('email_from_name');
			} else
				$rn = '';
			$mail->AddReplyTo($rt,$rn);
		}

		$mail->SetCharSet($this->GetProperty('email_encoding','utf-8'));

		$htmlemail = $this->GetProperty('html_email',0);

		Utils::SetupFormVars($this->formdata,$tplvars,$htmlemail);

		$subject = Utils::ProcessTemplateFromData($mod,$subject,$tplvars);
		$mail->SetSubject($subject);

		$message = $this->GetProperty('email_template');
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
 			  !$one->GetProperty('suppress_attachment') && !$one->GetProperty('sendto_uploads'))
			{
				// file(s) to be attached to email
				$ud = Utils::GetUploadsPath($mod);
				if (!$ud) {
					$mail->reset();
					return array(FALSE,$mod->Lang('err_uploads_dir'));
				}

				$thisAtt = $one->DisplayableValue(FALSE);

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
							return array(FALSE,$mod->Lang('err_attach',
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
						return array(FALSE,$mod->Lang('err_attach',
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
