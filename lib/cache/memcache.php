<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_memcache extends BasePhpFastCache implements phpfastcache_driver {

//	var $instant; see parent

	function __construct($config = array()) {
		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
		if(class_exists('Memcache')) {
			$this->instant = new Memcache();
		} else {
			$this->fallback = true;
		}
	}

	function __destruct() {
		$this->driver_clean();
	}

	// Check memcache
	function checkdriver() {
		if(function_exists('memcache_connect')) {
			return true;
		}
		$this->fallback = true;
		return false;
	}


	function connectServer() {
		$server = $this->option['memcache'];
		if(count($server) < 1) {
			$server = array(
				array('127.0.0.1',11211),
			);
		}

		foreach($server as $s) {
			$name = $s[0].'_'.$s[1];
			if(!isset($this->checked[$name])) {
				try {
					if(!$this->instant->addserver($s[0],$s[1])) {
						$this->fallback = true;
					}

					$this->checked[$name] = 1;
				} catch(Exception $e) {
					$this->fallback = true;
				}


			}

		}
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array() ) {
		$this->connectServer();

		if(empty($option['skipExisting'])) {
			return $this->instant->set($keyword, $value, false, $time );
		} else {
			return $this->instant->add($keyword, $value, false, $time );
		}
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {

		$this->connectServer();

		$x = $this->instant->get($keyword);

		if($x == false) {
			return null;
		} else {
			return $x;
		}

	}

	function driver_delete($keyword, $option = array()) {
		$this->connectServer();
		$this->instant->delete($keyword);
	}

	function driver_stats($option = array()) {
		$this->connectServer();
		$res = array(
			'info' => '',
			'size' => '',
			'data' => $this->instant->getStats(),
		);
		return $res;
	}

	function driver_clean($option = array()) {
		$this->connectServer();
		$this->instant->flush();
	}

	function driver_isExisting($keyword) {
		$this->connectServer();
		$x = $this->get($keyword);
		if($x == null) {
			return false;
		} else {
			return true;
		}
	}

}

?>
