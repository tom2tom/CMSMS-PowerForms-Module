<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/*
A class to provide a dynamic multiselect list to allow selecting one or
more items from the cataloger.
The item list is filtered by an array of options as specified in the admin.
*/
class pwfCatalogerItemsField extends pwfFieldBase
{
	var $optionCount;
	var $optionAdd;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'CatalogerItemsField';
		$this->DisplayInForm = true;
		$this->NonRequirableField = false;
		$this->HasAddOp = false;
		$this->HasDeleteOp = false;
		$this->ValidationTypes = array();
		$this->optionAdd = 0;
		$this->sortable = false;
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$gCms = cmsms();

		$mod = $this->formdata->pwfmodule;
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if(!$cataloger)
		{
			return $mod->Lang('error_cataloger_module_not_available');
		}

		$cataloger->getUserAttributes();
		$tmp_attrs = $gCms->variables['catalog_attrs'];
		$lines = (int)$this->GetOption('lines','5');
		$nameregex = trim($this->GetOption('nameregex',''));

		$attrs = array();
		foreach($tmp_attrs as $one)
		{
		$safeattr = strtolower(preg_replace('/\W/','',$one->attr));
		$val = trim($this->GetOption('attr_'.$safeattr,''));
		if(empty($val)) continue;
		$one->input = $val;
		$attrs[] = $one;
		}

		$smarty = $gCms->GetSmarty();
		// put the hidden fields into smarty.
		if(!isset($gCms->variables['pwf_smarty_vars_set'])) //FIXME
		{
			$theFields = $this->formdata->GetFields();

			for($i = 0; $i < count($theFields); $i++)
			{
				if($theFields[$i]->GetFieldType() != 'HiddenField') continue;
				$smarty->assign('fld_'.$theFields[$i]->GetId(),$theFields[$i]->Value);
				if($theFields[$i]->GetAlias() != '')
				{
					$smarty->assign($theFields[$i]->GetAlias(),$theFields[$i]->Value);
				}
			}

			$gCms->variables['pwf_smarty_vars_set'] = 1; //FIXME
		}

		// for each hierarchy item (from the root down)
		$hm = $gCms->GetHierarchyManager();
		$allcontent = $hm->getFlatList();
		$results = array();
		foreach($allcontent as $onepage)
		{
			$content = $onepage->GetContent();

			// if it's not a cataloger item continue
			if($content->Type() != 'catalogitem') continue;

			// if it's not active or shown in menu continue
			if(!$content->Active() || !$content->ShowInMenu()) continue;

			// if the nameregex string is not empty, and the name does not
			// match the regex, continue
			if(!empty($nameregex) && !preg_match('/'.$nameregex.'/',$content->Name()))
			{
				continue;
			}

			// for each attribute
			$passed = true;
			foreach($attrs as $oneattr)
			{
				// parse the field value through smarty, without cacheing (->fetch() fails)
				$expr = $mod->ProcessTemplateFromData($oneattr->input);
				if(empty($expr)) continue; // no expression for this field. pass

				// get the value for this attribute for this content
				$currentval = $content->GetPropertyValue($oneattr->attr);
				if(empty($currentval))
				{
					// no value for this field, but we have an expression
					// this catalog item fails.
					$passed = false;
					break;
				}

				list($type,$expr) = explode(':',$expr,2);
				$type = trim($type);
				$expr = trim($expr);

				$res = false;
				switch(strtolower($type))
				{
				 case 'range':
					// for ranges:
					// grab min and max values
					list($minval,$maxval) = explode('to',$expr);
					$minval = trim($minval); $maxval = trim($maxval);
					// check for numeric
					if(!is_numeric($minval) || !is_numeric($maxval))
					{
						// can't test ranges with non numeric values
						// so fail
						$passed = false;
						break;
					}
					if($minval > $maxval)
					{
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

				if(!$res)
				{
					$passed = false;
					break;
				}
			} // foreach attr

			if($passed)
			{
				$results[$content->Name()] = $content->Name();
			}
		} // foreach content

		// All done, do we have something to display?
		if(count($results))
		{
			$size = min($lines,count($results));
			$size = min(50,$size); // maximum 50 lines, though this is probably big

			$val = array();
			if($this->Value !== false)
			{
				$val = $this->Value;
				if(!is_array($this->Value))
				{
					$val = array($this->Value);
				}
			}
			$cssid = $this->GetCSSIdTag();
			return $mod->CreateInputSelectList($id,'pwfp__'.$this->Id.'[]', $results, $val,
						   $size, $cssid);
		}

		return ''; // error
	}

	function StatusInfo()
	{
		// return a string for displaying in the options field
		$mod = $this->formdata->pwfmodule;
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if(!$cataloger)
		{
			return $mod->Lang('error_cataloger_module_not_available');
		}
		return '';
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$main = array();
		$cataloger = $mod->GetModuleInstance('Cataloger');
		if(!$cataloger)
		{
			$main[] = array($mod->Lang('warning'),$mod->Lang('error_cataloger_module_not_available'));
		}
		else
		{
			$main[] = array($mod->Lang('title_field_height'),
					 $mod->CreateInputText($formDescriptor,
							 'pwfp_opt_lines', $this->GetOption('lines','5'),3,3),
					 $mod->Lang('help_field_height'));

			$main[] = array($mod->Lang('title_name_regex'),
					 $mod->CreateInputText($formDescriptor,
							 'frbp_opt_nameregex', $this->GetOption('nameregex',''),25,25),
					 $mod->Lang('help_name_regex'));

			$main[] = array('','',$mod->Lang('help_cataloger_attribute_fields'));

			$attrs = cmsms()->variables['catalog_attrs'];
			foreach($attrs as $one)
			{
				if($one->is_text) continue;
				$safeattr = strtolower(preg_replace('/\W/','',$one->attr));
				$main[] = array($one->attr,
					 $mod->CreateInputText($formDescriptor,
							'pwfp_opt_attr_'.$safeattr,
								   $this->GetOption('attr_'.$safeattr,''),30,80));
			}
		}

		return array('main'=>$main);
	}

	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		$form = $this->formdata;
		if($this->HasValue())
		{
			$fieldRet = array();
			if(!is_array($this->Value))
			{
				$this->Value = array($this->Value);
			}
			if($as_string)
			{
				return join($form->GetAttr('list_delimiter',','),$this->Value);
			}
			else
			{
				return array($this->Value);
			}
		}
		else
		{
			if($as_string)
			{
				return $mod->Lang('unspecified');
			}
			else
			{
				return array($mod->Lang('unspecified'));
			}
		}
	}

}
?>