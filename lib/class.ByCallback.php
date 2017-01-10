<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class ByCallback extends FieldBase
{
	/*
	handler can be one of
	 an array (classname,methodname) where methodname is static and the method returns boolean for success
	 a string 'classname::methodname' where the method returns boolean for success
	 an array (modulename,'method.whatever') to be included, the code must conclude with variable $res = T/F indicating success
	 a string 'absolute-path-to-whatever.php' to be included, the code must conclude with variable $res = T/F indicating success

	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz info about those isn't transferrable between requests
	*/
	private $handlertype;
	private $handler;
	private $overrides = [];

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'ByCallback';
		$this->AddOverride('ExternValidate', function ($obj, $id) {
			$obj->valid = TRUE;
			$obj->ValidationMessage = '';
		});
	}

	//TODO namespace management for this
	public function __call($func, $args)
	{
		if (is_callable([$this, $func])) {
			return call_user_func_array([$this, $func], $args);
		} elseif (isset($this->overrides[$func])) {
			array_unshift($args, $this); //prepend the object to the argument list
			return call_user_func_array($this->overrides[$func], $args);
		} else {
			throw new BadMethodCallException('Method '.$func.' does not exist');
		}
	}

	public function AddOverride($name, $callback)
	{
		$this->overrides[$name] = $callback;
	}

	//?SetValue()

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Callback to '.$this->handler.']';
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function GetSynopsis()
	{
		//Callback: $this->handler;
	}

	public function AdminPopulate($id)
	{
		list($main, $adv) = $this->AdminPopulateCommon($id);
		$main[] = [$mod->Lang('title_handler'),
				$mod->CreateInputText($id, 'fp_handler',
					$this->GetProperty('handler'), 30, 40),
				$mod->Lang('help_handler')];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function AdminValidate($id)
	{
		$type = FALSE;
		$handler = $this->GetProperty('handler');
		if (is_callable($handler)) { //BUT the class may have a __call() method
			if (is_array($handler && count($handler) == 2)) {
				$method = new \ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			} elseif (is_string($handler) && strpos($handler, '::') !== FALSE) {
				//PHP 5.2.3+, supports passing 'ClassName::methodName'
				$method = new \ReflectionMethod($handler);
				if ($method && $method->isStatic()) {
					$type = 1;
				}
			}
		} elseif (is_array($handler) && count($handler) == 2) {
			$ob = \cms_utils::get_module($handler[0]);
			if ($ob) {
				$dir = $ob->GetModulePath();
				unset($ob);
				if (strpos($handler[1], 'method.') === 0) {
					$fp = $dir.DIRECTORY_SEPARATOR.$handler[1].'.php';
					if (@is_file($fp)) {
						$type = 2;
					}
				}
			}
		} elseif (is_string($handler)) {
			if (@is_file($handler)) {
				if (substr_compare($handler, '.php', -4, 4, TRUE) === 0) {
					$type = 3;
				}
			}
		}

		if ($type !== FALSE) {
			$this->handlertype = $type;
			$this->handler = $handler;
			return [TRUE.''];
		}
		return [FALSE,$this->formdata->formsmodule->Lang('TODO')];
	}

	public function Populate($id, &$params)
	{
		switch ($this->type) {
		 case 1: //callable, 2-member array or string like 'ClassName::methodName'
			$params['FIELD'] =& $this;
			$res = call_user_func_array($this->handler, $id, $params);
			break;
		 case 2: //code inclusion
			$ob = \cms_utils::get_module($this->handler[0]);
			$fp = $ob->GetModulePath().DIRECTORY_SEPARATOR.$this->handler[1].'.php';
			unset($ob);
			$res = FALSE;
			require $fp;
			break;
		 case 3: //code inclusion
			$res = FALSE;
			require $this->handler;
			break;
		}
		//TODO handle $res == FALSE
		//TODO update object properties, methods e.g. $this->IsInput,
		//$this->addOverride(ExternValidate, $callback); //method to be populated by the callback
		return $res;
	}

	public function Validate($id)
	{
		$this->valid = TRUE;
		$this->ValidationMessage = '';
		$this->ExternValidate(); //method to be populated by the callback
		return [$this->valid,$this->ValidationMessage];
	}
}
