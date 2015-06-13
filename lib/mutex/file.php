<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfMutex_file implements pwfMutex
{
	var $pause;
	var $maxtries;
	var $fp; //lock-file path
	var $fh; //file handle

	function __construct($timeout=200,$tries=200)
	{
		$ud = pwfUtils::GetUploadsPath();
		if($ud == FALSE)
			throw new Exception('Error getting file lock');
		if(!function_exists('flock'))
			throw new Exception('Error getting file lock');
		$dir = $ud.DIRECTORY_SEPARATOR.'file_locks';
		if(!file_exists($dir))
		{
			if(!@mkdir($dir) && !file_exists($dir)) 
				throw new Exception('Error getting file lock');
		}
		$this->pause = $timeout;
		$this->maxtries = $tries;
		$name = uniqid('pwf',TRUE);
		$this->fp = $dir.DIRECTORY_SEPARATOR.$name.'.lock';
		touch($this->fp,time());
		//TODO .htaccess for $dir
	}

	function timeout($msec=200)
	{
		$this->pause = $usec;
	}

	function lock($token)
	{
		$this->fh = fopen($this->fp,'r');
		if($this->fh === FALSE)
			throw new Exception("Error opening lock file {$this->fp}");
		$count = 0;
		do
		{
			if(flock($this->fh,LOCK_EX))
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries)
		return FALSE; //failed
	}

	function unlock()
	{
		if(!flock($this->fh,LOCK_UN))
			throw new Exception('Error unlocking mutex');
		fclose($this->fh);
	}

	function reset()
	{
		$this->unlock();
	}
}

?>
