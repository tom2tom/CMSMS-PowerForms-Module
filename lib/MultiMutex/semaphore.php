<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace MultiMutex;

class Mutex_semaphore implements iMutex
{
	private $keybase;
	private $gets;
	private $pause;
	private $maxtries;

	public function __construct($config)
	{
		if (!function_exists('sem_get')
		 || version_compare(PHP_VERSION,'5.6.1') < 0) //need non-blocking mode
			throw new \Exception('No semaphore available');
		$this->keybase = PHP_INT_MAX / 2 + 1101;
		$this->gets = array();
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 200;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
	}

	public function __destruct()
	{
		$this->reset();
	}

	public function hash($token)
	{
		//djb2 hash : see http://www.cse.yorku.ca/~oz/hash.html
		$l = strlen($token);
		$hash = 5381;
		for ($i = 0; $i < $l; $i++)
			$hash = $hash * 33 + $token[$i] + $i;
		return $hash % 1011011; //limit the offset
	}

	public function lock($token)
	{
		if (isset($this->gets[$token]))
			$res = $this->gets[$token];
		else {
			$key = $this->keybase + $this->hash(''.$token);
			$res = sem_get($key,1,0660,0); //preserve past end-of-request == LEAK ?
			if (!$res)
				return FALSE;
			$this->gets[$token] = $res;
		}
		$count = 0;
		do {
			if (sem_acquire($res,TRUE)) //non-blocking
				return TRUE;
			usleep($this->pause);
		} while ($this->maxtries == 0 || $count++ < $this->maxtries);
		return FALSE; //failed
	}

	public function unlock($token)
	{
		if (isset($this->gets[$token])) {
			sem_release($this->gets[$token]);
			unset($this->gets[$token]);
		}
	}

	//as of 2014, "sem_remove() shouldn't be part of a normal cleanup/teardown
	//	and should be called very rarely due to bugs in the implementation"
	public function reset()
	{
		foreach ($this->gets as $one)
			sem_release($one);
		$this->gets = array();
	}
}
