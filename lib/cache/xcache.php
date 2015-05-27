<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_xcache extends BasePhpFastCache implements phpfastcache_driver  {

	function __construct($config = array()) {
		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
	}

	function __destruct() {
		$this->driver_clean();
	}

	// Check xcache
	function checkdriver() {
		if(extension_loaded('xcache') && function_exists('xcache_get')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function driver_set($keyword, $value = "", $time = 300, $option = array() ) {

		if(empty($option['skipExisting'])) {
			return xcache_set($keyword,$value,$time);
		} else if(!$this->isExisting($keyword)) {
			return xcache_set($keyword,$value,$time);
		} else {
		return false;
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		$data = xcache_get($keyword);
		if($data === false || $data == '') {
			return null;
		}
		return $data;
	}

	function driver_delete($keyword, $option = array()) {
		return xcache_unset($keyword);
	}

	function driver_stats($option = array()) {
		$res = array(
			'info' => '',
			'size' => '',
			'data' => '',
		);
		try {
			$res['data'] = xcache_list(XC_TYPE_VAR,100);
		} catch(Exception $e) {
			$res['data'] = array();
		}
		return $res;
	}

	function driver_clean($option = array()) {
		$cnt = xcache_count(XC_TYPE_VAR);
		for ($i=0; $i < $cnt; $i++) {
			xcache_clear_cache(XC_TYPE_VAR, $i);
		}
		return true;
	}

	function driver_isExisting($keyword) {
		return xcache_isset($keyword);
	}

}

?>
