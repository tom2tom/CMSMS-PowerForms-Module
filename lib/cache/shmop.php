<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_shmop extends BasePhpFastCache implements phpfastcache_driver  {

	private static $index;

	function __construct($config = array()) {
		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			throw new Exception('Can\'t use this driver for your website!');
		}
	}

	function checkdriver() {
		if (extension_loaded('shmop')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function connectServer() {
		self::index = array()
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array() ) {
		if (driver_isExisting($keyword)) {
			if(empty($option['skipExisting'])) {
				driver_delete($keyword, $option);
			} else {
				return false;
			}
		}
		$sysid = md5(uniqid($keyword,TRUE));
		$size = strlen($value); // byte-size of the segment
		$shmid = shmop_open($sysid, 'c', 0644, $size);
		if($shmid !== FALSE) {
			if(shmop_write($shmid, $value, 0) !== FALSE) {
				self::index[$keyword] = $shmid;
				return true;
			}
		}
		return false;
	}

	function driver_get($keyword, $option = array()) {
		if(array_key_exists($keyword, self::index) {
			$shmid = self::index[$keyword];
			$size = shmop_size($shmid);
			return shmop_read($shmid, 0, $size);
		}
		return null;
	}

	function driver_delete($keyword, $option = array()) {
		if (array_key_exists($keyword, self::index)) {
			$shmid = self::index[$keyword];
			shmop_delete($shmid);
			shmop_close($shmid);
			unset(self::index[$keyword]);
		}
	}

	function driver_stats($option = array()) {
		$res = array(
			'info' => 'Number of cached items',
			'size' => count(self::index),
			'data' => '',
		);
		return $res;
	}

	function driver_clean($option = array()) {
		foreach(self::index as $key=>$item) {
			$this->driver_delete($key, $option);
		}
	}

	function driver_isExisting($keyword) {
		return array_key_exists($keyword, self::index);
	}

}

?>
