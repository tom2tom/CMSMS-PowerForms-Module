<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived from phpFastCache http://www.phpfastcache.com <khoaofgod@gmail.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCache
{
	/**
	Get:
	@module: reference to current PowerForms module object
	@storage: optional cache-type name, one (or more, ','-separated) of
		auto,shmop,apc,memcached,wincache,xcache,memcache,redis,database
		default = 'auto'
	@settings: optional array of general and cache-type-specific parameters,
		(e.g. see default array in this func)
		default empty
	Returns: cache-object $module->cache (after creating it if not already done) or FALSE
	*/
	public static function Get(&$module, $storage = 'auto', $settings = array())
	{
		if($module->cache)
			return $module->cache;

		$config = cmsms()->GetConfig();
		$url = $config['root_url'];
		$settings = array_merge(
			array(
				'memcache' => array(
					array($url,11211,1)
				),
				'redis' => array(
					'host' => $url,
					'port' => '',
					'password' => '',
					'database' => '',
					'timeout' => ''
				),
				'database' => array(
					'table' => cms_db_prefix().'module_pwf_cache'
				)
			), $settings);

		if($storage)
			$storage = strtolower($storage);
		else
			$storage = 'auto';
		if(strpos($storage,'auto') !== FALSE)
			$storage = 'shmop,apc,memcached,wincache,xcache,memcache,redis,database';

		$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
		require($path.'interface.FastCache.php');
		require($path.'FastCacheBase.php');

		$types = explode(',',$storage);
		foreach($types as $one)
		{
			$one = trim($one);
			require($path.$one.'.php');
			$class = 'FastCache_'.$one;
			try
			{
				$ob = new $class($settings);
				$module->cache =& $ob;
				return $module->cache;
			}
			catch(Exception $e) {}
		}
		return NULL;
	}

}

?>
