<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFieldBase
{
	var $formdata; //reference to shared pwfData data-object
	//field status
	var $loaded = FALSE;
	var $validated = TRUE;
	//field properties
	var $DisplayInForm = TRUE;
	var $DisplayInSubmission = TRUE;
	var $DispositionPermitted = TRUE;
	var $FormId = 0;
	var $HasAddOp = FALSE;
	var $HasDeleteOp = FALSE;
	var $HasLabel = TRUE;
	var $HasUserAddOp = FALSE;
	var $HasUserDeleteOp = FALSE;
	var $HideLabel = FALSE;
	var $Id = 0;
	var $IsComputedOnSubmission = FALSE;
	var $IsDisposition = FALSE;
	var $IsEmailDisposition = FALSE;
	var $IsInput = FALSE;
	var $IsSortable = TRUE;
	var $LabelSubComponents = TRUE;
	var $MultiPopulate = FALSE; //whether Populate() generates array of objects
	var $Name = '';
	var $NeedsDiv = TRUE;
	var $NonRequirableField = FALSE;
	var $Options = array();
	var $OrderBy = 0; //form display-order
	var $Required = FALSE;
	var $SmartyEval = FALSE; //TRUE for textinput field whose value is to be processed via smarty
	var $Type = '';
	var $ValidationMessage = ''; //post-validation error message, or ''
	var $ValidationType = 'none';
	var $ValidationTypes; //if set, an array of choices suitable for populating pulldowns
	var $Value; //when set, can be scalar or array, with all content processed by pwfUtils::html_myentities_decode()

	function __construct(&$formdata,&$params)
	{
		$this->formdata = $formdata;
		$this->ValidationTypes = array($formdata->formsmodule->Lang('validation_none')=>'none');

		if(isset($params['form_id']))
			$this->FormId = $params['form_id'];

		if(isset($params['field_id']))
			$this->Id = $params['field_id'];

		if(isset($params['field_name']))
			$this->Name = $params['field_name'];

		if(isset($params['field_type']))
			$this->Type = $params['field_type'];

		if(isset($params['hide_label']))
			$this->HideLabel = $params['hide_label'];

		if(isset($params['field_required']))
			$this->Required = $params['field_required'];

		if(isset($params['validation_type']))
			$this->ValidationType = $params['validation_type'];
		//admin parameters present ?
		foreach($params as $key=>$val)
		{
			if(strncmp($key,'opt_',4) == 0)
				$this->Options[substr($key,4)] = $val;
		}
		//frontend parameter present ? TODO captcha-field value has different type of key
		$key = $this->formdata->current_prefix.$this->Id;
		if(isset($params[$key]))
			$this->Value = $params[$key];
	}

	// Returns a form-option value, or $default if the option doesn't exist
	function GetFormOption($optname,$default='')
	{
		if(isset($this->formdata->Options[$optname]))
			return $this->formdata->Options[$optname];
		else
			return $default;
	}

	// Gets the cached field-id
	function GetId()
	{
		return $this->Id;
	}

	function SetName($name)
	{
		$this->Name = $name;
	}

	// Gets the cached field-name
	function GetName()
	{
		return $this->Name;
	}

	// Check whether this field has a name or doesn't need one
	// Returns array, 1st member is T/F, 2nd is '' or message
	function FieldIsNamed()
	{
		$mod = $this->formdata->formsmodule;
		if($this->Name || !$mod->GetPreference('require_fieldnames'))
			return array(TRUE,'');
		return array(FALSE,$mod->Lang('field_no_name'));
	 }

	// Check whether this field's name is the same as anothter field's name
	// Returns array, 1st member is T/F, 2nd is '' or message
	function FieldNameInvalid()
	{
		foreach($this->formdata->Fields as &$one)
		{
			if($one->Name == $this->Name && $one->Id != $this->Id)
			{
				unset($one);
				return array(TRUE,$this->formdata->formsmodule->Lang('field_name_in_use',$this->Name));
			}
		}
		unset($one);
		return array(FALSE,'');
	}

	// Caches a new field-alias
	function SetAlias($alias)
	{
		$this->SetOption('alias',$alias);
	}

	// Gets the cached field-alias
/*	function GetAlias()
	{
		return $this->GetOption('alias');
	}
*/
	// Gets the field alias, after creating it if not already recorded
	function ForceAlias()
	{
		$alias = $this->GetOption('alias');
		if(!$alias)
		{
			$alias = $this->GetVariableName();
			if($alias)
				$this->SetOption('alias',$alias);
			else
				$alias = 'fld_'.$this->Id;
		}
		return $alias;
	}

	// Gets an alias-like string derived from field name, for use as a smarty var
	function GetVariableName()
	{
		$alias = strtolower(trim($this->Name,"\t\n\r\0 _"));
		if(!$alias)
			return '';
		$alias = preg_replace('/[^\w]+/','_',$alias);
		$parts = array_slice(explode('_',$alias),0,5);
		$alias = substr(implode('_',$parts),0,12);
		return trim($alias,'_');
	}

/*	function GetIdTag($suffix='')
	{
		return ' id="'.$this->ForceAlias().$suffix.'"';
	}
*/
	function GetInputId($suffix='')
	{
		return $this->ForceAlias().$suffix;
	}

	function GetScript($prefix=' ')
	{
		$js = $this->GetOption('javascript');
		if($js)
			return $prefix.$js;
		return '';

	}

	function SetSmartyEval($bool)
	{
		$this->SmartyEval = $bool;
	}

	function GetSmartyEval()
	{
		return $this->SmartyEval;
	}

	function SetOrder($order)
	{
		$this->OrderBy = $order;
	}

	function GetOrder()
	{
		return $this->OrderBy;
	}

	function SetFieldType($type)
	{
		$this->Type = $type;
	}

	function GetFieldType()
	{
		return $this->Type;
	}

	function IsDisposition()
	{
		return $this->IsDisposition;
	}

	function IsEmailDisposition()
	{
		return $this->IsEmailDisposition;
	}

	// Set flag determining whether this disposition field can inhibit (other) dispositions
	function SetDispositionPermission($permitted=TRUE)
	{
		$this->DispositionPermitted = $permitted;
	}

	// Get flag determining whether this disposition field can inhibit (other) dispositions
	function DispositionIsPermitted()
	{
		return $this->DispositionPermitted;
	}

	function IsInputField()
	{
		return $this->IsInput;
	}

	function HasLabel()
	{
		return $this->HasLabel;
	}

	function SetHideLabel($hide)
	{
		$this->HideLabel = $hide;
	}

	function GetHideLabel()
	{
		return $this->HideLabel;
	}

	function DisplayInForm()
	{
		return $this->DisplayInForm;
	}

	function DisplayInSubmission()
	{
		return $this->DisplayInSubmission; //&& $this->DisplayInForm
	}

	function IsNonRequirableField()
	{
		return $this->NonRequirableField;
	}

	function SetRequired($required)
	{
		$this->Required = $required;
	}

	function IsRequired()
	{
		return $this->Required;
	}

	function ToggleRequired()
	{
		$this->Required = !$this->Required;
	}

	function SetValidationType($type)
	{
		$this->ValidationType = $type;
	}

	function GetValidationType()
	{
		return $this->ValidationType;
	}

	function RequiresValidation()
	{
		return ($this->ValidationType != 'none');
	}

	function GetValidationTypes()
	{
		return $this->ValidationTypes;
	}

	function IsValid()
	{
		return $this->validated;
	}

	function GetValidationMessage()
	{
		return $this->ValidationMessage;
	}

	// Subclass this with a displayable type
	function GetDisplayType()
	{
		return $this->formdata->formsmodule->Lang('field_type_'.$this->Type);
	}

	function GetMultiPopulate()
	{
		return $this->MultiPopulate;
	}

	// Subclass this if appropriate
	function LabelSubComponents()
	{
		return $this->LabelSubComponents;
	}

	function ComputeOnSubmission()
	{
		return $this->IsComputedOnSubmission;
	}

	// Subclass this if appropriate
	function ComputeOrder()
	{
		return 0;
	}

	function NeedsDiv()
	{
		return $this->NeedsDiv;
	}

/*	function HasMultipleValues()
	{
		return ($this->MultiPopulate || $this->HasUserAddOp); //TODO multipopulate not relevant
	}
*/
	// Subclass this
	// Returns field value as a scalar or array (per $as_string), suitable for display in the form
	function GetHumanReadableValue($as_string=TRUE)
	{
		if(property_exists($this,$Value))
		{
			$ret = $this->Value;
			if(is_array($ret))
			{
				if($as_string)
					return implode($this->GetFormOption('list_delimiter',','),$ret);
				else
					return $ret; //assume array members are all displayable
			}
			else
				$ret = (string)$ret;
		}
		else
		{
			$ret = $this->GetFormOption('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	// Subclass this
	// Returns array of option values if the option is an array with member(s),
	// or else FALSE
	function GetAllHumanReadableValues()
	{
		if(array_key_exists('option_value',$this->Options))
		{
			if($this->GetOption('option_value')) //array with member(s)
				return $this->GetOption('option_value');
		}
		return FALSE;
	}

	// Subclass this if necessary to convert type or something
	function SetValue($newvalue)
	{
		if(is_array($newvalue))
		{
			$this->Value = array();
			foreach($newvalue as &$one)
				$this->Value[] = pwfUtils::html_myentities_decode($one);
			unset($one);
		}
		else
			 $this->Value = pwfUtils::html_myentities_decode($newvalue);
	}

/*	function LoadValue($newvalue)
	{
		if(property_exists($this,$Value))
		{
			if(!is_array($this->Value))
				$this->Value = array($this->Value);
			if(is_array($newvalue))
			{
				foreach($newvalue as &$one)
					$this->Value[] = pwfUtils::html_myentities_decode($one);
				unset($one);
			}
			else
				$this->Value[] = pwfUtils::html_myentities_decode($newvalue);
		}
		elseif(is_array($newvalue))
		{
			$this->Value = array();
			foreach($newvalue as &$one)
				$this->Value[] = pwfUtils::html_myentities_decode($one);
			unset($one);
		}
		else
			 $this->Value = pwfUtils::html_myentities_decode($newvalue);
	}
*/
	// Probably don't need to subclass this
	function GetValue()
	{
		return $this->Value;
	}

	function ResetValue()
	{
		unset($this->Value);
	}

	// Subclass this if needed to support some unusual format for the value
	// Returns boolean T/F indication whether the field value is present and non-default
	function HasValue($deny_blank_responses=FALSE)
	{
		if(property_exists($this,$Value))
		{
			if(isset($this->Options['default'])) // fields with defaults
			{
				$def = $this->Options['default'];
				if($def && $this->Value == $def) //TODO if array
					return FALSE;
			}
			return (!$deny_blank_responses ||
					is_array($this->Value) ||
					trim($this->Value));
		}
		return FALSE;
	}

	// Returns a member of the field-value-array, or if $index == 0, the entire value, or FALSE
	function GetArrayValue($index)
	{
		if(property_exists($this,$Value))
		{
			if(is_array($this->Value))
			{
				if(isset($this->Value[$index]))
					return $this->Value[$index];
			}
			elseif($index == 0)
				return $this->Value;
		}
		return FALSE;
	}

	// Subclass this?
	// Returns TRUE if $value is contained in array $Value or matches scalar $Value
	function FindArrayValue($value)
	{
		if(property_exists($this,$Value))
		{
			if(is_array($this->Value))
				return array_search($value,$this->Value) !== FALSE;
			elseif($this->Value == $value)
				return TRUE;
		}
		return FALSE;
	}

	function SetOption($optionName,$optionValue)
	{
		$this->Options[$optionName] = $optionValue;
	}

	// Returns a field-option value, or $default if the option doesn't exist
	function GetOption($optionName,$default='')
	{
		if(isset($this->Options[$optionName]))
			return $this->Options[$optionName];

		return $default;
	}

	// Returns array of option-values, or FALSE
	// Each array-key is the numeric-suffix to $optionName, & array-value is the stored value
	function GetOptionRef($optionName)
	{
		$len = strlen($optionName);
		$matches = array();
		foreach($this->Options as $key => &$val)
		{
			if(strpos($key,$optionName) === 0)
			{
				$o = (int)substr($key,$len);
				$matches[$o] = $val;
			}
		}
		unset($val);
		return ($matches) ? ksort($matches) : FALSE;
	  }

	function SetOptionElement($optionName,$index,$value)
	{
		$this->Options[$optionName.$index] = $value;
	}

	function GetOptionElement($optionName,$index,$default='')
	{
		$so = $optionName.$index; 
		if(isset($this->Options[$so]))
			return $this->Options[$so];
		elseif($index == 0)
		{
			if(isset($this->Options[$optionName]))
				return $this->Options[$optionName];
		}
		return $default;
	}

	function AddOptionElement($optionName,$value)
	{
		$len = strlen($optionName);
		$max = -1;
		foreach($this->Options as $key => &$one)
		{
			if (strpos($key,$optionName) === 0)
			{
				$o = (int)substr($key,$len);
				if($o > $max)
					$max = $o;
			}
		}
		unset($one);
		$index = ($max > -1) ? $max + 1 : 1;
		$this->Options[$optionName.$index] = $value;
	}

	function RemoveOptionElement($optionName,$index)
	{
		unset($this->Options[$optionName.$index]);
	}

	/**
	Load:
	@id: module id, unused here but is needed in subclass
	@params: array of action-parameters, unused here but may be needed in some subclass

	Loads data for this field from database tables
	TODO OK? Field options are merged with any existing options
	Returns: boolean T/F per successful operation
	*/
	function Load($id,&$params)
	{
		return pwfFieldOperations::LoadField($this);
	}

	/**
	Store:
	@deep: optional boolean, whether to also save all options for the field, default=FALSE
	Stores (by insert or update) data for this field in database tables.
	Multi-valued (array) options are saved merely as multiple records with same name
	Sets field->Id to real value if it was -1 i.e. a new field
	Returns: boolean T/F per success of executed db commands
	*/
	function Store($deep=FALSE)
	{
		return pwfFieldOperations::StoreField($this,$deep);
	}

	// Subclass this if needed to do stuff after the field is stored
	function PostFieldSaveProcess(&$params)
	{
	}

	/**
	Delete:
	Clears data for this field (if it's not new) from database tables
	Returns: boolean T/F per success of executed db commands
	*/
	function Delete()
	{
		if($this->Id)
			return pwfFieldOperations::RealDeleteField($this);
		return FALSE;
	}

/*	function GetFieldInputId($id,&$params)
	{
		return $id.$this->formdata->current_prefix.$this->Id;
	}
*/
	// Sends logic along with field, also allows smarty logic
	// Doesn't need subclass in most cases
	function GetFieldLogic()
	{
		$code = $this->GetOption('field_logic');
		if(!empty($code))
			return $this->formdata->formsmodule->ProcessTemplateFromData($code);
		return '';
	}

	// Subclass this with something to show users
	function GetFieldStatus()
	{
		return '';
	}

	//Whether to generate a submit-button labelled 'add',along with the field
	function HasAddOp()
	{
		return $this->HasAddOp;
	}

	// Subclass this to generate appropriate add-button label
	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	// Subclass this when necessary or useful (often, just set a flag)
	function DoOptionAdd(&$params)
	{
	}
	//Whether to generate a submit-button labelled 'delete',along with the field
	function HasDeleteOp()
	{
		return $this->HasDeleteOp;
	}
	// Subclass this to generate appropriate delete-button label
	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	// Subclass this when necessary or useful to delete option-data
	function DoOptionDelete(&$params)
	{
	}

	/**
	  PrePopulateAdminFormCommon:
	  @id: id given to the PowerForms module on execution  

	  Generates 'base'/common content for editing a field.
	  See also - comments below, for PrePopulateAdminForm()

	  Returns: array with keys 'main' and 'adv', for use by the relevant
	  PrePopulateAdminForm(), and ultimately in method.update_field.php.
	*/
	function PrePopulateAdminFormCommon($id)
	{
		$mod = $this->formdata->formsmodule;
		//init main tab content
		$main = array();
/*0*/	$main[] = array(
			$mod->Lang('title_field_name'),
			$mod->CreateInputText($id,'field_name',$this->GetName(),50));

		$alias = $this->ForceAlias();
/*1*/	$main[] = array($mod->Lang('title_field_alias'),
			$mod->CreateInputText($id,'opt_field_alias',$alias,30));

/*2*/	$main[] = array($mod->Lang('title_field_type'),
			$mod->CreateInputHidden($id,'field_type',$this->Type).$this->GetDisplayType());

		if(!$this->IsNonRequirableField())
		{
/*3*/		$main[] = array($mod->Lang('title_field_required'),
			$mod->CreateInputCheckbox($id,'field_required',1,$this->IsRequired()),
			$mod->Lang('help_field_required'));
		}
		//choice of validation type ?
		if(count($this->GetValidationTypes()) > 1)
			$validInput = $mod->CreateInputDropdown($id,'validation_type',
				$this->GetValidationTypes(),-1,$this->GetValidationType());
		else
			$validInput = $mod->Lang('automatic'); //or 'none' ?
/*3or4*/$main[] = array($mod->Lang('title_field_validation'),$validInput);

		if($this->DisplayInForm())
		{
/*4or5*/	$main[] = array($mod->Lang('title_field_helptext'),
				$mod->CreateTextArea(FALSE,$id,$this->GetOption('helptext'),
					'opt_helptext','pwf_shortarea','','','',50,8));
		}

		//init advanced tab content
		$adv = array();
		if($this->HasLabel)
		{
			$adv[] = array($mod->Lang('title_hide_label'),
				$mod->CreateInputHidden($id,'hide_label',0).
				$mod->CreateInputCheckbox($id,'hide_label',1,$this->HideLabel),
				$mod->Lang('help_hide_label'));
		}
		if($this->DisplayInForm())
		{
			$adv[] = array($mod->Lang('title_field_css_class'),
				$mod->CreateInputText($id,'opt_css_class',$this->GetOption('css_class'),30));
			$adv[] = array($mod->Lang('title_field_javascript'),
				$mod->CreateTextArea(FALSE,$id,$this->GetOption('javascript'),
					'opt_javascript','pwf_shortarea','','','',50,8,'','js'),
				$mod->Lang('help_field_javascript'));
			$adv[] = array($mod->Lang('title_field_resources'),
				$mod->CreateTextArea(FALSE,$id,$this->GetOption('field_logic'),
					'opt_field_logic','pwf_shortarea','','','',50,8),
				$mod->Lang('help_field_resources'));
		}

		return array('main'=>$main,'adv'=>$adv);
	}

	function RemoveAdminField(&$array,$fieldname)
	{
		$c = count($array);
		for ($i=0; $i<$c; $i++)
		{
			if(isset($array[$i]->title) && $array[$i]->title == $fieldname)
			{
				unset($array[$i]);
				return;
			}
		}
	}

	// Clear fields not relevant for non-displayed disposition-field
	function OmitAdminVisible(&$mainArray,&$advArray,$hideReq=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		// (maybe) no "required"
		if($hideReq)
			$this->RemoveAdminField($mainArray,$mod->Lang('title_field_required'));
		// no help
		$this->RemoveAdminField($mainArray,$mod->Lang('title_field_helptext'));
		// no "hide name"
		$this->RemoveAdminField($advArray,$mod->Lang('title_hide_label'));
		// no styling
		$this->RemoveAdminField($advArray,$mod->Lang('title_field_css_class'));
		// no input-control script
		$this->RemoveAdminField($advArray,$mod->Lang('title_field_javascript'));
	}

	/**
	PrePopulateAdminForm:
	@id: id given to the PowerForms module on execution  

	Construct content for field edit. Subclass this.
	Array keys presently recognised are: 'main','adv','table','extra','funcs'.
	'main' and 'adv', if they exist, refer to arrays of content for the main and
	advanced settings tabs shown when adding/editing the field. Each member of
	those arrays is itself an array of 1 to 3 members, for respectively generating
	title, (optional) input and (optional) help.
	That input should of course be a form input suitable for that field attribute/option.

	Returns: associative array with 0 or more keys recognised in method.update_field.php.
	*/
	function PrePopulateAdminForm($id)
	{
		return array();
	}

	// Subclass this if needed
	// Opportunity to alter (but not add to) array contents before they get rendered
	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
	}

	// Subclass this if needed
	// Returns: array, in which 1st member is boolean T/F (indicating whether or
	// not everything is ok), 2nd member is '' or a message
	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = $this->FieldIsNamed();
		if($ret)
		{
			list($ret,$msg) = $this->FieldNameInvalid();
			if($ret)
				$messages[] = $msg;
		}
		else
			$messages[] = $msg;

		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	// Subclass this if needed (especially for cleanup of classes with HasAddOp=TRUE)
	function PostAdminSubmitCleanup(&$params)
	{
	}


	/*Subclass this to generate content for the frontend form, either:
	* an xhtml string which constitutes the field-input(s) to be displayed in the
	(frontend or backend) form. Only the input portion itself, any title and/or
	container(s) will be provided by the form renderer
	OR if the field->MultiPopulate, then
	* an array of stdClass objects, each with properties:
	->name, ->title, ->input, and optionally ->op 
	Object-names must begin with $this->formdata->current_prefix, so as to not be
	dropped as 'unknown' frontend parameters (see PowerForms::InitializeFrontend())
	and not be excluded as time-expired
	*/
	function Populate($id,&$params)
	{
		return '';
	}

	// Subclass this for fields that need validation
	// Sets 2 field properties
	// Returns an array: 1st member is boolean T/F (indicating whether or not
	// the value is valid), 2nd member is '' or error message
	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		return array($this->validated,$this->ValidationMessage);
	}

	// Subclass this to do stuff (e.g. modify other fields) after validation
	// and before (compute if relevant) and disposition
	function PreDispositionAction()
	{
	}

	/* Subclass this for a disposition field
	This method can do just about anything you want it to, in order to handle
	form contents.
	Returns: array,in which the first member is a boolean TRUE or FALSE
	(indicating whether or not the disposition succeeded), and the second member
	is empty, or explanatory text about the failure
	*/
	function Dispose($id,$returnid)
	{
		return array(TRUE,'');
	}

	// Subclass this to do stuff after the form has been disposed
	function PostDispositionAction()
	{
	}

}

?>
