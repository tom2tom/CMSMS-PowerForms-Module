<?php

abstract class BasePhpFastCache {

	var $tmp = array();

	// default options, this will be merge to Driver's Options
	var $config = array();
	// log of items in the cache
	var $index = array();

	var $fallback = false;
	var $instant;

	/*
	 * Basic Functions
	 */

	public function set($keyword, $value = '', $time = 0, $option = array() ) {
		/*
		 * Infinity Time
		 * Khoa. B
		 */
		if((Int)$time <= 0) {
			// 5 years, however memcached or memory cached will gone when u restart it
			// just recommended for sqlite. files
			$time = 3600*24*365*5;
		}
		/*
		 * Temporary disabled phpFastCache::$disabled = true
		 * Khoa. B
		 */
		if(phpFastCache::$disabled === true) {
			return false;
		}
		$object = array(
			'value' => $value,
			'write_time' => @date('U'),
			'expired_in' => $time,
			'expired_time' => @date('U') + (Int)$time,
		);

		return $this->driver_set($keyword,$object,$time,$option);

	}

	public function get($keyword, $option = array()) {
		/*
	   * Temporary disabled phpFastCache::$disabled = true
	   * Khoa. B
	   */

		if(phpFastCache::$disabled === true) {
			return null;
		}

		$object = $this->driver_get($keyword,$option);

		if($object == null) {
			return null;
		}
		return isset($option['all_keys']) && $option['all_keys'] ? $object : $object['value'];
	}

	function getInfo($keyword, $option = array()) {
		$object = $this->driver_get($keyword,$option);

		if($object == null) {
			return null;
		}
		return $object;
	}

	function delete($keyword, $option = array()) {
		return $this->driver_delete($keyword,$option);
	}

	function stats($option = array()) {
		return $this->driver_stats($option);
	}

	function clean($option = array()) {
		return $this->driver_clean($option);
	}

	function isExisting($keyword) {
		if(method_exists($this,'driver_isExisting')) {
			return $this->driver_isExisting($keyword);
		}

		$data = $this->get($keyword);
		return ($data != null);

	}

	public function setup($config_name,$value = '') {
		/*
		 * Config for class
		 */
		if(is_array($config_name)) {
			$this->config = $config_name;
		} else {
			$this->config[$config_name] = $value;
		}
	}

	/*
	 * Magic Functions
	 */
	function __get($name) {
		return $this->get($name);
	}

	function __set($name, $v) {
		if(isset($v[1]) && is_numeric($v[1])) {
			return $this->set($name,$v[0],$v[1], isset($v[2]) ? $v[2] : array() );
		} else {
			throw new Exception("Example ->$name = array('VALUE', 300);",98);
		}
	}

	public function __call($name, $args) {
		$str = implode(',',$args);
		eval('return $this->instant->$name('.$str.');');
	}

}

?>
