<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if (!empty($params['sel']))
{
	if(isset($params['clone']))
	{
		if(!$this->CheckAccess('ModifyPFForms')) exit;
		foreach ($params['sel'] as $rid)
		{
			//do stuff
		}
	}
	elseif(isset($params['delete']))
	{
		if(!$this->CheckAccess('ModifyPFForms')) exit;
		foreach ($params['sel'] as $rid)
		{
			//do stuff
		}
	}
	elseif(isset($params['export']))
	{
		foreach ($params['sel'] as $rid)
		{
			//do stuff
		}
		return;
	}
}

$this->Redirect($id,'defaultadmin');

?>
