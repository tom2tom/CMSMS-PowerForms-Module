<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_wincache extends BasePhpFastCache implements phpfastcache_driver  {

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
		if(extension_loaded('wincache') && function_exists('wincache_ucache_set')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function driver_set($keyword, $value = "", $time = 300, $option = array() ) {
		if(empty($option['skipExisting'])) {
			return wincache_ucache_set($keyword, $value, $time);
		} else {
			return wincache_ucache_add($keyword, $value, $time);
		}
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		$x = wincache_ucache_get($keyword,$suc);

		if($suc == false) {
			return null;
		} else {
			return $x;
		}
	}

	function driver_delete($keyword, $option = array()) {
		return wincache_ucache_delete($keyword);
	}

	function driver_stats($option = array()) {
		$res = array(
			'info' => '',
			'size' => '',
			'data' => wincache_scache_info(),
		);
		return $res;
	}

	function driver_clean($option = array()) {
		wincache_ucache_clear();
		return true;
	}

	function driver_isExisting($keyword) {
		return wincache_ucache_exists($keyword);
	}

}

?>
