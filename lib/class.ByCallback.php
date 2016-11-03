<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class ByCallback extends FieldBase
{
	/*
	handler can be one of
	 an array (classname,methodname) where methodname is static and the method returns boolean for success
	 a string 'classname::methodname' where the method returns boolean for success
	 an array (modulename,actionname) AND the action should be a 'doer', not a 'shower', returns HTML code
	 an array (modulename,'method.whatever') to be included, the code must conclude with variable $res = T/F indicating success
	 a string 'absolute-path-to-whatever.php' to be included, the code must conclude with variable $res = T/F indicating success
	 an URL like <server-root-url>/index.php?mact=<modulename>,cntnt01,<actionname>,0
	 	- provided the PHP curl extension is available
	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz info about those isn't transferrable between requests
	*/
	private $handlertype;
	private $handler;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'Callback';
	}

	public function GetFieldStatus()
	{
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = '[Callback to '.$this->handler.']';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
	}

	public function AdminValidate($id)
	{
		$type = FALSE;
		if (is_callable($handler)) { //BUT the class may have a __call() method
			if (is_array($handler && count($handler) == 2)) {
				$method = new \ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			} elseif (is_string($handler) && strpos($handler,'::') !== FALSE) {
				//PHP 5.2.3+, supports passing 'ClassName::methodName'
				$method = new \ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			} /* elseif (is_object($handler) && ($handler instanceof Closure)) {
				if ($this->isStatic($handler)) {
					$type = 2;
				}
			}
*/
		} elseif (is_array($handler) && count($handler) == 2) {
			$ob = \cms_utils::get_module($handler[0]);
			if ($ob) {
				$dir = $ob->GetModulePath();
				unset($ob);
				$fp = $dir.DIRECTORY_SEPARATOR.'action.'.$handler[1].'.php';
				if (@is_file($fp)) {
					$type = 3;
				} elseif (strpos($handler[1],'method.') === 0) {
					$fp = $dir.DIRECTORY_SEPARATOR.$handler[1].'.php';
					if (@is_file($fp)) {
						$type = 4;
					}
				}
			}
		} elseif (is_string($handler)) {
			if (@is_file($handler)) {
				if (substr_compare($handler,'.php',-4,4,TRUE) === 0) {
					$type = 5;
				}
			} elseif ($TODO) { //curl is installed
				$config = \cmsms()->GetConfig();
				$u = (empty($_SERVER['HTTPS'])) ? $config['root_url'] : $config['ssl_url'];
				$u .= '/index.php?mact=';
				$len = strlen($u);
				if (strncasecmp($u,$handler,$len) == 0) {
					$type = 6;
				}
			}
		}

		if ($type !== FALSE) {
			$this->handlertype = $type;
			$this->handler = $handler;
			return TRUE;
		}
		return FALSE;
	}

	public function Populate($id,&$params)
	{
	}

	public function Validate($id)
	{
	}
}
