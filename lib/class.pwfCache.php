<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived from phpFastCache http://www.phpfastcache.com <khoaofgod@gmail.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfCache
{
	/**
	Returns cache-object $module->cache, after creating it if not already done
	@module: reference to current PowerForms module object
	@storage: optional cache-type name, one (or more, ','-separated) of
		auto,shmop,apc,memcached,wincache,xcache,memcache,redis,database
		default = 'auto'
	@settings: optional array of general and cache-type-specific parameters,
		(e.g. see default array in this func)
		default empty
	*/
	function __construct(&$module, $storage = 'auto', $settings = array())
	{
		if(!$module->cache)
		{
			if($storage)
				$settings['storage'] = $storage;

			if (isset($settings['storage']
			{
				if $settings['storage'])
					$settings['storage'] = strtolower($settings['storage']);
				else
					unset($settings['storage']);
			}
			$config = cmsms()->GetConfig();
			$url = $config['root_url'];
			$settings = array_merge(
				array(
					'storage' => 'auto', // or '' == auto
					'fallback' => 'database', // storage when nothing else is available
					'memcache' => array(
						array($url,11211,1)
					),
					'redis' => array(
						'host' => $url,
						'port' => '',
						'password' => '',
						'database' => '',
						'timeout' => ''
					)
				), $settings);
			if(strpos($settings['storage'],'auto') !== false)
				$settings['storage'] = 'shmop,apc,memcached,wincache,xcache,memcache,redis,database';
			$types = explode(',',$settings['storage']);

			$type = false;
			foreach($types as $one)
			{
				$one = trim($one);
				switch($one)
				{
				 case 'apc':
					if(extension_loaded('apc') && ini_get('apc.enabled') && strpos(PHP_SAPI,'CGI') === false)
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'database':
					$type = $one;
					break 2;
				 case 'memcached':
					if(class_exists('memcached'))
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'memcache':
					if(function_exists('memcache_connect'))
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'redis':
					if(class_exists('Redis'))
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'shmop':
					if(extension_loaded('shmop'))
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'wincache':
					if(extension_loaded('wincache') && function_exists('wincache_ucache_set'))
					{
						$type = $one;
						break 2;
					}
					break;
				 case 'xcache':
					if(extension_loaded('xcache') && function_exists('xcache_get'))
					{
						$type = $one;
						break 2;
					}
					break;
				}
			}

			if(!$type)
				return false;
			$path = dirname(__FILE__).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
			require($path.'interface.phpfastcache_driver.php');
			require($path.'BasePhpFastCache.php');
			require($path.$type.'.php');
			$class = 'phpfastcache_'.$type;
			$ob = new $class($settings);
			if($ob)
				$module->cache =& $ob;
			else
				return false;
		}
		return $module->cache;
	}
}

?>
