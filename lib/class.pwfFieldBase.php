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
	var $ModifiesOtherFields = FALSE;
	var $MultiPopulate = FALSE; //whether Populate() generates array of objects
	var $Name = '';
	var $NeedsDiv = TRUE;
	var $NonRequirableField = FALSE;
	var $Options = array();
	var $OrderBy = '';
	var $Required = FALSE;
	var $SmartyEval = FALSE; //TRUE for textinput field whose value is to be processed via smarty
	var $Type = '';
	var $ValidationMessage = ''; //post-validation error message, or ''
	var $ValidationType = 'none';
	var $ValidationTypes;
	var $Value; //when set, can be scalar or array all content passed via pwfUtils::html_myentities_decode()

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

		if(isset($params['order_by']))
			$this->OrderBy = $params['order_by'];

		if(isset($params['hide_label']))
			$this->HideLabel = $params['hide_label'];
		elseif(isset($params['set_from_form']))
			$this->HideLabel = FALSE;

//done to here

		if(isset($params['field_required']))
			$this->Required = $params['field_required'];
		elseif(isset($params['set_from_form']))
			$this->Required = FALSE;

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

	function GetMultiPopulate()
	{
		return $this->MultiPopulate;
	}

	// Override this if appropriate
	function LabelSubComponents()
	{
		return $this->LabelSubComponents;
	}

	function ComputeOnSubmission()
	{
		return $this->IsComputedOnSubmission;
	}

	// Override this if appropriate
	function ComputeOrder()
	{
		return 0;
	}

/*	function HasMultipleValues()
	{
		return ($this->MultiPopulate || $this->HasUserAddOp); //TODO multipopulate not relevant
	}
*/
	function ModifiesOtherFields()
	{
		return $this->ModifiesOtherFields;
	}

	// Get flag determining whether field can inhibit (other) dispositions
	function DispositionIsPermitted()
	{
		return $this->DispositionPermitted;
	}

	// Set flag determining whether field can inhibit (other) dispositions
	function SetDispositionPermission($permitted=TRUE)
	{
		$this->DispositionPermitted = $permitted;
	}

	// Override this to do something after the form has been disposed
	function PostDispositionAction()
	{
	}

	// Override this to adjust other field(s) before disposition
	function ModifyOtherFields()
	{
	}

/*	function GetFieldInputId($id,&$params)
	{
		return $id.$this->formdata->current_prefix.$this->Id;
	}
*/
	/*Override this to generate content for the frontend form, either:
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

	// Sends logic along with field, also allows smarty logic
	// Doesn't need override in most cases
	function GetFieldLogic()
	{
		$code = $this->GetOption('field_logic');
		if(!empty($code))
			return $this->formdata->formsmodule->ProcessTemplateFromData($code);
		return '';
	}

	// Override this with something to show users
	function GetFieldStatus()
	{
		return '';
	}

	//Whether to generate a submit-button labelled 'add',along with the field
	function HasAddOp()
	{
		return $this->HasAddOp;
	}

	// Override this to generate appropriate add-button label
	function GetOptionAddButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('add_options');
	}

	// Override this when necessary or useful (often, just set a flag)
	function DoOptionAdd(&$params)
	{
	}
	//Whether to generate a submit-button labelled 'delete',along with the field
	function HasDeleteOp()
	{
		return $this->HasDeleteOp;
	}
	// Override this to generate appropriate delete-button label
	function GetOptionDeleteButton()
	{
		$mod = $this->formdata->formsmodule;
		return $mod->Lang('delete_options');
	}

	// Override this when necessary or useful to delete option-data
	function DoOptionDelete(&$params)
	{
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

	function IsInputField()
	{
		return $this->IsInput;
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

	function GetValidationMessage()
	{
		return $this->ValidationMessage;
	}

	// Override this with a displayable type
	function GetDisplayType()
	{
		return $this->formdata->formsmodule->Lang('field_type_'.$this->Type);
	}
	/**
	  PrePopulateAdminFormCommon:
	  @module_id: id given to the PowerForms module on execution  

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

//		if($this->Type)
			$typeInput = $this->GetDisplayType().$mod->CreateInputHidden($id,'field_type',$this->Type);
/*		else //field type can be chosen
			$typeInput = $mod->CreateInputDropdown($id,'field_type',
				array_merge(array($mod->Lang('select_type')=>''),$mod->field_types),
				-1,'','onchange="this.form.submit()"');
*/
/*2*/	$main[] = array($mod->Lang('title_field_type'),
			$typeInput);

		//init advanced tab content
		$adv = array();

/*		// if we know our type,we can load up with additional options
		if($this->Type)
		{
*/
			if(!$this->IsNonRequirableField())
			{
				$main[] = array($mod->Lang('title_field_required'),
				$mod->CreateInputCheckbox($id,'field_required',1,$this->IsRequired()),
				$mod->Lang('help_field_required'));
			}
			//choice of validation type ?
			if(count($this->GetValidationTypes()) > 1)
				$validInput = $mod->CreateInputDropdown($id,'validation_type',
					$this->GetValidationTypes(),-1,$this->GetValidationType());
			else
				$validInput = $mod->Lang('automatic'); //or 'none' ?
			$main[] = array($mod->Lang('title_field_validation'),$validInput);

			if($this->HasLabel)
			{
				$adv[] = array($mod->Lang('title_hide_label'),
					$mod->CreateInputHidden($id,'hide_label',0).
					$mod->CreateInputCheckbox($id,'hide_label',1,$this->HideLabel),
					$mod->Lang('help_hide_label'));
			}

			if($this->DisplayInForm())
			{
				$main[] = array($mod->Lang('title_field_helptext'),
					$mod->CreateTextArea(FALSE,$id,$this->GetOption('helptext'),
						'opt_helptext','pwf_shortarea','','','',50,8));
				$adv[] = array($mod->Lang('title_field_css_class'),
					$mod->CreateInputText($id,'opt_css_class',$this->GetOption('css_class'),30));
				$adv[] = array($mod->Lang('title_field_javascript'),
					$mod->CreateTextArea(FALSE,$id,$this->GetOption('javascript'),
						'opt_javascript','pwf_shortarea','','','',50,8,'','js'),
					$mod->Lang('help_field_javascript'));
				$adv[] = array($mod->Lang('title_field_logic'),
					$mod->CreateTextArea(FALSE,$id,$this->GetOption('field_logic'),
						'opt_field_logic','pwf_shortarea','','','',50,8),
					$mod->Lang('help_field_logic'));
			}
/*		}
		else
		{
			// no advanced options until we know our type
			$adv[] = array($mod->Lang('tab_advanced'),$mod->Lang('notice_select_type'));
		}
*/
		return array('main'=>$main,'adv'=>$adv);
	}

	function RemoveAdminField(&$array,$fieldname)
	{
		$c = count($array);
		for ($i=0; $i<$c; $i++)
		{
			if(isset($array[$i]->title) && $array[$i]->title == $fieldname)
			{
				array_splice($array,$i,1);
				return;
			}
		}
	}

	// clear fields unused by invisible dispositions
	function OmitAdminVisible(&$mainArray,&$advArray,$hideReq=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		// no "required" (maybe)
		if($hideReq)
			$this->RemoveAdminField($mainArray,$mod->Lang('title_field_required'));
		// no "help text"
		$this->RemoveAdminField($mainArray,$mod->Lang('title_field_helptext'));
		// no "hide name"
		$this->RemoveAdminField($advArray,$mod->Lang('title_hide_label'));
		// no "css"
		$this->RemoveAdminField($advArray,$mod->Lang('title_field_css_class'));
		// no "javascript"
		$this->RemoveAdminField($advArray,$mod->Lang('title_field_javascript'));
		// no "logic"
		$this->RemoveAdminField($advArray,$mod->Lang('title_field_logic'));
	}

	/**
	  PrePopulateAdminForm:
	  @module_id: id given to the PowerForms module on execution  
		
	  Construct content for field add/edit. Override this.
	  Array keys presently recognised are: 'main','adv','table','extra','funcs'.
	  'main' and 'adv', if they exist, refer to arrays of content for the main and
	  advanced settings tabs shown when adding/editing the field. Each member of
	  those arrays is itself an array of title,input and (optionally) help values.
	  That input should of course be a form input suitable for that field attribute/option.

	  Returns: an associative array with 0 or more keys recognised in method.update_field.php.
	*/
	function PrePopulateAdminForm($id)
	{
		return array();
	}

	// Override this
	// Opportunity to alter (but not add to) array contents before they get rendered
	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
	}

	// Override this as necessary (especially for cleanup of classes with HasAddOp=TRUE)
	function PostAdminSubmitCleanup(&$params)
	{
	}

	// Override this as necessary
	function PostFieldSaveProcess(&$params)
	{
	}

	function RequiresValidation()
	{
		return ($this->ValidationType != 'none');
	}

	// Override this
	// Returns an array: first member is boolean TRUE or FALSE (indicating
	// whether or not the value is valid), the second is error message or ''
	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		return array(TRUE,'');
	}

	// Override this
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

	// Override this
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

	// Whether the field value is currenly valid.
	// Override this if needed to support some unusual format for the value,
	// especially if "FALSE" is a valid value!
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

	// Override this if necessary to convert type or something
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

/*	function ExpandValue($newvalue)
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

	// probably don't need to override this
	function GetValue()
	{
		return $this->Value;
	}

	// Override this? Returns the (possibly converted) value of the field
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

	//Override this?
	//Returns TRUE if $value is contained in array ::Value or matches scalar ::Value
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

	function ResetValue()
	{
		unset($this->Value);
	}

	function DoesFieldNameExist()
	{
		// field name in use??
		if(self::HasFieldNamed($this->Name) != $this->Id)
		{
			$mod = $this->formdata->formsmodule;
			return array(FALSE,$mod->Lang('field_name_in_use',$this->Name));
		}
		return array(TRUE,'');
	}

	private function HasFieldNamed($name)
	{
		foreach($this->Fields as &$one)
		{
			if($one->Name == $name)
			{
				$ret = $one->Id;
				unset($one);
				return $ret;
			}
		}
		unset($one);
		return -1;
	}

	function DoesFieldHaveName()
	{
		$mod = $this->formdata->formsmodule;
		if($mod->GetPreference('require_fieldnames') && !$this->Name)
			return array(FALSE,$mod->Lang('field_no_name'));
		return array(TRUE,'');
	 }

	// Override this if needed.
	//Returns: array,in which first member is a boolean TRUE or FALSE
	//(indicating whether or not the value is valid),the second is a message
	function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = $this->DoesFieldHaveName();
		if($ret)
		{
			list($ret,$msg) = $this->DoesFieldNameExist();
			if(!$ret)
				$messages[] = $msg;
		}
		else
			$messages[] = $msg;

		$msg = ($ret)?'':implode('<br />',$messages);
	    return array($ret,$msg);
	}

	/* Override this for a Form Disposition pseudo-field
	 This method can do just about anything you want it to,in order to handle
	 form contents.
	 Returns: array,in which the first member is a boolean TRUE or FALSE
	(indicating whether or not the disposition succeeded),and the second member
	is empty,or explanatory text about the failure
	*/
	function Dispose($id,$returnid)
	{
		return array(TRUE,'');
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

	//returns array of option-values, or FALSE
	//each array-key is the numeric-suffix to $optionName, & array-value is the stored value
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
	LoadField:
	@params: array of parameters
	Loads data for this field (if it's not new) and its options from database tables and/or @params
	Returns: nothing
	*/
	function LoadField(&$params)
	{
		if($this->Id > 0)
			$this->Load(0,$params,TRUE);
	}

	/**
	Load:
	@id: module id, unused here but can be needed in sub-class
	@params: array of parameters
	@deep: optional boolean, whether to also load all options for the field, default=FALSE

	Loads data for this field from database tables and possibly from @params.
	@params['value_'.$this->Name] and/or @params['value_fld'.$this->Id]
		set corresponding field value, superseding any value loaded from table
	Only used by this->LoadField()
	Returns: boolean T/F per successful operation
	*/
	protected function Load($id,&$params,$deep=FALSE)
	{
		$pre = cms_db_prefix();
		$sql = 'SELECT * FROM '.$pre.'module_pwf_field WHERE field_id=?';
		$db = cmsms()->GetDb();
		if($row = $db->GetRow($sql,array($this->Id)))
		{
			if(!$this->Name)
				$this->Name = $row['name'];
			$this->Type = $row['type'];
			$this->OrderBy = $row['order_by'];
		}
		else
			return FALSE;

		$this->loaded = TRUE;

		if($deep)
		{
			$sql = 'SELECT name,value FROM '.$pre.
			  'module_pwf_field_opt WHERE field_id=? ORDER BY option_id';
			$rs = $db->Execute($sql,array($this->Id));
			if($rs)
			{
				$tmpOpts = array();
				while ($results = $rs->FetchRow())
				{
/* PROPERTIES MIGRATED TO OPTIONS
					if(!$this->ValidationType)
						$this->ValidationType = $results['validation_type'];

					if($this->Required == -1)
						$this->Required = $results['required'];

					if($this->HideLabel == -1)
						$this->HideLabel = $results['hide_label'];
*/
					if(isset($tmpOpts[$results['name']]))
					{
						if(!is_array($tmpOpts[$results['name']]))
							$tmpOpts[$results['name']] = array($tmpOpts[$results['name']]);

						$tmpOpts[$results['name']][] = $results['value'];
					}
					else
						$tmpOpts[$results['name']] = $results['value'];
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

		return TRUE;
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
		$db = cmsms()->GetDb();
		$pre = cms_db_prefix();
		if($this->Id == -1)
		{
			$this->Id = $db->GenID($pre.'module_pwf_field_seq');
			$sql = 'INSERT INTO '.$pre.'module_pwf_field (field_id,form_id,name,type,' .
			  'required,validation_type,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
					array($this->Id,$this->FormId,$this->Name,$this->Type,
						($this->Required?1:0),$this->ValidationType,$this->HideLabel,
						$this->OrderBy));
		}
		else
		{
			$sql = 'UPDATE ' .$pre.
			  'module_pwf_field SET name=?,type=?,required=?,validation_type=?,order_by=?,hide_label=? WHERE field_id=?';
			$res = $db->Execute($sql,
					array($this->Name,$this->Type,($this->Required?1:0),
						$this->ValidationType,$this->OrderBy,$this->HideLabel,$this->Id));
		}

		if($deep)
		{
			// drop all current options
			$sql = 'DELETE FROM '.$pre.'module_pwf_field_opt where field_id=?';
			$res = $db->Execute($sql,array($this->Id)) && $res;
			// add back current ones
			foreach($this->Options as $name=>$optvalue)
			{
				if(!is_array($optvalue))
					$optvalue = array($optvalue);
				$sql = 'INSERT INTO ' .$pre.
				'module_pwf_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
				foreach($optvalue as &$one)
				{
					$newid = $db->GenID($pre.'module_pwf_field_opt_seq');
					$res = $db->Execute($sql,
						array($newid,$this->Id,$this->FormId,$name,$one)) && $res;
				}
				unset($one);
			}
		}
		return $res;
	}

	/**
	Delete:
	Clears data for this field from database tables.
	Returns: boolean T/F per success of last-executed db command
	*/
	function Delete()
	{
		if($this->Id == -1)
			return FALSE;

		$pre = cms_db_prefix();
		$db = cmsms()->GetDb();
		$sql = 'DELETE FROM '.$pre.'module_pwf_field where field_id=?';
		$res = $db->Execute($sql,array($this->Id));
		$sql = 'DELETE FROM '.$pre.'module_pwf_field_opt where field_id=?';
		$res = $db->Execute($sql,array($this->Id)) && $res;
		return $res;
	}

}

?>
