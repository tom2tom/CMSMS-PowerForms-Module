<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class FastCache_memcached extends FastCacheBase implements iFastCache {

	var $instance;

	function __construct($config = array()) {

		if($this->checkdriver()) {
			$this->setup($config);
			$this->instance = new Memcached();
			if($this->connectServer()) {
				return;
			}
			unset($this->instance);
		}
		throw new Exception('no memcached storage');
	}

/*	function __destruct() {
		$this->driver_clean();
	}
*/
	function checkdriver() {
		return class_exists('Memcached');
	}

	function connectServer() {

		if(!$this->checkdriver()) {
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
					if($sharing > 0) {
						if($this->instance->addServer($name,$port,$sharing)) {
							$this->checked[$checked] = 1;
							return true;
						}
					} elseif($this->instance->addServer($name,$port)) {
						$this->checked[$checked] = 1;
						return true;
					}
				} catch(Exception $e) {}
			}
		}
		return false;
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array() ) {
		if(empty($option['isExisting'])) {
			$ret = $this->instance->set($keyword, $value, time() + $time );
		} else {
			$ret = $this->instance->add($keyword, $value, time() + $time );
		}
		if($ret) {
			$this->index[$keyword] = 1;
		}
		return $ret;
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		$x = $this->instance->get($keyword);
		if($x) {
			return $x;
		} else {
			return null;
		}
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
			'data' => $this->instance->getStats()
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
