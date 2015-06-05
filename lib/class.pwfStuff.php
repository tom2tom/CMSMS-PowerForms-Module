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

UNUSED	function def(&$var)
	{
		if(!isset($var))
		{
			return FALSE;
		}
		else if(is_null($var))
		{
			return FALSE;
		}
		else if(!is_array($var) && empty($var))
		{
			return FALSE;
		}
		else if(is_array($var) && count($var) == 0)
		{
			return FALSE;
		}
		return TRUE;
	}

UNUSED	function DeleteFromSearchIndex(&$params)
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
					$module->DeleteWords('FormBrowser',$params['response_id'],'sub_'.$one);
			}
		}
	}


/*
	var $formsmodule = NULL;
	var $module_params = -1; UNUSED
	var $Id = -1;
	var $Name = '';
	var $Alias = '';
	var $loaded = 'not';
	var $FormPagesCount = 0;
	var $Page;
	var $Options;
	var $Fields;
	var $FormState;
	var $sampleTemplateCode;
	var $templateVariables;

	function __construct(&$formsmodule,&$params,$loadDeep=FALSE,$loadResp=FALSE)
	{
		$this->formsmodule = $formsmodule;
		$this->module_params = $params;
		$this->Fields = array();
		$this->Options = array();
		$this->FormState = 'new';

		if(isset($params['form_id']))
		{
			$this->Id = $params['form_id'];
		}

		if(isset($params['pwfp_NNN_form_alias']))
		{
			$this->Alias = $params['pwfp_NNN_form_alias'];
		}

		if(isset($params['pwfp_NNN_form_name']))
		{
			$this->Name = $params['pwfp_NNN_form_name'];
		}

		$fieldExpandOp = FALSE;
		foreach($params as $pKey=>$pVal)
		{
			if(substr($pKey,0,9) == 'pwfp_NNN_FeX_' || substr($pKey,0,9) == 'pwfp_NNN_FeD_')
			{
				// expanding or shrinking a field
				$fieldExpandOp = TRUE;
			}
		}

		if($fieldExpandOp)
		{
			$params['pwfp_NNN_done'] = 0;
			if(isset($params['pwfp_NNN_continue']))
			{
				$this->Page = $params['pwfp_NNN_continue'] - 1;
			}
			else
			{
				$this->Page = 1;
			}
		}
		else
		{
			if(isset($params['pwfp_NNN_continue']))
			{
				$this->Page = $params['pwfp_NNN_continue'];
			}
			else
			{
				$this->Page = 1;
			}

			if(isset($params['pwfp_NNN_prev']) && isset($params['pwfp_NNN_previous']))
			{
				$this->Page = $params['pwfp_NNN_previous'];
				$params['pwfp_NNN_done'] = 0;
			}
		}

		$this->FormPagesCount = 1;
		if(isset($params['pwfp_NNN_done'])&& $params['pwfp_NNN_done'] == 1)
		{
			$this->FormState = 'submit';
		}

		if(!empty($params['pwfp_NNN_user_form_validate']))
		{
			$this->FormState = 'confirm';
		}

		if($this->Id != -1)
		{
			if(isset($params['response_id']) && $this->FormState == 'submit')
			{
				$this->FormState = 'update';
			}

			$this->Load($this->Id,$params,$loadDeep,$loadResp);
		}

		foreach($params as $thisParamKey=>$thisParamVal)
		{
			if(substr($thisParamKey,0,11) == 'pwfp_NNN_forma_') //TODO what set this?
			{
				$thisParamKey = substr($thisParamKey,11);
				$this->Options[$thisParamKey] = $thisParamVal;
			}
			else if($thisParamKey == 'form_template' && $this->Id != -1)
			{
				$this->Options[$thisParamKey] = 'pwf_'.$this->Id;
				$this->formsmodule->SetTemplate('pwf_'.$this->Id,$thisParamVal);
			}
		}

		$this->templateVariables = array(
			'{$form_name}'=>$this->formsmodule->Lang('title_form_name'),
			'{$sub_date}'=>$this->formsmodule->Lang('help_submission_date'),
			'{$form_host}'=>$this->formsmodule->Lang('help_server_name'),
			'{$sub_source}'=>$this->formsmodule->Lang('help_sub_source'),
			'{$form_url}'=>$this->formsmodule->Lang('help_form_url'),
			'{$version}'=>$this->formsmodule->Lang('help_module_version')
		);
	}
*/
UNUSED	function SetAttributes($attrArray)
	{
		$this->Options = array_merge($this->Options,$attrArray);
	}

UNUSED	function SetId($id)
	{
		$this->Id = $id;
	}

UNUSED	function GetFormState()
	{
		return $this->FormState;
	}

UNUSED	function SetName($name)
	{
		$this->Name = $name;
	}

	// dump params
/*	function DebugDisplay($params=array())
	{
		$tmp = $this->formsmodule;
		$this->formsmodule = '[mdptr]';

		if(isset($params['FORM']))
		{
			$fpt = $params['FORM'];
			$params['FORM'] = '[form_pointer]';
		}

		$template_tmp = $this->GetFormOption('form_template','');
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
		$this->formsmodule = $tmp;
	}

ONLY DEBUG	function SetAttr($attrname,$val)
	{
		$this->Options[$attrname] = $val;
	}
*/
	//called only from AdminTemplateHelp()
/*	function CreateSampleTemplateJavascript($fieldName='opt_email_template',$button_text='',$suffix='')
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

	function AdminTemplateHelp($id,$fieldStruct)
	{
		$mod = $this->formsmodule;

		$ret = '<table class="pwf_legend"><tr><th colspan="2">'.$mod->Lang('title_template_variables').'</th></tr>';
		$ret .= '<tr><th>'.$mod->Lang('help_variable_name').'</th><th>'.$mod->Lang('title_form_field').'</th></tr>';
		$odd = FALSE;
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
TODO DUP		$ret .= ' / {$'.$fld->ForceAlias().'}';
				$ret .= '</td><td class="'.($odd?'odd':'even').
				'">' .$fld->GetName() . '</td></tr>';
				$odd = ! $odd;
			}
		}
		unset ($fld);

//		$ret .= '<tr><td colspan="2">'.$mod->Lang('help_field_values').'</td></tr>';
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
				$sample = $this->CreateSampleTemplate(FALSE,$is_email,$is_oneline,$is_header,$is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
					self::CreateSampleTemplateJavascript($key,$mod->Lang('title_create_sample_template'),'text'));
			}

			$sample = $this->CreateSampleTemplate($html_button,$is_email,$is_oneline,$is_header,$is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
				self::CreateSampleTemplateJavascript($key,$button_text));
		}

		$sampleTemplateCode = str_replace('ID',$id,$sampleTemplateCode);
		$ret .= '<tr><td colspan="2">'.$sampleTemplateCode.'</td></tr>';
		$ret .= '</table>';

		return $ret;
	}
*/

	// returns a string.
UNUSED	function LoadForm($loadDeep=FALSE)
	{
		$noparms = array();
		return $this->Load($mod,$this->Id,$noparms,$loadDeep);
	}


//BROWSER STUFF

	// Check if FormBrowser field exists
MULTI	function &GetFormBrowserField() SEE ALSO GetFormBrowserField($form_id)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'FormBrowser')
				return $fld;
		}
		unset ($fld);
		// error handling goes here.
		$fld = FALSE; //needed reference
		return $fld;
	}

UNUSED	function GetResponse($form_id,$response_id,$field_list=array(),$dateFmt='d F y')
	{
		$names = array();
		$values = array();
		$db = cmsms()->GetDb();
		$obfield = $this->GetFormBrowserField($form_id);
		if($obfield == FALSE)
		{
			// error handling goes here
			echo($this->Lang('error_no_browser_field'));
		}

		$rs = $db->Execute('SELECT * FROM '.cms_db_prefix().
			'module_pwf_browse WHERE browser_id=?',array($response_id));

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

		$populate_names = TRUE;
		$this->HandleResponseFromXML($obfield,$oneset);
		list($fnames,$aliases,$vals) = $this->ParseResponseXML($oneset->xml);

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

MULTI	function ParseResponseXML($xmlstr,$human_readable_values = TRUE)
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
		return array($names,$aliases,$vals);
	}

ONCE	function ParseResponseXMLType($xmlstr)
	{
		$types = array();
		$xml = new SimpleXMLElement($xmlstr);
		foreach($xml->field as $xmlfield)
		{
			$id = (int)$xmlfield['id'];
			$types[$this->formdata->current_prefix.$id] = (string)$xmlfield['type']; TODO formdata
		}
		return $types;
	}

MULTI	function GetFormBrowserField($form_id) SEE ALSO &GetFormBrowserField()
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_field WHERE form_id=? and type=?';
		$rs = $db->Execute($sql,array($form_id,'DispositionFormBrowser'));
		if(!$rs)
			return FALSE;

		if($rs->RecordCount() == 0)
		{
			$rs->Close();
			return FALSE;
		}

		$thisRes = $rs->GetArray();
		$className = pwfUtils::MakeClassName($thisRes[0]['type']);
		$rs->Close();
		// create the field object
		$noparams = array();
TODO 		$funcs = new pwfFieldOperations($this,$noparams,FALSE);
		$obfield = $funcs->NewField($formdata,$params);//$this,$thisRes[0]);
		return $obfield;
	}

MULTI	function HandleResponseFromXML(&$obfield,&$responseObj)
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
				if($responseObj->xml == FALSE)
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

UNUSED BROWSER FUNC	function GetSortedResponses($form_id,$start_point,$number=100,$admin_approved=FALSE,$user_approved=FALSE,$field_list=array(),$dateFmt='d F y',&$params)
	{
		$db = cmsms()->GetDb();
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
TODO		if(!empty($params['pwfp_NNN_response_search']) && (is_array($params['pwfp_NNN_response_search'])))
		{
			$sql .= ' AND browser_id IN ('. implode(',',$params['pwfp_NNN_response_search']) .')';
		}
		if(isset($params['filter_field']) && substr($params['filter_field'],0,5) =='index')
		{
			$idxfld = intval(substr($params['filter_field'],5));
			$sql .= ' AND index_key_'.$idxfld.'=?';
			$sqlparms[] = $params['filter_value'];
		}
		if(!isset($params['pwfp_NNN_sort_field']) || $params['pwfp_NNN_sort_field']=='submitdate' || empty($params['pwfp_NNN_sort_field']))
		{
			if(isset($params['pwfp_NNN_sort_dir']) && $params['pwfp_NNN_sort_dir'] == 'a')
			{
				$sql .= ' ORDER BY submitted';
			}
			else
			{
				$sql .= ' ORDER BY submitted DESC';
			}
		}
		else if(isset($params['pwfp_NNN_sort_field']))
		{
			if(isset($params['pwfp_NNN_sort_dir']) && $params['pwfp_NNN_sort_dir'] == 'd')
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_NNN_sort_field'].' DESC';
			}
			else
			{
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_NNN_sort_field']; TODO token
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
			$rs = $db->SelectLimit('SELECT * '.$sql,$number,$start_point,$sqlparms);
		}
		else
		{
			$rs = $db->Execute('SELECT * '.$sql,$sqlparms);
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
		if($obfield == FALSE)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$populate_names = TRUE;
		$mapfields = (count($field_list) > 0);
		for ($i=0; $i<count($values); $i++)
		{
			$this->HandleResponseFromXML($obfield,$values[$i]);
			list($fnames,$aliases,$vals) = $this->ParseResponseXML($values[$i]->xml);
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
			$populate_names = FALSE;
		}
		return array($records,$names,$values);
	}


/*			if($loadResp)
			{
				// if it's a stored form,load the results -- but we need to manually merge them,
				// since $params[] should override the database value (say we're resubmitting a form)
TODO				$obfield = $mod->GetFormBrowserField($form_id);
				if($obfield != FALSE)
				{
					// if we're binding to FEU,get the FEU ID,see if there's a response for
					// that user. If so,load it. Otherwise,bring up an empty form.
					if($obfield->GetOption('feu_bind',0))
					{
						$feu = $mod->GetModuleInstance('FrontEndUsers');
						if($feu == FALSE)
						{
							debug_display("FAILED to instatiate FEU!");
							return;
						}
						if(!isset($_COOKIE['cms_admin_user_id']))
						{
TODO							$response_id = pwfDummy:GetResponseIDFromFEUID($feu->LoggedInId(),$form_id);
							if($response_id !== FALSE)
							{
								$check = $db->GetOne('SELECT count(*) FROM '.$pre.
									'module_pwf_browse WHERE browser_id=?',array($response_id));
								if($check == 1)
								{
									$params['response_id'] = $response_id;
								}
							}
						}
					}
				}
				if(isset($params['response_id']))
				{
					$loadParams = array('response_id'=>$params['response_id']);
					$loadTypes = array();
					self::LoadResponseValues($loadParams,$loadTypes);
					foreach($loadParams as $thisParamKey=>$thisParamValue)
					{
						if(!isset($params[$thisParamKey]))
						{
							if($formdata->FormState == 'update' && $loadTypes[$thisParamKey] == 'CheckboxField')
							{
								$params[$thisParamKey] = '';
							}
							else
							{
								$params[$thisParamKey] = $thisParamValue;
							}
						}
					}
				}
			}
*/


	// write records into a flat file
UNUSED 	function WriteSortedResponsesToFile($form_id,$filespec,$striptags=TRUE,$dateFmt='d F y',&$params)
	{
		$db = cmsms()->GetDb();
		$names = array();
		$values = array();
		$sql = 'FROM '.cms_db_prefix().'module_pwf_browse WHERE form_id=?';

		if(!isset($params['pwfp_NNN_sort_field']) || $params['pwfp_NNN_sort_field']=='submitdate' || empty($params['pwfp_NNN_sort_field']))
		{
			if(isset($params['pwfp_NNN_sort_dir']) && $params['pwfp_NNN_sort_dir'] == 'd')TODO token
				$sql .= ' ORDER BY submitted DESC';
			else
				$sql .= ' ORDER BY submitted';
		}
		else if(isset($params['pwfp_NNN_sort_field']))TODO token
		{
			if(isset($params['pwfp_sort_dir']) && $params['pwfp_NNN_sort_dir'] == 'd') TODO token
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_NNN_sort_field'].' DESC'; TODO token
			else
				$sql .= ' ORDER BY index_key_'.(int)$params['pwfp_NNN_sort_field']; TODO token
		}

		$obfield = $this->GetFormBrowserField($form_id);
		if($obfield == FALSE)
		{
			// error handling goes here.
			echo($this->Lang('error_no_browser_field'));
		}

		$fh = fopen($filespec,'w+');
		if($fh === FALSE)
		{
			return FALSE;
		}

		$populate_names = TRUE;
		$rs = $db->Execute('SELECT * '.$sql,array($form_id));
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
				$this->HandleResponseFromXML($obfield,$oneset);
				list($fnames,$aliases,$vals) = $this->ParseResponseXML($oneset->xml);
				if($populate_names)
				{
					if($striptags)
			     	{
						foreach($fnames as $id=>$name)
						{
				        	$fnames[$i] = strip_tags($fnames[$i]);
			        	}
			     	}
					fputs ($fh,$this->Lang('title_submit_date')."\t".
						implode("\t",$fnames)."\n");
					$populate_names = FALSE;
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
		return TRUE;
	}

UNUSED	function GetSortableFields($form_id)
	{
		$parm = array('form_id'=>$form_id);
TODO		$funcs = new pwfDummy($this,$parm,TRUE);
		$obfield = $funcs->GetFormBrowserField();
		if($obfield != FALSE)
		{
			return $obfield->getSortFieldList();
		}
		// error handling goes here
		return array();
	}

ONCE	function GetFEUIDFromResponseID($response_id)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT feuid FROM '.cms_db_prefix().'module_pwf_browse WHERE browser_id=?';
		if($result = $db->GetOne($sql,array($response_id)))
			return $result;
		return -1;
	}

ONCE	function GetResponseIDFromFEUID($feu_id,$form_id=-1)
	{
		$db = cmsms()->GetDb();
		$sql = 'SELECT browser_id FROM '.cms_db_prefix().'module_pwf_browse WHERE feuid=?';
		if($form_id != -1)
			$sql .= ' AND form_id = '.$form_id.' ORDER BY submitted DESC';

		if($result = $db->GetOne($sql,array($feu_id)))
			return $result;
		return FALSE;
 	}

UNUSED	function field_sorter_asc($a,$b)
	{
		return strcasecmp($a->fields[$a->sf],$b->fields[$b->sf]);
	}

UNUSED	function field_sorter_desc($a,$b)
	{
		return strcasecmp($b->fields[$b->sf],$a->fields[$a->sf]);
	}

	// get array of the response objects for a form
UNUSED	function ListResponses($form_id,$sort_order='submitted')
	{
		$ret = array();
		$db = cmsms()->GetDb();
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_resp WHERE form_id=? ORDER BY ?';
		$rs = $db->Execute($query,array($form_id,$sort_order));
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

$flds = "
	resp_id I KEY,
	form_id I,
	feuser_id I,
	user_approved ".CMS_ADODB_DT.",
	secret_code C(35),
	admin_approved ".CMS_ADODB_DT.",
	submitted ".CMS_ADODB_DT;
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_resp',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_resp_seq');

$flds = "
	resp_attr_id I KEY,
	resp_id I,
	name C(35),
	value X
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_resp_attr',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$db->CreateSequence($pre.'module_pwf_resp_attr_seq');

$flds = "
	resp_val_id I KEY,
	resp_id I,
	field_id I,
	value X
";
$sqlarray = $dict->CreateTableSQL($pre.'module_pwf_resp_val',$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$db->CreateSequence($pre.'module_pwf_resp_val_seq');

}

	/**
	StoreResponse:
	Master response saver,used by various field-classes
	@formdata: reference to pwfData object
	@response_id:  default = -1
	@approver:  default = ''
	@disposer: default = NULL
	*/
	public static function StoreResponse(&$formdata,$response_id=-1,$approver='',&$disposer=NULL)
	{
/*		$mod = $formdata->formsmodule;
		$db = cmsms()->GetDb();
		$newrec = FALSE;
		$crypt = FALSE;
		$hash_fields = FALSE;
		$sort_fields = array();

		// Check if form has database fields,do init
/*redundant FormBrowser
		if(is_object($disposer) &&
			$disposer->GetFieldType() == 'FormBrowser')
		{
			$crypt = ($disposer->GetOption('crypt',0));
			$hash_fields = ($disposer->GetOption('hash_sort',0));
			for ($i=0; $i<5; $i++)
				$sort_fields[$i] = $disposer->getSortFieldVal($i+1);
		}
* /
		// If new field
		if($response_id == -1)
		{
			if(is_object($disposer) && $disposer->GetOption('feu_bind',0))
			{
				$feu = $mod->GetModuleInstance('FrontEndUsers');
				if($feu == FALSE)
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
//TODO			$response_id = $db->GenID(cms_db_prefix(). 'module_pwf_browse_seq');
			foreach($formdata->Fields as &$one)
			{
				// set the response_id to be the attribute of the formbrowser disposition
				if($one->GetFieldType() == 'FormBrowser')
					$one->SetValue($response_id);
			}
			unset ($one);
			$newrec = TRUE;
		}
		else
		{
//TODO		$feu_id = $mod->getFEUIDFromResponseID($response_id);
		}

		// convert to XML
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<response form_id=\"".$formdata->Id."\">\n";
		foreach($formdata->Fields as &$one)
			$xml .= $one->ExportXML(TRUE);
		unset($one);
		$xml .= "</response>\n";

		// add
		if(!$crypt)
		{
			$output = self::StoreResponseXML(
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
			list($res,$xml) = self::Crypt($xml,$disposer);
			if(!$res)
				return array(FALSE,$xml);

			$output = self::StoreResponseXML(
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
			list($res,$xml) = self::Crypt($xml,$disposer);
			if(!$res)
				return array(FALSE,$xml);

			$output = self::StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?self::getHashedSortFieldVal($sort_fields[0]):'',
				isset($sort_fields[1])?self::getHashedSortFieldVal($sort_fields[1]):'',
				isset($sort_fields[2])?self::getHashedSortFieldVal($sort_fields[2]):'',
				isset($sort_fields[3])?self::getHashedSortFieldVal($sort_fields[3]):'',
				isset($sort_fields[4])?self::getHashedSortFieldVal($sort_fields[4]):'',
				$feu_id,
				$xml);
		}
		return $output;
*/
	}

	// Insert parsed XML data to database
	private static function StoreResponseXML($response_id=-1,$newrec=FALSE,$approver='',$sortfield1,
	   $sortfield2,$sortfield3,$sortfield4,$sortfield5,$feu_id,$xml)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		$secret_code = '';

		if($newrec)
		{
			// saving a new response
/* TODO save into browser-module table,or just send there via API
			$secret_code = substr(md5(session_id().'_'.time()),0,7);
			$response_id = $db->GenID($pre.'module_pwf_browse_seq');
			$sql = 'INSERT INTO '.$pre.
				'module_pwf_browse (browser_id,form_id,submitted,secret_code,index_key_1,index_key_2,index_key_3,index_key_4,index_key_5,feuid,response) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
				array($response_id,
					$formdata->Id,
					$db->DBTimeStamp(time()),
					$secret_code,
					$sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,
					$feu_id,
					$xml
				));
*/
		}
		else if($approver)
		{
/* TODO save into browser-module table,or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set user_approved=? where browser_id=?';
			$res = $db->Execute($sql,array($db->DBTimeStamp(time()),$response_id));
			$mod = cms_utils::get_module('PowerForms');
			audit(-1,$mod->GetName(),$mod->Lang('user_approved_submission',array($response_id,$approver)));
*/
		}
		if(!$newrec)
		{
/* TODO save into browser-module table,or just send there via API
			$sql = 'UPDATE '.$pre.
				'module_pwf_browse set index_key_1=?,index_key_2=?,index_key_3=?,index_key_4=?,index_key_5=?,response=? where browser_id=?';
			$res = $db->Execute($sql,
				array($sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,$xml,$response_id));
*/
		}
		return array($response_id,$secret_code);
	}

/*	private static function getHashedSortFieldVal($val)
	{
		if(strlen($val) > 4)
			$val = substr($val,0,4). md5(substr($val,4));
		return $val;
	}

	private static function Crypt($string,$dispositionField)
	{
		if($dispositionField->GetOption('crypt_lib') == 'openssl')
		{
			$openssl = $this->GetModuleInstance('OpenSSL');
			if($openssl === FALSE)
			{
				return array(FALSE,$this->Lang('title_install_openssl'));
			}
			$openssl->Reset();
			if(!$openssl->load_certificate($dispositionField->GetOption('crypt_cert')))
			{
				return array(FALSE,$openssl->openssl_errors());
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
		return array(TRUE,$enc);
	}





?>
