<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfData
{
	public $formsmodule = NULL; //reference to PowerForms-module object
	//time-specific object-name-prefixes (for bot-combat), must begin with 'pwfp_NNN_' where N = digit
	public $current_prefix = FALSE; //for current 30-minute period
	public $prior_prefix = FALSE; //for prior-period
	//known form-properties
	public $Alias = '';
	public $Options = array();
	public $Fields = array();
	public $Id = -1;
	public $Name = '';
	public $Page = -1;
	public $FormState = 'new';
	public $FormPagesCount = 0;
	public $loaded = 'not';
//	public $sampleTemplateCode = '';
	public $templateVariables;
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
        return NULL;			
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
