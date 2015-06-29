<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//for sorting field display-orders
class SortOrdersClosure
{
    private $fields;
	private $orders;

    function __construct(&$fields,&$orders)
	{
        $this->fields = $fields;
        $this->orders = $orders;
    }

    function compare($a,$b)
	{
		$fa = $this->fields[$this->orders[$a]];
		$fb = $this->fields[$this->orders[$b]];
		if($fa->IsDisposition)
		{
			if($fb->IsDisposition)
			{
				if($fb->DisplayInForm) //email confirmation first
					return 1;
				elseif($fa->DisplayInForm)
					return -1;
			}
			elseif(!$fa->DisplayInForm)
				return 1;
		}
		elseif($fb->IsDisposition)
		{
			if(!$fb->DisplayInForm)
				return 1;
		}
		//TODO field type '...start' before corresponding type '...end'
		return $a - $b; //stet current order
    }
}

//for filtering field options
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
	@params: reference to array of parameters,which must include
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
		$formdata = self::Load($mod,$id,$noparms,$form_id);
		if(!$formdata)
			return FALSE;
		foreach($formdata->Fields as &$one)
			$one->Delete();
		unset($one);
*/
		$mod->DeleteTemplate('pwf_'.$form_id);
		$mod->DeleteTemplate('pwf_sub_'.$form_id);
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
		$sql = 'DELETE FROM '.$pre.'module_pwf_form WHERE form_id=?';
		if(!$db->Execute($sql,array($form_id)))
			$res = FALSE;
		return ($res != FALSE);
	}

	/**
	Copy:
	Copy and store entire form
	@mod: reference to the current PowerForms module object
	@id: module id
	@params: reference to array of parameters
	@form_id: enumerator of form to be processed
	Returns: new form id or FALSE
	$params['form_name'] and $params['form_alias'] are set/updated
	*/
	function Copy(&$mod,$id,&$params,$form_id)
	{
		$noparms = array();
		$formdata = self::Load($mod,$id,$noparms,$form_id);
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
		foreach($formdata->Options as $key=>&$val)
		{
			$AttrId = $db->GenID($pre.'module_pwf_form_opt_seq');
			if($key == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$form_id,$val);
				$val = 'pwf_'.$form_id;
			}
			elseif($key == 'submission_template')
			{
				$mod->SetTemplate('pwf_sub_'.$form_id,$val);
				$val = 'pwf_sub_'.$form_id;
			}
			if(!$db->Execute($sql,array($AttrId,$form_id,$key,$val)))
			{
				$params['message'] = $mod->Lang('database_error');
				$res = FALSE;
			}
		}
		unset($val);

		$neworder = 1;
		foreach($formdata->Fields as &$one)
		{
			if(!pwfFieldOperations::CopyField((int)$one->GetId(),$newid,$neworder))
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
	Updates data in tables: form, form_opt, field, field_opt
	 and stores form template as such
	@mod: reference to the current PowerForms module object
	@formdata: reference to form data object
	Returns: boolean T/F indicating success, with $params['message'] set upon failure
	*/
	function Store(&$mod,&$formdata)
	{
		$form_id = $formdata->Id;
		$newform = ($form_id <= 0);
		// if it's a new form, check for duplicate name and/or alias
		if($newform && !self::NewID($formdata->Name,$formdata->Alias))
			return array(FALSE,$mod->Lang('duplicate_identifier'));

		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		if($newform)
		{
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
			return array(FALSE,$mod->Lang('database_error'));

		//store form options
		//TODO incremental instead of junk current then re-store
		if(!$newform)
		{
			$sql = 'DELETE FROM '.$pre.'module_pwf_form_opt WHERE form_id=?';
			if($db->Execute($sql,array($form_id)) == FALSE)
				return array(FALSE,$mod->Lang('database_error'));
		}
		$sql = 'INSERT INTO '.$pre.'module_pwf_form_opt (option_id,form_id,name,value) VALUES (?,?,?,?)';
		foreach($formdata->Options as $key=>$val)
		{
			if($key == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$form_id,$val);
				$val = 'pwf_'.$form_id;
			}
			elseif($key == 'submission_template')
			{
				$mod->SetTemplate('pwf_sub_'.$form_id,$val);
				$val = 'pwf_sub_'.$form_id;
			}
			$newid = $db->GenID($pre.'module_pwf_form_opt_seq');
			if(!$db->Execute($sql,array($newid,$form_id,$key,$val)))
				return array(FALSE,$mod->Lang('database_error'));
		}

		// store fields
		$newfields = array();
		foreach($formdata->Fields as $key=>&$fld)
		{
			$fld->Store(TRUE);
			if($key <= 0) //new field, after save it will include an actual id
				$newfields[$key] = $fld->GetId();
		}
		unset($fld);
		// conform array-keys of new fields
		foreach($newfields as $key=>$newkey)
		{
			$formdata->Fields[$newkey] = $formdata->Fields[$key];
			unset($formdata->Fields[$key]);
		}
		return array(TRUE,'');
	}

	/**
	Load:
	Populates a data object from tables or from suitably-keyed members of @params
	@mod: reference to the current PowerForms module object
	@id: module id
	@params: reference to array of parameters
	@form_id: enumerator of form to be processed
	Returns: reference to a pwfData object for the form, or FALSE
	*/
	function &Load(&$mod,$id,&$params,$form_id)
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
		//some form properties (if absent from $params) default to stored values
		if(empty($params['form_name']))
			$formdata->Name = $row['name'];
		if(empty($params['form_alias']))
			$formdata->Alias = $row['alias'];

		//no form opt value is an array, so no records with same name
		$sql = 'SELECT name,value FROM '.$pre.'module_pwf_form_opt WHERE form_id=?';
		$formdata->Options = $db->GetAssoc($sql,array($form_id));

		$val = $formdata->Options['form_template'];
		$formdata->Options['form_template'] = $mod->GetTemplate($val);
//		$val = $formdata->Options['submission_template'];
//		$formdata->Options['submission_template'] = $mod->GetTemplate($val);

		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE form_id=? ORDER BY order_by';
		$fields = $db->GetArray($sql,array($form_id));
		if($fields)
		{
			foreach($fields as &$row)
			{
				$fid = $row['field_id'];
				// create the field object
				if(isset($params[$formdata->current_prefix.$fid]) ||
					isset($params[$formdata->prior_prefix.$fid]) ||
					isset($params['value_'.$row['name']]) || 
					isset($params['value_fld'.$fid]) ||
					(isset($params['field_id']) && $params['field_id'] == $fid)
				  )
				{
					$row = array_merge($row,$params); //TODO
				}
				$obfield = pwfFieldOperations::NewField($formdata,$id,$row);
				$formdata->Fields[$obfield->Id] = $obfield;
				if($obfield->Type == 'PageBreakField')
					$formdata->PagesCount++;
			}
			unset($row);
		}
		return $formdata;
	}

	/**
	CreateXML:
	@mod: reference to PowerForms module
	@form_id: single form identifier,or array of them
	@date: date string for inclusion in the content
	@charset: optional,name of content encoding,default = FALSE
	@dtd: optional boolean,whether to consruct DTD in file,default TRUE
	Returns: XML string,or FALSE. Included value-data are not bound by
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
						$value = $mod->GetTemplate($value);
/*					elseif($name == 'submission_template')
						$value = $mod->GetTemplate($value);
*/
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
						else//as above,mask potentially-bad content
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
	 Read,parse and check high-level structure of xml file whose path is @xmlfile
	 Returns: xml'ish tree-shaped array (with text encoded as UTF-8),or FALSE
	 form-data are in sub-array(s) keyed as 'form1',... 'form{array['count']}'
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
					$path = explode('][',substr($index,1,-1));
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
				if(strncmp($one,']][[',4) != 0)
					$val = $one;
				else //encoded value
				{
					$val = urldecode(substr($one,4)); //TODO translate numbered fields in templates
					if($name == 'form_template')
					{
						$mod->SetTemplate('pwf_'.$form_id,$val);
						$val = 'pwf_'.$form_id;
					}
					elseif($name == 'submission_template')
					{
						$mod->SetTemplate('pwf_sub_'.$form_id,$val);
						$val = 'pwf_sub_'.$form_id;
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
					if(strncmp($one,']][[',4) != 0)
						$val = $one;
					else //encoded
						$val = urldecode(substr($one,4)); //TODO translate numbered fields in templates
					$db->Execute($sql2,array($option_id,$field_id,$form_id,$name,$val));
				}
				unset($one);
			}
			unset($fld);
		}
		return TRUE;
	}

	/**
	Arrange:
	Ensure reasonable field-order (mostly, non-displayed disposition-fields toward end)
	Updates @fields and @orders
	@fields: reference to a formdata->Fields array
	@orders: reference to array of id's for @fields, ordered by e.g.
		current in-page position, or as-walked
	*/
	function Arrange(&$fields,&$orders)
	{
		$keys = array_keys($orders);
		//old-PHP usort-with-extras
		$soc = new SortOrdersClosure($fields,$orders);
		usort($keys,array($soc,"compare"));
		unset($soc);
/*		usort($keys,
		function($a,$b) use ($fields,$orders) //this syntax is for PHP 5.3+
		{
			$fa = $fields[$orders[$a]];
			$fb = $fields[$orders[$b]];
			if($fa->IsDisposition)
			{
				if($fb->IsDisposition)
				{
					if($fb->Type == 'PageRedirector') //page redirect last
						return -1;
					elseif($fb->DisplayInForm) //email confirmation first
						return 1;
					elseif($fa->Type == 'PageRedirector')
						return 1;
					elseif($fa->DisplayInForm)
						return -1;
				}
				elseif(!$fa->DisplayInForm)//includes $fa->Type == 'PageRedirector'
					return 1;
			}
			elseif($fb->IsDisposition)
			{
				if(!$fb->DisplayInForm)
					return 1;
			}
			//TODO field type '...start' before corresponding type '...end'
			return $a - $b; //stet current order
		});
*/
		// update source-arrays accordingly
		$neworder = array();
		foreach($keys as $val)
			$neworder[] = $orders[$val];
		foreach($neworder as $i=>$val)
		{
			$fields[$val]->SetOrder($i+1);
			$orders[$i] = $val;
		}
	}

	/**
	NewID:
	@name: optional form-name string, default = FALSE
	@alias: optional form-alias string, default = FALSE
	Returns TRUE if there's no form with matching name OR alias
	*/
	function NewID($name=FALSE,$alias=FALSE)
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

	/**
	HasDisposition:
	@formdata: reference to pwfData form data object
	Returns: boolean, TRUE if a disposition field is found among the fields in @formdata
	*/
	function HasDisposition(&$formdata)
	{
		foreach($formdata->Fields as &$one)
		{
			if($one->IsDisposition())
			{
				unset($one);
				return TRUE;
			}
		}
		unset($one);
		return FALSE;
	}

	// $params[] interpreters

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
