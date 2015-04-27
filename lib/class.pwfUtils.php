<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUtils
{
	/**
	GetForms:
	@orderby: forms-table field name, optional, default 'name'
	Returns: array of all content of the forms-table
	*/
	public static function GetForms($orderby='name')
	{
		// DO NOT parameterise $orderby! If ADODB quotes it, the SQL is not valid
		// instead, rudimentary security checks
		$orderby = preg_replace('/\s/','',$orderby);
		$orderby = preg_replace('/[^\w\-.]/','_',$orderby);
		$sql = 'SELECT * FROM '.cms_db_prefix().'module_pwf_form ORDER BY '.$orderby;
		$db = cmsms()->GetDb();
		return $db->GetArray($sql);
	}

	/**
	MakeClassName:
	@type: 'core' part of class name
	Returns: a namespaced class name, optionally loads the corresponding class file
	*/
	public static function MakeClassName($type)
	{
		// rudimentary security, cuz' $type could come from a form
		$type = preg_replace('/[\W]|\.\./', '_', $type); //TODO
		if(!$type)
			$type = 'Field';
		// prepend our "namespace"
		return 'pwf'.$type;
	}

	/**
	CleanLog:
	@module: reference to PowerTools module object
	@time: timestamp, optional, default = 0
	*/
	public static function CleanLog(&$module,$time = 0)
	{
		if(!$time) $time = time();
		$time -= 86400;
		$db = cmsms()->GetDb();
		$limit = $db->DbTimeStamp($time);
		$db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_ip_log WHERE sent_time<'.$limit);
	}

}

?>
