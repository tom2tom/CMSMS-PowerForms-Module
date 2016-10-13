<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FormData implements \Serializable
{
	public $formsmodule = NULL; //reference to PWForms-module object
	//time-specific object-name-prefixes (for bot-combat), must begin with 'pwfp_NNN_' where N = digit
	public $current_prefix = FALSE; //for current 30-minute period
	public $prior_prefix = FALSE; //for prior-period
	//known form-properties
	public $Alias = '';
	public $Fields = array(); //merged array of display and/or disposition field objects, each key = field->Id
	public $FieldOrders = FALSE; //when needed, set to ordered array representing field->Orderby
	public $Id = 0;
	public $Name = '';
	public $Options = array();
	public $Page = 0; //current page in the form
	public $PagesCount = 0; //no. of pages in the form
//	public $sampleTemplateCode = '';
	public $templateVariables = array(); //extra 'global' items for template-help, each like 'var_name'=>'help_lang_key'
	public $jsincs = array(); //'include' directives
	public $jsfuncs = array(); //funcs and/or instructions
	public $jsloads = array(); //document-ready funcs and/or instructions
	//extra form-properties
	private $extradata = array();

	public function __set($name,$value)
	{
		$this->extradata[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name,$this->extradata))
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

	public function __toString()
	{
		$mod = $this->formsmodule;
		$this->formsmodule = ($mod) ? $mod->GetName():'PWForms'; //no need to log all 'public' data
		$saved = $this->Fields;
		$afields = array();
		foreach ($saved as $one) {
			$afields[] = serialize($one);
		}
		$this->Fields = '||~||';
		$ret = json_encode(get_object_vars($this)); //include private properties
		$jf = json_encode($afields);
		$ret = str_replace('"||~||"',$jf,$ret);
		//reinstate
		$this->formsmodule = $mod;
		$this->Fields = $saved;
		return $ret;
	}

	// Serializable interface methods

	public function serialize()
	{
		return $this->__toString();
	}

	public function unserialize($serialized)
	{
		if ($serialized) {
			$props = json_decode($serialized);
			if ($props !== NULL) {
				$arr = (array)$props;
				foreach ($arr as $key=>$one) {
					switch ($key) {
					 case 'formsmodule':
						$this->$key =& \cms_utils::get_module($one);
						break;
					 case 'Fields':
						$members = array();
						foreach ($one as $subkey=>$mdata) {
							$members[$subkey] = unserialize($mdata);
							$members[$subkey]->formdata =& $this->formsmodule;
						    $members[$subkey]->loaded = FALSE;
						}
						$this->$key = $members;
						break;
					 case 'FieldOrders':
					 case 'templateVariables':
					 case 'jsincs':
					 case 'jsfuncs':
					 case 'jsloads':
						$this->$key = ($one) ? (array)$one : array();
						break;
					 case 'Options':
					 case 'extradata':
 						$one = (array)$one;
						$members = array();
						foreach ($one as $subkey=>$mdata) {
							if (is_object($mdata)) {
								$mdata = array($mdata);
							}
							$members[$subkey] = $mdata;
						}
						$this->$key = $members;
						break;
					 default:
						$this->$key = (is_object($one)) ? (array)$one : $one;
						break;
					}
				}
			}
		}
	}
}
