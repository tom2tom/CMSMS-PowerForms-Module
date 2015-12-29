<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_shmop implements iMutex
{
	private $gets;
	private $pause;
	private $maxtries;

	function __construct($config)
	{
		if(!function_exists('shmop_open'))
			throw new Exception('no shared-memory Mutex');

		$this->gets = array();
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 200;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
	}

	function __destruct()
	{
		$this->reset();
	}

	function lock($token)
	{
		$sysid = abs(crc32($token.__FILE__));
		$count = 0;
		do
		{
			if($shmid = @shmop_open($sysid,'n',0660,1))
			{
				if(shmop_write($shmid,'m',0) !== false)
				{
					shmop_close($shmid);
					$this->gets[$token] = $shmid;
					return true;
				}
				shmop_close($shmid);
			}
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return false; //failed
	}

	function unlock($token)
	{
		$sysid = abs(crc32($keyword.__FILE__));
		if($shmid = @shmop_open($sysid,'w',0660,1))
		{
			shmop_delete($shmid); //before closing! NOTE maybe just marked for deletion, at first
			shmop_close($shmid);
			unset($this->gets[$token]);
		}
	}

	function reset()
	{
		foreach($this->gets as $one)
		{
			shmop_delete($one);
			shmop_close($one);
		}
		$this->gets = array();
	}
}

?>
