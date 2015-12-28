<?php
/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */

interface iFastCache {
	/*
	 * Check if this Cache driver is available for server or not
	 */
	function __construct($config);

//	 function __destruct();

	function checkdriver();

	/*
	 * SET
	 * set a obj to cache
	 */
	function driver_set($keyword, $parms, $duration, $option);

	/*
	 * GET
	 * return null or value of cache
	 */
	function driver_get($keyword, $option = array());

	function driver_getall($option = array());

	/*
	* isExisting
	* check whether a key is cached
	*/
	function driver_isExisting($keyword);

	/*
	 * Stats
	 * Show stats of caching
	 * Return array ("info","size","data")
	 */
	function driver_stats($option = array());

	/*
	 * Delete
	 * Delete a cached item
	 */
	function driver_delete($keyword, $option);

	/*
	 * clean
	 * Clean up whole cache
	 */
	function driver_clean($option);

}

?>
