<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FormData implements \Serializable
{
	public $formsmodule = NULL; //reference to PWForms-module object
	//known form-properties
	public $Alias = '';
	public $Fields = []; //merged array of display and/or disposition field objects, each key = field->Id
	public $FieldOrders = FALSE; //when needed, set to ordered array representing field->Orderby
	public $Id = 0;
	public $Name = '';
	//extra form-properties
	public $XtraProps = [];
	//diplay-time properties
	public $Page = 0; //current page in the form
	public $PagesCount = 0; //no. of pages in the form
	//time-specific object-name-prefixes (for bot-combat), must begin with 'pwfp_NNN_' where N = digit
	public $current_prefix = FALSE; //for current 30-minute period
	public $prior_prefix = FALSE; //for prior-period
	//container for form-script accumulators (won't work in array e.g. XtraProps[] if array_merge() used directly)
	public $Jscript = null; //init & use in & via populate.show
//	public $sampleTemplateCode = '';
	public $templateVariables = []; //extra 'global' items for template-help, each like 'var_name'=>'help_lang_key'

	public function __set($name, $value)
	{
		$this->XtraProps[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->XtraProps)) {
			return $this->XtraProps[$name];
		}
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
		return isset($this->XtraProps[$name]);
	}

	public function __unset($name)
	{
		unset($this->XtraProps[$name]);
	}

	public function __toString()
	{
		$mod = $this->formsmodule;
		$this->formsmodule = ($mod) ? $mod->GetName():'PWForms'; //no need to log all 'public' data
		$saved = $this->Fields;
		$afields = [];
		foreach ($saved as $i=>$one) {
			$afields[$i] = serialize($one);
		}
		$this->Fields = '||~||';
		$ret = json_encode(get_object_vars($this)); //include private properties
		$jf = json_encode($afields);
		$ret = str_replace('"||~||"', $jf, $ret);
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
						$members = [];
						foreach ($one as $i=>$mdata) {
							$i = (int)$i;
							$members[$i] = unserialize($mdata);
							if ($members[$i]) { //not marked for delete
								$members[$i]->formdata =& $this;
							}
						}
						$this->$key = $members;
						break;
					 case 'FieldOrders':
					 case 'templateVariables':
					 case 'jsincs':
					 case 'jsfuncs':
					 case 'jsloads':
						$this->$key = ($one) ? (array)$one : [];
						break;
					 case 'XtraProps':
						$one = (array)$one;
						$members = [];
						foreach ($one as $subkey=>$mdata) {
							if (is_object($mdata)) {
								$mdata = [$mdata];
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
