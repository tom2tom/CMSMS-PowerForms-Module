<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_memcached extends BasePhpFastCache implements phpfastcache_driver  {

//	var $instant; see parent

	function __construct($config = array()) {

		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
		if(class_exists('Memcached')) {
			$this->instant = new Memcached();
		} else {
			$this->fallback = true;
		}
	}

	function __destruct() {
		$this->driver_clean();
	}

	function checkdriver() {
		if(class_exists('Memcached')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function connectServer() {

		if($this->checkdriver() == false) {
			return false;
		}

		$s = $this->option['memcache'];
		if(count($s) < 1) {
			$s = array(
				array('127.0.0.1',11211,100),
			);
		}

		foreach($s as $server) {
			$name = isset($server[0]) ? $server[0] : '127.0.0.1';
			$port = isset($server[1]) ? $server[1] : 11211;
			$sharing = isset($server[2]) ? $server[2] : 0;
			$checked = $name.'_'.$port;
			if(!isset($this->checked[$checked])) {
				try {
					if($sharing >0 ) {
						if(!$this->instant->addServer($name,$port,$sharing)) {
							$this->fallback = true;
						}
					} else {
						if(!$this->instant->addServer($name,$port)) {
							$this->fallback = true;
						}
					}
					$this->checked[$checked] = 1;
				} catch (Exception $e) {
					$this->fallback = true;
				}
			}
		}
		return !$this->fallback;
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array() ) {
		if($this->connectServer()) {
			if(empty($option['isExisting'])) {
				$ret = $this->instant->set($keyword, $value, time() + $time );
			} else {
				$ret = $this->instant->add($keyword, $value, time() + $time );
			}
			if($ret) {
				$this->index[$keyword] = 1;
			}
		} else {
			$ret = false;
		}
		return $ret;
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		if($this->connectServer()) {
			$x = $this->instant->get($keyword);
			if($x) {
				return $x;
			}
		}
		return null;
	}

	function driver_getall($option = array()) {
		return array_keys($this->index);
	}

	function driver_delete($keyword, $option = array()) {
		if($this->connectServer()) {
			$this->instant->delete($keyword);
			unset($this->index[$keyword]);
			return true;
		}
		return false;
	}

	function driver_stats($option = array()) {
		if($this->connectServer()) {
			$res = array(
			'info' => '',
			'size' => count($this->index),
			'data' => $this->instant->getStats()
			);
		} else {
			$res = array(
			'info' => '',
			'size' => '',
			'data' => ''
			);
		}
		return $res;
	}

	function driver_clean($option = array()) {
		if($this->connectServer()) {
			$this->instant->flush();
			$this->index = array();
		}
	}

	function driver_isExisting($keyword) {
		$this->connectServer();
		return ($this->get($keyword) != null);
	}

}

?>
