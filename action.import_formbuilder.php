<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess('ModifyPFForms')) exit;

function Match_Browses(&$db,$pre)
{
	$sql = 'SELECT * FROM '.$pre.'module_pwf_trans ORDER BY isform,trans_id';
	$data = $db->GetAssoc($sql);
	if($data)
	{
		/*
		UPDATE form_id IN module_pwbr_browser module_pwbr_record
		UPDATE form_field IN module_pwbr_field
		*/
		$sql = 'UPDATE '.$pre.'module_pwbr_browser SET form_id=? WHERE form_id=?';
		$sql2 = 'UPDATE '.$pre.'module_pwbr_record SET form_id=? WHERE form_id=?';
		$sql3 = 'UPDATE '.$pre.'module_pwbr_field SET form_field=? WHERE form_field=?';
		foreach($data as &$row)
		{
			if($row['isform'])
			{
				$db->Execute($sql,array($row['new_id'],-$row['old_id']));
				$db->Execute($sql2,array($row['new_id'],-$row['old_id']));
			}
			else
				$db->Execute($sql3,array($row['new_id'],-$row['old_id']));
		}
		unset($row);
	}
}

function Get_FieldOpts(&$db,$pre,$oldfid,$newfid,$oldf,$newf,&$fieldrow)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_field_opt WHERE form_id=? AND field_id=? ORDER BY option_id';
	$data = $db->GetArray($sql,array($oldfid,$oldf));
	if($data)
	{
		$extras = array();
		$extras['alias'] = pwfUtils::MakeAlias($fieldrow['name'],24); //length conform to pwfFieldBase::GetVariableName()
		if($fieldrow['hide_label']) $extras['hide_label'] = 1;
		if($fieldrow['required']) $extras['required'] = 1;
		if($fieldrow['validation_type']) $extras['validation_type'] = trim($fieldrow['validation_type']);
		//some field-types simply repeat the same option-name (relying on save-order for any reconciliation!)
		//we are more careful!
		$sequence = in_array($fieldrow['type'],array(
			'PulldownField',
			'RadioGroupField',
			'CheckboxGroupField',
			'MultiselectField',
			'DispositionDirector',
			'DispositionEmail',
			'DispositionEmailBasedFrontendFields',
			'DispositionFileDirector',
			'DispositionMultiselectFileDirector',
			'DispositionPageRedirector'
		));
		if($sequence)
			$desc = '';

		$sql = 'INSERT INTO '.$pre.'module_pwf_field_opt
(option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
		foreach($data as $row)
		{
			$oid = $db->GenID($pre.'module_pwf_field_opt_seq');
			$nm = $row['name'];
			if($sequence)
			{
				if($nm != $desc)
				{
					$desc = $nm;
					$indx = 1;
				}
				else
					$indx++;
				$nm .= $indx;
			}
			$db->Execute($sql,array($oid,$newf,$newfid,$nm,$row['value']));
			//existing option-value prevails over actions-table 'transfer'
			if(isset($extras[$row['name']]))
				$extras[$row['name']] = FALSE;
		}
		foreach($extras as $name=>$value)
		{
			if ($value)
			{
				$oid = $db->GenID($pre.'module_pwf_field_opt_seq');
				$db->Execute($sql,array($oid,$newf,$newfid,$name,$value));
			}
		}
	}
}

function Get_Fields(&$db,$pre,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_field WHERE form_id=? ORDER BY order_by,field_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pre.'module_pwf_field
(field_id,form_id,name,type,order_by) VALUES (?,?,?,?,?)';
		$sql2 = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,0)';
		foreach($data as $row)
		{
			$oldf = (int)$row['field_id'];
			$newf = $db->GenID($pre.'module_pwf_field_seq');
			$db->Execute($sql,array($newf,$newfid,$row['name'],$row['type'],$row['order_by']));
			$db->Execute($sql2,array($oldf,$newf));
			Get_FieldOpts($db,$pre,$oldfid,$newfid,$oldf,$newf,$row);
		}
	}
}

function Get_Opts(&$mod,&$db,$pre,$oldfid,$newfid)
{
	$sql = 'SELECT * FROM '.$pre.'module_fb_form_attr WHERE form_id=? ORDER BY form_attr_id';
	$data = $db->GetArray($sql,array($oldfid));
	if($data)
	{
		$sql = 'INSERT INTO '.$pre.'module_pwf_form_opt
(option_id,form_id,name,value) VALUES (?,?,?,?)';
		foreach($data as $row)
		{
			if($row['name'] == 'form_template')
			{
				$mod->SetTemplate('pwf_'.$newfid,$row['value']);
				$row['value'] = 'pwf_'.$newfid;
			}
			$newopt = $db->GenID($pre.'module_pwf_form_opt_seq');
			$db->Execute($sql,array($newopt,$newfid,$row['name'],$row['value']));
		}
	}
}

if(isset($params['import']))
{
	$pre = cms_db_prefix();
	$db->Execute('DELETE FROM '.$pre.'module_pwf_trans');
	$sql = 'SELECT * FROM '.$pre.'module_fb_form ORDER BY form_id';
	$oldforms = $db->GetArray($sql);
	if($oldforms)
	{
		$funcs = new pwfFormOperations();
		$sql = 'INSERT INTO '.$pre.'module_pwf_form (form_id,name,alias) VALUES (?,?,?)';
		$renums = array();
		foreach($oldforms as $row)
		{
			$fid = $db->GenID($pre.'module_pwf_form_seq');
			$alias = $row['alias'];
			if(!$alias)
				$alias = pwfUtils::MakeAlias();
			$ta = $alias;
			$i = 1;
			while(!$funcs->NewID(FALSE,$alias))
			{
				$alias = $ta."[$i]";
				$i++;
			}
			$db->Execute($sql,array($fid,$row['name'],$alias));
			$renums[$fid] = (int)$row['form_id'];
		}
		$sql = 'INSERT INTO '.$pre.'module_pwf_trans (old_id,new_id,isform) VALUES (?,?,1)';
		foreach($renums as $new=>$old)
		{
			$db->Execute($sql,array($old,$new));
			Get_Opts($this,$db,$pre,$old,$new);
			Get_Fields($db,$pre,$old,$new);
			//data may've already been imported by the browser module
			$rs = $db->SelectLimit('SELECT * FROM '.$pre.'module_pwbr_browser',1);
			if($rs)
			{
				if(!$rs->EOF)
					Match_Browses($db,$pre);
				$rs->Close();
			}
		}
		$this->Redirect($id,'defaultadmin');
	}
	else
		$message = $this->PrettyMessage('no_forms',FALSE);
}
elseif(isset($params['conform']))
{
	//relevant checks are done upstream (method.defaultadmin.php)
	$pre = cms_db_prefix();
	Match_Browses($db,$pre);
	$message = $this->PrettyMessage('browsers_updated');
}
else
	$message = $this->PrettyMessage('error',FALSE);

$this->Redirect($id,'defaultadmin','',array(
	'message'=>$message,'active_tab'=>'import'));

?>
