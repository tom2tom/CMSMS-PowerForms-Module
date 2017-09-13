<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class FormData implements \Serializable
{
	public $pwfmod = NULL; //reference to PWForms-module object, or NULL
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
	public $Jscript = NULL; //init & use in & via populate.show
//	public $sampleTemplateCode = '';
	public $templateVariables = []; //extra 'global' items for template-help, each like 'var_name'=>'help_lang_key'

	public function __construct(&$mod=NULL, $params=NULL)
	{
		$this->pwfmod = $mod;
		if ($params) {
			if (isset($params['form_id'])) {
				$this->Id = (int)$params['form_id'];
			}
			if (isset($params['form_name'])) {
				$this->Name = trim($params['form_name']);
			}
			if (isset($params['form_alias'])) {
				$this->Alias = trim($params['form_alias']);
			}
		}
	}

	// Get array defining non-constant field properties (for e.g. export/save)
	// $nobase = FALSE to include non-ExtraProps names
	// Returned keys may be intersected with class-property names and/or database-column names
	// Values indicate property type:  0 bool; 1 number; 2 string, 3 template; 4 mixed; +10 for an XtraProp
	// Subclass when there are also field-specific properties
	public function GetMutables($nobase=TRUE)
	{
		$vars = ($nobase) ? [] : [
		'Id' => 1,
		'form_id' => 1,
		'Name' => 2,
		'name' => 2,
		'Alias' => 2,
		'alias' => 2,
		];
		$vars += [
		'blank_invalid' => 10,
		'css_class' => 12,
		'css_file' => 12,
		'form_template' => 13,
		'help_icon' => 12,
		'inline' => 10,
		'input_button_safety' => 10,
		'list_delimiter' => 12,
		'next_button_text' => 12,
		'predisplay_each_udt' => 12,
		'predisplay_udt' => 12,
		'prev_button_text' => 12,
		'redirect_page' => 11,
		'required_field_symbol' => 12,
		'submission_template' => 13,
		'submit_action' => 12,
		'submit_button_text' => 12,
		'submit_javascript' => 12,
		'submit_limit' => 11,
		'unspecified' => 12,
		'validate_udt' => 12,
		];
		return $vars;
	}

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
		$save = $this->pwfmod; //preserve object-reference
		$props = get_object_vars($this);
		$props['pwfmod'] = NULL;
		$props['Jscript'] = NULL;
		$props['templateVariables'] = [];
		$ret = serialize($props);
		$this->pwfmod = $save;
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
			$this->pwfmod =& \cms_utils::get_module('PWForms');
			$props = unserialize($serialized);
			if ($props !== NULL) {
				foreach ($props as $key => $one) {
					if ($key == 'Fields') {
						foreach ($one as &$obfld) {
							$obfld->formdata = &$this;
						}
						unset($obfld);
						$this->$key = $one;
					} elseif (is_array($one) && is_array($this->$key)) {
						$this->$key = array_merge_recursive($this->$key, $one);
					} else {
						$this->$key = $one;
					}
				}
				$adbg = 1;
			}
		}
	}
}
