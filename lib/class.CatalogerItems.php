<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This class provides a dynamic multiselect list to allow selecting one or
more items from the cataloger module. The list is filtered by an array of
options specified in the admin
*/
namespace PWForms;

class CatalogerItems extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsInput = TRUE;
		$this->Type = 'CatalogerItems';
		$this->ValidationTypes = array();
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if ($cataloger)
			return '';
		return $mod->Lang('error_module_cataloger');
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		if ($this->HasValue()) {
			if (is_array($this->Value)) {
				if ($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$this->Value);
				else {
					$ret = $this->Value; //copy
					return $ret;
				}
			}
			$ret = $this->Value;
		} else {
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}

		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if ($cataloger) {
			$main[] = array($mod->Lang('title_field_height'),
							$mod->CreateInputText($id,'opt_lines',$this->GetOption('lines','5'),3,3),
							$mod->Lang('help_field_height'));
			$main[] = array($mod->Lang('title_name_regex'),
							$mod->CreateInputText($id,'opt_nameregex',$this->GetOption('nameregex'),25,25),
							$mod->Lang('help_name_regex'));
			$main[] = array('','',$mod->Lang('help_cataloger_attribute_fields'));

			$attrs = cmsms()->variables['catalog_attrs']; //TODO bad module behaviour
			foreach ($attrs as &$one) {
				if (!$one->is_text) {
					$safeattr = strtolower(preg_replace('/\W/','',$one->attr));
					$main[] = array($one->attr,
									$mod->CreateInputText($id,'opt_attr_'.$safeattr,
									$this->GetOption('attr_'.$safeattr),30,80));
				}
			}
			unset($one);
		} else
			$main[] = array('<span style="color:red">'.$mod->Lang('warning').'</span>',
				'',$mod->Lang('error_module_cataloger'));

		return array('main'=>$main,'adv'=>$adv);
	}

	public function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if (!$cataloger)
			return $mod->Lang('error_module_cataloger');

		$cataloger->getUserAttributes();
		$gCms = cmsms();
		$tmp_attrs = $gCms->variables['catalog_attrs']; //BAD MODULE BEHAVIOUR!!
		$lines = (int)$this->GetOption('lines',5);
		$nameregex = trim($this->GetOption('nameregex'));

		$attrs = array();
		foreach ($tmp_attrs as $one) {
			$safeattr = strtolower(preg_replace('/\W/','',$one->attr));
			$val = trim($this->GetOption('attr_'.$safeattr));
			if ($val) {
				$one->input = $val;
				$attrs[] = $one;
			}
		}

		$tplvars = array(); //TODO need global vars?
		// put the hidden fields into smarty
		if (!isset($gCms->variables['pwf_smarty_vars_set'])) { //FIXME
			foreach ($this->formdata->Fields as &$one) {
				if ($one->GetFieldType() != 'Hidden') continue;
				$tplvars[$one->ForceAlias()] = $one->Value;
				$tplvars['fld_'.$one->GetId()] = $one->Value;
			}
			unset($one);
			$gCms->variables['pwf_smarty_vars_set'] = 1; //TODO BAD MODULE BEHAVIOUR 
		}

		// for each hierarchy item (from the root down)
		$hm = $gCms->GetHierarchyManager();
		$allcontent = $hm->getFlatList();
		$choices = array();
		foreach ($allcontent as $onepage) {
			$content = $onepage->GetContent();

			// if it's not a cataloger item continue
			if ($content->Type() != 'catalogitem') continue;

			// if it's not active or shown in menu continue
			if (!$content->Active() || !$content->ShowInMenu()) continue;

			// if the nameregex string is not empty,and the name does not
			// match the regex,continue
			if (!empty($nameregex) && !preg_match('/'.$nameregex.'/',$content->Name())) {
				continue;
			}

			// for each attribute
			$passed = TRUE;
			foreach ($attrs as $oneattr) {
				// parse the field value through smarty, without cacheing
				$expr = Utils::ProcessTemplateFromData($mod,$oneattr->input,$tplvars);
				if (empty($expr)) continue; // no expression for this field. pass

				// get the value for this attribute for this content
				$currentval = $content->GetPropertyValue($oneattr->attr);
				if (empty($currentval)) {
					// no value for this field,but we have an expression
					// this catalog item fails.
					$passed = FALSE;
					break;
				}

				list($type,$expr) = explode(':',$expr,2);
				$type = trim($type);
				$expr = trim($expr);

				$res = FALSE;
				switch (strtolower($type)) {
				 case 'range':
					// for ranges:
					// grab min and max values
					list($minval,$maxval) = explode('to',$expr);
					$minval = trim($minval); $maxval = trim($maxval);
					// check for numeric
					if (!is_numeric($minval) || !is_numeric($maxval)) {
						// can't test ranges with non numeric values
						// so fail
						$passed = FALSE;
						break;
					}
					if ($minval > $maxval) {
						$tmp = $minval;
						$minval = $maxval;
						$maxval = $tmp;
					}
					$res = ($currentval >= $minval && $currentval <= $maxval);
					break;
				 case 'multi':
					// for multi
					$tmp = explode('|',$expr);
					$res = in_array($currentval,$tmp);
					break;
				}

				if (!$res) {
					$passed = FALSE;
					break;
				}
			} // foreach attr

			if ($passed) {
				$choices[$content->Name()] = $content->Name();
			}
		} // foreach content

		// Do we have something to display?
		if ($choices) {
			$size = min($lines,count($choices));
			$size = min(50,$size); // maximum 50 lines, though this is probably big

			$val = array();
			if (property_exists($this,'Value')) {
				$val = $this->Value;
				if (!is_array($this->Value))
					$val = array($this->Value);
			}
			$tmp = $mod->CreateInputSelectList(
				$id,$this->formdata->current_prefix.$this->Id.'[]',
				$choices,$val,$size,'id="'.$this->GetInputId().'"');
			return $this->SetClass($tmp);
		}

		return ''; // error
	}

}
