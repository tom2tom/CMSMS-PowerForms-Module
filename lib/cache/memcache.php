<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class FastCache_memcache extends FastCacheBase implements iFastCache {

	public $instance;

	function __construct($config) {
		if($this->checkdriver()) {
			$this->instance = new Memcache();
			$this->setup($config);
			if($this->connectServer()) {
				return;
			}
			unset($this->instance);
		}
		throw new Exception('no memcache storage');
	}

/*	function __destruct() {
		$this->driver_clean();
	}
*/
	function checkdriver() {
		return class_exists('Memcache') && function_exists('memcache_connect');
	}

	function connectServer() {
		$settings = isset($this->option['memcache']) ? $this->option['memcache'] : array();
		$server = array_merge($settings, array(
				array('127.0.0.1',11211)
				));
		foreach($server as $s) {
			$name = $s[0].'_'.$s[1];
			if(!isset($this->checked[$name])) {
				try {
					if($this->instance->addserver($s[0],$s[1])) {
						$this->checked[$name] = 1;
						return true;
					}
				} catch(Exception $e) {}
			}
		}
		return false;
	}

	function driver_set($keyword, $parms, $duration = 0, $option = array()) {
		if($duration) {
			$duration += time();
		}
		if(empty($option['skipExisting'])) {
			$ret = $this->instance->set($keyword,$parms['value'],0,$duration);
		} else {
			$ret = $this->instance->add($keyword,$parms['value'],0,$duration);
		}
		if($ret) {
			$this->index[$keyword] = 1;
		}
		return $ret;
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		if(empty($option['all_keys'])) {
			$data = $this->instance->get($keyword);
			if($data) {
				return array('value'=>$data);
			} else {
				return null;
			}
		}
		//TODO array of 'all data' ?
		return null;
	}

	function driver_getall($option = array()) {
		return array_keys($this->index);
	}

	function driver_delete($keyword, $option = array()) {
		$this->instance->delete($keyword);
		unset($this->index[$keyword]);
		return true;
	}

	function driver_stats($option = array()) {
		return array(
			'info' => '',
			'size' => count($this->index),
			'data' => $this->instance->getStats(),
		);
	}

	function driver_clean($option = array()) {
		$this->instance->flush();
		$this->index = array();
	}

	function driver_isExisting($keyword) {
		return ($this->get($keyword) != null);
	}

}

?>
