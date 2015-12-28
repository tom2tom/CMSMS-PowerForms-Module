<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class Mutex_semaphore implements iMutex
{
	public $instance;
	private $pause;
	private $maxtries;

	function __construct($config)
	{
		if($config['instance'])
			$this->instance = $config['instance'];
		else
		{
			if(!function_exists('sem_get')
			 || version_compare(PHP_VERSION,'5.6.1') < 0) //need non-blocking mode
				throw new Exception('No semaphore available');
			$fp = dirname(__FILE__).get_class();
			$key = ftok($fp) % 101 + PHP_INT_MAX / 2;
			$this->instance = sem_get($key);
			if($this->instance === FALSE)
				throw new Exception('Error getting semaphore');
		}
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 200;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
	}

	function lock($token)
	{
		$count = 0;
		do
		{
			if(sem_acquire($this->instance,TRUE)) //non-blocking
				return TRUE;
			usleep($this->pause);
		} while($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE; //failed
	}

	function unlock($token)
	{
		if(!sem_release($this->instance))
			throw new Exception('Error unlocking mutex');
	}

	//as of 2014, "sem_remove() shouldn't be part of a normal cleanup/teardown
	//	and should be called very rarely due to bugs in the implementation"
	function reset()
	{
		$this->unlock('');
	}
}
?>
