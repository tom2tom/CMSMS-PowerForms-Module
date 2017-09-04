<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FormOperations
{
	//for CMSMS 2+
	private static $editors = NULL;

/* disabled pending better template management process in CMSMS2
	private function SetTemplate($type, $id, $val)
	{
		if (self::$editors === NULL) {
			$editors = [];

			$db = \cmsms()->GetDb();
			$pre = \cms_db_prefix();
			$sql = <<<EOS
SELECT G.group_id FROM {$pre}groups G
JOIN {$pre}group_perms GP ON G.group_id = GP.group_id
JOIN {$pre}permissions P on GP.permission_id = P.permission_id
WHERE G.active=1 AND P.permission_name='ModifyPFSettings'
EOS;
			$all = $db->GetCol($sql);
			if ($all) {
				foreach ($all as $id) {
					$editors[] = -$id;
				}
			}
			$sql = <<<EOS
SELECT DISTINCT U.user_id FROM {$pre}users U
JOIN {$pre}user_groups UG ON U.user_id = UG.user_id
JOIN {$pre}group_perms GP ON GP.group_id = UG.group_id
JOIN {$pre}permissions P ON P.permission_id = GP.permission_id
JOIN {$pre}groups GR ON GR.group_id = UG.group_id
WHERE U.admin_access=1 AND U.active=1 AND GR.active=1 AND
P.permission_name='ModifyPFSettings'
EOS;
			$all = $db->GetCol($sql);
			if ($all) {
				foreach ($all as $id) {
					$editors[] = $id;
				}
			}
			self::$editors = $editors;
		}
		$tpl = new \CmsLayoutTemplate();
		$tpl->set_type($type);
		$pref = ($type == 'form') ? 'pwf_':'pwf_sub_';
		$tpl->set_name($pref.$id);
		$tpl->set_owner(1); //original admin user
		if ($this->editors) {
			$tpl->set_additional_editors($this->editors);
		} // !too bad if permissions change? or handle that event ?
		$tpl->set_content($val);
		$tpl->save();
	}
*/
	/**
	Add:
	@mod: reference to the current PWForms module object
	@params: reference to array of parameters,which must include
		'form_name' and preferably also 'form_alias'
	$params['form_name'] and $params['form_alias'] may be set/updated to unique values
	Returns: new form id or FALSE
	*/
	public function Add(&$mod, &$params)
	{
		$name = self::GetName($params);
		if (!$name) {
			return FALSE;
		}
		$alias = self::GetAlias($params);
		if (!$alias) {
			$alias = Utils::MakeAlias($name);
		}
		$tn = $name;
		$ta = $alias;
		$i = 1;
		while (!$this->NewID($name, $alias)) {
			$name = $tn.'('.$i.')';
			$alias = $ta.'_'.$i;
			$i++;
		}
		$params['form_name'] = $name;
		$params['form_alias'] = $alias;
		$pre = \cms_db_prefix();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (name,alias) VALUES (?,?)';
		$db = \cmsms()->GetDb();
		$db->Execute($sql, [$name, $alias]);
		return $db->Insert_ID();
	}

	/**
	Delete:
	@mod: reference to the current PWForms module object
	@form_id: enumerator of form to be processed
	Returns: boolean T/F indicating whether deletion succeeded
	*/
	public function Delete(&$mod, $form_id)
	{
/*		$noparms = array();
		$formdata = self::Load($mod,$form_id,$id,$noparms);
		if (!$formdata)
			return FALSE;
		foreach ($formdata->Fields as &$one)
			$one->Delete();
		unset($one);
*/
		if ($mod->oldtemplates) {
			$mod->DeleteTemplate('pwf_'.$form_id);
			$mod->DeleteTemplate('pwf_sub_'.$form_id);
		} else {
			try {
				$tpl = \CmsLayoutTemplateType::load('pwf_'.$form_id);
				$tpl->delete();
			} catch (Exception $e) {
			}
			try {
				$tpl = \CmsLayoutTemplateType::load('pwf_sub_'.$form_id);
				$tpl->delete();
			} catch (Exception $e) {
			}
		}

		$pre = \cms_db_prefix();
		$sql = 'DELETE FROM '.$pre.'module_pwf_trans WHERE new_id=? AND isform=1';
		$db = \cmsms()->GetDb();
		$db->Execute($sql, [$form_id]);
		$sql = 'DELETE FROM '.$pre.'module_pwf_fieldprops WHERE form_id=?';
		$db->Execute($sql, [$form_id]);
		$res = $db->Affected_Rows() > 0;
		$sql = 'DELETE FROM '.$pre.'module_pwf_field WHERE form_id=?';
		$db->Execute($sql, [$form_id]);
		$res = $res && ($db->Affected_Rows() > 0);
		//no need for longvalue check
		$file = $db->GetOne('SELECT value FROM '.$pre.'module_pwf_formprops WHERE form_id=? AND name=\'css_file\'');
		if ($file) {
			Utils::DeleteUploadFile($mod, $file, $form_id);
		}
		$sql = 'DELETE FROM '.$pre.'module_pwf_formprops WHERE form_id=?';
		$db->Execute($sql, [$form_id]);
		$res = $res && ($db->Affected_Rows() > 0);
		$sql = 'DELETE FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$db->Execute($sql, [$form_id]);
		$res = $res && ($db->Affected_Rows() > 0);

		return $res;
	}

	/**
	Copy:
	Copy and store entire form
	@mod: reference to the current PWForms module object
	@id: module id
	@params: reference to array of parameters
	@form_id: enumerator of form to be processed
	Returns: new form id or FALSE
	$params['form_name'] and $params['form_alias'] are set/updated
	*/
	public function Copy(&$mod, $id, &$params, $form_id)
	{
		$noparms = [];
		$formdata = self::Load($mod, $form_id, $id, $noparms);
		if (!$formdata) {
			return FALSE;
		}
		$tn = $mod->Lang('copy');
		$name = self::GetName($params);
		if (!$name) {
			$name = $formdata->Name;
			if ($name) {
				$name .= ' '.$tn;
			} else {
				return FALSE;
			}
		}
		$alias = self::GetAlias($params);
		if (!$alias) {
			$alias = $formdata->Alias;
			if ($alias) {
				$alias .= '_'.Utils::MakeAlias($tn);
			} else {
				$alias = Utils::MakeAlias($name);
			}
		}
		$tn = $name;
		$ta = $alias;
		$i = 1;
		while (!$this->NewID($name, $alias)) {
			$name = $tn.'('.$i.')';
			$alias = $ta.'_'.$i;
			$i++;
		}
		$params['form_name'] = $name;
		$params['form_alias'] = $alias;

		$pre = \cms_db_prefix();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (name,alias) VALUES (?,?)';
		$db = \cmsms()->GetDb();
		$db->Execute($sql, [$name, $alias]);
		$newfid = $db->Insert_ID();

		$res = TRUE;
		$sql = 'INSERT INTO '.$pre.'module_pwf_formprops (form_id,name,value,longvalue) VALUES (?,?,?,?)';
		foreach ($formdata->XtraProps as $key=>&$val) {
			$lval = NULL;
			if ($key == 'form_template') {
				if ($mod->oldtemplates) {
					$mod->SetTemplate('pwf_'.$newfid, $val);
				} else {
					self::SetTemplate('form', $newfid, $val);
				}
				$val = 'pwf_'.$newfid;
			} elseif ($key == 'submission_template') {
				if ($mod->oldtemplates) {
					$mod->SetTemplate('pwf_sub_'.$newfid, $val);
				} else {
					self::SetTemplate('submission', $newfid, $val);
				}
				$val = 'pwf_sub_'.$newfid;
			} else {
				if (strlen($val) > \PWForms::LENSHORTVAL) {
					$lval = $val;
					$val = NULL;
				}
			}
			$db->Execute($sql, [$newfid, $key, $val, $lval]);
			if ($db->Affected_Rows() > 0) {
				$newid = $db->Insert_ID();
			} else {
				$params['message'] = $mod->Lang('database_error');
				$res = FALSE;
			}
		}
		unset($val);

		$neworder = 1;
		foreach ($formdata->Fields as &$one) {
			if (!FieldOperations::Copy((int)$one->GetId(), $newid, $neworder)) {
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
	@mod: reference to the current PWForms module object
	@formdata: reference to form data object
	@params: reference to array of request-parameters including form/field property-values
	Returns: 2-member array,
	 [0] = boolean indicating success
	 [1] = error message or ''
	*/
	public function Store(&$mod, &$formdata, &$params)
	{
		$form_id = $formdata->Id;
		$newform = ($form_id <= 0);
		//for a new form, check for duplicate name and/or alias
		if ($newform && !$this->NewID($formdata->Name, $formdata->Alias)) {
			return [FALSE,$mod->Lang('duplicate_identifier')];
		}

		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		if ($newform) {
			$sql = 'INSERT INTO '.$pre.'module_pwf_form (name,alias) VALUES (?,?)';
			$db->Execute($sql, [$params['form_Name'], $params['form_Alias']]);
			if ($db->Affected_Rows() < 1) {
				return [FALSE,$mod->Lang('database_error')];
			}
			$form_id = $db->Insert_ID();
		} else {
			$sql = 'UPDATE '.$pre.'module_pwf_form SET name=?,alias=? WHERE form_id=?';
			$db->Execute($sql, [$params['form_Name'], $params['form_Alias'], $form_id]);
			if ($db->Affected_Rows() == -1) {
				return [FALSE,$mod->Lang('database_error')];
			}
		}
		//upsert, sort-of
		$sql = 'UPDATE '.$pre.'module_pwf_formprops
SET value=?,longvalue=? WHERE form_id=? AND name=?';
		$sql2 = 'INSERT INTO '.$pre.'module_pwf_formprops
(form_id,name,value,longvalue) VALUES (?,?,?,?)';
		//store form options
		foreach ($params as $key=>$val) {
			if (strncmp($key, 'fp_', 3) == 0) {
				$key = substr($key, 3);
				$lval = NULL;
				if (($p = strpos($key, '_template')) !== FALSE) {
					$type = substr($key, 0, $p);
					switch ($type) {
						case 'form':
							$name = 'pwf_'.$form_id;
							break;
						case 'submission':
							$name = 'pwf_sub_'.$form_id;
							break;
						default:
							break 2;
					}
					if ($mod->oldtemplates) {
						$mod->SetTemplate($name, $val);
					} elseif ($newform) {
						self::SetTemplate($type, $form_id, $val);
					} else {
						$ob = \CmsLayoutTemplate::load($name);
						$ob->set_content($val);
						$ob->save();
					}
					$val = $name; //record a pointer
				} elseif (strlen($val > \PWForms::LENSHORTVAL)) {
					$lval = $val;
					$val = NULL;
				}

				$db->Execute($sql, [$val, $lval, $form_id, $key]);
				if ($db->Affected_Rows() == -1) {
					$db->Execute($sql2, [$form_id, $key, $val, $lval]);
				}
			}
		}

		self::Arrange($formdata->Fields, $params['form_FieldOrders']);

		//store fields
		$newfields = [];
		foreach ($formdata->Fields as $key=>&$obfld) {
			if ($obfld) {
				$obfld->Store(TRUE);
				if ($key < 0) {
					//new field, after save it will include an actual id
					$newfields[$key] = $obfld->GetId();
				}
			} else { //marked for deletion
				$obfld = new \stdClass();
				$obfld->Id = $key;
				FieldOperations::RealDelete($obfld);
			}
		}
		unset($obfld);

		//conform array-keys of new fields
		foreach ($newfields as $key=>$newkey) {
			$formdata->Fields[$newkey] = $formdata->Fields[$key];
			unset($formdata->Fields[$key]);
		}
		return [TRUE,''];
	}

	/**
	Load:
	Create a FormData object (including all fields) with values populated
	 from database tables and/or from suitably-keyed members of @params.
	 For the latter, @params keys may be
	 $formdata->current_prefix.<FID> or $formdata->prior_prefix.<FID> or
	 'value_'.<FIELDNAME> or 'value_fld'.<FID>
	 where <FID> is the relevant field_id enumerator, <FIELDNAME> is recorded fieldname
	 If present, @params['field_id'] = <FID> generates a field per that id
	@mod: reference to PWForms module object
	@form_id: enumerator of form to be processed
	@id: module id
	@params: reference to array of request-parameters
	@admin: optional boolean whether to load for administration, default FALSE
	Returns: a FormData object, or FALSE
	*/
	public function Load(&$mod, $form_id, $id, &$params, $admin=FALSE)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT name,alias FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$row = Utils::SafeGet($sql, [$form_id], 'row');
		if (!$row) {
			return FALSE;
		}

		$formdata = $mod->_GetFormData($params);
		//some form properties (if absent from $params) default to stored values
		if (empty($params['form_name'])) {
			$formdata->Name = $row['name'];
		}
		if (empty($params['form_alias'])) {
			$formdata->Alias = $row['alias']; //alias used only for admin
		}
		//no form property value is an array, so no records with same name
		$sql = 'SELECT name,value,longvalue FROM '.$pre.'module_pwf_formprops WHERE form_id=?';
		$data = Utils::SafeGet($sql, [$form_id]);
		foreach ($data as $one) {
			$nm = $one['name'];
/*			TODO support arrays when 'name' field like A[B...
			if (strpos($nm,'[') !== FALSE) {
				$parts = explode('[',$nm);
				foreach ($parts as $a) {
					if (!is_array(<pathto>$a)) {
						create it in <pathto>
					}
					process rest of parts as members
				}
			}
*/
			$val = $one['value'];
			if ($val === NULL) {
				$val = $one['longvalue'];
			} //maybe still FALSE
			if (property_exists($formdata, $nm)) {
				$formdata->$nm = $val;
			} else {
				$formdata->XtraProps[$nm] = $val;
			}
		}

		if ($admin) {
			$val = $formdata->XtraProps['form_template'];
			if ($mod->oldtemplates) {
				$tpl = $mod->GetTemplate($val);
			} else {
				$ob = \CmsLayoutTemplate::load($val);
				$tpl = $ob->get_content();
			}

			$formdata->XtraProps['form_template'] = $tpl;
			$val = $formdata->XtraProps['submission_template'];
			if ($val) {
				if ($mod->oldtemplates) {
					$tpl = $mod->GetTemplate($val);
				} else {
					$ob = \CmsLayoutTemplate::load($val);
					$tpl = $ob->get_content();
				}
				$formdata->XtraProps['submission_template'] = $tpl;
			}
		}

		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE form_id=? ORDER BY order_by';
		$fields = Utils::SafeGet($sql, [$form_id]);
		if ($fields) {
			//TODO if (!$admin) { populate sequences }
			foreach ($fields as &$row) {
				$fid = $row['field_id'];
				//TODO ensure data are present for field setup: value etc
				if (isset($params[$formdata->current_prefix.$fid]) ||
					isset($params[$formdata->prior_prefix.$fid]) ||
					isset($params['value_'.$row['name']]) ||
					isset($params['value_fld'.$fid]) ||
					(isset($params['field_id']) && $params['field_id'] == $fid)
				  ) {
					$row = array_merge($row, $params); //make field id/values available
				}
				// create the field object
				$obfld = FieldOperations::Get($formdata, $row);
				if ($obfld) {
					$formdata->Fields[$obfld->Id] = $obfld;
					if ($obfld->Type == 'PageBreak') {
						$formdata->PagesCount++;
					}
				}
			}
			unset($row);
		}
		return $formdata;
	}

	/**
	xml_entities:
	Escape chars in @str which are a problem for the PHP xml parser
	@str: string to be encoded
	See also: replicated function in init/encode-defaultform-templates.php
	Returns: encoded string
	*/
	public function xml_entities($str)
	{
		$nl = urlencode(PHP_EOL);
		return strtr($str, [
		'<' => '%3C',
		'>' => '%3E',
		'"' => '%22',
		"'" => '%27',
		'&' => '%26',
		"\r\n" => $nl,
		"\n" => $nl,
		"\r" => $nl,
		]);
	}

	/**
	xml_entity_decode:
	@str: string to be decoded
	Returns: decoded string
	*/
	public function xml_entity_decode($str)
	{
		return strtr($str, [
		'%3C' => '<',
		'%3E' => '>',
		'%22' => '"',
		'%27' => "'",
		'%26' => '&',
		'%0D%0A' => PHP_EOL,
		'%0A' => PHP_EOL,
		'%0D' => PHP_EOL,
		]);
	}

	/**
	CreateXML:
	@mod: reference to PWForms module
	@formid: single form identifier,or array of them
	@date: date string for inclusion in the content
	@charset: optional,name of content encoding,default = FALSE
	@dtd: optional boolean,whether to consruct DTD in file,default TRUE
	Returns: XML string,or FALSE. Included value-data are not bound by
		<![CDATA[...]]> cuz that mechanism is not nestable. This means that
		values which include javascript may be unbrowsable in a XML-viewer.
	*/
	public function CreateXML(&$mod, $formid, $date, $charset=FALSE, $dtd=TRUE)
	{
		$pre = \cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_form WHERE form_id=?';
		$db = \cmsms()->GetDb();
		if (!is_array($formid)) {
			$properties = $db->GetRow($sql, [$formid]);
			$formid = [$formid];
		} else {
			//use form-properties data from first-found
			foreach ($formid as $one) {
				$properties = $db->GetRow($sql, [$one]);
				if ($properties) {
					break;
				}
			}
		}
		if (!$properties) {
			return FALSE;
		}

		$count = count($formid);
		$t = '<?xml version="1.0" standalone="yes"';
		if ($charset) {
			$t .= ' encoding="'.strtoupper($charset).'"';
		}
		$outxml = [$t.'?>'];
		if ($dtd) {
			$xml[] = <<<'EOS'
<!DOCTYPE powerforms [
<!ELEMENT powerforms (version,date,count,form)>
<!ELEMENT version (#PCDATA)>
<!ELEMENT date (#PCDATA)>
<!ELEMENT count (#PCDATA)>
<!ELEMENT form (properties,fields?)>
<!ELEMENT properties (#PCDATA)>
<!ELEMENT fields (field)>
<!ELEMENT field (properties?)>
]>
EOS;
		}
		$outxml[] = <<<EOS
<powerforms>
\t<version>{$mod->GetVersion()}</version>
\t<date>{$date}</date>
\t<count>{$count}</count>
EOS;
		$sql = 'SELECT name,value,longvalue FROM '.$pre.'module_pwf_formprops WHERE form_id=? ORDER BY name';
		$sql2 = 'SELECT field_id,name,alias,type,order_by FROM '.$pre.'module_pwf_field WHERE form_id=? ORDER BY order_by';
		$sql3 = 'SELECT prop_id,field_id,name,value,longvalue FROM '.$pre.'module_pwf_fieldprops WHERE form_id=? ORDER BY prop_id,name';
		$formpropkeys = array_keys($properties);

		foreach ($formid as $one) {
			$formopts = $db->GetAssoc($sql, [$one]);
			$formfields = $db->GetArray($sql2, [$one]);
			$fieldkeys = ($formfields) ? array_keys($formfields[0]) : [];
			$fieldopts = $db->GetArray($sql3, [$one]);
			$xml = [];
			$xml[] =<<<EOS
\t<form>
\t\t<properties>
EOS;
			foreach ($formpropkeys as $name) {
				$value = $properties[$name];
				if ($value) {
					$value = trim($value);
					if ($value) {
						$value = $this->xml_entities($value);
					}
				}
				$name = $this->xml_entities($name);
				$xml[] = "\t\t\t<$name>".$value."</$name>";
			}
			foreach ($formopts as $name=>$row) {
				$value = $row['value'];
				if ($value === null || $value === '') {
					$value = $row['longvalue'];
					if ($value === null || $value === '') {
						continue;
					}
				}
				if (strpos($name, 'template') !== FALSE) {
					switch ($name) {
					 case 'form_template':
					 case 'submission_template':
						if ($mod->oldtemplates) {
							$value = $mod->GetTemplate($value);
						} else {
							$ob = \CmsLayoutTemplate::load($value);
							$value = $ob->get_content();
						}
//					 default:
						break;
					}
				}
				if ($value) {
					$value = trim($value);
					if ($value) {
						$value = $this->xml_entities($value);
					}
				}
				$name = $this->xml_entities($name);
				$xml[] = "\t\t\t<$name>".$value."</$name>";
			}
			$xml[] =<<<EOS
\t\t</properties>
\t\t<fields>
EOS;
			foreach ($formfields as $thisfield) {
				$xml[] =<<<EOS
\t\t\t<field>
\t\t\t\t<properties>
EOS;
				foreach ($fieldkeys as $name) {
					$value = $thisfield[$name];
					if ($value) {
						$value = trim($value);
						if ($value) {
							$value = $this->xml_entities($value);
						}
					}
					$name = $this->xml_entities($name);
					$xml[] = "\t\t\t\t\t<$name>".$value."</$name>";
				}
				//get $fieldopts[] for this field
//				$myopts = array_filter($fieldopts,array(new IsFieldOption($thisfield['field_id']),'isMine'));
				$fid = $thisfield['field_id'];
				$myopts = array_filter($fieldopts, function ($fieldopt) use ($fid) {
					return $fieldopt['field_id'] == $fid;
				});
				if ($myopts) {
					foreach ($myopts as &$oneopt) {
						$value = $oneopt['value'];
						if ($value === null || $value === '') {
							$value = $oneopt['longvalue'];
							if ($value === null || $value === '') {
								continue;
							}
						}
						$name = $this->xml_entities($oneopt['name']);
						if ($value) {
							$value = trim($value);
							if ($value) {
								$value = $this->xml_entities($value);
							}
						}
						$xml[] = "\t\t\t\t\t<$name>".$value."</$name>";
					}
					unset($oneopt);
				}
				$xml[] =<<<EOS
\t\t\t\t</properties>
\t\t\t</field>
EOS;
			}
			$xml[] =<<<EOS
\t\t</fields>
\t</form>
EOS;
			$outxml[] = implode(PHP_EOL, $xml);
		}
		$outxml[] = '</powerforms>';
		return implode(PHP_EOL, $outxml);
	}

	private function ClearTags(&$array)
	{
		$suff = 1;
		foreach ($array as $indx=>&$val) {
			if (is_array($val)) {
				if (is_numeric($indx)) {
					$key = key(array_slice($val, 0, 1, TRUE));
					array_shift($val);
					if ($key == 'field') {
						$key .= $suff++;
					}
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
	public function ParseXML($xmlfile)
	{
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
//		xml_parser_set_option($parser,XML_OPTION_TARGET_ENCODING,'UTF-8');
		$res = xml_parse_into_struct($parser, file_get_contents($xmlfile), $xmlarray);
		xml_parser_free($parser);
		if ($res === 0) {
			return FALSE;
		}
		if (empty($xmlarray[0]) || empty($xmlarray[0]['tag']) || $xmlarray[0]['tag'] != 'powerforms') {
			return FALSE;
		}
		array_shift($xmlarray); //ignore 'powerforms' tag
		$arrsize = count($xmlarray);
		//migrate $xmlarray to condensed format
		$opened = [];
		$opened[1] = 0;
		for ($j = 0; $j < $arrsize; $j++) {
			$val = $xmlarray[$j];
			switch ($val['type']) {
				case 'open': //start of a new level
					$opened[$val['level']]=0;
				case 'complete': //a single value
					$lvl = $val['level'];
					$index = '';
					for ($i = 1; $i < $lvl; $i++) {
						$index .= '['.$opened[$i].']';
					}
					$path = explode('][', substr($index, 1, -1));
					if ($val['type'] == 'complete') {
						array_pop($path);
					}
					$value = &$array;
					foreach ($path as $segment) {
						$value = &$value[$segment];
					}
					$v = (!empty($val['value'])) ? $val['value'] : NULL; //default value is NULL
					$value[$val['tag']] = $v;
					if ($val['type'] == 'complete' && $lvl > 1) {
						$opened[$lvl-1]++;
					}
					break;
				case 'close': //end of a level
					if ($val['level'] > 1) {
						$opened[$val['level']-1]++;
					}
					unset($opened[$val['level']]);
					break;
			}
		}
		unset($value);
		unset($xmlarray);
		//clear top-level numeric keys and related tags
		$suff = 1;
		foreach ($array as $indx=>&$value) {
			if (is_numeric($indx)) {
				$key = key(array_slice($value, 0, 1, TRUE));
				if ($key == 'form') {
					$key .= $suff++;
				}
				array_shift($value);
				$array[$key] = $value;
				unset($array[$indx]);
			}
		}
		unset($value);
		foreach (['version', 'date', 'count', 'form1'] as $expected) {
			if (!array_key_exists($expected, $array)) {
				return FALSE;
			}
		}
		//and lower-level tags
		self::ClearTags($array);
		$expected = ['properties','fields'];
		foreach ($array['form1'] as $indx=>&$check) {
			if (!in_array($indx, $expected)) {
				unset($check);
				return FALSE;
			}
		}
		unset($check);
		return $array;
	}

	/**
	ImportXML:
	@mod: reference to the current PWForms module object
	@xmlfile: filesystem path of uploaded temp file to be processed
	@formname: optional name for the imported form
	@formalias: optional alias for the imported form
	Returns 2-member array,
	 [0] = boolean T/F indicating success
	 [1] = message reflecting [0]
	*/
	public function ImportXML(&$mod, $xmlfile, $name='', $alias='')
	{
		$data = self::ParseXML($xmlfile);
		if (!$data) {
			return [FALSE, $mod->Lang('err_form_import')];
		}
		$db = \cmsms()->GetDb();
		$pre = \cms_db_prefix();
		$c = $data['count'];
		for ($i=1; $i<=$c; $i++) {
			$fdata = $data['form'.$i];
			$fprops = &$fdata['properties'];
			if (!$name) {
				if ($alias) {
					$name = $alias;
				} else {
					$name = $this->xml_entity_decode($fprops['name']);
				}
			}
			if ($alias) {
				$val = $alias;
			} elseif ($name) {
				$val = Utils::MakeAlias($name);
			} else {
				$val = $fprops['alias'];
			}

			$tn = $name;
			$ta = $val;
			$i = 1;
			while (!$this->NewID($name, $alias)) {
				$name = $tn.'('.$i.')';
				$val = $ta.'_'.$i;
				$i++;
			}

			$sql = 'INSERT INTO '.$pre.'module_pwf_form (name,alias) VALUES (?,?)';
			$db->Execute($sql, [$name, $val]);
			$form_id = $db->Insert_ID();
			unset($fprops['form_id']);
			unset($fprops['name']);
			unset($fprops['alias']);

			$sql = 'INSERT INTO '.$pre.'module_pwf_formprops
(form_id,name,value,longvalue) VALUES (?,?,?,?)';
			foreach ($fprops as $name=>&$one) {
				$val = $this->xml_entity_decode($one); //TODO translate numbered fields in templates
				if ($name == 'form_template') {
					if ($mod->oldtemplates) {
						$mod->SetTemplate('pwf_'.$form_id, $val);
					} else {
						self::SetTemplate('form', $form_id, $val);
					}
					$val = 'pwf_'.$form_id;
				} elseif ($name == 'submission_template') {
					if ($mod->oldtemplates) {
						$mod->SetTemplate('pwf_sub_'.$form_id, $val);
					} else {
						self::SetTemplate('submission', $form_id, $val);
					}
					$val = 'pwf_sub_'.$form_id;
				}
				$args = (strlen($val) <= \PWForms::LENSHORTVAL) ?
					[$form_id,$name,$val,NULL]:
					[$form_id,$name,NULL,$val];
				$db->Execute($sql, $args);
//				$prop_id = $db->Insert_ID();
			}
			unset($one);
			unset($fprops);
			$sql = 'INSERT INTO '.$pre.'module_pwf_field
(form_id,name,alias,type,order_by) VALUES (?,?,?,?,?)';
			$sql2 = 'INSERT INTO '.$pre.'module_pwf_fieldprops
(field_id,form_id,name,value,longvalue) VALUES (?,?,?,?,?)';
			foreach ($fdata['fields'] as &$fld) {
				unset($fld['properties']['field_id']);
				if (isset($fld['properties']['name'])) {
					$name = $this->xml_entity_decode($fld['properties']['name']);
					unset($fld['properties']['name']);
				} else {
					$name = '';
				}
				foreach (['alias', 'type', 'order_by'] as $key) {
					if (isset($fld['properties'][$key])) {
						$$key = $fld['properties'][$key];
						unset($fld['properties'][$key]);
					} else {
						$$key = '';
					}
				}
				$db->Execute($sql, [$form_id, $name, $alias, $type, $order_by]);
				$field_id = $db->Insert_ID();

				foreach ($fld['properties'] as $name=>&$one) {
					$val = $this->xml_entity_decode($one); //TODO translate numbered fields in templates
					$args = (strlen($val) <= \PWForms::LENSHORTVAL) ?
						[$field_id,$form_id,$name,$val,NULL]:
						[$field_id,$form_id,$name,NULL,$val];
					$db->Execute($sql2, $args);
//					$prop_id = $db->Insert_ID();
				}
				unset($one);
			}
			unset($fld);
		}
		return [TRUE, $mod->Lang('form_imported')];
	}

	/**
	Arrange:
	Ensure reasonable field-order (mostly, non-displayed disposition-fields toward end)
	Updates @fields and @orders
	@fields: reference to a formdata->Fields array
	@orders: reference to array of id's for @fields, ordered by e.g.
		current in-page position, or as-walked
	*/
	public function Arrange(&$fields, &$orders)
	{
		$keys = array_keys($orders);
/*		//old-PHP usort-with-extras
		$soc = new SortOrdersClosure($fields,$orders);
		usort($keys,array($soc,'compare'));
		unset($soc);
*/
		usort($keys, function ($a, $b) use ($fields, $orders) {
			$fa = $fields[$orders[$a]];
			$fb = $fields[$orders[$b]];
			if ($fa && $fb) { //neither field is deleted
				if ($fa->IsDisposition) {
					if ($fb->IsDisposition) {
						if ($fb->Type == 'PageRedirector') { //page redirect last
							return -1;
						} elseif ($fb->DisplayInForm) { //email confirmation first
							return 1;
						} elseif ($fa->Type == 'PageRedirector') {
							return 1;
						} elseif ($fa->DisplayInForm) {
							return -1;
						}
					} elseif (!$fa->DisplayInForm) {
						//includes $fa->Type == 'PageRedirector'
						return 1;
					}
				} elseif ($fb->IsDisposition) {
					if (!$fb->DisplayInForm) {
						return 1;
					}
				}
				//TODO field type '...start' before corresponding type '...end'
				return $a - $b; //stet current order
			} elseif ($fa) { //$fb is gone
				return -1;
			} else {
				return 1;
			}
		});
		// update source-arrays accordingly
		$neworder = [];
		foreach ($keys as $val) {
			$neworder[] = (int)$orders[$val];
		}
		foreach ($neworder as $i=>$val) {
			if ($fields[$val]) {
				$fields[$val]->SetOrder($i+1);
				$orders[$i] = $val;
			}
		}
	}

	/**
	NewID:
	@name: optional form-name string, default = FALSE
	@alias: optional form-alias string, default = FALSE
	Returns TRUE if there's no form with matching name OR alias
	*/
	public function NewID($name='', $alias='')
	{
		$where = [];
		$vars = [];

		if ($name) {
			$where[] = 'name=?';
			$vars[] = $name;
		}
		if ($alias) {
			$where[] = 'alias=?';
			$vars[] = $alias;
		}
		if ($where) {
			$pre = \cms_db_prefix();
			$sql = 'SELECT form_id FROM '.$pre.'module_pwf_form WHERE ';
			$sql .= implode(' OR ', $where);
			$db = \cmsms()->GetDb();
			$exists = $db->GetOne($sql, $vars);
			if ($exists) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/* *
	HasDisposition:
	@formdata: reference to FormData form data object
	Returns: boolean, TRUE if a disposition field is found among the fields in @formdata
	*/
/*	public function HasDisposition(&$formdata)
	{
		foreach ($formdata->Fields as &$one) {
			if ($one->IsDisposition()) {
				unset($one);
				return TRUE;
			}
		}
		unset($one);
		return FALSE;
	}
*/
	// $params[] interpreters

	public function GetId(&$params)
	{
		return (isset($params['form_id'])) ? (int)$params['form_id'] : -1;
	}

	public function GetName(&$params)
	{
		return (isset($params['form_name'])) ? trim($params['form_name']) : '';
	}

	public function GetAlias(&$params)
	{
		return (isset($params['form_alias'])) ? trim($params['form_alias']) : '';
	}
}
