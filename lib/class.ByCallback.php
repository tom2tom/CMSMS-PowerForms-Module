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
	 an array (modulename,'method.whatever') to be included, the code must conclude with variable $res = T/F indicating success
	 a string 'absolute-path-to-whatever.php' to be included, the code must conclude with variable $res = T/F indicating success

	 NOT a closure in a static context (PHP 5.3+) OR static closure (PHP 5.4+)
	 cuz info about those isn't transferrable between requests
	*/
	private $handlertype;
	private $handler;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'ByCallback';
	}

	//?SetValue()

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = '[Callback to '.$this->handler.']';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function GetSynopsis()
	{
		//Callback: $this->handler;
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$main[] = array($mod->Lang('title_handler'),
				$mod->CreateInputText($id,'fp_handler',
					$this->GetProperty('handler'),30,40),
				$mod->Lang('help_handler'));
		return array('main'=>$main,'adv'=>$adv);
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
			} elseif (is_string($handler) && strpos($handler,'::') !== FALSE) {
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
				if (strpos($handler[1],'method.') === 0) {
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
			}
		}

		if ($type !== FALSE) {
			$this->handlertype = $type;
			$this->handler = $handler;
			return array(TRUE.'');
		}
		return array(FALSE,$this->formdata->formsmodule->Lang('TODO'));
	}

	public function Populate($id,&$params)
	{
		switch ($this->type) {
		 case 1: //callable, 2-member array or string like 'ClassName::methodName'
			$res = call_user_func_array($this->handler,$id,$params);
			break;
		 case 4: //code inclusion
			$ob = \cms_utils::get_module($this->handler[0]);
			$fp = $ob->GetModulePath().DIRECTORY_SEPARATOR.$this->handler[1].'.php';
			unset($ob);
			$res = FALSE;
			require $fp;
			break;
		 case 5: //code inclusion
			$res = FALSE;
			require $this->handler;
			break;
		}
		//TODO handle $res == FALSE
		//TODO update object properties, methods e.g. $this->IsInput, $this->Validate

		return $res;
	}

	public function Validate($id)
	{
	}
}
