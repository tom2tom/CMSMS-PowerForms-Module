<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

class phpfastcache_database extends BasePhpFastCache implements phpfastcache_driver  {

	function __construct($config = array()) {
		$this->setup($config);
	}

	function __destruct() {
		$this->driver_clean();
	}

	function checkdriver() {
		return true;
	}

	function connectServer() {
	}

	function driver_set($keyword, $value = '', $time = 300, $option = array()) {
		$db = cmsms()->GetDb();
		$sql = 'SELECT cache_id FROM '.$pre.'module_pwf_cache WHERE key=?';
		$id = $db->GetOne($sql,array($keyword));
		if(empty($option['skipExisting']) {
			//upsert, sort-of
			if($id)
			{
			   $sql = 'UPDATE '.$pre.'module_pwf_cache set value=? WHERE cache_id=?';
			   $db->Execute($sql,array($value,$id));
			}
			else
			{
				$sql = 'INSERT INTO '.$pre.'module_pwf_cache (key,value,save_time) VALUES (?,?,NOW())';
				$db->Execute($sql,array($keyword,$value));
			}
		} else {
			// skip driver
			if(!$id)
			{
				$sql = 'INSERT INTO '.$pre.'module_pwf_cache (key,value,save_time) VALUES (?,?,NOW())';
				$db->Execute($sql,array($keyword,$value));
			}
		}
	}

	function driver_get($keyword, $option = array()) {
		$db = cmsms()->GetDb();
		$val = $db->GetOne('SELECT value FROM '.cms_db_prefix().'module_pwf_cache WHERE key=?',array($keyword));
		if ($val !== FALSE) {
			return $val;
		}
		return null;
	}

	function driver_delete($keyword, $option = array()) {
		$db = cmsms()->GetDb();
		$db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_cache WHERE key=?',array($keyword));
	}

	function driver_stats($option = array()) {
		$res = array(
			'info' => '',
			'size' => '',
			'data' => '',
		);
		return $res;
	}

	function driver_clean($option = array()) {
		$db = cmsms()->GetDb();
		db->Execute('DELETE FROM '.cms_db_prefix().'module_pwf_cache');
	}

	function driver_isExisting($keyword) {
		$db = cmsms()->GetDb();
		$res = $db->GetOne('SELECT key FROM '.cms_db_prefix().'module_pwf_cache WHERE key=?',array($keyword));
		return ($res !== FALSE);
	}

}

?>
