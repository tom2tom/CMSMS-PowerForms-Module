<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FieldBase implements \Serializable
{
	public $formdata; //reference to FormData object for the form to which this field belongs
	//field status
	public $loaded = FALSE;
	public $valid = TRUE; //TRUE unless validation has failed
	//field properties
	public $Alias = '';
	public $FormId = 0;
	public $Id = 0;
	public $Name = '';
	public $OrderBy = 0; //form display-order
	public $Type = '';
	public $Value; //when set, can be scalar or array, with all content processed by Utils::html_myentities_decode()
	public $XtraProps; //container for other properties

	public function __construct(&$formdata, &$params)
	{
		$this->formdata = $formdata;
		$this->XtraProps = array(
		'ChangeRequirement' => TRUE, //whether admin user may change 'Required' state
		'DisplayExternal' => FALSE, //whether field is for use in another module e.g. browser
		'DisplayInForm' => TRUE,
		'DisplayInSubmission' => TRUE, //whether field value is shown in submission template (if used) (effectively ~ self::IsInput)
		'Disposable' => TRUE, //a field-status, not so much a continuing property
		'HasAddOp' => FALSE,
		'HasDeleteOp' => FALSE,
		'HasLabel' => TRUE,
		'HasUserAddOp' => FALSE,
		'HasUserDeleteOp' => FALSE,
		'HideLabel' => FALSE,
		'IsComputedOnSubmission' => FALSE,
		'IsDisposition' => FALSE,
		'IsEmailDisposition' => FALSE,
		'IsInput' => FALSE,
		'LabelSubComponents' => TRUE,
		'MultiPopulate' => FALSE, //whether Populate() generates array of objects
		'NeedsDiv' => TRUE,
		'Required' => FALSE,
		'SmartyEval' => FALSE, //whether to process Populate() output as a smarty-template (i.e. treat that output as a sub-template)
		'ValidationMessage' => '', //post-validation error message, or ''
		'ValidationType' => 'none',
		'ValidationTypes' => array() //array of choices suitable for populating a pulldown
		);

		if (isset($params['form_id']))
			$this->FormId = $params['form_id'];

		if (isset($params['field_id']))
			$this->Id = $params['field_id'];

		if (isset($params['field_Name']))
			$this->Name = $params['field_Name'];

		if (isset($params['field_Alias']))
			$this->Alias = $params['field_Alias'];

		if (isset($params['field_type']))
			$this->Type = $params['field_type'];

		if (isset($params['validation_type']))
			$this->XtraProps['ValidationType'] = $params['validation_type'];
		//admin parameters present ?
		foreach ($params as $key=>$val) {
			if (strncmp($key,'fp_',3) == 0) {
				$key = substr($key,3);
				if (property_exists($this,$key)) {
					$this->$key = $val;
				} else {
					$this->XtraProps[$key] = $val;
				}
			}
		}
		//frontend parameter present ? TODO captcha-field value has different type of key
		$key = $this->formdata->current_prefix.$this->Id;
		if (isset($params[$key]))
			$this->SetValue($params[$key]);
	}

	public function __set($name,$value)
	{
		$this->XtraProps[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name,$this->XtraProps))
			return $this->XtraProps[$name];
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

	public function SetProperty($propName, $propValue)
	{
		$this->XtraProps[$propName] = $propValue;
	}

	// Returns a field-property value, or $default if the property doesn't exist
	public function GetProperty($propName, $default='')
	{
		if (isset($this->XtraProps[$propName]))
			return $this->XtraProps[$propName];
		return $default;
	}

	// Returns array of property-values (possibly just 0=>NULL), or FALSE
	// Each array-key is the numeric-suffix to $propName, & array-value is the stored value
	public function GetPropArray($propName)
	{
		$len = strlen($propName);
		$matches = array();
		foreach ($this->XtraProps as $key => &$val) {
			if (strncmp($key,$propName,$len) == 0) {
				$o = (int)substr($key,$len);
				$matches[$o] = $val;
			}
		}
		unset($val);

		if ($matches) {
			if (count($matches) > 1)
				ksort($matches);
			elseif (key($matches) == 0) {
				$matches[1] = $matches[0];
				unset($matches[0]);
				$this->XtraProps[$propName.'1'] = $matches[1];
				unset($this->XtraProps[$propName]);
			}
			return $matches;
		}
		return FALSE;
	  }

	public function SetPropIndexed($propName, $index, $value)
	{
		$this->XtraProps[$propName.$index] = $value;
	}

	public function GetPropIndexed($propName, $index, $default='')
	{
		$so = $propName.$index;
		if (isset($this->XtraProps[$so]))
			return $this->XtraProps[$so];
		elseif ($index == 0) {
			if (isset($this->XtraProps[$propName]))
				return $this->XtraProps[$propName];
		}
		return $default;
	}

	public function AddPropIndexed($propName,$value)
	{
		$len = strlen($propName);
		$max = -1;
		foreach ($this->XtraProps as $key => &$one) {
			if (strpos($key,$propName) === 0) {
				$o = (int)substr($key,$len);
				if ($o > $max)
					$max = $o;
			}
		}
		unset($one);
		$index = ($max > -1) ? $max + 1 : 1;
		$this->XtraProps[$propName.$index] = $value;
	}

	public function RemovePropIndexed($propName,$index)
	{
		unset($this->XtraProps[$propName.$index]);
	}

	// Returns a form-option value, or $default if the option doesn't exist
	public function GetFormProperty($optname, $default='')
	{
		if (isset($this->formdata->XtraProps[$optname]))
			return $this->formdata->XtraProps[$optname];
		else
			return $default;
	}

	public function SetId($fid)
	{
		$this->Id = (int)$fid;
	}

	// Gets the cached field-id
	public function GetId()
	{
		return (int)$this->Id;
	}

	public function SetName($name)
	{
		$this->Name = $name;
	}

	// Gets the cached field-name
	public function GetName()
	{
		return $this->Name;
	}

	// Check whether this field has a name or doesn't need one
	// Returns array, 1st member is T/F, 2nd is '' or message
	public function FieldIsNamed()
	{
		$mod = $this->formdata->formsmodule;
		if ($this->Name || !$mod->GetPreference('require_fieldnames'))
			return array(TRUE,'');
		return array(FALSE,$mod->Lang('field_no_name'));
	 }

	// Confirm this field's name is the not same as another field's name
	// Returns array, 1st member is T/F, 2nd is '' or message
	public function FieldNameUnique()
	{
		foreach ($this->formdata->Fields as &$one) {
			if ($one->Name == $this->Name && $one->Id != $this->Id) {
				unset($one);
				return array(FALSE,$this->formdata->formsmodule->Lang('field_name_in_use',$this->Name));
			}
		}
		unset($one);
		return array(TRUE,'');
	}

	// Caches a new field-alias
	public function SetAlias($alias)
	{
		$this->Alias = $alias;
	}

	// Gets the cached field-alias
	public function GetAlias()
	{
		return $this->Alias;
	}

	// Gets the field alias, after creating it if not already recorded
	public function ForceAlias()
	{
		$alias = $this->Alias;
		if (!$alias) {
			$alias = $this->GetVariableName();
			if ($alias)
				$this->Alias = $alias;
			else
				$alias = 'fld_'.$this->Id;
		}
		return $alias;
	}

	// Gets an alias-like string derived from field name, for use as a smarty var
	public function GetVariableName()
	{
		$alias = strtolower(trim($this->Name,"\t\n\r\0 _"));
		if (!$alias)
			return '';
		$alias = preg_replace('/[^\w]+/','_',$alias);
		$parts = array_slice(explode('_',$alias),0,5);
		$alias = substr(implode('_',$parts),0,12);
		return trim($alias,'_');
	}

/*	public function GetIdTag($suffix='')
	{
		return ' id="'.$this->ForceAlias().$suffix.'"';
	}
*/
	public function GetInputId($suffix='')
	{
		return $this->ForceAlias().$suffix;
	}

	public function SetOrder($order)
	{
		$this->OrderBy = $order;
	}

	public function GetOrder()
	{
		return $this->OrderBy;
	}

	public function SetFieldType($type)
	{
		$this->Type = $type;
	}

	public function GetFieldType()
	{
		return $this->Type;
	}

	public function IsValid()
	{
		return $this->valid;
	}

	public function GetScript($prefix=' ')
	{
		if (!empty($this->XtraProps['javascript'])) {
			return $prefix.$this->XtraProps['javascript'];
		}
		return '';
	}

	public function SetSmartyEval($state=TRUE)
	{
		$this->XtraProps['SmartyEval'] = $state;
	}

	public function GetSmartyEval()
	{
		return !empty($this->XtraProps['SmartyEval']);
	}

	public function IsDisposition()
	{
		return !empty($this->XtraProps['IsDisposition']);
	}

	public function IsEmailDisposition()
	{
		return !empty($this->XtraProps['IsEmailDisposition']);
	}

	// Set flag determining whether this disposition field is permitted to be disposed (i.e. not inhibited)
	public function SetDisposable($state=TRUE)
	{
		$this->XtraProps['Disposable'] = $state;
	}

	// Get flag determining whether this disposition field is currently permitted to be disposed
	public function IsDisposable()
	{
		return !empty($this->XtraProps['Disposable']);
	}

	public function IsInputField()
	{
		return !empty($this->XtraProps['IsInput']);
	}

	public function HasLabel()
	{
		return !empty($this->XtraProps['HasLabel']);
	}

	public function SetHideLabel($state=TRUE)
	{
		$this->XtraProps['HideLabel'] = $state;
	}

	public function GetHideLabel()
	{
		return !empty($this->XtraProps['HideLabel']);
	}

	public function IsDisplayed()
	{
		return (!empty($this->XtraProps['DisplayInForm'])
			 || !empty($this->XtraProps['DisplayInSubmission'])
			 || !empty($this->XtraProps['DisplayExternal']));
	}

	public function DisplayExternal()
	{
		return !empty($this->XtraProps['DisplayExternal']);
	}

	public function DisplayInForm()
	{
		return !empty($this->XtraProps['DisplayInForm']);
	}

	public function DisplayInSubmission()
	{
		return !empty($this->XtraProps['DisplayInSubmission']); //&& !empty($this->XtraProps['DisplayInForm'])
	}

	public function GetChangeRequirement()
	{
		return !empty($this->XtraProps['ChangeRequirement']);
	}

	public function IsRequired()
	{
		return !empty($this->XtraProps['Required']);
	}

	public function SetRequired($state=TRUE)
	{
		$this->XtraProps['Required'] = $state;
	}

/*	public function ToggleRequired()
	{
		$this->XtraProps['Required'] = empty($this->XtraProps['Required']);
	}
*/
	public function SetValidationType($type)
	{
		$this->XtraProps['ValidationType'] = $type;
	}

	public function GetValidationType()
	{
		if (empty($this->XtraProps['ValidationType']))
			$this->XtraProps['ValidationType'] = 'none';
		return $this->XtraProps['ValidationType'];
	}

	public function RequiresValidation()
	{
		return (!empty($this->XtraProps['ValidationType']) &&
			$this->XtraProps['ValidationType'] != 'none');
	}

	public function GetValidationTypes()
	{
		return $this->XtraProps['ValidationTypes'];
	}

	public function GetValidationMessage()
	{
		return (isset($this->XtraProps['ValidationMessage'])) ?
			$this->XtraProps['ValidationMessage'] : '';
	}

	protected function GetErrorMessage($key)
	{
		return '<span style="color:red">'.
			$this->formdata->formsmodule->Lang('error').'</span> '.
			$this->formdata->formsmodule->Lang($key);
	}

	// Subclass this with a displayable type
	public function GetDisplayType()
	{
		return $this->formdata->formsmodule->Lang('fieldlabel_'.$this->Type);
	}

	public function GetMultiPopulate()
	{
		return !empty($this->XtraProps['MultiPopulate']);
	}

	// Subclass this if appropriate
	public function LabelSubComponents()
	{
		return (isset($this->XtraProps['LabelSubComponents'])) ?
			$this->XtraProps['LabelSubComponents'] : '';
	}

	public function ComputeOnSubmission()
	{
		return !empty($this->XtraProps['IsComputedOnSubmission']);
	}

	// Subclass this if appropriate
	public function ComputeOrder()
	{
		return 0;
	}

	public function NeedsDiv()
	{
		return !empty($this->XtraProps['NeedsDiv']);
	}

/*	public function HasMultipleValues()
	{
		return (!empty($this->XtraProps['MultiPopulate']) || 1empty($this->XtraProps['HasUserAddOp'])); //TODO multipopulate not relevant
	}
*/
	//apply frontend class(es) to string $html
	public function SetClass($html,$extra='')
	{
		$html = preg_replace('/class *= *".*"/U','',$html);
		$cls = (!empty($this->XtraProps['css_class'])) ? $this->XtraProps['css_class']:'';
		if ($this->Required)
			$cls .= ' required';
		if (!$this->valid)
			$cls .= ' invalid_field';
		if ($extra)
			$cls .= ' '.$extra;
		$cls = trim($cls);
		if ($cls) {
			$html = preg_replace(
			array(
			'/<input +type *= *"(\w+)"/U',
			'/<label/',
			'/<option/',
			),
			array(
			'<input type="$1" class="'.$cls.'"',
			'<label class="'.$cls.'"',
			'<option class="'.$cls.'"',
			),$html);
		}
		return $html;
	}

	// Subclass this
	// Returns field value as a scalar or array (per $as_string), suitable for display in the form
	public function GetDisplayableValue($as_string=TRUE)
	{
		if ($this->Value || is_numeric($this->Value)) { //0-value is acceptable
			$ret = $this->Value;
			if (is_array($ret)) {
				if ($as_string)
					return implode($this->GetFormProperty('list_delimiter',','),$ret);
				else
					return $ret; //assume array members are all displayable
			} else
				$ret = (string)$ret;
		} else {
			$ret = $this->GetFormProperty('unspecified',
				$this->formdata->formsmodule->Lang('unspecified'));
		}
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	// Subclass this
	// Returns array of property values or FALSE
	public function GetIndexedValues()
	{
		if (array_key_exists('indexed_value',$this->XtraProps)) {
			return $this->XtraProps['indexed_value'];
		}
		return FALSE;
	}

	// Subclass this if necessary to convert type or something
	public function SetValue($newvalue)
	{
		if (is_array($newvalue)) {
			$this->Value = array();
			foreach ($newvalue as &$one)
				$this->Value[] = Utils::html_myentities_decode($one);
			unset($one);
		} else
			 $this->Value = Utils::html_myentities_decode($newvalue);
	}

/*	public function LoadValue($newvalue)
	{
		if ($this->Value || is_numeric($this->Value)) {
			if (!is_array($this->Value))
				$this->Value = array($this->Value);
			if (is_array($newvalue)) {
				foreach ($newvalue as &$one)
					$this->Value[] = Utils::html_myentities_decode($one);
				unset($one);
			} else
				$this->Value[] = Utils::html_myentities_decode($newvalue);
		} elseif (is_array($newvalue)) {
			$this->Value = array();
			foreach ($newvalue as &$one)
				$this->Value[] = Utils::html_myentities_decode($one);
			unset($one);
		} else
			 $this->Value = Utils::html_myentities_decode($newvalue);
	}
*/
	// Probably don't need to subclass this
	public function GetValue()
	{
		return $this->Value;
	}

	public function ResetValue()
	{
		unset($this->Value);
	}

	// Subclass this if needed to support some unusual format for the value
	// Returns boolean T/F indicating whether the field value is present and non-default
	public function HasValue($deny_blank_responses=FALSE)
	{
		if ($this->Value || is_numeric($this->Value)) {
			if (isset($this->XtraProps['default'])) { // field has default
				if ($this->Value == $this->XtraProps['default']) //TODO if array
					return FALSE;
			}
			return (!$deny_blank_responses ||
					is_array($this->Value) ||
					trim($this->Value));
		}
		return FALSE;
	}

	// Returns a member of the field-value-array, or if the value is not an array and $index == 0, the value, or FALSE
	public function GetArrayValue($index)
	{
		if ($this->Value) {
			if (is_array($this->Value)) {
				if (isset($this->Value[$index]))
					return $this->Value[$index];
			} elseif ($index == 0)
				return $this->Value;
		}
		return FALSE;
	}

	// Returns TRUE if $value is contained in array self::$Value or matches scalar self::$Value
	public function InArrayValue($value)
	{
		if ($this->Value || is_numeric($this->Value)) {
			if (is_array($this->Value))
				return array_search($value,$this->Value) !== FALSE;
			elseif ($this->Value == $value)
				return TRUE;
		}
		return FALSE;
	}

/*	public function GetFieldInputId($id, &$params)
	{
		return $id.$this->formdata->current_prefix.$this->Id;
	}
*/
	// Sends logic along with field, also allows smarty logic
	// Doesn't need subclass in most cases
	public function GetLogic()
	{
		if (!empty($this->XtraProps['field_logic'])) {
			$code = $this->XtraProps['field_logic'];
			return Utils::ProcessTemplateFromData($this->formdata->formsmodule,$code,array());
		}
		return '';
	}

	// Subclass this with something to show in admin fields-list
	public function GetSynopsis()
	{
		return '';
	}

	//Whether to generate a submit-button labelled 'add', along with the field
	public function HasOptionAdd()
	{
		return !empty($this->XtraProps['HasAddOp']);
	}

	// Subclass this to generate appropriate add-button label
	public function GetOptionAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_options');
	}

	// Subclass this when necessary or useful (often, just set a flag)
	public function OptionAdd(&$params)
	{
	}
	//Whether to generate a submit-button labelled 'delete', along with the field
	public function HasOptionDelete()
	{
		return !empty($this->XtraProps['HasDeleteOp']);
	}

	// Subclass this to generate appropriate delete-button label
	public function GetOptionDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_options');
	}

	// Subclass this when necessary or useful to delete option-data
	public function OptionDelete(&$params)
	{
	}

	/**
	Load:
	@id: module id, unused here but is needed in subclass
	@params: array of action-parameters, unused here but may be needed in some subclass

	Loads data for this field from database tables
	TODO OK? Field options are merged with any existing options
	Returns: boolean T/F per successful operation
	*/
	public function Load($id, &$params)
	{
		return FieldOperations::LoadField($this);
	}

	/**
	Store:
	@allprops: optional boolean, whether to also save all field properties, default=FALSE
	Stores (by insert or update) data for this field in database tables.
	Multi-valued (array) options are saved merely as multiple records with same name
	Sets field->Id to real value if it was -1 i.e. a new field
	Returns: boolean T/F per success of executed db commands
	*/
	public function Store($allprops=FALSE)
	{
		return FieldOperations::StoreField($this,$allprops);
	}

	// Subclass this if needed to do stuff after the field is stored
	public function PostFieldSaveProcess(&$params)
	{
	}

	/**
	Delete:
	Clears data for this field (if it's not new) from database tables
	Returns: boolean T/F per success of executed db commands
	*/
	public function Delete()
	{
		if ($this->Id)
			return FieldOperations::RealDeleteField($this);
		return FALSE;
	}

	/**
	AdminPopulateCommon:
	@id: id given to the PWForms module on execution
	@except: optional title-lang-key, or array of them, to be excluded from the setup, default FALSE
	@boolean: whether to exclude some options irrelevant to boolean-fields, default=FALSE
	@visible: whether to include some options irrelevant to non-displayed disposition-fields, default=TRUE

	Generates 'base'/common content for editing a field.
	See also - comments below, for AdminPopulate()

	Returns: 2-member array of stuff for use ultimately in method.open_field.php
	 [0] = array of things for 'main' tab
	 [1] = (possibly empty) array of things for 'adv' tab
	*/
	public function AdminPopulateCommon($id, $except=FALSE, $boolean=FALSE, $visible=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		$displayable = !empty($this->XtraProps['DisplayInForm']) || !empty($this->XtraProps['DisplayExternal']);
		if ($except && !is_array($except)) {
			$except = is_array($except);
		}
		//init main tab content
		$main = array();
		$key = 'title_field_name';
		if (!$except || !in_array($key,$except))
			$main[] = array($mod->Lang($key),
							$mod->CreateInputText($id,'field_Name',$this->GetName(),50));
		$key = 'title_field_alias';
		if (!$except || !in_array($key,$except)) {
			$alias = $this->ForceAlias();
			$main[] = array($mod->Lang($key),
							$mod->CreateInputText($id,'field_Alias',$alias,30)); //no 'fp_' prefix for maintable properties
		}
		$key = 'title_field_type';
		if (!$except || !in_array($key,$except))
			$main[] = array($mod->Lang($key),
						$mod->CreateInputHidden($id,'field_Type',$this->Type).
						$this->GetDisplayType());

		if (!$boolean && $visible && !empty($this->XtraProps['ChangeRequirement'])) {
			$key = 'title_field_required';
			if (!$except || !in_array($key,$except))
				$main[] = array($mod->Lang($key),
							$mod->CreateInputHidden($id,'fp_Required',0). //was field_required
							$mod->CreateInputCheckbox($id,'fp_Required',1,
								$this->IsRequired()),
							$mod->Lang('help_field_required'));
		}

		if (!$boolean) {
			$key = 'title_field_validation';
			if (!$except || !in_array($key,$except)) {
				//choice of validation type ?
				$c = count($this->GetValidationTypes());
				$t = $this->GetValidationType();
				if ($c > 1)
					$validInput = $mod->CreateInputDropdown($id,'fp_ValidationType', //was validation_type
						$this->GetValidationTypes(),-1,$t);
				elseif ($c > 0 || $t)
					$validInput = $mod->Lang('automatic');
				else
					$validInput = $mod->Lang('none');
				$main[] = array($mod->Lang($key),$validInput);
			}
		}

		$helper = FALSE;
		if ($visible && $displayable) {
			$key = 'title_field_helptext';
			if (!$except || !in_array($key,$except)) {
				$main[] = array($mod->Lang($key),
							$mod->CreateTextArea(FALSE,$id,$this->helptext,
							'fp_helptext','pwf_shortarea','','','',50,8));
				$helper = TRUE;
			}
		}

		//init advanced tab content
		$adv = array();
		if ($visible && !empty($this->XtraProps['HasLabel'])) {
			$key = 'title_hide_label';
			if (!$except || !in_array($key,$except))
				$adv[] = array($mod->Lang($key),
							$mod->CreateInputHidden($id,'fp_HideLabel',0).
							$mod->CreateInputCheckbox($id,'fp_HideLabel',1,$this->HideLabel),
							$mod->Lang('help_hide_label'));
		}
		if ($helper) {
			$key = 'title_field_helptoggle';
			if (!$except || !in_array($key,$except))
				$adv[] = array($mod->Lang($key),
							$mod->CreateInputHidden($id,'fp_helptoggle',0).
							$mod->CreateInputCheckbox($id,'fp_helptoggle',1,$this->helptoggle),
							$mod->Lang('help_field_helptoggle'));
		}

		if ($displayable) {
			if ($visible) {
				$key = 'title_field_css_class';
				if (!$except || !in_array($key,$except))
					$adv[] = array($mod->Lang($key),
								$mod->CreateInputText($id,'fp_css_class',$this->css_class,30));
				$key = 'title_field_javascript';
				if (!$except || !in_array($key,$except))
					$adv[] = array($mod->Lang($key),
								$mod->CreateTextArea(FALSE,$id,$this->javascript,
								'fp_javascript','pwf_shortarea','','','',50,8,'','js'),
								$mod->Lang('help_field_javascript'));
			}
			$key = 'title_field_resources';
			if (!$except || !in_array($key,$except))
				$adv[] = array($mod->Lang($key),
							$mod->CreateTextArea(FALSE,$id,$this->resources, //was field_logic
							'fp_resources','pwf_shortarea','','','',50,8),
							$mod->Lang('help_field_resources'));
		}

		$key = 'title_smarty_eval';
		if (!$except || !in_array($key,$except))
			$adv[] = array($mod->Lang($key),
					$mod->CreateInputHidden($id,'fp_SmartyEval',0).
					$mod->CreateInputCheckbox($id,'fp_SmartyEval',1,$this->SmartyEval),
					$mod->Lang('help_smarty_eval'));

		return array($main,$adv);
	}

	/**
	AdminPopulate:
	@id: id given to the PWForms module on execution
	Construct content for field edit. Subclass this.
	Array keys presently recognised are: 'main','adv','table','extra','funcs'.
	'main' and 'adv', if present, refer to arrays of content for the main and
	advanced settings tabs shown when adding/editing the field. Each member of
	those arrays is itself an array of 1 to 3 members, for respectively generating
	title, (optional) input and (optional) help.
	That input should of course be a form input suitable for that field attribute/option.
	'funcs' if present refers to array of js functions to be applied (not inc's or load's)
	Returns: associative array with 0 or more keys recognised in method.open_field.php.
	*/
	public function AdminPopulate($id)
	{
	}

	// Subclass this if needed (especially for cleanup of classes with HasAddOp=TRUE)
	// called before AdminValidate()
	public function PostAdminAction(&$params)
	{
	}

	/* Subclass this if needed
	Returns: 2-member array:
	 [0] = boolean T/F indicating whether or not everything is ok
	 [1] = '' or a (possibly multi-line) message
	*/
	public function AdminValidate($id)
	{
		$messages = array();
  		list($ret,$msg) = $this->FieldIsNamed();
		if ($ret) {
			list($ret,$msg) = $this->FieldNameUnique();
			if (!$ret)
				$messages[] = $msg;
		} else
			$messages[] = $msg;
		$msg = ($ret)?'':implode('<br />',$messages);
		return array($ret,$msg);
	}

	/*Subclass this to generate content for the frontend form, either:
	* an xhtml string which constitutes the field-input(s) to be displayed in the
	(frontend or backend) form. Only the input portion itself, any title and/or
	container(s) will be provided by the form renderer
	OR if the field->MultiPopulate, then
	* an array of stdClass objects, each with properties:
	->name, ->title and ->input (and for a couple of field-types, also ->op)
	Object-names must begin with $this->formdata->current_prefix, so as to not be
	dropped as 'unknown' frontend parameters (see PWForms::InitializeFrontend())
	and not be excluded as time-expired
	self::Value is applied to the field control(s)
	*/
	public function Populate($id,&$params)
	{
		return '';
	}

	/* Subclass this for fields that need validation
	Sets field properties valid & ValidationMessage
	Returns: 2-member array:
	 [0] = boolean T/F indicating whether the field value is valid
	 [1] = '' or error message
	*/
	public function Validate($id)
	{
		$this->valid = TRUE;
		$this->XtraProps['ValidationMessage'] = '';
		return array(TRUE,'');
	}

	// Subclass this to do stuff (e.g. modify other fields) after validation
	// and before (compute if relevant) and disposition
	public function PreDisposeAction()
	{
	}

	/* Subclass this for a disposition field
	This method can do just about anything you want it to, in order to handle
	form contents.
	Returns: 2-member array:
	 [0] = boolean T/F indicating whether or not the disposition succeeded
	 [1] = '' or error message
	*/
	public function Dispose($id,$returnid)
	{
		return array(TRUE,'');
	}

	// Subclass this to do stuff after the form has been disposed
	public function PostDisposeAction()
	{
	}

/*	// Cleanup after serialize()
	protected function EnsureArray(&$val)
	{
		if (is_string($val)) {
			$val = json_decode($val);
		}
		if (is_object($val)) {
			$val = (array)$val;
		}
	}
*/
	public function __toString()
	{
		//no need to fully-document our 'parent'
 		$ob = $this->formdata;
		$this->formdata = NULL; //upstream must reinstate ref to relevant FormData-object when unserializing
		$ret = json_encode(get_object_vars($this));
		$this->formdata = $ob;
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
					if (is_object($one)) {
						$this->$key = (array)$one; //no objects in field properties
					} else {
						$this->$key = $one;
					}
				}
			}
		}
	}
}
