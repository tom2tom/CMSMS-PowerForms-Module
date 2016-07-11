<?php
/*
 * This is slow on PHP < 7
 * It's primarily for arrays & objects, there's no performance benefit for strings etc
 * see https://blog.graphiq.com/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad#.nc4zcruy0
 */
namespace MultiCache;

class Cache_phpfile extends CacheBase implements CacheInterface
{
	protected $basepath; //has trailing separator

	public function __construct($config = array())
	{
		if ($this->use_driver()) {
			parent::__construct($config);
			if ($this->connectServer()) {
				return;
			}
		}
		throw new \Exception('no phpfile storage');
	}

	public function use_driver()
	{
		return TRUE;
	}
	
	public function connectServer()
	{
		if (empty($this->config['path'])) {
			return FALSE;
		}
		$dir = trim(rtrim($this->config['path'],'/\\ \t'));
		if (!$dir) {
			return FALSE;
		}
		$dir = str_replace(array('/','\\'),array(DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR),$dir);
		 //hacky check for relative-path
		$real = realpath(__DIR__); // gets /path/to/here or X:\path\to\here
		if (($dir[0] != DIRECTORY_SEPARATOR && $real[0] == DIRECTORY_SEPARATOR)
		|| ($dir[1] != ':' && $real[1] == ':')) {
			$dir = __DIR__.DIRECTORY_SEPARATOR.$dir;
		}
		$dir .= DIRECTORY_SEPARATOR.'file_cache';
		if (!is_dir($dir)) {
			if (!(@mkdir($dir) && file_exists($dir)))
				return FALSE;
		}
		$this->basepath = $dir.DIRECTORY_SEPARATOR;
		return TRUE;
	}

	public function _newsert($keyword, $value, $lifetime=FALSE)
	{
		$fp = $this->basepath.$this->filename($keyword);
		if (!file_exists($fp)) {
			return $this->writefile($keyword,$value);
		}
		return FALSE;
	}

	public function _upsert($keyword, $value, $lifetime=FALSE)
	{
		return $this->writefile($keyword,$value);
	}

	public function _get($keyword)
	{
		$value = $this->readfile($keyword);
		if ($value !== FALSE) {
			return $value;
		}
		return NULL;
	}

	public function _getall($filter)
	{
		$vals = array();
		$files = glob($this->basepath.'*',GLOB_NOSORT);
		foreach ($files as $fp) {
			if (is_file($fp)) {
				$keyword = $this->keyword($fp);
				$value = $this->_get($keyword);
				$again = is_object($value); //get it again, in case the filter played with it!
				if ($this->filterKey($filter,$keyword,$value)) {
					if ($again) {
						$value = $this->_get($keyword);
					}
					if ($value !== NULL) {
						$vals[$keyword] = $value;
					}
				}
			}
		}
		return $vals;
	}

	public function _has($keyword)
	{
		$fp = $this->basepath.$this->filename($keyword);
		return file_exists($fp);
	}

	public function _delete($keyword)
	{
		$fp = $this->basepath.$this->filename($keyword);
		if (is_file($fp)) {
			return @unlink($fp);
		}
		return FALSE;
	}

	public function _clean($filter)
	{
		$ret = TRUE;
		$files = glob($this->basepath.'*',GLOB_NOSORT);
		foreach ($files as $fp) {
			if (is_file($fp)) {
				$keyword = $this->keyword($fp);
				$value = $this->_get($keyword);
				if ($this->filterKey($filter,$keyword,$value)) {
					$ret = $ret && @unlink($fp);
				}
			}
		}
		return $ret;
	}

	/*
	$keyword may include a namespace, which can look like a filepath
	*/
	private public function filename($keyword)
	{
		return str_replace('\\','|%|',$keyword);
	}

	private public function keyword($filepath)
	{
		return str_replace('|%|','\\',basename($filepath));
	}

	private public function readfile($keyword)
	{
		@include $this->basepath.$this->filename($keyword);
		return (isset($val)) ? $val : FALSE;
	}

	private public function writefile($keyword,$value)
	{
		return @file_put_contents($this->basepath.$this->filename($keyword),
			'<?php $val = '.$value.';');
	}

}
