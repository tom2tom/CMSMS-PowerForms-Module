<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */
/*
This class is CMSMS-specific.
The database table must (first) have (at least) four fields:
 I(2) AUTO KEY = unique identifier
 C = cache key
 B = cache value
 CMS_ADODB_DT = stamp for when stored
*/
class FastCache_database extends FastCacheBase implements iFastCache {

	private $db;
	private $table;
	private $fields;

	function __construct($config) {
		if(!empty($config['table'])) {
			$this->table = $config['table'];
			$this->db = cmsms()->GetDb();
			if($this->checkdriver()) {
				$this->setup($config);
				$this->fields = $this->db->GetCol('SELECT column_name FROM information_schema.columns WHERE table_name=\''.$this->table.'\'');
				return;
			}
		}
		throw new Exception('no database storage');
	}

/*	function __destruct() {
		$this->driver_clean();
	}
*/
	function checkdriver() {
		$rst = $this->db->Execute('SELECT * FROM '.$this->table);
		if($rst) {
			$ret = ($rst->FieldCount() >= 4);
			$rst->Close();
			return $ret;
		}
		return false;
	}

	function driver_set($keyword, $parms, $duration = 0, $option = array()) {
		$ret = false;
		$value = serialize($parms['value']);
		if(empty($option['skipExisting'])) {
			//upsert, sort-of
			$sql = 'UPDATE '.$this->table.' SET '.$this->fields[2].'=? WHERE '.$this->fields[1].'=?';
			$ret = $this->db->Execute($sql,array($value,$keyword));
			$sql = 'INSERT INTO '.$this->table.' ('.$this->fields[1].','.$this->fields[2].','.$this->fields[3].')
SELECT ?,?,NOW() FROM (SELECT 1 AS dmy) Z WHERE NOT EXISTS (SELECT 1 FROM '.
			$this->table.' T WHERE T.'.$this->fields[1].'=?)';
			$ret = $this->db->Execute($sql,array($keyword,$value,$keyword));
		} else {
			// skip driver
			$sql = 'SELECT '.$this->fields[0].' FROM '.$this->table.' WHERE '.$this->fields[1].'=?';
			$id = $this->db->GetOne($sql,array($keyword));
			if(!$id)
			{
				$sql = 'INSERT INTO '.$this->table.' ('.$this->fields[1].','.$this->fields[2].','.$this->fields[3].') VALUES (?,?,NOW())';
				$ret = $this->db->Execute($sql,array($keyword,$value));
			}
		}
		if($ret) {
			$this->index[$keyword] = 1;
		}
		return ($ret != false);
	}

	function driver_get($keyword, $option = array()) {
		if(empty($option['all_keys'])) {
			$data = $this->db->GetOne('SELECT '.$this->fields[2].' FROM '.$this->table.' WHERE '.$this->fields[1].'=?',array($keyword));
			if ($data !== FALSE) {
				return array('value'=>unserialize($data));
			}
			return null;
		}
		//TODO array of 'all data' ?
		return null; 
	}

	function driver_getall($option = array()) {
		return array_keys($this->index); //CRAP past sessions too
	}

	function driver_delete($keyword, $option = array()) {
		if($this->db->Execute('DELETE FROM '.$this->table.' WHERE '.$this->fields[1].'=?',array($keyword))) {
			unset($this->index[$keyword]);
			return true;
		} else {
			return false;
		}
	}

	function driver_stats($option = array()) {
		return array(
			'info' => '',
			'size' => count($this->index),
			'data' => '',
		);
	}

	function driver_clean($option = array()) {
		if($this->index) {
//ADODB BUG prevents this
//			$fillers = str_repeat('?,',count($this->index)-1).'?';
//			$args = array_keys($this->index);
//			$this->db->Execute('DELETE FROM '.$this->table.' WHERE '.$this->fields[1].' IN('.$fillers.')',$args);
			$fillers = implode(',',array_keys($this->index));
			$this->db->Execute('DELETE FROM '.$this->table.' WHERE '.$this->fields[1].' IN('.$fillers.')');
			$this->index = array();
		}
	}

}

?>
