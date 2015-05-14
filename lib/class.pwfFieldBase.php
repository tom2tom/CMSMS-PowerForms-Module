<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldBase
{
	var $DisplayInForm = true;
	var $DisplayInSubmission = true;
	var $DispositionPermitted = true;
	var $FormId = -1;
	var $HasAddOp = false;
	var $HasDeleteOp = false;
	var $HasLabel = 1;
	var $HasUserAddOp = false;
	var $HasUserDeleteOp = false;
	var $HideLabel = -1;
	var $Id = -1;
	var $IsComputedOnSubmission = false;
	var $IsDisposition = false; //CHECKME used?
	var $IsEmailDisposition = false; //CHECKME used?
	var $Name = '';
	var $NeedsDiv = 1;
	var $NonRequirableField = false;
	var $Options = aray();
	var $OrderBy = '';
	var $Required = -1;
	var $SmartyEval = false; //true for textinput field whose value is to be processed via smarty
	var $Type = '';
	var $ValidationType;
	var $ValidationTypes;
	var $Value = false;

	var $formdata; //reference to shared pwfData data-object
	var $hasMultipleFormComponents = false;
	var $labelSubComponents = true;
	var $loaded = false;
	var $modifiesOtherFields = false;
	var $sortable = true;
	var $validated = true;
	var $validationErrorText;

	function __construct(&$formdata, &$params)
	{
		$this->formdata = $formdata;
		$this->ValidationTypes = array($formdata->pwfmodule->Lang('validation_none')=>'none');

		if(isset($params['form_id']))
			$this->FormId = $params['form_id'];

		if(isset($params['field_id']))
			$this->Id = $params['field_id'];

		if(isset($params['field_name']))
			$this->Name = $params['field_name'];

		if(isset($params['field_type']))
			$this->Type = $params['field_type'];

		if(isset($params['pwfp_order_by']))
			$this->OrderBy = $params['pwfp_order_by'];

		if(isset($params['pwfp_hide_label']))
			$this->HideLabel = $params['pwfp_hide_label'];
		elseif(isset($params['pwfp_set_from_form']))
			$this->HideLabel = 0;

//done to here

		if(isset($params['pwfp_required']))
		{
			$this->Required = $params['pwfp_required'];
		}
		elseif(isset($params['pwfp_set_from_form']))
		{
			$this->Required = 0;
		}
		if(isset($params['pwfp_validation_type']))
		{
			$this->ValidationType = $params['pwfp_validation_type'];
		}

		foreach($params as $thisParamKey=>$thisParamVal)
		{
			if(substr($thisParamKey,0,9) == 'pwfp_opt_')
			{
				$thisParamKey = substr($thisParamKey,9);
				$this->Options[$thisParamKey] = $thisParamVal;
			}
		}

		// Check value setup against $params
		if(isset($params['pwfp__'.$this->Id]) &&
			(is_array($params['pwfp__'.$this->Id]) || strlen($params['pwfp__'.$this->Id]) > 0))
		{
			$this->SetValue($params['pwfp__'.$this->Id]);
		}
	}

	function HasMultipleFormComponents()
	{
		return $this->hasMultipleFormComponents;
	}

	function LabelSubComponents()
	{
		return $this->labelSubComponents;
	}

	function ComputeOnSubmission()
	{
		return $this->IsComputedOnSubmission;
	}

	// Override this
	function ComputeOrder()
	{
		return 0;
	}

	function HasMultipleValues()
	{
		return ($this->hasMultipleFormComponents || $this->HasUserAddOp);
	}

	function GetFieldInputId($id, &$params, $returnid)
	{
		return $id.'pwfp__'.$this->Id;
	}

	function ModifiesOtherFields()
	{
		return $this->modifiesOtherFields;
	}

	// Mechanism for fields/dispositions to inhibit other dispositions
	function DispositionIsPermitted()
	{
		return $this->DispositionPermitted;
	}

	// Mechanism for fields/dispositions to inhibit other dispositions
	function SetDispositionPermission($permitted=true)
	{
		$this->DispositionPermitted = $permitted;
	}

	// Override this if you need to do something after the form has been disposed
	function PostDispositionAction()
	{
	}

	// Override this if you're just tweaking other fields before disposition
	function ModifyOtherFields()
	{
	}

	// Override this with a form input string or something
	// this should just be the input portion. The title
	// and any wrapping divs will be provided by the form
	// renderer.
	function GetFieldInput($id, &$params, $returnid)
	{
		return '';
	}

	// Sends logic along with field, also allows smarty logic
	// Doesn't need override in most cases
	function GetFieldLogic()
	{
		$code = $this->GetOption('field_logic','');
		if(!empty($code))
		{
			$mod = $this->formdata->pwfmodule;
			return $mod->ProcessTemplateFromData($code);
		}
	}

	// Override this with something to show users
	function StatusInfo()
	{
		return '';
	}

	function DebugDisplay()
	{
		$tmp = $this->formdata;
		$this->formdata = '[frmptr: '.$tmp->GetId().']';
		debug_display($this);
		$this->formdata = $tmp;
	}

	function GetId()
	{
		return $this->Id;
	}

	function HasAddOp()
	{
		return $this->HasAddOp;
	}

	// Override this when necessary or useful
	function DoOptionAdd(&$params)
	{
	}

	// Override this
	function GetOptionAddButton()
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->Lang('add_options');
	}

	function HasDeleteOp()
	{
		return $this->HasDeleteOp;
	}

	// Override this when necessary or useful
	function DoOptionDelete(&$params)
	{
	}

	// Override this
	function GetOptionDeleteButton()
	{
		$mod = $this->formdata->pwfmodule;
		return $mod->Lang('delete_options');
	}

	function SetName($name)
	{
		$this->Name = $name;
	}

	function GetName()
	{
		return $this->Name;
	}

	function GetAlias()
	{
		return $this->GetOption('field_alias','');
	}

	function GetVariableName()
	{
		$maxvarlen = 24;
		$string = strtolower(preg_replace(array('/\s+/','/\W/'),array('_','_'),$this->Name));
		if(strlen($string) > $maxvarlen)
		{
			$string = substr($string,0,$maxvarlen);
			$pos = strrpos($string,'_');
			if($pos !== false)
			{
				$string = substr($string,0,$pos);
			}
		}
	   return $string;
	}

	function GetCSSIdTag($suffix='')
	{
		return ' id="'.$this->GetCSSId($suffix).'"';
	}

	function GetCSSId($suffix='')
	{
		$alias = $this->GetAlias();
		if(empty($alias))
		{
			$cssid = 'pwfp__'.$this->Id;
			if($this->HasMultipleFormComponents())
			{
				$cssid .= '_1';
			}
		}
		else
		{
			$cssid = $alias;
		}
		$cssid .= $suffix;
		return $cssid;
	}

	function SetAlias($alias)
	{
		$this->SetOption('field_alias',$alias);
	}

	function SetSmartyEval($bool)
	{
		$this->SmartyEval = $bool;
	}

	function GetSmartyEval()
	{
		return $this->SmartyEval;
	}

	function GetOrder()
	{
		return $this->OrderBy;
	}

	function SetOrder($order)
	{
		$this->OrderBy = $order;
	}

	function GetFieldType()
	{
		return $this->Type;
	}

	function SetFieldType($type)
	{
		return $this->Type = $type;
	}

	function IsDisposition()
	{
		return $this->IsDisposition;
	}

	function IsEmailDisposition()
	{
		return $this->IsEmailDisposition;
	}

	function HasLabel()
	{
		return $this->HasLabel;
	}

	function NeedsDiv()
	{
		return $this->NeedsDiv;
	}

	function SetHideLabel($hide)
	{
		$this->HideLabel = $hide?1:0;
	}

	function HideLabel()
	{
		return ($this->HideLabel==1);
	}

	function DisplayInForm()
	{
		return $this->DisplayInForm;
	}

	function DisplayInSubmission()
	{
//		return ($this->DisplayInForm && $this->DisplayInSubmission);
		return $this->DisplayInSubmission;
	}

	function IsNonRequirableField()
	{
		return $this->NonRequirableField;
	}

	function IsRequired()
	{
		return ($this->Required == 1);
	}

	function SetRequired($required)
	{
		$this->Required = $required?1:0;
	}

	function ToggleRequired()
	{
		$this->Required = ($this->Required?0:1);
	}

	function GetValidationTypes()
	{
		return $this->ValidationTypes;
	}

	function GetValidationType()
	{
		return $this->ValidationType;
	}

	function SetValidationType($theType)
	{
		$this->ValidationType = $theType;
	}

	function IsValid()
	{
		return $this->validated;
	}

	function GetValidationErrorText()
	{
		return $this->validationErrorText;
	}

	// Override this with a displayable type
	function GetDisplayType()
	{
		return $this->formdata->pwfmodule->Lang('field_type_'.$this->Type);
	}

	/*
	  PrePopulateBaseAdminForm:

	  Construct base content for field add/edit.
	  See also - comments below, for PrePopulateAdminForm()

	  Returns: an associative array with keys 'main' and 'adv', for use
	  by the relevant PrePopulateAdminForm(), and ultimately by
	  pwfFieldOperations::AddEdit().
	*/
	function PrePopulateBaseAdminForm($formDescriptor,$disposeOnly=0)
	{
		$mod = $this->formdata->pwfmodule;

		// Do the field type check
		if($this->Type == '')
		{
			if($disposeOnly == 1)
			{
				$typeInput = $mod->CreateInputDropdown($formDescriptor, 'field_type',array_merge(array($mod->Lang('select_type')=>''),$mod->disp_field_types), -1,'', 'onchange="this.form.submit()"');
			}
			else
			{
				$typeInput = $mod->CreateInputDropdown($formDescriptor, 'field_type',array_merge(array($mod->Lang('select_type')=>''),$mod->field_types), -1,'', 'onchange="this.form.submit()"');
			}
		}
		else
		{
			$typeInput = $this->GetDisplayType().$mod->CreateInputHidden($formDescriptor, 'field_type', $this->Type);
		}

		// Init main tab
		$main = array(
			array($mod->Lang('title_field_name'),$mod->CreateInputText($formDescriptor, 'field_name', $this->GetName(), 50)),
			array($mod->Lang('title_field_type'),$typeInput)
			);

		// Init advanced tab
		$adv = array();

		// if we know our type, we can load up with additional options
		if($this->Type != '')
		{
			// validation types?
			if(count($this->GetValidationTypes()) > 1)
			{
				$validInput = $mod->CreateInputDropdown($formDescriptor, 'pwfp_validation_type', $this->GetValidationTypes(), -1, $this->GetValidationType());
			}
			else
			{
				$validInput = $mod->Lang('automatic');
			}

			if(!$this->IsNonRequirableField())
			{
				$main[] = array($mod->Lang('title_field_required'),
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_required', 1, $this->IsRequired()),
				$mod->Lang('title_field_required_long'));
			}

			$main[] = array($mod->Lang('title_field_validation'),$validInput);

			if($this->HasLabel == 1)
			{
				$adv[] = array($mod->Lang('title_hide_label'),
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_hide_label', 1, $this->HideLabel()),
				$mod->Lang('title_hide_label_long'));
			}

			$alias = $this->GetOption('field_alias','');
			if($alias == '')
			{
				$alias = 'fld'.$this->GetId();
			}

			$adv[] = array($mod->Lang('title_field_alias'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_field_alias', $this->GetOption('field_alias'), 50));

			if($this->DisplayInForm())
			{
				$adv[] = array($mod->Lang('title_field_css_class'),$mod->CreateInputText($formDescriptor, 'pwfp_opt_css_class', $this->GetOption('css_class'), 50));
				$adv[] = array($mod->Lang('title_field_helptext'),$mod->CreateTextArea(false, $formDescriptor, $this->GetOption('helptext',''), 'pwfp_opt_helptext','pwf_shortarea'));
				$adv[] = array($mod->Lang('title_field_javascript'),$mod->CreateTextArea(false, $formDescriptor, $this->GetOption('javascript',''),
					'pwfp_opt_javascript','pwf_shortarea','','', '', '80', '15','','js'),$mod->Lang('title_field_javascript_long'));
				$adv[] = array($mod->Lang('title_field_logic'),$mod->CreateTextArea(false, $formDescriptor, $this->GetOption('field_logic',''),
					'pwfp_opt_field_logic','pwf_shortarea','','', '', '80', '15'),$mod->Lang('title_field_logic_long'));
			}
		}
		else
		{
			// no advanced options until we know our type
			$adv[] = array($mod->Lang('tab_advanced'),$mod->Lang('notice_select_type'));
		}

		return array('main'=>$main, 'adv'=>$adv);
	}

	/*
	  PrePopulateAdminForm:

	  Construct content for field add/edit. Override this.

	  Array keys presently recognised are: 'main', 'adv', 'table', 'extra', 'funcs'.
	  'main' and 'adv', if they exist, refer to arrays of content for the main and
	  advanced settings tabs shown when adding/editing the field. Each member of those
	  arrays is itself an array of title, input and (optionally) help values. That
	  input should be a form input suitable for that field attribute/option.

	  Returns: an associative array with 0 or more keys recognised by pwfFieldOperations::AddEdit().
	*/
	function PrePopulateAdminForm($formDescriptor)
	{
		return array();
	}

	// Override this.
	// This gives you a chance to alter the array contents before
	// they get rendered.
	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
	}

	// Override this as necessary
	function PostAdminSubmitCleanup()
	{
	}

	// Override this as necessary
	function PostFieldSaveProcess(&$params)
	{
	}

	function RemoveAdminField(&$array, $fieldname)
	{
		$reqIndex = -1;
		for ($i=0;$i<count($array);$i++)
		{
			if(isset($array[$i]->title) && $array[$i]->title == $fieldname)
			{
				$reqIndex = $i;
			}
		}
		if($reqIndex != -1)
		{
			array_splice($array, $reqIndex, 1);
		}
	}

	function CheckForAdvancedTab(&$advArray)
	{
		if(count($advArray) == 0)
		{
			$advArray[0]->title = $mod->Lang('tab_advanced');
			$advArray[0]->input = $mod->Lang('title_no_advanced_options');
		}
	 }

	// clear fields unused by invisible dispositions
	function HiddenDispositionFields(&$mainArray, &$advArray, $hideReq=true)
	{
		$mod = $this->formdata->pwfmodule;

		// remove the "required" field
		if($hideReq)
		{
			$this->RemoveAdminField($mainArray, $mod->Lang('title_field_required'));
		}

		// remove the "hide name" field
		$this->RemoveAdminField($advArray, $mod->Lang('title_hide_label'));

		// remove the "css" field
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_css_class'));

		// hide "javascript"
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_javascript'));

		// hide "logic"
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_logic'));

		// hide "help text"
		$this->RemoveAdminField($advArray, $mod->Lang('title_field_helptext'));

		$this->CheckForAdvancedTab($advArray);
	}

	// Override this.
	// Returns an array: first value is a true or false (whether or not
	// the value is valid), the second is a message
	function Validate()
	{
		$this->validated = true;
		$this->validatedErrorText = '';
		return array($this->validated, $this->validatedErrorText);
	}

	// Override this.
	function GetHumanReadableValue($as_string=true)
	{
		$mod = $this->formdata->pwfmodule;
		if($this->Value !== false)
		{
			$ret = $this->Value;
		}
		else
		{
			$ret = $this->formdata->GetAttr('unspecified',$mod->Lang('unspecified'));
		}
		if($as_string)
		{
			return $ret;
		}
		else
		{
			return array($ret);
		}
	}

	// Override this
	function GetAllHumanReadableValues()
	{
		$mod = $this->formdata->pwfmodule;
		if(in_array('option_value',$this->GetOptionNames()))
		{
			if(count($this->GetOption('option_value')) > 0)
			{
				return $this->GetOption('option_value');
			}
		}
		return false;
	}

	// Override this if you have some unusual format for values,
	// especially if "false" is a valid value!
	function HasValue($deny_blank_responses=false)
	{
		if($this->GetFieldType()=='TextField' || $this->GetFieldType()=='TextAreaField')
		{
			// fields with defaults
			$def = $this->GetOption('default','');
			if($this->Value !== false &&
				($def == '' || $this->Value != $def))
			{
				if($deny_blank_responses && !is_array($this->Value)
					&& preg_match('/^\s+$/',$this->Value))
				{
					return false;
				}
				return true;
			}
		}
		else if($this->Value !== false)
		{
			if($deny_blank_responses && !is_array($this->Value)
				&& preg_match('/^\s+$/',$this->Value))
			{
				return false;
			}
			return true;
		}
		return false;
	}

	// probably don't need to override this
	function GetValue()
	{
		return $this->Value;
	}

	// Override this? Returns the (possibly converted) value of the field.
	function GetArrayValue($index)
	{
		if($this->Value !== false)
		{
			if(is_array($this->Value))
			{
				if(isset($this->Value[$index]))
				{
					return $this->Value[$index];
				}
			}
			elseif($index == 0)
			{
				return $this->Value;
			}
		}
		return false;
	}

	// Override this? Returns true if the value is contained in the Value array
	function FindArrayValue($value)
	{
		if($this->Value !== false)
		{
			if(is_array($this->Value))
			{
				return array_search($value,$this->Value);
			}
			elseif($this->Value == $value)
			{
				return true;
			}
		}
		return false;
	}

	function ResetValue()
	{
		$this->Value = false;
	}

	// Override this if necessary to convert type or something
	function SetValue($valStr)
	{
		//error_log($this->GetName().':'.print_r($valStr,true));
		$fm = $this->formdata;
		if($this->Value === false)
		{
			if(is_array($valStr))
			{
				$this->Value = $valStr;
				for ($i=0;$i<count($this->Value);$i++)
				{
					while ($this->Value[$i] != $fm->unmy_htmlentities($this->Value[$i]))
					{
						$this->Value[$i] = $fm->unmy_htmlentities($this->Value[$i]);
					}
				}
			}
			else
			{
				while ($this->Value !== $fm->unmy_htmlentities($valStr))
				{
					 $this->Value = $fm->unmy_htmlentities($valStr);
				}
			}
		}
		else
		{
			while ($valStr != $fm->unmy_htmlentities($valStr))
			{
				$valStr = $fm->unmy_htmlentities($valStr);
			}
			if(!is_array($this->Value))
			{
				$this->Value = array($this->Value);
			}
			$this->Value[] = $valStr;
		}
	}

	function RequiresValidation()
	{
		if($this->ValidationType == 'none')
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	function DoesFieldNameExist()
	{
		$mod = $this->formdata->pwfmodule;

		// field name in use??
		if($mod->GetPreference('unique_fieldnames','1') == '1' &&
			$this->formdata->HasFieldNamed($this->GetName()) != $this->Id)
		{
			return array(false,$mod->Lang('field_name_in_use',$this->GetName()).
			'<br />');
		}
		return array(true,'');
	}

	function DoesFieldHaveName()
	{
		$mod = $this->formdata->pwfmodule;
		if($mod->GetPreference('require_fieldnames','1') == '1' &&
			strlen($this->GetName()) < 1)
		{
			return array(false, $mod->Lang('field_no_name').'<br />');
		}
		return array(true,'');
	 }

	// Override this if needed. Returns an array: first value is a true or
	// false (whether or not the value is valid), the second is a message
	function AdminValidate()
	{
		list($ret, $message) = $this->DoesFieldHaveName();
		if($ret)
		{
			list($ret, $message) = $this->DoesFieldNameExist();
		}
		return array($ret, $message);
	}

	/* Override this for a Form Disposition pseudo-field
	 This method can do just about anything you want it to, in order to handle
	 form contents.
	 It returns an array, where the first element is true on success, or false
	 on failure, and the second element is explanatory text for the failure
	*/
	function DisposeForm($returnid)
	{
		return array(true, '');
	}

	function ExportXML($exportValues = false)
	{
		$xmlstr = "\t<field id=\"".$this->Id."\"\n";
		$xmlstr .= "\t\ttype=\"".$this->Type."\"\n";
//		$xmlstr .= "\t\tname=\"".htmlspecialchars($this->Name)."\"\n";
		$xmlstr .= "\t\tvalidation_type=\"".$this->ValidationType."\"\n";
		$xmlstr .= "\t\torder_by=\"".$this->OrderBy."\"\n";
		$xmlstr .= "\t\trequired=\"".$this->Required."\"\n";
		$xmlstr .= "\t\thide_label=\"".$this->HideLabel."\"\n";
		$xmlstr .= "\t\tdisplay_in_submission=\"".$this->DisplayInSubmission."\"";
		$xmlstr .= ">\n";
		$xmlstr .= "\t\t\t<field_name><![CDATA[".$this->Name."]]></field_name>\n";
		$xmlstr .= "\t\t\t<options>\n".$this->OptionsAsXML()."\t\t\t</options>\n";
		if($exportValues)
		{
			$xmlstr .= "\t\t\t<human_readable_value><![CDATA[".
			$this->GetHumanReadableValue().
			"]]></human_readable_value>\n";
		}

		$xmlstr .= "</field>\n";
		return $xmlstr;
	}

	// override as necessary
	function OptionFromXML($theArray)
	{
		if($theArray['name'] != 'option')
		{
			return;
		}
		if(!isset($this->Options))
		{
			$this->Options = array();
		}
		if(isset($this->Options[$theArray['attributes']['name']]))
		{
			if(!is_array($this->Options[$theArray['attributes']['name']]))
			{
				$this->Options[$theArray['attributes']['name']] = array($this->Options[$theArray['attributes']['name']]);
			}
			array_push($this->Options[$theArray['attributes']['name']], $theArray['content']);
		}
		else
		{
//			$this->Options[$theArray['name']] = $theArray['attributes']['name'];
			$this->Options[$theArray['attributes']['name']] = $theArray['content'];
		}
	}

	// override as necessary
	function OptionsAsXML()
	{
		$xmlstr = "";
		foreach($this->Options as $name=>$value)
		{
			if(!is_array($value))
			{
				$value = array($value);
			}
			foreach($value as $thisVal)
			{
				$xmlstr .= "\t\t\t<option name=\"$name\"><![CDATA[".$thisVal.
			   "]]></option>\n";
			}
		}
		if(isset($this->Value))
		{
			if(!is_array($this->Value))
			{
				$thisVal = array($this->Value);
			}
			else
			{
				$thisVal = $this->Value;
			}
			foreach($thisVal as $thisValOut)
			{
				$xmlstr .= "\t\t\t<value><![CDATA[".$thisValOut."]]></value>\n";
			}
		}
		return  $xmlstr;
	}

	function ExportObject()
	{
		$obj = new stdClass();
		$obj->name = $this->Name;
		$obj->type = $this->Type;
		$obj->id = $this->Id;
		$obj->value = $this->GetHumanReadableValue(true);
		$obj->valueArray = $this->GetHumanReadableValue(false);
		return $obj;
	}

	function GetOptionNames()
	{
		return array_keys($this->Options);
	}

	function GetOption($optionName, $default='')
	{
		if(isset($this->Options[$optionName]))
		{
			return $this->Options[$optionName];
		}
		return $default;
	}

	function GetOptionRef($optionName)
	{
		if(isset($this->Options[$optionName]))
		{
			return $this->Options[$optionName];
		}
		return false;
	  }

	function RemoveOptionElement($optionName, $index)
	{
		if(isset($this->Options[$optionName]))
		{
			if(is_array($this->Options[$optionName]))
			{
				if(isset($this->Options[$optionName][$index]))
				{
					array_splice($this->Options[$optionName],$index,1);
				}
			}
		}
	}

	function GetOptionElement($optionName, $index, $default="")
	{
		if(isset($this->Options[$optionName]))
		{
			if(is_array($this->Options[$optionName]))
			{
				if(isset($this->Options[$optionName][$index]))
				{
					return $this->Options[$optionName][$index];
				}
			}
			elseif($index == 0)
			{
				return $this->Options[$optionName];
			}
		}

		return $default;
	}

	function SetOption($optionName, $optionValue)
	{
		$this->Options[$optionName] = $optionValue;
	}

	function PushOptionElement($optionName, $val)
	{
		if(isset($this->Options[$optionName]))
		{
			if(is_array($this->Options[$optionName]))
			{
				array_push($this->Options[$optionName],$val);
			}
			else
			{
				$this->Options[$optionName] = array($this->Options[$optionName],$val);
			}
		}
		else
		{
			$this->Options[$optionName] = $val;
		}
	}

	function LoadField(&$params)
	{
		if($this->Id > 0)
		{
			$this->Load($this->Id, $params, true);
		}
		return;
	}

	// customized version of API function CreateTextInput. This doesn't throw in an ID that's the same as the field name.
	function TextField($id, $name, $value='', $size='10', $maxlength='255', $addttext='')
	{
		$value = cms_htmlentities(html_entity_decode($value));
		$id = cms_htmlentities(html_entity_decode($id));
		$name = cms_htmlentities(html_entity_decode($name));
		$size = ($size!=''?cms_htmlentities($size):10);
		$maxlength = ($maxlength!=''?cms_htmlentities($maxlength):255);

		$value = str_replace('"', '&quot;', $value);

		$text = '<input type="text" name="'.$id.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'"';
		if($addttext != '')
		{
			$text .= ' ' . $addttext;
		}
		$text .= " />\n";
		return $text;
	}

	// loadDeep also loads all options for a field
	function Load($id, &$params, $loadDeep=false)
	{
		$pref = cms_db_prefix();
		$sql = 'SELECT * FROM '.$pref.'module_pwf_field WHERE field_id=?';
		$db = $this->formdata->pwfmodule->dbHandle;
		if($result = $db->GetRow($sql, array($this->Id)))
		{
			if(strlen($this->Name) < 1)
			{
				$this->Name = $result['name'];
			}
			if(strlen($this->ValidationType) < 1)
			{
				$this->ValidationType = $result['validation_type'];
			}
			$this->Type = $result['type'];
			$this->OrderBy = $result['order_by'];
			if($this->Required == -1)
			{
				$this->Required = $result['required'];
			}
			if($this->HideLabel == -1)
			{
				$this->HideLabel = $result['hide_label'];
			}
		}
		else
		{
			return false;
		}
		$this->loaded = true;
		if($loadDeep)
		{
			$sql = 'SELECT name, value FROM '.$pref.
			  'module_pwf_field_opt WHERE field_id=? ORDER BY option_id';
			$rs = $db->Execute($sql,array($this->Id));
			if($rs)
			{
				$tmpOpts = array();
				while ($results = $rs->FetchRow())
				{
					if(isset($tmpOpts[$results['name']]))
					{
						if(!is_array($tmpOpts[$results['name']]))
						{
							$tmpOpts[$results['name']] = array($tmpOpts[$results['name']]);
						}
						array_push($tmpOpts[$results['name']],$results['value']);
					}
					else
					{
						$tmpOpts[$results['name']]=$results['value'];
					}
				}
				$rs->Close();
				$this->Options = array_merge($tmpOpts,$this->Options);
			}

			if(isset($params['value_'.$this->Name]) &&
				(is_array($params['value_'.$this->Name]) ||
				 strlen($params['value_'.$this->Name]) > 0))
			{
				$this->SetValue($params['value_'.$this->Name]);
			}

			if(isset($params['value_fld'.$this->Id]) &&
				(is_array($params['value_fld'.$this->Id]) ||
				 strlen($params['value_fld'.$this->Id]) > 0))
			{
				$this->SetValue($params['value_fld'.$this->Id]);
			}
		}

		return true;
	}

	function Store($storeDeep=false)
	{
		$db = $this->formdata->pwfmodule->dbHandle;
		$pref = cms_db_prefix();
		if($this->Id == -1)
		{
			$this->Id = $db->GenID($pref.'module_pwf_field_seq');
			$sql = 'INSERT INTO '.$pref.'module_pwf_field (field_id,form_id,name,type,' .
			  'required,validation_type,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
					array($this->Id, $this->FormId, $this->Name, $this->Type,
						($this->Required?1:0), $this->ValidationType, $this->HideLabel,
						$this->OrderBy));
		}
		else
		{
			$sql = 'UPDATE ' .$pref.
			  'module_pwf_field SET name=?,type=?,required=?,validation_type=?,order_by=?,hide_label=? WHERE field_id=?';
			$res = $db->Execute($sql,
					array($this->Name, $this->Type, ($this->Required?1:0),
						$this->ValidationType,$this->OrderBy, $this->HideLabel, $this->Id));
		}

		if($storeDeep)
		{
			// drop old options
			$sql = 'DELETE FROM '.$pref.'module_pwf_field_opt where field_id=?';
			$res = $db->Execute($sql,array($this->Id)) && $res;

			foreach($this->Options as $thisOptKey=>$thisOptValueList)
			{
				if(!is_array($thisOptValueList))
				{
					$thisOptValueList = array($thisOptValueList);
				}
				foreach($thisOptValueList as $thisOptValue)
				{
					$optId = $db->GenID($pref.'module_pwf_field_opt_seq');
					$sql = 'INSERT INTO ' .$pref.
					  'module_pwf_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
					$res = $db->Execute($sql,
							array($optId, $this->Id, $this->FormId, $thisOptKey,$thisOptValue)) && $res;
				}
			}
		}
		return $res;
	}

	function Delete()
	{
		if($this->Id == -1)
		{
			return false;
		}
		$pref = cms_db_prefix();
		$db = $this->formdata->pwfmodule->dbHandle;
		$sql = 'DELETE FROM '.$pref.'module_pwf_field where field_id=?';
		$res = $db->Execute($sql,array($this->Id));
		$sql = 'DELETE FROM '.$pref.'module_pwf_field_opt where field_id=?';
		$res = $db->Execute($sql,array($this->Id)) && $res;
		return $res;
	}
}

?>