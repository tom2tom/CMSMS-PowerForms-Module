<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

function Get_Browses(&$db,$pref,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pref.'module_fb_formbrowser WHERE form_id=? ORDER BY fbr_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pref.'module_pwf_browse (
fbr_id,
form_id,
index_key_1,
index_key_2,
index_key_3,
index_key_4,
index_key_5,
feuid,
response,
user_approved,
secret_code,
admin_approved,
submitted) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
		foreach($data as $row)
		{
			$row['form_id'] = $newfid;
			$db->Execute($sql,array_values($row));
		}
	}
}

function Get_Attrs(&$db,$pref,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pref.'module_fb_form_attr WHERE form_id=? ORDER BY form_attr_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pref.'module_pwf_form_attr
(form_attr_id,form_id,name,value)
VALUES (?,?,?,?)';
		foreach($data as $row)
		{
			$newf = $db->GenID($pref.'module_pwf_form_attr_seq'); //CHECKME also need this elsewhere?
			$row['form_attr_id'] = $newf;
			$row['form_id'] = $newfid;
			$db->Execute($sql,array_values($row));
		}
	}
}

function Get_FieldOpts(&$db,$pref,$oldfid,$newfid,$oldf,$newf)
{
	$sql = 'SELECT * FROM '.$pref.'module_fb_field_opt WHERE form_id=? AND field_id=? ORDER BY option_id';
	$data = $db->GetArray($sql,array($oldfid,$oldf));
	if($data)
	{
		$sql = 'INSERT INTO '.$pref.'module_pwf_field_opt
(option_id,field_id,form_id,name,value)
VALUES (?,?,?,?,?)';
		foreach($data as $row)
		{
			$row['option_id'] = $db->GenID($pref.'module_pwf_field_opt_seq');
			$row['field_id'] = $newf;
			$row['form_id'] = $newfid;
			$db->Execute($sql,array_values($row));
		}
	}
}

function Get_Fields(&$db,$pref,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pref.'module_fb_field WHERE form_id=? ORDER BY order_by,field_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pref.'module_pwf_field
(field_id,form_id,name,type,validation_type,required,hide_label,order_by)
VALUES (?,?,?,?,?,?,?,?)';
		foreach($data as $row)
		{
			$oldf = (int)$row['field_id'];
			$newf = $db->GenID($pref.'module_pwf_field_seq');
			$row['field_id'] = $newf;
			$row['form_id'] = $newfid;
			$db->Execute($sql,array_values($row));
			Get_FieldOpts($db,$pref,$oldfid,$newfid,$oldf,$newf);
		}
	}
}

$pref = cms_db_prefix();
$sql = 'SELECT * FROM '.$pref.'module_fb_form ORDER BY form_id';
$oldforms = $db->GetArray($sql);
if($oldforms)
{
	$sql = 'INSERT INTO '.$pref.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
	$renums = array();
	foreach($oldforms as $row)
	{
		$fid = $db->GenID($pref.'module_pwf_form_seq');
		$db->Execute($sql,array($fid,$row['name'],$row['alias']));
		$renums[$fid] = (int)$row['form_id'];
	}
	foreach($renums as $new=>$old)
	{
		Get_Attrs($db,$pref,$old,$new);
		Get_Fields($db,$pref,$old,$new);
		if(!empty($params['importdata']))
		{
			Get_Browses($db,$pref,$old,$new);
			//TODO etc
		}
	}
}

$this->Redirect($id,'defaultadmin');

?>
