<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_apc extends BasePhpFastCache implements phpfastcache_driver {

	function __construct($config = array()) {
		$this->setup($config);

		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
	}

	function __destruct() {
		$this->driver_clean();
	}

	function checkdriver() {
		// Check apc
		if(extension_loaded('apc') && ini_get('apc.enabled')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array()) {
		if(empty($option['skipExisting'])) {
			return apc_store($keyword,$value,$time);
		} else {
			return apc_add($keyword,$value,$time);
		}
	}

	function driver_get($keyword, $option = array()) {
		$data = apc_fetch($keyword,$bo);
		if($bo !== false) {
			return $data;
		}
		return null; //no caching
	}

	function driver_delete($keyword, $option = array()) {
		return apc_delete($keyword);
	}

	function driver_stats($option = array()) {
		$res = array(
			'info' => '',
			'size' => '',
			'data' => ''
		);

		try {
			$res['data'] = apc_cache_info('user');
		} catch(Exception $e) {
			$res['data'] =  array();
		}

		return $res;
	}

	function driver_clean($option = array()) {
		@apc_clear_cache();
		@apc_clear_cache('user');
	}

	function driver_isExisting($keyword) {
		return apc_exists($keyword);
	}

}

?>
