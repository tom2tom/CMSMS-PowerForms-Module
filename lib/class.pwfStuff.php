<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDummy
{
}

// TODO this is stuff exported from the module class

	var $email_regex; //used beyond email dispatchers
	var $email_regex_relaxed;

	var $field_types;
	var $disp_field_types;
	var $std_field_types;
	var $all_validation_types;

	function initialize()
	{
		$this->email_regex = "/^([\w\d\.\-\_])+\@([\w\d\.\-\_]+)\.(\w+)$/i";
		$this->email_regex_relaxed = "/^([\w\d\.\-\_])+\@([\w\d\.\-\_])+$/i";
	
		$dir = opendir(cms_join_path(dirname(__FILE__),'lib'));
		$this->field_types = array();
		$feu = $this->GetModuleInstance('FrontEndUsers');
		$mail = $this->GetModuleInstance('CMSMailer');
		if($mail != false)
		{
//			define('MailerReqVersion', '1.73'); //minumum acceptable version of CMSMailer module
			if(version_compare($mail->GetVersion(),MailerReqVersion) < 0) $mail = false;
		}

		while($filespec = readdir($dir))
		{
		  if(!endswith($filespec,'.php')) continue;
			if(strpos($filespec,'Field') === false && strpos($filespec,'Disposition') === false)
			{
				continue;
			}
			if($mail == false && strpos($filespec,'Disposition') !== false && strpos($filespec,'Email') != false)
			{
				continue;
			}
			if($feu == false && strpos($filespec,'FEU') !== false)
			{
				continue;
			}
			$shortname = substr($filespec,0,strpos($filespec,'.'));
			if(substr($shortname,-4) == 'Base')
			{
				continue;
			}
			$this->field_types[$this->Lang('field_type_'.$shortname)] = $shortname;
		}

		foreach($this->field_types as $tName=>$tType)
		{
			if(substr($tType,0,11) == 'Disposition')
			{
				$this->disp_field_types[$tName]=$tType;
			}
		}
		$this->all_validation_types = array();
		ksort($this->field_types);
		$this->std_field_types = array(
			$this->Lang('field_type_TextField')=>'TextField',
			$this->Lang('field_type_TextAreaField')=>'TextAreaField',
			$this->Lang('field_type_CheckboxField')=>'CheckboxField',
			$this->Lang('field_type_CheckboxGroupField')=>'CheckboxGroupField',
			$this->Lang('field_type_PulldownField')=>'PulldownField',
			$this->Lang('field_type_RadioGroupField')=>'RadioGroupField',
			$this->Lang('field_type_DispositionEmail')=>'DispositionEmail',
			$this->Lang('field_type_DispositionFile')=>'DispositionFile',
			$this->Lang('field_type_PageBreakField')=>'PageBreakField',
			$this->Lang('field_type_StaticTextField')=>'StaticTextField');
		ksort($this->std_field_types);
	}

	function GetFormIDFromAlias($form_alias)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwf_form WHERE alias = ?';
		$fid = $db->GetOne($sql, array($form_alias));
		if($fid) return (int)$fid;
		return -1;
	}

	function GetFormNameFromID($form_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT name FROM '.cms_db_prefix().'module_pwf_form WHERE form_id = ?';
		$name = $db->GetOne($sql, array($form_id));
		if($name) return $name;
		return '';
	}

	function GetResponse($form_id,$response_id,$field_list=array(),$dateFmt='d F y')
	{
		$names = array();
		$values = array();
		$db = $this->dbHandle;
		$obfield = $this->GetFormBrowserField($form_id);
		if($obfield == false)
		{
			// error handling goes here
			echo($this->Lang('error_no_browser_field'));
		}

		$rs = $db->Execute('SELECT * FROM '.cms_db_prefix().
			'module_pwf_browse WHERE browser_id=?', array($response_id));

		$oneset = new stdClass();
		if($rs)
		{
			$row = $rs->FetchRow();
			$oneset->id = $row['browser_id'];
			$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
			$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
			$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
			$oneset->user_approved_date = (empty($row['user_approved'])?'':$db->UnixTimeStamp($row['user_approved']));
			$oneset->admin_approved_date = (empty($row['admin_approved'])?'':$db->UnixTimeStamp($row['admin_approved']));
			$oneset->submitted_date = $db->UnixTimeStamp($row['submitted']);
			$oneset->xml = $row['response'];
			$oneset->fields = array();
			$oneset->names = array();
			$oneset->fieldsbyalias = array();
			$rs->Close();
		}

		$populate_names = true;
		$this->HandleResponseFromXML($obfield, $oneset);
		list($fnames, $aliases, $vals) = $this->ParseResponseXML($oneset->xml);

		foreach($fnames as $id=>$name)
		{
			if(isset($field_list[$id]) && $field_list[$id] > -1)
			{
				$oneset->values[$field_list[$id]]=$vals[$id];
				$oneset->names[$field_list[$id]]=$fnames[$id];
			}
			if(isset($aliases[$id]))
			{
				$oneset->fieldsbyalias[$aliases[$id]] = $vals[$id];
			}
		}
		return $oneset;
	}

	function ParseResponseXML($xmlstr,$human_readable_values = true)
	{
		$names = array();
		$aliases = array();
		$vals = array();
		$xml = new SimpleXMLElement($xmlstr);
		foreach($xml->field as $xmlfield)
		{
			if($human_readable_values)
			{
				if(!empty($xmlfield['display_in_submission']))
				{
					$id = (int)$xmlfield['id'];
					$names[$id] = ((string)$xmlfield->field_name);
					$vals[$id] = ((string)$xmlfield->human_readable_value);
					if(isset($xmlfield->options))
					{
						foreach($xmlfield->options->option as $to)
						{
							if($to['name'] == 'field_alias')
							{
								$aliases[$id]=((string)$to);
							}
						}
					}
				}
			}
			else
			{
				$id = (int)$xmlfield['id'];
				$arrTypes = $xmlfield->xpath('options/value');
				if(count($arrTypes) > 1)
				{
					$vals[$id] = array();
					foreach($arrTypes as $tv)
					{
						$vals[$id][] = (string)$tv;
					}
				}
				else
				{
					$vals[$id] = (string)$xmlfield->options->value;
				}
			}
		}
		return array($names, $aliases, $vals);
	}

	function ParseResponseXMLType($xmlstr)
	{
		$types = array();
		$xml = new SimpleXMLElement($xmlstr);
		foreach($xml->field as $xmlfield)
		{
			$id = (int)$xmlfield['id'];
			$types['pwfp__'.$id] = (string)$xmlfield['type'];
		}
		return $types;
	}

	function GetFormBrowserField($form_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_field WHERE form_id=? and type=?';
		$rs = $db->Execute($sql, array($form_id,'DispositionFormBrowser'));
		if(!$rs)
			return false;

		if($rs->RecordCount() == 0)
		{
			$rs->Close();
			return false;
		}

		$thisRes = $rs->GetArray();
		$className = pwfUtils::MakeClassName($thisRes[0]['type']);
		$rs->Close();
		// create the field object
		$noparams = array();
TODO 		$funcs = new pwfFieldOperations($this,$noparams,false);
		$obfield = $funcs->NewField($this,$thisRes[0]);
		return $obfield;
	}

	function HandleResponseFromXML(&$obfield,&$responseObj)
	{
		$crypt = $obfield->GetOption('crypt','0');
		if($crypt == '1')
		{
			$cryptlib = $obfield->GetOption('crypt_lib');
			$keyfile = $obfield->GetOption('keyfile');
			if($cryptlib == 'openssl')
			{
				$openssl = $this->GetModuleInstance('OpenSSL');
				$pkey = $obfield->GetOption('private_key');
				$openssl->Reset();
				$openssl->load_private_keyfile($pkey,$keyfile);
			}
			else
			{
				if(file_exists($keyfile))
		    	{
			        $keyfile = file_get_contents($keyfile);
		 		}
			}
		}

		if($crypt == '1')
		{
			if($cryptlib == 'openssl')
			{
				$responseObj->xml = $openssl->decrypt_from_payload($responseObj->xml);
				if($responseObj->xml == false)
				{
					debug_display($openssl->openssl_errors());
				}
			}
			else
			{
				$responseObj->xml = self::Decrypt($responseObj->xml,$keyfile);
			}
		}
	}

	function GetSortedResponses($form_id,$start_point,$number=100,$admin_approved=false,$user_approved=false,$field_list=array(),$dateFmt='d F y',&$params)
	{
		$db = $this->dbHandle;
		$names = array();
		$values = array();
		$sql = 'FROM '.cms_db_prefix().'module_pwf_browse WHERE form_id=?';
		$sqlparms = array($form_id);
		if($user_approved)
		{
			$sql .= ' AND user_approved IS NOT NULL';
		}
		if($admin_approved)
		{
			$sql .= ' AND admin_approved IS NOT NULL';
		}
		if(!empty($params['pwfp_response_search']) && (is_array($params['pwfp_response_search'])))
		{
			$sql .= ' AND browser_id IN ('. implode(',', $params['pwfp_response_search']) .')';
		}
		if(isset($params['filter_field']) && substr($params['filter_field'],0,5) =='index')
		{
			$idxfld = intval(substr($params['filter_field'],5));
			$sql .= ' AND index_key_'.$idxfld.'=?';
			$sqlparms[] = $params['filter_value'];
		}
		if(!isset($params['pwfp_sort_field']) || $params['pwfp_sort_field']=='submitdate' || empty($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'a')
			{
				$sql .= ' ORDER BY submitted';
			}
			else
			{
				$sql .= ' ORDER BY submitted DESC';
			}
		}
		else if(isset($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'].' DESC';
			}
			else
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'];
			}
		}

		$records = 0;
		$rs = $db->Execute('SELECT COUNT(*) AS num '.$sql,$sqlparms);
		if($rs)
		{
			$row = $rs->FetchRow())
			$records = $row['num'];
			$rs->Close();
		}

		if($number > -1)
		{
			$rs = $db->SelectLimit('SELECT * '.$sql, $number, $start_point, $sqlparms);
		}
		else
		{
			$rs = $db->Execute('SELECT * '.$sql, $sqlparms);
		}

		if($rs)
		{
			while($row = $rs->FetchRow())
			{
				$oneset = new stdClass();
				$oneset->id = $row['browser_id'];
				$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
				$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
				$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
				$oneset->user_approved_date = (empty($row['user_approved'])?'':$db->UnixTimeStamp($row['user_approved']));
				$oneset->admin_approved_date = (empty($row['admin_approved'])?'':$db->UnixTimeStamp($row['admin_approved']));
				$oneset->submitted_date = $db->UnixTimeStamp($row['submitted']);

				$oneset->xml = $row['response'];
				$oneset->fields = array();
				$oneset->fieldsbyalias = array();
				$values[] = $oneset;
			}
			$rs->Close();
		}

		$obfield = $this->GetFormBrowserField($form_id);
		if($obfield == false)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$populate_names = true;
		$mapfields = (count($field_list) > 0);
		for ($i=0;$i<count($values);$i++)
		{
			$this->HandleResponseFromXML($obfield, $values[$i]);
			list($fnames, $aliases, $vals) = $this->ParseResponseXML($values[$i]->xml);
			foreach($fnames as $id=>$name)
			{
				if($mapfields)
				{
					if(isset($field_list[$id]) && $field_list[$id] > -1)
					{
						if($populate_names)
						{
							$names[$field_list[$id]] = $name;
						}
						$values[$i]->fields[$field_list[$id]]=$vals[$id];
					}
					if(isset($aliases[$id]))
					{
						$values[$i]->fieldsbyalias[$aliases[$id]] = $vals[$id];
					}
				}
				else
				{
					if($populate_names)
					{
						$names[$id] = $name;
					}
					$values[$i]->fields[$id]=$vals[$id];
					if(isset($aliases[$id]))
					{
						$values[$i]->fieldsbyalias[$aliases[$id]] = $vals[$id];
					}
				}
			}
			$populate_names = false;
		}
		return array($records, $names, $values);
	}

	// write records into a flat file
	function WriteSortedResponsesToFile($form_id,$filespec,$striptags=true,$dateFmt='d F y',&$params)
	{
		$db = $this->dbHandle;
		$names = array();
		$values = array();
		$sql = 'FROM '.cms_db_prefix().'module_pwf_browse WHERE form_id=?';

		if(!isset($params['pwfp_sort_field']) || $params['pwfp_sort_field']=='submitdate' || empty($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY submitted DESC';
			}
			else
			{
				$sql .= ' ORDER BY submitted';
			}
		}
		else if(isset($params['pwfp_sort_field']))
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'].' DESC';
			}
			else
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_sort_field'];
			}
		}

		$obfield = $this->GetFormBrowserField($form_id);
		if($obfield == false)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$fh = fopen($filespec, 'w+');
		if($fh === false)
		{
			return false;
		}

		$populate_names = true;
		$rs = $db->Execute('SELECT * '.$sql, array($form_id));
		if($rs)
		{
			while ($row = $rs->FetchRow())
			{
				$oneset = new stdClass();
				$oneset->id = $row['browser_id'];
				$oneset->user_approved = (empty($row['user_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['user_approved'])));
				$oneset->admin_approved = (empty($row['admin_approved'])?'':date($dateFmt,$db->UnixTimeStamp($row['admin_approved'])));
				$oneset->submitted = date($dateFmt,$db->UnixTimeStamp($row['submitted']));
				$oneset->xml = $row['response'];
				$this->HandleResponseFromXML($obfield, $oneset);
				list($fnames, $aliases, $vals) = $this->ParseResponseXML($oneset->xml);
				if($populate_names)
				{
					if($striptags)
			     	{
						foreach($fnames as $id=>$name)
						{
				        	$fnames[$i] = strip_tags($fnames[$i]);
			        	}
			     	}
					fputs ($fh, $this->Lang('title_submit_date')."\t".
						$this->Lang('title_approval_date')."\t".
						$this->Lang('title_user_approved')."\t".
						implode("\t",$fnames)."\n");
					$populate_names = false;
				}
				fputs ($fh,$oneset->submitted . "\t");
				fputs ($fh,$oneset->admin_approved . "\t");
				fputs ($fh,$oneset->user_approved . "\t");
				foreach($vals as $tv)
				{
					if($striptags)
			        {
						$tv = strip_tags($tv);
			        }
					fputs ($fh,preg_replace('/[\n\t\r]/',' ',$tv));
					fputs ($fh,"\t");
				}
				fputs($fh,"\n");
			}
			$rs->Close();
		}
		fclose($fh);
		return true;
	}

	function GetSortableFields($form_id)
	{
		$parm = array('form_id'=>$form_id);
TODO		$funcs = new pwfDummy($this, $parm, true);
		$obfield = $funcs->GetFormBrowserField();
		if($obfield != false)
		{
			return $obfield->getSortFieldList();
		}
		// error handling goes here
		return array();
	}

	function GetFEUIDFromResponseID($response_id)
	{
		$db = $this->dbHandle;
		$sql = 'SELECT feuid FROM '.cms_db_prefix().'module_pwf_browse WHERE browser_id=?';
		if($result = $db->GetRow($sql, array($response_id)))
		{
			return $result['feuid'];
		}
		return -1;
	}

	function GetResponseIDFromFEUID($feu_id,$form_id=-1)
	{
		$db = $this->dbHandle;

		$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_pwf_browse WHERE feuid=?';
		if($form_id != -1)
		{
			$sql .= ' AND form_id = '.$form_id.' ORDER BY submitted DESC';
		}

		if($result = $db->GetRow($sql, array($feu_id)))
		{
			return $result['browser_id'];
		}
		return false;
 	}

	function field_sorter_asc($a,$b)
	{
		return strcasecmp($a->fields[$a->sf], $b->fields[$b->sf]);
	}

	function field_sorter_desc($a,$b)
	{
		return strcasecmp($b->fields[$b->sf], $a->fields[$a->sf]);
	}

	// get array of the response objects for a form
	function ListResponses($form_id,$sort_order='submitted')
	{
		$ret = array();
		$db = $this->dbHandle;
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_resp WHERE form_id=? ORDER BY ?';
		$rs = $db->Execute($query, array($form_id,$sort_order));
		if($rs)
		{
			while ($row = $rs->FetchRow())
			{
				$oneset = new stdClass();
				$oneset->id = $row['resp_id'];
				$oneset->user_approved = $db->UnixTimeStamp($row['user_approved']);
				$oneset->admin_approved = $db->UnixTimeStamp($row['admin_approved']);
				$oneset->submitted = $db->UnixTimeStamp($row['submitted']);
				$ret[] = $oneset;
			}
			$rs->Close();
		}
		return $ret;
	}

/*	function def(&$var)
	{
		if(!isset($var))
		{
			return false;
		}
		else if(is_null($var))
		{
			return false;
		}
		else if(!is_array($var) && empty($var))
		{
			return false;
		}
		else if(is_array($var) && count($var) == 0)
		{
			return false;
		}
		return true;
	}
*/
	function GetFileLock()
	{
		$db = $this->dbHandle;
		$pref = cms_db_prefix();
		$sql = 'INSERT INTO '.$pref.'module_pwf_flock (flock_id, flock) VALUES (1,'.$db->sysTimeStamp.')';
		if($db->Execute($sql))
		{
			return true;
		}
		$sql = 'SELECT flock_id FROM '.cms_db_prefix().
				"module_pwf_flock WHERE flock + interval 15 second < ".$db->sysTimeStamp;
		$rs = $db->Execute($sql);
		if($rs)
		{
			if($rs->RecordCount() > 0)
			{
				$this->ClearFileLock();
			}
			$rs->Close();
		}
		return false;
	}

	function ClearFileLock()
	{
		$db = $this->dbHandle;
		$sql = 'DELETE FROM '.cms_db_prefix().'module_pwf_flock';
		$db->Execute($sql);
	}

	function ReturnFileLock()
	{
		$this->ClearFileLock();
	}

	function CreatePageDropdown($id,$name,$current='',$addtext='',$markdefault=true)
	{
		// we get here (hopefully) when the template is changed in the dropdown.
		$db = $this->dbHandle;
		$defaultid = '';
		if($markdefault)
		{
			$contentops = cmsms()->GetContentOperations();
			$defaultid = $contentops->GetDefaultPageID();
		}

		// get a list of the pages used by this template
		$mypages = array();

		$sql = 'SELECT content_id,content_name FROM '.cms_db_prefix().
			'content WHERE type = ? AND active = 1';
		$rs = $db->Execute($sql, array('content'));
		if($rs)
		{
			while($row = $rs->FetchRow())
			{
				if($defaultid != '' && $row['content_id'] == $defaultid)
				{
					// use a star instead of a word here so I don't have to
					// worry about translation stuff
					$mypages[$row['content_name'].' (*)'] = $row['content_id'];
				}
				else
				{
					$mypages[$row['content_name']] = $row['content_id'];
				}
			}
			$rs->Close();
		}
		return $this->CreateInputDropdown($id,'pwfp_'.$name,$mypages,-1,$current,$addtext);
	}

	function crypt($string,$dispositionField)
	{
		if($dispositionField->GetOption('crypt_lib') == 'openssl')
		{
			$openssl = $this->GetModuleInstance('OpenSSL');
			if($openssl === FALSE)
			{
				return array(false,$this->Lang('title_install_openssl'));
			}
			$openssl->Reset();
			if(!$openssl->load_certificate($dispositionField->GetOption('crypt_cert')))
			{
				return array(false,$openssl->openssl_errors());
			}
			$enc = $openssl->encrypt_to_payload($string);
		}
		else
		{
			$kf = $dispositionField->GetOption('keyfile');
			if(file_exists($kf))
			{
				$key = file_get_contents($kf);
			}
			else
			{
				$key = $kf;
			}
			$enc = self::Encrypt($string,$key);
		}
		return array(true,$enc);
	}

	function getHashedSortFieldVal($val)
	{
		if(strlen($val) > 4)
		{
			$val = substr($val,0,4). md5(substr($val,4));
		}
		return $val;
	}

	function Encrypt($string,$key)
	{
		$key = substr(md5($key),0,24);
		$td = mcrypt_module_open ('tripledes', '', 'ecb', '');
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
		mcrypt_generic_init ($td, $key, $iv);
		$enc = base64_encode(mcrypt_generic ($td, $string));
		mcrypt_generic_deinit ($td);
		mcrypt_module_close ($td);
		return $enc;
	}

	function Decrypt($crypt,$key)
	{
		$crypt = base64_decode($crypt);
		$td = mcrypt_module_open ('tripledes', '', 'ecb', '');
		$key = substr(md5($key),0,24);
		$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
		mcrypt_generic_init ($td, $key, $iv);
		$plain = mdecrypt_generic ($td, $crypt);
		mcrypt_generic_deinit ($td);
		mcrypt_module_close ($td);
		return $plain;
	}

	function CustomCreateInputText($id,$name,$value='',$size='10',$maxlength='255',$addttext='',$type='text')
	{
		$value = cms_htmlentities($value);
		$id = cms_htmlentities($id);
		$name = cms_htmlentities($name);
		$size = cms_htmlentities($size);
		$maxlength = cms_htmlentities($maxlength);

		$value = str_replace('"', '&quot;', $value);

		$text = '<input type="'.$type.'" name="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
		if($addttext != '')
		{
			$text .= ' ' . $addttext;
		}
		$text .= " />\n";
		return $text;
	}

	function CustomCreateInputSubmit($id,$name,$value='',$addttext='',$image='',$confirmtext='')
	{
		$id = cms_htmlentities($id);
		$name = cms_htmlentities($name);
		$image = cms_htmlentities($image);
		$text = '<input name="'.$id.$name.'" value="'.$value.'" type=';
		if($image != '')
		{
			$text .= '"image"';
			$config = cmsms()->GetConfig();
			$img = $config['root_url'] . '/' . $image;
			$text .= ' src="'.$img.'"';
		}
		else
		{
			$text .= '"submit"';
		}
		if($confirmtext != '')
		{
			$text .= ' onclick="return confirm(\''.$confirmtext.'\');"';
		}
		if($addttext != '')
		{
			$text .= ' '.$addttext;
		}
		$text .= ' />';
		return $text . "\n";
	}

	function DeleteFromSearchIndex(&$params)
	{
		// find browsers keyed to this
		$funcs = new pwfFormOperations();
		$browsers = $funcs->GetBrowsers($this,$params['form_id']);
		if(count($browsers) > 0)
		{
			$module = $this->GetModuleInstance('Search');
			if($module != FALSE)
			{
				foreach($browsers as $one)
					$module->DeleteWords('FormBrowser', $params['response_id'], 'sub_'.$one);
			}
		}
	}


// TODO this is stuff left over from the old fbForm class

/*
	var $pwfmodule = NULL;
	var $module_params = -1; UNUSED
	var $Id = -1;
	var $Name = '';
	var $Alias = '';
	var $loaded = 'not';
	var $FormTotalPages = 0;
	var $Page;
	var $Attrs;
	var $Fields;
	var $FormState;
	var $sampleTemplateCode;
	var $templateVariables;

	function __construct(&$pwfmodule, &$params, $loadDeep=false, $loadResp=false)
	{
		$this->pwfmodule = $pwfmodule;
		$this->module_params = $params;
		$this->Fields = array();
		$this->Attrs = array();
		$this->FormState = 'new';

		if(isset($params['form_id']))
		{
			$this->Id = $params['form_id'];
		}

		if(isset($params['pwfp_form_alias']))
		{
			$this->Alias = $params['pwfp_form_alias'];
		}

		if(isset($params['pwfp_form_name']))
		{
			$this->Name = $params['pwfp_form_name'];
		}

		$fieldExpandOp = false;
		foreach($params as $pKey=>$pVal)
		{
			if(substr($pKey,0,9) == 'pwfp_FeX_' || substr($pKey,0,9) == 'pwfp_FeD_')
			{
				// expanding or shrinking a field
				$fieldExpandOp = true;
			}
		}

		if($fieldExpandOp)
		{
			$params['pwfp_done'] = 0;
			if(isset($params['pwfp_continue']))
			{
				$this->Page = $params['pwfp_continue'] - 1;
			}
			else
			{
				$this->Page = 1;
			}
		}
		else
		{
			if(isset($params['pwfp_continue']))
			{
				$this->Page = $params['pwfp_continue'];
			}
			else
			{
				$this->Page = 1;
			}

			if(isset($params['pwfp_prev']) && isset($params['pwfp_previous']))
			{
				$this->Page = $params['pwfp_previous'];
				$params['pwfp_done'] = 0;
			}
		}

		$this->FormTotalPages = 1;
		if(isset($params['pwfp_done'])&& $params['pwfp_done'] == 1)
		{
			$this->FormState = 'submit';
		}

		if(!empty($params['pwfp_user_form_validate']))
		{
			$this->FormState = 'confirm';
		}

		if($this->Id != -1)
		{
			if(isset($params['response_id']) && $this->FormState == 'submit')
			{
				$this->FormState = 'update';
			}

			$this->Load($this->Id, $params, $loadDeep, $loadResp);
		}

		foreach($params as $thisParamKey=>$thisParamVal)
		{
			if(substr($thisParamKey,0,11) == 'pwfp_forma_')
			{
				$thisParamKey = substr($thisParamKey,11);
				$this->Attrs[$thisParamKey] = $thisParamVal;
			}
			else if($thisParamKey == 'pwfp_form_template' && $this->Id != -1)
			{
				$this->pwfmodule->SetTemplate('pwf_'.$this->Id,$thisParamVal);
			}
		}

		$this->templateVariables = array(
			'{$sub_form_name}'=>$this->pwfmodule->Lang('title_form_name'),
			'{$sub_date}'=>$this->pwfmodule->Lang('help_submission_date'),
			'{$sub_host}'=>$this->pwfmodule->Lang('help_server_name'),
			'{$sub_source_ip}'=>$this->pwfmodule->Lang('help_sub_source_ip'),
			'{$sub_url}'=>$this->pwfmodule->Lang('help_sub_url'),
			'{$version}'=>$this->pwfmodule->Lang('help_module_version'),
			'{$TAB}'=>$this->pwfmodule->Lang('help_tab')
		);
	}
*/
	function SetAttributes($attrArray)
	{
		$this->Attrs = array_merge($this->Attrs,$attrArray);
	}

	function SetTemplate($template)
	{
		$this->Attrs['form_template'] = $template;
		$this->pwfmodule->SetTemplate('pwf_'.$this->Id,$template);
	}

	function SetId($id)
	{
		$this->Id = $id;
	}

	function GetFormState()
	{
		return $this->FormState;
	}

	function GetPageCount()
	{
		return $this->FormTotalPages;
	}

	function GetPageNumber()
	{
		return $this->Page;
	}

	function PageBack()
	{
		$this->Page--;
	}

	function SetName($name)
	{
		$this->Name = $name;
	}

	function GetAlias()
	{
		return $this->Alias;
	}

	function SetAlias($alias)
	{
		$this->Alias = $alias;
	}

	// dump params
	function DebugDisplay($params=array())
	{
		$tmp = $this->pwfmodule;
		$this->pwfmodule = '[mdptr]';

		if(isset($params['FORM']))
		{
			$fpt = $params['FORM'];
			$params['FORM'] = '[form_pointer]';
		}

		$template_tmp = $this->GetAttr('form_template','');
		$this->SetAttr('form_template',strlen($template_tmp).' characters');
		$field_tmp = $this->Fields;
		$this->Fields = 'Field Array: '.count($field_tmp);
		debug_display($this);
		$this->SetAttr('form_template',$template_tmp);
		$this->Fields = $field_tmp;
		foreach($this->Fields as &$fld)
		{
			$fld->DebugDisplay();
		}
		unset ($fld);
		$this->pwfmodule = $tmp;
	}

	function SetAttr($attrname, $val)
	{
		$this->Attrs[$attrname] = $val;
	}

	//returns first match (formerly - the last match)
	function HasFieldNamed($name)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetName() == $name)
			{
				return $fld->GetId();
			}
		}
		unset ($fld);
		return -1;
	}

	function AddTemplateVariable($name,$def)
	{
		$theKey = '{$'.$name.'}';
		$this->templateVariables[$theKey] = $def;
	}

	function fieldValueTemplate(&$extras=array())
	{
		$mod = $this->pwfmodule;
		$smarty->assign('title_variables',$mod->Lang('title_variables_available'));
		$smarty->assign('title_name',$mod->Lang('title_php_variable'));
		$smarty->assign('title_field',$mod->Lang('title_form_field'));
		$rows = array();
		foreach($this->Fields as &$fld)
		{
			$oneset = new StdClass();
			$oneset->id = $fld->GetId();
			$oneset->name = $fld->GetName();
			$rows[] = $oneset;
		}
		unset ($fld);
		if($extras)
		{
			foreach($extras as $id=>$name)
			{
				$oneset = new StdClass();
				$oneset->id = $id;
				$oneset->name = $name;
				$rows[] = $oneset;
			}
		}
		
		$smarty->assign('rows',$rows);
		return $mod->ProcessTemplate('field_vars.tpl');
	}

	function createSampleTemplate($htmlish=false,$email=true, $oneline=false,$header=false,$footer=false)
	{
		$mod = $this->pwfmodule;
		$ret = '';

		if($email)
		{
			if($htmlish)
			{
				$ret .= '<h1>'.$mod->Lang('email_default_template')."</h1>\n";
			}
			else
			{
				$ret .= $mod->Lang('email_default_template')."\n";
			}
			foreach($this->templateVariables as $thisKey=>$thisVal)
			{
				if($htmlish)
				{
					$ret .= '<strong>'.$thisVal.'</strong>: '.$thisKey.'<br />';
				}
				else
				{
					$ret .= $thisVal.': '.$thisKey;
				}
				$ret .= "\n";
			}
			if($htmlish)
			{
				$ret .= "\n<hr />\n";
			}
			else
			{
				$ret .= "\n-------------------------------------------------\n";
			}
		}
		elseif(!$oneline)
		{
			if($htmlish)
			{
				$ret .= '<h2>';
			}
			$ret .= $mod->Lang('thanks');
			if($htmlish)
			{
				$ret .= "</h2>\n";
			}
		}
		elseif($footer)
		{
			 $ret .= "------------------------------------------\n<!--EOF-->\n";
			 return $ret;
		}

		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				if($fld->GetAlias() != '')
				{
					$fldref = $fld->GetAlias();
				}
				else
				{
					$fldref = 'fld_'. $fld->GetId();
				}

				$ret .= '{if $'.$fldref.' != "" && $'.$fldref.' != "'.$this->GetAttr('unspecified',$mod->Lang('unspecified')).'"}';
				$fldref = '{$'.$fldref.'}';

				if($htmlish)
				{
					$ret .= '<strong>'.$fld->GetName() . '</strong>: ' . $fldref. '<br />';
				}
				elseif($oneline && !$header)
				{
					$ret .= $fldref. '{$TAB}';
				}
				elseif($oneline && $header)
				{
					$ret .= $fld->GetName().'{$TAB}';
				}
				else
				{
					$ret .= $fld->GetName() . ': ' .$fldref;
				}
				$ret .= "{/if}\n";
			}
		}
		unset ($fld);
		return $ret;
	}

	//called only from AdminTemplateHelp()
	function createSampleTemplateJavascript($fieldName='opt_email_template', $button_text='', $suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix;
		$content = <<<EOS
<script type="text/javascript">
//<![CDATA[
function populate_{$fldAlias}(formname)
{
 var fname = 'IDpwfp_{$fieldName}';
 $(formname[fname]).val(|TEMPLATE|).change();
}
//]]>
</script>

	<input type="button" value="{$button_text}" onclick="javascript:populate_{$fldAlias}(this.form)" />
EOS;
		return $content;
	}

/*	function AdminTemplateHelp($formDescriptor,$fieldStruct)
	{
		$mod = $this->pwfmodule;

		$ret = '<table class="pwf_legend"><tr><th colspan="2">'.$mod->Lang('help_variables_for_template').'</th></tr>';
		$ret .= '<tr><th>'.$mod->Lang('help_variable_name').'</th><th>'.$mod->Lang('title_form_field').'</th></tr>';
		$odd = false;
		foreach($this->templateVariables as $thisKey=>$thisVal)
		{
			$ret .= '<tr><td class="'.($odd?'odd':'even').
			'">'.$thisKey.'</td><td class="'.($odd?'odd':'even').
			'">'.$thisVal.'</td></tr>';
		 	$odd = ! $odd;
		}

		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				$ret .= '<tr><td class="'.($odd?'odd':'even').
				'">{$'.$fld->GetVariableName().
				'} / {$fld_'.$fld->GetId().'}';
				if($fld->GetAlias() != '')
				{
					$ret .= ' / {$'.$fld->GetAlias().'}';
				}
				$ret .= '</td><td class="'.($odd?'odd':'even').
				'">' .$fld->GetName() . '</td></tr>';
				$odd = ! $odd;
			}
		}
		unset ($fld);

		$ret .= '<tr><td colspan="2">'.$mod->Lang('help_array_fields').'</td></tr>';
		$ret .= '<tr><td colspan="2">'.$mod->Lang('help_other_fields').'</td></tr>';

		$sampleTemplateCode = '';
		foreach($fieldStruct as $key=>$val)
		{
			$html_button = (!empty($val['html_button']));
			$text_button = (!empty($val['text_button']));
			$is_oneline = (!empty($val['is_oneline']));
			$is_email = (!empty($val['is_email']));
			$is_header = (!empty($val['is_header']));
			$is_footer = (!empty($val['is_footer']));

			if($html_button)
			{
				$button_text = $mod->Lang('title_create_sample_html_template');
			}
			elseif($is_header)
			{
				$button_text = $mod->Lang('title_create_sample_header_template');
			}
			elseif($is_footer)
			{
				$button_text = $mod->Lang('title_create_sample_footer_template');
			}
			else
			{
				$button_text = $mod->Lang('title_create_sample_template');
			}

			if($html_button && $text_button)
			{
				$sample = $this->createSampleTemplate(false, $is_email, $is_oneline, $is_header, $is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
					self::createSampleTemplateJavascript($key, $mod->Lang('title_create_sample_template'),'text'));
			}

			$sample = $this->createSampleTemplate($html_button, $is_email, $is_oneline, $is_header, $is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
				self::createSampleTemplateJavascript($key, $button_text));
		}

		$sampleTemplateCode = str_replace('ID', $formDescriptor, $sampleTemplateCode);
		$ret .= '<tr><td colspan="2">'.$sampleTemplateCode.'</td></tr>';
		$ret .= '</table>';

		return $ret;
	}
*/
	//called only from AdminTemplateActions()
	private function CreateAction($id, $fieldName='opt_email_template', $button_text='', $suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix;
		$msg = $this->pwfmodule->Lang('confirm');
		$func = <<<EOS  
function populate_{$fldAlias}(formname) {
 if(confirm ('{$msg}')) {
  formname['{$id}pwfp_{$fieldName}'].value=|TEMPLATE|;
 }
}
EOS;
		$btn = <<<EOS
<input type="button" class="cms_submit" value="{$button_text}" onclick="javascript:populate_{$fldAlias}(this.form)" />
EOS;
		return (array($func,$btn));
	}

	function AdminTemplateActions($formDescriptor,$fieldStruct)
	{
		$mod = $this->pwfmodule;
		$funcs = array();
		$buttons = array();
		foreach($fieldStruct as $key=>$val)
		{
			$html_button = !empty($val['html_button']);
			$text_button = !empty($val['text_button']);
			$gen_button = !empty($val['general_button']);
			$is_oneline = !empty($val['is_oneline']);
			$is_email = !empty($val['is_email']);
			$is_header = !empty($val['is_header']);
			$is_footer = !empty($val['is_footer']);

			if($html_button)
				$button_text = $mod->Lang('title_create_sample_html_template');
			elseif($is_header)
				$button_text = $mod->Lang('title_create_sample_header_template');
			elseif($is_footer)
				$button_text = $mod->Lang('title_create_sample_footer_template');
			else
				$button_text = $mod->Lang('title_create_sample_template');

			if($html_button && $text_button)
			{
				$sample = self::createSampleTemplate(false, $is_email, $is_oneline, $is_header, $is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				list($func,$btn) = self::CreateAction($formDescriptor, $key, $mod->Lang('title_create_sample_template'),'text');
				$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
				$buttons[] = $btn;
			}

			$sample = self::createSampleTemplate($html_button || $gen_button, $is_email, $is_oneline, $is_header, $is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($func,$btn) = self::CreateAction($formDescriptor, $key, $button_text);
			$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
			$buttons[]= $btn;
		}
		return array($funcs,$buttons);
	}

	function SetupVarsHelp(&$mod, &$smarty)
	{
		$smarty->assign('help_vars_title',$mod->Lang('help_variables_for_template'));

		$sysfields = array();
		foreach($this->templateVariables as $thisKey=>$thisVal)
		{
			$oneset = new stdClass();
			$oneset->name = $thisKey;
			$oneset->title = $thisVal;
			$sysfields[] = $oneset;
		}
		$smarty->assign('sysfields',$sysfields);

		$subfields = array();
		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				$oneset = new stdClass();
				$oneset->name = $fld->GetVariableName();
				$oneset->id = $fld->GetId();
				$oneset->alias = $fld->GetAlias();
				$oneset->title = $fld->GetName();
				$oneset->escaped = str_replace("'","\\'",$oneset->title);
				$subfields[] = $oneset;
			}
		}
		unset ($fld);
		$smarty->assign('subfields',$subfields);

		$obfields = array();
		foreach(array ('name', 'type', 'id', 'value', 'valuearray') as $name)
		{
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->title = $mod->Lang('title_field_'.$name);
			$obfields[] = $oneset;
		}
		$smarty->assign('obfields',$obfields);
//		$oneset->title = $mod->Lang('title_field_id2');
		$smarty->assign('help_field_object',$mod->Lang('help_array_fields'));
		$smarty->assign('help_object_example',$mod->Lang('help_object_example'));
		$smarty->assign('help_other_fields',$mod->Lang('help_other_fields'));
		$smarty->assign('help_vars',$mod->ProcessTemplate('vars_help.tpl'));
	}

	function Validate()
	{
		$validated = true;
		$message = array();
		$formPageCount=1;
		$valPage = $this->Page - 1;
		$usertagops = cmsms()->GetUserTagOperations();
		$mod = $this->pwfmodule;
		$udt = $this->GetAttr('validate_udt','');
		$unspec = $this->GetAttr('unspecified',$mod->Lang('unspecified'));

		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'PageBreakField')
			{
				$formPageCount++;
			}
			if($valPage != $formPageCount)
			{
				continue;
			}

			$deny_space_validation = !!$mod->GetPreference('blank_invalid');
/*			debug_display($fld->GetName().' '.
				($fld->HasValue() === false?'False':'true'));
			if($fld->HasValue())
				debug_display($fld->GetValue());
*/
			if(//! $fld->IsNonRequirableField() &&
				$fld->IsRequired() && $fld->HasValue($deny_space_validation) === false)
			{
				$message[] = $mod->Lang('please_enter_a_value',$fld->GetName());
				$validated = false;
				$fld->SetOption('is_valid',false);
				$fld->validationErrorText = $mod->Lang('please_enter_a_value',$fld->GetName());
				$fld->validated = false;
			}
			else if($fld->GetValue() != $mod->Lang('unspecified'))
			{
				$res = $fld->Validate();
				if($res[0] != true)
				{
					$message[] = $res[1];
					$validated = false;
					$fld->SetOption('is_valid',false);
				}
				else
				{
					$fld->SetOption('is_valid',true);
				}
			}

			if($validated == true && !empty($udt) && "-1" != $udt)
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
				if($res[0] != true)
				{
					$message[] = $res[1];
					$validated = false;
				}
			}
		}
		unset ($fld);
		return array($validated, $message);
	}

	function HasDisposition()
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->IsDisposition())
				return true;
		}
		unset ($fld);
		return false;
	}

	// return an array: element 0 is true for success, false for failure
	// element 1 is an array of reasons, in the event of failure.
	function Dispose($returnid,$suppress_email=false)
	{
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
		$retCode = true;
		// for each form disposition pseudo-field, dispose the form results
		foreach($this->Fields as &$fld)
		{
			if($fld->IsDisposition() && $fld->DispositionIsPermitted())
			{
				if(!($suppress_email && $fld->IsEmailDisposition()))
				{
					$res = $fld->DisposeForm($returnid);
					if($res[0] == false)
					{
						$retCode = false;
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

	function RenderFormHeader()
	{
		if($this->pwfmodule->GetPreference('show_version',0) == 1)
		{
			return "\n<!-- Start PowerForms Module (".$this->pwfmodule->GetVersion().") -->\n";
		}
	}

	function RenderFormFooter()
	{
		if($this->pwfmodule->GetPreference('show_version',0) == 1)
		{
			return "\n<!-- End PowerForms Module -->\n";
		}
	}

	  // returns a string.
	function RenderForm($id, &$params, $returnid)
	{
		$parts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
		array_splice ($parts,count($parts)-3,3,array('lib','replacement.php'));
		include(implode(DIRECTORY_SEPARATOR,$parts));

		// Check if form id given
		$mod = $this->pwfmodule;

		if($this->Id == -1)
		{
			return "<!-- no form -->\n";
		}

		// Check if show full form
		if($this->loaded != 'full')
		{
			$this->Load($this->Id,$params,true);
		}

		// Usual crap
		$reqSymbol = $this->GetAttr('required_field_symbol','*');
		$smarty = cmsms()->GetSmarty();

		$smarty->assign('title_page_x_of_y',$mod->Lang('title_page_x_of_y',array($this->Page,$this->FormTotalPages)));

		$smarty->assign('css_class',$this->GetAttr('css_class',''));
		$smarty->assign('total_pages',$this->FormTotalPages);
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
		if($this->Page == $this->FormTotalPages)
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
				if($fld->GetFieldType() == 'FileUploadField')
				{
					$smarty->assign('fld_'.$fld->GetId(),$fld->GetHumanReadableValue());
					$hidden .= $mod->CreateInputHidden($id,
						$testIndex,
						$this->unmy_htmlentities($fld->GetHumanReadableValue()));
					$thisAtt = $fld->GetHumanReadableValue(false);
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
									$this->unmy_htmlentities($val));
					}
				}
				else
				{
					$hidden .= $mod->CreateInputHidden($id,
							   $testIndex,
							   $this->unmy_htmlentities($params[$testIndex]));
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
			$oneset->display = $fld->DisplayInForm()?1:0;
			$oneset->required = $fld->IsRequired()?1:0;
			$oneset->required_symbol = $fld->IsRequired()?$reqSymbol:'';
			$oneset->css_class = $fld->GetOption('css_class');
			$oneset->helptext = $fld->GetOption('helptext');
			$oneset->field_helptext_id = 'pwfp_ht_'.$fld->GetID();
		//	$oneset->valid = $fld->GetOption('is_valid',true)?1:0;
			$oneset->valid = $fld->validated?1:0;
			$oneset->error = $fld->GetOption('is_valid',true)?'':$fld->validationErrorText;
			$oneset->hide_name = 0;
			if(((!$fld->HasLabel()) || $fld->HideLabel()) && ($fld->GetOption('fbr_edit','0') == '0' || $params['in_admin'] != 1))
			{
				$oneset->hide_name = 1;
			}
			$oneset->has_label = $fld->HasLabel();
			$oneset->needs_div = $fld->NeedsDiv();
			$oneset->name = $fld->GetName();
			$oneset->input = $fld->GetFieldInput($id, $params, $returnid);
			$oneset->logic = $fld->GetFieldLogic();
			$oneset->values = $fld->GetAllHumanReadableValues();
			$oneset->smarty_eval = $fld->GetSmartyEval()?1:0;

			$oneset->multiple_parts = $fld->HasMultipleFormComponents()?1:0;
			$oneset->label_parts = $fld->LabelSubComponents()?1:0;
			$oneset->type = $fld->GetDisplayType();
			$oneset->input_id = $fld->GetCSSId();
			$oneset->id = $fld->GetId();

			// Added by Stikki STARTS
			$name_alias = $fld->GetName();
			$name_alias = str_replace($toreplace, $replacement, $name_alias);
			$name_alias = strtolower($name_alias);
			$name_alias = preg_replace('/[^a-z0-9]+/i','_',$name_alias);

			$smarty->assign($name_alias,$oneset);
			// Added by Stikki ENDS

			if($fld->GetAlias() != '')
			{
				$smarty->assign($fld->GetAlias(),$oneset);
				$oneset->alias = $fld->GetAlias();
			}
			else
			{
				$oneset->alias = $name_alias;
			}

			$fields[$oneset->input_id] = $oneset;
			//$fields[] = $oneset;
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
  if(item != null) {
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
			$jsTrigger = " onclick='return LockButton()'";
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
			if($this->GetAttr('use_captcha','0') == '1' && $captcha != null)
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

	function LoadForm($loadDeep=false)
	{
		$noparms = array();
		return $this->Load($mod,$this->Id,$noparms,$loadDeep);
	}

	function unmy_htmlentities($val)
	{
		if($val == "")
		{
			return "";
		}
		$val = html_entity_decode($val);
		$val = str_replace("&amp;","&",$val);
		$val = str_replace("&#60;&#33;--","<!--",$val);
		$val = str_replace("--&#62;","-->",$val);
		$val = str_replace("&gt;",">", $val);
		$val = str_replace("&lt;","<",$val);
		$val = str_replace("&quot;","\"",$val);
		$val = str_replace("&#036;","\$",$val);
		$val = str_replace("&#33;","!",$val);
		$val = str_replace("&#39;","'",$val);

		// Uncomment if you need to convert unicode chars
		return $val;
	}

	function updateRefs($text, &$fieldMap)
	{
		foreach($fieldMap as $k=>$v)
		{
			$text = preg_replace('/([\{\b\s])\$fld_'.$k.'([\}\b\s])/','$1\$fld_'.$v.'$2',$text);
		}
		return $text;
	 }

	function MakeAlias($string, $isForm=false)
	{
		$string = trim(htmlspecialchars($string));
		if($isForm)
		{
			return strtolower($string);
		}
		else
		{
			return 'pwf'.strtolower($string);
		}
	}

	function MergeEmails(&$params)
	{
		if($params['pwfp_opt_destination_address'])
		{
			if(!is_array($params['pwfp_opt_destination_address']))
				$params['pwfp_opt_destination_address'] = array($params['pwfp_opt_destination_address']);

			foreach($params['pwfp_opt_destination_address'] as $i => $to)
			{
				if(isset($params['pwfp_aef_to_'.$i]))
				{
					$totype = $params['pwfp_aef_to_'.$i];
					switch ($totype)
					{
					 case 'cc';
						$params['pwfp_opt_destination_address'][$i] = '|cc|'.$to;
						break;
					 case 'bc':
						$params['pwfp_opt_destination_address'][$i] = '|bc|'.$to;
						break;
					}
					unset($params['pwfp_aef_to_'.$i]);
				}
			}
		}
	}

	function DefaultTemplate()
	{
		return file_get_contents(cms_join_path(dirname(dirname(__FILE__)),'templates','RenderFormDefault.tpl'));
	}

	// FormBrowser >= 0.3 Response load method. This populates the Field values directly
	// (as opposed to LoadResponseValues, which places the values into the $params array)
	function LoadResponse($response_id)
	{
		$mod = $this->pwfmodule;
		$db = $this->pwfmodule->dbHandle;

		$oneset = new StdClass();
		$row = $db->GetRow('SELECT response, form_id FROM '.cms_db_prefix().
						'module_pwf_browse WHERE browser_id=?', array($response_id));

		if($row)
		{
			if($row['form_id'] == $this->GetId())
			{
				$oneset->xml = $row['response'];
				$oneset->form_id = $row['form_id'];
			}
			else
				return false;
		}
		else
			return false;

		$obfield = $this->GetFormBrowserField();
		if($obfield == false)
		{
			// error handling goes here.
			echo($mod->Lang('error_no_browser_field'));
		}
		$mod->HandleResponseFromXML($obfield, $oneset);

		list($fnames, $aliases, $vals) = $mod->ParseResponseXML($oneset->xml, false);
		$this->ResetFields();
		foreach($vals as $id=>$val)
		{
			//error_log("setting value of field ".$id." to be ".$val);
			$index = $this->GetFieldIndexFromId($id);
			if($index != -1 &&  is_object($this->Fields[$index]))
			{
				$this->Fields[$index]->SetValue($val);
			}
		}
		return true;
	}

	// Check if FormBrowser field exists
	function &GetFormBrowserField()
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'DispositionFormBrowser')
			{
				return $fld;
			}
		}
		unset ($fld);
		// error handling goes here.
		$fld = false; //needed reference
		return $fld;
	}

	function ReindexResponses()
	{
		@set_time_limit(0);
		$mod = $this->pwfmodule;
		$db = $this->pwfmodule->dbHandle;
		$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_pwf_browse WHERE form_id=?';
		$responses = $db->GetAll($sql, array($this->Id));
		$obfield = $this->GetFormBrowserField();
		foreach($responses as &$this_resp)
		{
			if($this->LoadResponse($this_resp))
			{
				$this->StoreResponse($this_resp,'',$obfield);
			}
		}
		unset ($this_resp);
	}

	// FormBrowser >= 0.3 Response load method. This populates the $params array for later processing/combination
	// (as opposed to LoadResponse, which places the values into the Field values directly)
	function LoadResponseValues(&$params, &$types)
	{
		$mod = $this->pwfmodule;
		$db = $this->pwfmodule->dbHandle;
		$oneset = new StdClass();
		$form_id = -1;
		$sql = 'SELECT response, form_id FROM '.cms_db_prefix().'module_pwf_browse WHERE browser_id=?';
		$row = $db->GetRow($sql, array($params['response_id']));

		if($row)
		{
			$oneset->xml = $row['response'];
			$form_id = $row['form_id'];
		}
		// loaded a response -- at this point, we check that the response
		// is for the correct form_id!
		if($form_id != $this->GetId())
		{
			return false;
		}
		$obfield = $mod->GetFormBrowserField($form_id);
		if($obfield == false)
		{
			// error handling goes here.
			echo($mod->Lang('error_no_browser_field'));
		}
		$mod->HandleResponseFromXML($obfield, $oneset);
		list($fnames, $aliases, $vals) = $mod->ParseResponseXML($oneset->xml, false);
		$types = $mod->ParseResponseXMLType($oneset->xml);
		foreach($vals as $id=>$val)
		{
			if(isset($params['pwfp__'.$id]) && !is_array($params['pwfp__'.$id]))
			{
				$params['pwfp__'.$id] = array($params['pwfp__'.$id]);
				array_push($params['pwfp__'.$id], $val);
			}
			elseif(isset($params['pwfp__'.$id]))
			{
				array_push($params['pwfp__'.$id], $val);
			}
			else
			{
				$params['pwfp__'.$id] = $val;
			}
		}
		return true;
	}

	// FormBrowser < 0.3 Response load method
	function LoadResponseValuesOld(&$params)
	{
		$db = $this->pwfmodule->dbHandle;
		// loading a response -- at this point, we check that the response
		// is for the correct form_id!
		$sql = 'SELECT form_id FROM ' . cms_db_prefix().
			'module_pwf_resp where resp_id=?';
		if($result = $db->GetRow($sql, array($params['response_id'])))
		{
			if($result['form_id'] == $this->GetId())
			{
				$sql = 'SELECT field_id, value FROM '.cms_db_prefix().
				'module_pwf_resp_val WHERE resp_id=? order by resp_val_id';
				$allrows = $db->GetAll($sql, array($params['response_id']));
				foreach($allrows as &$row)
				{ // was '__'
					$fid = 'pwfp__'.$row['field_id'];
					if(isset($params[$fid]))
					{
						if(!is_array($params[$fid]))
							$params[$fid] = array($params[$fid]);
						$params[$fid][] = $row['value'];
					}
					else
						$params[$fid] = $row['value'];
				}
				unset ($row);
				return true;
			}
		}
		return false;
	}

	// Validation stuff action.validate_form.php
	function CheckResponse($form_id, $response_id, $code)
	{
		$db = $this->pwfmodule->dbHandle;
		$sql = 'SELECT secret_code FROM ' . cms_db_prefix(). 'module_pwf_browse WHERE form_id=? AND browser_id=?';
		if($result = $db->GetRow($sql, array($form_id,$response_id)))
		{
			if($result['secret_code'] == $code)
			{
				return true;
			}
		}
		return false;
	}

	// Master response inputter
	function StoreResponse($response_id=-1,$approver='',&$Disposer)
	{
		$mod = $this->pwfmodule;
		$db = $mod->dbHandle;
		$newrec = false;
		$crypt = false;
		$hash_fields = false;
		$sort_fields = array();

		// Check if form has database fields, do init
		if(is_object($Disposer) &&
			$Disposer->GetFieldType() == 'DispositionFormBrowser')
		{
			$crypt = ($Disposer->GetOption('crypt','0') == '1');
			$hash_fields = ($Disposer->GetOption('hash_sort','0') == '1');
			for ($i=0;$i<5;$i++)
			{
				$sort_fields[$i] = $Disposer->getSortFieldVal($i+1);
			}
		}

		// If new field
		if($response_id == -1)
		{
			if(is_object($Disposer) && $Disposer->GetOption('feu_bind','0') == '1')
			{
				$feu = $mod->GetModuleInstance('FrontEndUsers');
				if($feu == false)
				{
					debug_display("FAILED to instatiate FEU!");
					return;
				}
				$feu_id = $feu->LoggedInId();
			}
			else
			{
				$feu_id = -1;
			}
			$response_id = $db->GenID(cms_db_prefix(). 'module_pwf_browse_seq');
			foreach($this->Fields as &$fld)
			{
				// set the response_id to be the attribute of the formbrowser disposition
				$type = $fld->GetFieldType();
				if($type == 'DispositionFormBrowser')
				{
					$fld->SetValue($response_id);
				}
			}
			unset ($fld);
			$newrec = true;
			}
		else
		{
			$feu_id = $mod->getFEUIDFromResponseID($response_id);
		}

		// Convert form to XML
		$xml = $this->ResponseToXML();

		// Do the actual adding
		if(!$crypt)
		{
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		elseif(!$hash_fields)
		{
			list($res, $xml) = $mod->crypt($xml,$Disposer);
			if(!$res)
			{
				return array(false, $xml);
			}
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		else
		{
			list($res, $xml) = $mod->crypt($xml,$Disposer);
			if(!$res)
			{
				return array(false, $xml);
			}
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$mod->getHashedSortFieldVal($sort_fields[0]):'',
				isset($sort_fields[1])?$mod->getHashedSortFieldVal($sort_fields[1]):'',
				isset($sort_fields[2])?$mod->getHashedSortFieldVal($sort_fields[2]):'',
				isset($sort_fields[3])?$mod->getHashedSortFieldVal($sort_fields[3]):'',
				isset($sort_fields[4])?$mod->getHashedSortFieldVal($sort_fields[4]):'',
				$feu_id,
				$xml);
		}
		//return array(true,''); Stikki replaced: instead of true, return actual data, didn't saw any side effects.
		return $output;
	}

	// Converts form to XML
	function &ResponseToXML()
	{
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xml .= "<response form_id=\"".$this->Id."\">\n";
		foreach($this->Fields as &$fld)
		{
			$xml .= $fld->ExportXML(true);
		}
		unset ($fld);
		$xml .= "</response>\n";
		return $xml;
	}

	// Inserts parsed XML data to database
	function StoreResponseXML($response_id=-1,$newrec=false,$approver='',$sortfield1,
	   $sortfield2,$sortfield3,$sortfield4,$sortfield5, $feu_id,$xml)
	{
		$db = $this->pwfmodule->dbHandle;
		$pref = cms_db_prefix();
		$secret_code = '';

		if($newrec)
		{
			// saving a new response
			$secret_code = substr(md5(session_id().'_'.time()),0,7);
//			$response_id = $db->GenID($pref.'module_pwf_browse_seq');
			$sql = 'INSERT INTO '.$pref.
				'module_pwf_browse (browser_id, form_id, submitted, secret_code, index_key_1, index_key_2, index_key_3, index_key_4, index_key_5, feuid, response) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
				array($response_id,
					$this->GetId(),
					$this->clean_datetime($db->DBTimeStamp(time())),
					$secret_code,
					$sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,
					$feu_id,
					$xml
				));
		}
		else if($approver != '')
		{
			$sql = 'UPDATE '.$pref.
				'module_pwf_browse set user_approved=? where browser_id=?';
			$res = $db->Execute($sql,
				array($this->clean_datetime($db->DBTimeStamp(time())),$response_id));
			audit(-1, $this->pwfmodule->GetName(), $this->pwfmodule->Lang('user_approved_submission',array($response_id,$approver)));
		}
		if(!$newrec)
		{
			$sql = 'UPDATE '.$pref.
				'module_pwf_browse set index_key_1=?, index_key_2=?, index_key_3=?, index_key_4=?, index_key_5=?, response=? where browser_id=?';
			$res = $db->Execute($sql,
				array($sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,$xml,$response_id));
		}
		return array($response_id,$secret_code);
	}

	// Some stupid date function
	function clean_datetime($dt)
	{
		return substr($dt,1,strlen($dt)-2);
	}

	function AddToSearchIndex($response_id)
	{
		// find browsers keyed to this
		$funcs = new pwfFormOperations();
		$browsers = $funcs->GetBrowsers($this->pwfmodule,$this->Id);
		if(count($browsers) < 1)
			return;

		$module = $this->pwfmodule->GetModuleInstance('Search');
		if($module != FALSE)
		{
			$submitstring = '';
			foreach($this->Fields as &$fld)
			{
				if($fld->DisplayInSubmission())
				{
					$submitstring .= ' '.$fld->GetHumanReadableValue($as_string=true);
				}
			}
			unset ($fld);
			foreach($browsers as $thisBrowser)
			{
				$module->AddWords('FormBrowser', $response_id, 'sub_'.$thisBrowser, $submitstring, null);
			}
		}
	}

	function setFinishedFormSmarty($htmlemail=false)
	{
		$mod = $this->pwfmodule;

		$unspec = $this->GetAttr('unspecified',$mod->Lang('unspecified'));
		$smarty = cmsms()->GetSmarty();

		$formInfo = array();

		foreach($this->Fields as &$fld)
		{
			$replVal = $unspec;
			$replVals = array();
			if($fld->DisplayInSubmission())
			{
				$replVal = $fld->GetHumanReadableValue();
				if($htmlemail)
				{
					// allow <BR> as delimiter or in content
					$replVal = preg_replace(
						array('/<br(\s)*(\/)*>/i','/[\n\r]+/'),array('|BR|','|BR|'),
						$replVal);
					$replVal = htmlspecialchars($replVal);
					$replVal = str_replace('|BR|','<br />',$replVal);
				}
				if($replVal == '')
				{
					$replVal = $unspec;
				}
			}

			$name = $fld->GetVariableName();
			$fldobj = $fld->ExportObject();
			$smarty->assign($name,$replVal);
			$smarty->assign($name.'_obj',$fldobj);
			$id = $fld->GetId();
			$smarty->assign('fld_'.$id,$replVal);
			$smarty->assign('fld_'.$id.'_obj',$fldobj);
			$alias = $fld->GetAlias();
			if($alias != '')
			{
				$smarty->assign($alias,$replVal);
				$smarty->assign($alias.'_obj',$fldobj);
			}
		}
		unset ($fld);

		// general form details
		$smarty->assign('sub_form_name',$this->GetName());
		$smarty->assign('sub_date',date('r'));
		$smarty->assign('sub_host',$_SERVER['SERVER_NAME']);
		$smarty->assign('sub_source_ip',$_SERVER['REMOTE_ADDR']);
		$smarty->assign('sub_url',(empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']));
		$smarty->assign('version',$mod->GetVersion());
		$smarty->assign('TAB',"\t");
	}

	function manageFileUploads()
	{
		$config = cmsms()->GetConfig();
		$mod = $this->pwfmodule;

		// build rename map
		$mapId = array();
		$eval_string = false;
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
				$_id = 'm1_pwfp__'.$fld->GetId();
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
							return array($res, $mod->Lang('nouploads_error'));
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

						if($res[0] == false)
						{
							// failed upload kills the send.
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',$res[1]));
							return array($res[0], $mod->Lang('uploads_error',$res[1]));
						}

						$uploads_destpage = $fld->GetOption('uploads_destpage');
						$url = $uploads->CreateLink ($parms['category_id'], 'getfile', $uploads_destpage, '',
							array ('upload_id' => $res[1]), '', true);

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
						$valid = true;
						$ms = $fld->GetOption('max_size');
						$exts = $fld->GetOption('permitted_extensions','');
						if($ms != '' && $thisFile['size'] > ($ms * 1024))
						{
							$valid = false;
						}
						else if($exts != '')
						{
							$match = false;
							$legalExts = explode(',',$exts);
							foreach($legalExts as $thisExt)
							{
								if(preg_match('/\.'.trim($thisExt).'$/i',$thisFile['name']))
								{
									$match = true;
								}
								else if(preg_match('/'.trim($thisExt).'/i',$thisFile['type']))
								{
									$match = true;
								}
							}
							if(!$match)
							{
								$valid = false;
							}
						}
						if(!$valid)
						{
							unlink($src);
							audit(-1, $mod->GetName(), $mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
							return array(false, '');
						}
						$dest = $dest_path.DIRECTORY_SEPARATOR.$destination_name;
						if(file_exists($dest) && $fld->GetOption('allow_overwrite','0')=='0')
						{
							unlink($src);
							return array(false,$mod->Lang('file_already_exists', array($destination_name)));
						}
						if(!move_uploaded_file($src,$dest))
						{
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',''));
							return array(false, $mod->Lang('uploads_error',''));
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
		return array(true,'');
	}

}



?>
