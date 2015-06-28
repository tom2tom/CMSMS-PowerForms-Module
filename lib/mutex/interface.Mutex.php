<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

interface pwfMutex
{
	function __construct(&$instance=NULL,$timeout=200,$tries=0);

	function lock($token);

	function unlock($token);

	function reset();
}

?>
