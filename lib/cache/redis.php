<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 *
 * Redis Extension with:
 * http://pecl.php.net/package/redis
 */

class phpfastcache_redis extends BasePhpFastCache implements phpfastcache_driver {

	var $checked_redis = false;

	function __construct($config = array()) {
		$this->setup($config);
		if(!$this->checkdriver() && !isset($config['skipError'])) {
			$this->fallback = true;
		}
		if(class_exists('Redis')) {
			$this->instant = new Redis();
		}
	}

	function __destruct() {
		$this->driver_clean();
	}

	// Check redis
	function checkdriver() {
		if(class_exists('Redis')) {
			return true;
		} else {
			$this->fallback = true;
			return false;
		}
	}

	function connectServer() {

		$server = isset($this->option['redis']) ? $this->option['redis'] : array(
			'host' => '127.0.0.1',
			'port'  => '6379',
			'password' => '',
			'database' => '',
			'timeout' => '1',
		);

		if($this->checked_redis === false) {

			$host = $server['host'];

			$port = isset($server['port']) ? (Int)$server['port'] : '';
			if($port!='') {
				$c['port'] = $port;
			}

			$password = isset($server['password']) ? $server['password'] : '';
			if($password!='') {
				$c['password'] = $password;
			}

			$database = isset($server['database']) ? $server['database'] : '';
			if($database!='') {
				$c['database'] = $database;
			}

			$timeout = isset($server['timeout']) ? $server['timeout'] : '';
			if($timeout!='') {
				$c['timeout'] = $timeout;
			}

			$read_write_timeout = isset($server['read_write_timeout']) ? $server['read_write_timeout'] : '';
			if($read_write_timeout!='') {
				$c['read_write_timeout'] = $read_write_timeout;
			}

			if(!$this->instant->connect($host,(int)$port,(Int)$timeout)) {
				$this->checked_redis = true;
				$this->fallback = true;
				return false;
			} else {
				if($database!='') {
					$this->instant->select((Int)$database);
				}
				$this->checked_redis = true;
				return true;
			}
		}

		return true;
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array() ) {
		if($this->connectServer()) {
			$value = $this->encode($value);
			if (empty($option['skipExisting'])) {
				$ret = $this->instant->set($keyword, $value, $time);
			} else {
				$ret = $this->instant->set($keyword, $value, array('xx', 'ex' => $time));
			}
		} else {
			$ret = $this->backup()->set($keyword, $value, $time, $option);
		}
		if($ret) {
			$this->index[$keyword] = 1;
		}
		return $ret;
	}

	// return cached value or null
	function driver_get($keyword, $option = array()) {
		if($this->connectServer()) {
			$x = $this->instant->get($keyword);
			if($x) {
				return $this->decode($x);
			} else {
				return null;
			}
		} else {
			return $this->backup()->get($keyword, $option);
		}
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
				'data' => $this->instant->info(),
			);
			return $res;
		}
		return array();
	}

	function driver_clean($option = array()) {
		if($this->connectServer()) {
			$this->instant->flushDB();
			$this->index = array();
		}
	}

	function driver_isExisting($keyword) {
		if($this->connectServer()) {
			return ($this->instant->exists($keyword) != null);
		} else {
			return $this->backup()->isExisting($keyword);
		}
	}

}

?>
