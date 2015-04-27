<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfData
{
	public $pwfmodule = NULL; //reference to PowerForms-module object
	//known form-properties
	public $Alias = '';
	public $Attrs = array();
	public $Fields = array();
	public $Id = -1;
	public $Name = '';
	public $Page = -1;
	public $FormState = 'new';
	public $FormTotalPages = 0;
	public $loaded = 'not';
//	public $sampleTemplateCode = '';
	public $templateVariables = NULL;
	//extra form-properties
	private $extradata = array();

	public function __set($name,$value)
	{
		$this->extradata[$name] = $value;
	}

	public function __get($name)
	{
		if(array_key_exists($name,$this->extradata))
            return $this->extradata[$name];
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): '.$name.
            ' in '.$trace[0]['file'] .
            ' on line '.$trace[0]['line'],
            E_USER_NOTICE);
        return null;			
	}	

	public function __isset($name)
	{
		return isset($this->extradata[$name]);
	}
	
	public function __unset($name)
	{
		unset($this->extradata[$name]);
	}
}

?>
