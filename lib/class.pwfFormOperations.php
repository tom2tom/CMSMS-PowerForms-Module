<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//for filtering options for a field
class IsFieldOption
{
	private $id;
	function __construct($id)
	{
		$this->id = $id;
	}

	function isMine($fieldopt)
	{
		return $fieldopt['field_id'] == $this->id;
	}
}

class pwfFormOperations
{
	/**
	Add:
	@mod: reference to the current PowerForms module object
	@params: reference to array of parameters, which must include
		'form_name' and preferably also 'form_alias'
	$params['form_name'] and $params['form_alias'] may be set/updated to unique values
	Returns: new form id or FALSE
	*/
	function Add(&$mod,&$params)
	{
		$name = self::GetName($params);
		if(!$name)
			return FALSE;
		$alias = self::GetAlias($params);
		if(!$alias)
			$alias = pwfUtils::MakeAlias($name);
		$tn = $name;
		$ta = $alias;
		$i = 1;
		while(!self::NewID($name,$alias))
		{
			$name = $tn."[$i]";
			$alias = $ta."[$i]";
			$i++;
		}
		$params['form_name'] = $name;
		$params['form_alias'] = $alias;
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
		$newid = $db->GenID($pre.'module_pwf_form_seq');
		$db->Execute($sql,array($newid,$name,$alias));
		return $newid;
	}

	/**
	Delete:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	Returns: boolean TRUE/FALSE whether deletion succeeded
	*/
	function Delete(&$mod,$form_id)
	{
/*		$noparms = array();
		$formdata = self::Load($mod,$form_id,$noparms,TRUE);
		if(!$formdata)
			return FALSE;
		foreach($formdata->Fields as &$one)
			$one->Delete();
		unset($one);
*/
		$mod->DeleteTemplate('pwf_'.$form_id);
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.$pre.'module_pwf_trans WHERE new_id=? AND isform=1';
		$db->Execute($sql,array($form_id));
		$sql = 'DELETE FROM '.$pre.'module_pwf_field_opt WHERE form_id=?';
		$res = $db->Execute($sql,array($form_id));
		$sql = 'DELETE FROM '.$pre.'module_pwf_field WHERE form_id=?';
		if(!$db->Execute($sql,array($form_id)))
			$res = FALSE;
		$sql = 'DELETE FROM '.$pre.'module_pwf_form_opt WHERE form_id=?';
		if(!$db->Execute($sql,array($form_id)))
			$res = FALSE;
		$sql = 'DELETE FROM '. $pre.'module_pwf_form WHERE form_id=?';
		if(!$db->Execute($sql,array($form_id)))
			$res = FALSE;
		return ($res != FALSE);
	}

	/**
	Copy:
	Copy and store entire form
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	Returns: new form id or FALSE
	$params['form_name'] and $params['form_alias'] are set/updated
	*/
	function Copy(&$mod,$form_id,&$params)
	{
		$noparms = array();
		$formdata = self::Load($mod,$form_id,$noparms,TRUE);
		if(!$formdata)
			return FALSE;
		$tn = $mod->Lang('copy');
		$name = self::GetName($params);
		if(!$name)
		{
			$name = $formdata->Name;
			if($name)
				$name .= ' '.$tn;
			else
				return FALSE;
		}
		$alias = self::GetAlias($params);
		if(!$alias)
		{
			$alias = $formdata->Alias;
			if($alias)
				$alias .= '_'.pwfUtils::MakeAlias($tn);
			else
				$alias = pwfUtils::MakeAlias($name);
		}
		$tn = $name;
		$ta = $alias;
		$i = 1;
		while(!self::NewID($name,$alias))
		{
			$name = $tn."[$i]";
			$alias = $ta."[$i]";
			$i++;
		}
		$params['form_name'] = $name;
		$params['form_alias'] = $alias;
		
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
		$newid = $db->GenID($pre.'module_pwf_form_seq');
		$db->Execute($sql,array($newid,$name,$alias));

		$res = TRUE;
		$sql = 'INSERT INTO '.$pre.
		'module_pwf_form_opt (option_id,form_id,name,value) VALUES (?,?,?,?)';
		foreach($formdata->Attrs as $key=>&$one)
		{
			$AttrId = $db->GenID($pre.'module_pwf_form_opt_seq');
			if($key == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$form_id,$one);
				$val = 'pwf_'.$form_id;
			}
			if(!$db->Execute($sql,array($AttrId,$form_id,$key,$one)))
			{
				$params['message'] = $mod->Lang('database_error');
				$res = FALSE;
			}
		}
		unset($one);

		$funcs = new pwfFieldOperations();
		$neworder = 1;
		foreach($formdata->Fields as &$one)
		{
			if(!$funcs->CopyField((int)$one->GetId(),$newid,$neworder))
			{
				$params['message'] = $mod->Lang('database_error');
				$res = FALSE;
			}
			$neworder++;
		}
		unset($one);

		return ($res) ? $newid:FALSE;
	}

	/**
	Store:
	Updates tables: form,form_attr (by junking and re-insertion),field::order_by
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	*/
	function Store(&$mod,$form_id,&$params)
	{
		// if it's a new form,check for duplicate name and/or alias
		if($form_id == -1 && !self::NewID($params['form_name'],$params['form_alias']))
		{
			$params['message'] = $mod->Lang('duplicate_identifier');
			return FALSE;
		}

		$formdata = $mod->GetFormData($params);
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		if($form_id == -1)
		{
			// new form
			$form_id = $db->GenID($pre.'module_pwf_form_seq');
			$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
			$res = $db->Execute($sql,array($form_id,$formdata->Name,$formdata->Alias));
		}
		else
		{
			$sql = 'UPDATE '.$pre.'module_pwf_form SET name=?,alias=? WHERE form_id=?';
			$res = $db->Execute($sql,array($formdata->Name,$formdata->Alias,$form_id));
		}
		if($res == FALSE)
		{
			$params['message'] = $mod->Lang('database_error');
			return FALSE;
		}

		// store form options
		$sql = 'DELETE FROM '.$pre.'module_pwf_form_opt WHERE form_id=?';
		if($db->Execute($sql,array($form_id)) == FALSE)
		{
			$params['message'] = $mod->Lang('database_error');
			return FALSE;
		}

		$sql = 'INSERT INTO '.$pre.'module_pwf_form_opt (option_id,form_id,name,value) VALUES (?,?,?,?)';
		foreach($formdata->Attrs as $key=>$val)
		{
			$AttrId = $db->GenID($pre.'module_pwf_form_opt_seq');
			if($key == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$form_id,$val);
				$val = 'pwf_'.$form_id;
			}
			if(!$db->Execute($sql,array($AttrId,$form_id,$key,$val)))
			{
				$params['message'] = $mod->Lang('database_error');
				return FALSE;
			}
		}

		// Update field position
		//TODO all dispositions after all others
		if(isset($params['sort_order']))
			$order_list = explode(',',$params['sort_order']);
		else
			$order_list = FALSE;

		if($order_list)
		{
			$count = 1;
			$sql = 'UPDATE '.$pre.'module_pwf_field SET order_by=? WHERE field_id=?';

			foreach($order_list as $onefldid)
			{
				$fieldid = substr($onefldid,5); //CHECKME
				if($db->Execute($sql,array($count,$fieldid)))
					$count++;
				else
				{
					$params['message'] = $mod->Lang('database_error');
					return FALSE;
				}
			}
		}

		// Reload everything
		self::Load($mod,$form_id,$params,TRUE);
		return TRUE;
	}

	/**
	Load:
	@mod: reference to the current PowerForms module object
	@form_id: enumerator of form to be processed
	@params: reference to array of parameters
	@deep: optional whether to also load field data, default FALSE
	@loadResp: optional boolean, default FALSE
	Returns: reference to pwfData object for the form, or FALSE
	*/
	function &Load(&$mod,$form_id,&$params,$deep=FALSE,$loadResp=FALSE)
	{
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$row = $db->GetRow($sql,array($form_id));
		if(!$row)
		{
			$ret = FALSE;
			return $ret;
		}
		
		$formdata = $mod->GetFormData($params);
		$formdata->Id = $row['form_id'];
		//$params (if present) override stored values
		if(empty($params['form_name']))
			$formdata->Name = $row['name'];
		if(empty($params['form_alias']))
			$formdata->Alias = $row['alias'];

		$sql = 'SELECT name,value FROM '.$pre.'module_pwf_form_opt WHERE form_id=?';
		$formdata->Attrs = $db->GetAssoc($sql,array($form_id));
		$formdata->loaded = 'summary';

/*		if(isset($params['response_id']))
		{
			$deep = TRUE;
			$loadResp = TRUE;
		}
*/
		if($deep)
		{
/*			if($loadResp)
			{
				// if it's a stored form,load the results -- but we need to manually merge them,
				// since $params[] should override the database value (say we're resubmitting a form)
TODO				$obfield = $mod->GetFormBrowserField($form_id);
				if($obfield != FALSE)
				{
					// if we're binding to FEU,get the FEU ID,see if there's a response for
					// that user. If so,load it. Otherwise,bring up an empty form.
					if($obfield->GetOption('feu_bind','0')=='1')
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
			$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE form_id=? ORDER BY order_by';
			$fields = $db->GetArray($sql,array($form_id));
			if($fields)
			{
				$funcs = new pwfFieldOperations();
				foreach($fields as &$row)
				{
//					error_log("Instantiating Field. usage ".memory_get_usage());
//					$className = pwfUtils::MakeClassName($row['type']);
					$fid = $row['field_id'];
					// create the field object
					if((isset($params['pwfp__'.$fid]) || isset($params['pwfp___'.$fid])) ||
						isset($params['value_'.$row['name']]) || 
						isset($params['value_fld'.$fid]) ||
						(isset($params['field_id']) && $params['field_id'] == $fid)
					  )
					{
						$row = array_merge($row,$params); //TODO
					}
					$obfield = $funcs->NewField($formdata,$row);
					$formdata->Fields[] = $obfield;
					if($obfield->Type == 'PageBreakField')
						$formdata->FormPagesCount++;
				}
				unset($row);
			}
			$formdata->loaded = 'full';
		} //end of $deep

		return $formdata;
	}

	/**
	CreateXML:
	@mod: reference to PowerForms module
	@form_id: single form identifier, or array of them
	@date: date string for inclusion in the content
	@charset: optional, name of content encoding, default = FALSE
	@dtd: optional boolean, whether to consruct DTD in file, default TRUE
	Returns: XML string, or FALSE. Included value-data are not bound by
		<![CDATA[...]]> cuz that mechanism is not nestable. This means that
		values which include javascript may be unbrowsable in a XML-viewer.		
	*/
	function CreateXML(&$mod,$form_id,$date,$charset=FALSE,$dtd=TRUE)
	{
		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_form WHERE form_id=?';
		if (!is_array($form_id))
		{
			$properties = $db->GetRow($sql,array($form_id));
			$form_id = array($form_id);
		}
		else
		{
			//use form-properties data from first-found
			foreach($form_id as $one)
			{
				$properties = $db->GetRow($sql,array($one));
				if($properties != FALSE)
					break;
			}
		}
		if($properties == FALSE)
			return FALSE;

		$outxml = array();
		$t = '<?xml version="1.0" standalone="yes"';
		if($charset)
			$t .= ' encoding="'.strtoupper($charset).'"';
		$outxml[] = $t.'?>';
		if($dtd)
		{
			$xml[] = <<<EOS
<!DOCTYPE powerforms [
<!ELEMENT powerforms (version,date,count,form)>
<!ELEMENT version (#PCDATA)>
<!ELEMENT date (#PCDATA)>
<!ELEMENT count (#PCDATA)>
<!ELEMENT form (properties,options?,fields?)>
<!ELEMENT properties (#PCDATA)>
<!ELEMENT options (#PCDATA)>
<!ELEMENT fields (field)>
<!ELEMENT field (properties,options?)>
]>
EOS;
		}
		$count = (is_array($form_id))?count($form_id):1;
		$outxml[] = <<<EOS
<powerforms>
\t<version>{$mod->GetVersion()}</version>
\t<date>{$date}</date>
\t<count>{$count}</count>
EOS;
		$sql = 'SELECT name,value FROM '.$pre.'module_pwf_form_opt WHERE form_id=? ORDER BY name';
		$sql2 = 'SELECT field_id,name,type,order_by FROM '.$pre.'module_pwf_field WHERE form_id=? ORDER BY order_by';
		$sql3 = 'SELECT option_id,field_id,name,value FROM '.$pre.'module_pwf_field_opt WHERE form_id=? ORDER BY option_id,name';
		$formpropkeys = array_keys($properties);

		foreach($form_id as $one)
		{
			$formopts = $db->GetAssoc($sql,array($one));
			$formfields = $db->GetAll($sql2,array($one));
			$fieldkeys = ($formfields) ? array_keys($formfields[0]) : array();
			$fieldopts = $db->GetArray($sql3,array($one));
			$xml = array();
			$xml[] =<<<EOS
\t<form>
\t\t<properties>
EOS;
			foreach($formpropkeys as $onekey)
				$xml[] = "\t\t\t<$onekey>".$properties[$onekey]."</$onekey>";
			$xml[] =<<<EOS
\t\t</properties>
\t\t<options>
EOS;
			foreach($formopts as $name=>$value)
			{
				if($value === '') continue;
				if(strpos($name,'template') === FALSE)
					$xml[] = "\t\t\t<$name>".trim($value)."</$name>";
				else//smarty syntax can abort the xml-decoder - so mask it
				{
					if($name == 'form_template')
						$value = $mod->GetTemplate('pwf_'.$form_id);
					$xml[] = "\t\t\t<$name>]][[".urlencode(trim($value))."</$name>";
				}
			}
			$xml[] =<<<EOS
\t\t</options>
\t\t<fields>
EOS;
			foreach($formfields as $thisfield)
			{
				$xml[] =<<<EOS
\t\t\t<field>
\t\t\t\t<properties>
EOS;
				foreach($fieldkeys as $onekey)
					$xml[] = "\t\t\t\t\t<$onekey>".$thisfield[$onekey]."</$onekey>";
				$xml[] =<<<EOS
\t\t\t\t</properties>
\t\t\t\t<options>
EOS;
				//get $fieldopts[] for this field
				$myopts = array_filter($fieldopts,array(new IsFieldOption($thisfield['field_id']),'isMine'));
				if($myopts)
				{
					foreach($myopts as &$oneopt)
					{
						if($oneopt['value'] === '') continue;
						$name = $oneopt['name'];
						if(strpos($name,'template') === FALSE)
							$xml[] = "\t\t\t\t\t<$name>".trim($oneopt['value'])."</$name>";
						else//as above, mask potentially-bad content
							$xml[] = "\t\t\t\t\t<$name>]][[".urlencode(trim($oneopt['value']))."</$name>";
					}
					unset($oneopt);
				}
			$xml[] =<<<EOS
\t\t\t\t</options>
\t\t\t</field>
EOS;
			}
			$xml[] =<<<EOS
\t\t</fields>
\t</form>
EOS;
			$outxml[] = implode("\n",$xml);
		}
		$outxml[] = '</powerforms>';
		return implode("\n",$outxml);
	}

	private function ClearTags(&$array)
	{
		$suff = 1;
		foreach($array as $indx=>&$val)
		{
			if(is_array($val))
			{
				if(is_numeric($indx))
				{
					$key = key(array_slice($val,0,1,TRUE));
					array_shift($val);
					if($key == 'field')
						$key .= $suff++;
					$array[$key] = $val;
					unset($array[$indx]);
				}
				self::ClearTags($val); //recurse
			}
		}
		unset($val);
	 }

	/**
	 ParseXML:
	 @xmlfile: filepath of xml file to be processed
	 Read, parse and check high-level structure of xml file whose path is @xmlfile
	 Returns: xml'ish tree-shaped array (with text encoded as UTF-8), or FALSE
	 form-data are in sub-array(s) keyed as 'form1', ... 'form{array['count']}'
	*/
	function ParseXML($xmlfile)
	{
		$parser = xml_parser_create();
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,1);
//		xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,'UTF-8');
		$res = xml_parse_into_struct($parser,file_get_contents($xmlfile),$xmlarray);
		xml_parser_free($parser);
		if ($res === 0)
			return FALSE;
		if (empty($xmlarray[0]) || empty($xmlarray[0]['tag']) || $xmlarray[0]['tag'] != 'powerforms')
			return FALSE;
		array_shift($xmlarray); //ignore 'powerforms' tag
		$arrsize = count($xmlarray);
		//migrate $xmlarray to condensed format
		$opened = array();
		$opened[1] = 0;
		for($j = 0; $j < $arrsize; $j++)
		{
			$val = $xmlarray[$j];
			switch($val['type'])
			{
				case 'open': //start of a new level
					$opened[$val['level']]=0;
				case 'complete': //a single value
					$lvl = $val['level'];
					$index = '';
					for($i = 1; $i < $lvl; $i++)
						$index .= '['.$opened[$i].']';
					$path = explode('][', substr($index, 1, -1));
					if($val['type'] == 'complete')
						array_pop($path);
					$value = &$array;
					foreach($path as $segment)
						$value = &$value[$segment];
					$v = (!empty($val['value'])) ? $val['value'] : null; //default value is null
					$value[$val['tag']] = $v;
					if($val['type'] == 'complete' && $lvl > 1)
						$opened[$lvl-1]++;
					break;
				case 'close': //end of a level
					if ($val['level'] > 1)
						$opened[$val['level']-1]++;
					unset($opened[$val['level']]);
					break;
			}
		}
		unset($value);
		//clear top-level numeric keys and related tags
		$suff = 1;
		foreach($array as $indx=>&$value)
		{
			if (is_numeric($indx))
			{
				$key = key(array_slice($value,0,1,TRUE));
				if($key == 'form')
					$key .= $suff++;
				array_shift($value);
				$array[$key] = $value;
				unset($array[$indx]);
			}
		}
		unset($value);
		foreach(array('version','date','count','form1') as $expected)
		{
			if (!array_key_exists($expected,$array))
				return FALSE;
		}
		//and lower-level tags
		self::ClearTags($array);
		$expected = array('properties','options','fields');	
		foreach ($array['form1'] as $indx=>&$check)
		{
			if (!in_array($indx,$expected))
			{
				unset($check);
				return FALSE;
			}
		}
		unset($check);
		return $array;
	}

//====================
/*	private function inXML(&$var)
	{
		return (isset($var) && strlen($var) > 0);
	}
*/
	/**
	ImportXML:
	@mod: reference to the current PowerForms module object
	@xmlfile:
	Returns boolean T/F
	*/
	function ImportXML(&$mod,$xmlfile)
	{
		$data = self::ParseXML($xmlfile);
		if($data == FALSE)
			return FALSE;
		//? check $data['version'], $data['date']
/*$data = array (size>=4)
  'version' => string '0.7' (length=3)
  'date' => null
  'count' => string '1' (length=1)
  'form1' => 
    array (size=3)
      'properties' => 
        array (size=3)
          'form_id' => string '1' (length=1)
          'name' => string 'Sample Form' (length=11)
          'alias' => string 'sample_form' (length=11)
      'options' => 
        array (size=17)
          'captcha_wrong' => string 'The entered text was not correct' (length=32)
          'css_class' => string 'PowerFormsform' (length=14)
          'form_displaytype' => string 'tab' (length=3)
          'form_template' => string '%7B%2A+DEFAULT+FORM+LAYOUT+%2F+pure+CSS+%2A%7D%0A%7Bif+%24form_done%7D%0A%09%7B%2A+This+section+is+for+displaying+submission-errors+%2A%7D%0A%09%7Bif+%24submission_error%7D%0A%09%09%3Cdiv+class%3D%22error_message%22%3E%7B%24submission_error%7D%3C%2Fdiv%3E%0A%09%09%7Bif+%24show_submission_errors%7D%0A%09%09%09%3Cdiv+class%3D%22error%22%3E%0A%09%09%09%3Cul%3E%0A%09%09%09%7Bforeach+from%3D%24submission_error_list+item%3Done%7D%0A%09%09%09%09%3Cli%3E%7B%24one%7D%3C%2Fli%3E%0A%09%09%09%7B%2Fforeach%7D%0A%09%09%0'... (length=3531)
          'inline' => string '1' (length=1)
          'list_delimiter' => string '-' (length=1)
          'next_button_text' => string 'Continue...' (length=11)
          'prev_button_text' => string 'Back...' (length=7)
          'redirect_page' => string '-1' (length=2)
          'required_field_symbol' => string '*' (length=1)
          'submission_template' => string '%3Ch1%3EThanks%21%3C%2Fh1%3E%0A%3Cp%3EYour+feedback+helps+make+the+PowerForms+module+better.%3C%2Fp%3E' (length=102)
          'submit_action' => string 'text' (length=4)
          'submit_button_text' => string 'Send Feedback' (length=13)
          'title_position' => string 'left' (length=4)
          'title_user_captcha' => string 'Help to prevent abuse by spammers, enter the text from the image' (length=64)
          'unspecified' => string '[unspecified]' (length=13)
          'use_captcha' => string '1' (length=1)
      'fields' => 
        array (size=8)
          'field1' => 
            array (size=2)
              ...*/
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		for($i=1; $i<=$data['count']; $i++)
		{
			$fdata = $data['form'.$i];
			$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
			$form_id = $db->GenID($pre.'module_pwf_form_seq');
			$db->Execute($sql,array($form_id,
				$fdata['properties']['name'],
				$fdata['properties']['alias']));
			$sql = 'INSERT INTO '.$pre.'module_pwf_form_opt (option_id,form_id,name,value) VALUES (?,?,?,?)';
			foreach($fdata['options'] as $name=>&$one)
			{
				$option_id = $db->GenID($pre.'module_pwf_form_opt_seq');
				if(substr($one,0,4) != ']][[')
					$val = $one;
				else
				{
					$val = substr($one,4);
					$val = urldecode($val); //TODO translate numbered fields in templates
					if($name == 'form_template')
					{
						$mod->SetTemplate('pwf_'.$form_id,$val);
						$val = 'pwf_'.$form_id;
					}
				}
				$db->Execute($sql,array($option_id,$form_id,$name,$val));
			}
			unset($one);
			$sql = 'INSERT INTO '.$pre.'module_pwf_field (
field_id,form_id,name,type,order_by) VALUES (?,?,?,?,?)';
			foreach($fdata['fields'] as &$fld)
			{
				$field_id = $db->GenID($pre.'module_pwf_field_seq');
				$db->Execute($sql,array($field_id,$form_id,
					$fld['properties']['name'],
					$fld['properties']['type'],
					$fld['properties']['order_by']));
				$sql2 = 'INSERT INTO '.$pre.'module_pwf_field_opt (
option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
				foreach($fld['options'] as $name=>&$one)
				{
					$option_id = $db->GenID($pre.'module_pwf_field_opt_seq');
					if(substr($one,0,4) != ']][[')
						$val = $one;
					else
						$val = urldecode(substr($one,4)); //TODO translate numbered fields in templates
					$db->Execute($sql2,array($option_id,$field_id,$form_id,$name,$val));
				}
				unset($one);
			}
			unset($fld);
		}
		return TRUE;
/*
		$params['form_id'] = -1; // override any form_id values that may be around
		$formAttrs = &$elements[0]['attributes'];

		$formdata = $mod->GetFormData($params);

		if(!empty($params['import_formalias']))
			$formdata->Alias = $params['import_formalias'];
		else if(self::inXML($formAttrs['alias']))
			$formdata->Alias = $formAttrs['alias'];

		if(!empty($params['import_formname']))
			$formdata->Name = $params['import_formname'];

		$foundfields = FALSE;
		// populate the attributes and field name first. When we see a field,we save the form and then start adding the fields to it.

		foreach($elements[0]['children'] as $thisChild)
		{
			if($thisChild['name'] == 'form_name')
			{
				$curname = self::GetName($params);
				if(empty($curname))
					$formdata->Name = $thisChild['content'];
			}
			elseif($thisChild['name'] == 'attribute')
			{
				$formdata->Attrs[$thisChild['attributes']['key']] =  $thisChild['content'];
			}
			else
			{
				// we got us a field
				if(!$foundfields)
				{
					// first field
					$foundfields = TRUE;
					if(isset($params['import_formname']) &&
					   trim($params['import_formname']) != '')
						$formdata->Name = trim($params['import_formname']);

					if(isset($params['import_formalias']) &&
					   trim($params['import_formname']) != '')
						$formdata->Alias = trim($params['import_formalias']);

					self::Store($mod,$params['form_id'],$params);
				}
//				debug_display($thisChild);
				$fieldAttrs = &$thisChild['attributes'];
				$className = pwfUtils::MakeClassName($fieldAttrs['type']);
//				debug_display($className);
				$newField = new $className($formdata,$params);
				$oldId = $fieldAttrs['id'];

				if(self::inXML($fieldAttrs['alias']))
				{
					$newField->SetAlias($fieldAttrs['alias']);
				}
				$newField->SetValidationType($fieldAttrs['validation_type']);
				if(self::inXML($fieldAttrs['order_by']))
				{
					$newField->SetOrder($fieldAttrs['order_by']);
				}
				if(self::inXML($fieldAttrs['required']))
				{
					$newField->SetRequired($fieldAttrs['required']);
				}
				if(self::inXML($fieldAttrs['hide_label']))
				{
					$newField->SetHideLabel($fieldAttrs['hide_label']);
				}
				foreach($thisChild['children'] as $thisOpt)
				{
					if($thisOpt['name'] == 'field_name')
					{
						$newField->SetName($thisOpt['content']);
					}
					if($thisOpt['name'] == 'options')
					{
						foreach($thisOpt['children'] as $thisOption)
						{
							$newField->OptionFromXML($thisOption);
						}
					}
				}
				$newField->Store(TRUE);
				$formdata->Fields[] = $newField;
				$fieldMap[$oldId] = $newField->GetId();
			}
		}

		// clean up references
		if(!empty($params['xml_file']))
		{
			// need to update mappings in templates.
			$tmp = self::UpdateRefs(pwfUtils::GetAttr($formdata,'form_template',''),$fieldMap);
			$formdata->Attrs['form_template'] = $tmp;
			$tmp = self::UpdateRefs(pwfUtils::GetAttr($formdata,'submission_template',''),$fieldMap);
			$formdata->Attrs['submission_template'] = $tmp;

			// need to update mappings in field templates.
			$options = array('email_template','file_template');
			foreach($formdata->Fields as &$fld)
			{
				$changes = FALSE;
				foreach($options as $to)
				{
					$templ = $fld->GetOption($to,'');
					if(!empty($templ))
					{
						$tmp = self::UpdateRefs($templ,$fieldMap);
						$fld->SetOption($to,$tmp);
						$changes = TRUE;
					}
				}
				// need to update mappings in FormBrowser sort fields
				if($fld->GetFieldType() == 'DispositionFormBrowser')
				{
					for ($i=1;$i<6;$i++)
					{
						$old = $fld->GetOption('sortfield'.$i);
						if(isset($fieldMap[$old]))
						{
							$fld->SetOption('sortfield'.$i,$fieldMap[$old]);
							$changes = TRUE;
						}
					}
				}
				if($changes)
				{
					$fld->Store(TRUE);
				}
			}
			unset ($fld);

			self::Store($mod,$params['form_id'],$params);
		}

		return TRUE;
*/
	}

/*	private function UpdateRefs($text,&$fieldMap)
	{
		foreach($fieldMap as $k=>$v)
			$text = preg_replace('/([\{\b\s])\$fld_'.$k.'([\}\b\s])/','$1\$fld_'.$v.'$2',$text);
		return $text;
	}
*/
	//returns TRUE if a form with matching name OR alias does NOT exist
	function NewID($name = FALSE,$alias = FALSE)
	{
		$where = array();
		$vars = array();

		if($name)
		{
			$where[] = 'name=?';
			$vars[] = $name;
		}
		if($alias)
		{
			$where[] = 'alias=?';
			$vars[] = $alias;
		}
		if($where)
		{
			$db = cmsms()->GetDb();
			$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_pwf_form WHERE ';
			$sql .= implode(' OR ',$where);
			$exists = $db->GetOne($sql,$vars);
			if($exists)
				return FALSE;
		}
		return TRUE;
	}

	function GetId(&$params)
	{
		return (isset($params['form_id'])) ? (int)$params['form_id'] : -1;
	}

	function GetName(&$params)
	{
		return (isset($params['form_name'])) ? trim($params['form_name']) : '';
	}

	function GetAlias(&$params)
	{
		return (isset($params['form_alias'])) ? trim($params['form_alias']) : '';
	}

}

?>
