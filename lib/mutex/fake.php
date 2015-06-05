<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_fake extends MutexBase implements Mutex
{
	var $pause;
	var $maxtries;
	var $faketex = FALSE;

	function __construct($timeout=60,$tries=0)
	{
		$this->pause = $timeout;
		$this->maxtries = $tries;
	}

	function timeout($msec=60)
	{
		$this->pause = $usec;
	}

	function lock($token)
	{
		$count = 0;
		do
		{
			list($was,$this->faketex) = array($this->faketex,$token);
			if($was === $token || $was === FALSE)
				return TRUE;
			$this->faketex = $was;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE;
	}

	function unlock()
	{
		$this->faketex = FALSE;
	}

	function reset()
	{
		$this->faketex = FALSE;
	}
}

?>
