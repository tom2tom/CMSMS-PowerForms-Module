<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUtils
{
	var $module_ptr = NULL;
	var $module_params = -1;
	var $Id = -1;
	var $Name = '';
	var $Alias = '';
	var $loaded = 'not';
	var $formTotalPages = 0;
	var $Page;
	var $Attrs;
	var $Fields;
	var $formState;
	var $sampleTemplateCode;
	var $templateVariables;

	function __construct(&$module_ptr, &$params, $loadDeep=false, $loadResp=false)
	{
		$this->module_ptr = $module_ptr;
		$this->module_params = $params;
		$this->Fields = array();
		$this->Attrs = array();
		$this->formState = 'new';

		// Stikki adding: $id overwrite possible with $param
		if((!isset($this->module_ptr->module_id) || empty($this->module_ptr->module_id)) && isset($params['module_id']))
		{
			$this->module_ptr->module_id = $params['module_id'];
		}

		if(isset($params['form_id']))
		{
			$this->Id = $params['form_id'];
		}

		if(isset($params['fbrp_form_alias']))
		{
			$this->Alias = $params['fbrp_form_alias'];
		}

		if(isset($params['fbrp_form_name']))
		{
			$this->Name = $params['fbrp_form_name'];
		}

		$fieldExpandOp = false;
		foreach($params as $pKey=>$pVal)
		{
			if(substr($pKey,0,9) == 'fbrp_FeX_' || substr($pKey,0,9) == 'fbrp_FeD_')
			{
				// expanding or shrinking a field
				$fieldExpandOp = true;
			}
		}

		if($fieldExpandOp)
		{
			$params['fbrp_done'] = 0;
			if(isset($params['fbrp_continue']))
			{
				$this->Page = $params['fbrp_continue'] - 1;
			}
			else
			{
				$this->Page = 1;
			}
		}
		else
		{
			if(isset($params['fbrp_continue']))
			{
				$this->Page = $params['fbrp_continue'];
			}
			else
			{
				$this->Page = 1;
			}

			if(isset($params['fbrp_prev']) && isset($params['fbrp_previous']))
			{
				$this->Page = $params['fbrp_previous'];
				$params['fbrp_done'] = 0;
			}
		}

		$this->formTotalPages = 1;
		if(isset($params['fbrp_done'])&& $params['fbrp_done'] == 1)
		{
			$this->formState = 'submit';
		}

		if(isset($params['fbrp_user_form_validate']) && $params['fbrp_user_form_validate'] == true)
		{
			$this->formState = 'confirm';
		}

		if($this->Id != -1)
		{
			if(isset($params['response_id']) && $this->formState == 'submit')
			{
				$this->formState = 'update';
			}

			$this->Load($this->Id, $params, $loadDeep, $loadResp);
		}

		foreach($params as $thisParamKey=>$thisParamVal)
		{
			if(substr($thisParamKey,0,11) == 'fbrp_forma_')
			{
				$thisParamKey = substr($thisParamKey,11);
				$this->Attrs[$thisParamKey] = $thisParamVal;
			}
			else if($thisParamKey == 'fbrp_form_template' && $this->Id != -1)
			{
				$this->module_ptr->SetTemplate('fb_'.$this->Id,$thisParamVal);
			}
		}

		$this->templateVariables = array(
			'{$sub_form_name}'=>$this->module_ptr->Lang('title_form_name'),
			'{$sub_date}'=>$this->module_ptr->Lang('help_submission_date'),
			'{$sub_host}'=>$this->module_ptr->Lang('help_server_name'),
			'{$sub_source_ip}'=>$this->module_ptr->Lang('help_sub_source_ip'),
			'{$sub_url}'=>$this->module_ptr->Lang('help_sub_url'),
			'{$fb_version}'=>$this->module_ptr->Lang('help_fb_version'),
			'{$TAB}'=>$this->module_ptr->Lang('help_tab')
		);
	}

	function SetAttributes($attrArray)
	{
		$this->Attrs = array_merge($this->Attrs,$attrArray);
	}

	function SetTemplate($template)
	{
		$this->Attrs['form_template'] = $template;
		$this->module_ptr->SetTemplate('fb_'.$this->Id,$template);
	}

	function GetId()
	{
		return $this->Id;
	}

	function SetId($id)
	{
		$this->Id = $id;
	}

	function GetName()
	{
		return $this->Name;
	}

	function GetFormState()
	{
		return $this->formState;
	}

	function GetPageCount()
	{
		return $this->formTotalPages;
	}

	function GetPageNumber()
	{
		return $this->Page;
	}

	function PageBack()
	{
		$this->Page--;
	}

	function SetName($name)
	{
		$this->Name = $name;
	}

	function GetAlias()
	{
		return $this->Alias;
	}

	function SetAlias($alias)
	{
		$this->Alias = $alias;
	}

	// dump params
	function DebugDisplay($params=array())
	{
		$tmp = $this->module_ptr;
		$this->module_ptr = '[mdptr]';

		if(isset($params['FORM']))
		{
			$fpt = $params['FORM'];
			$params['FORM'] = '[form_pointer]';
		}

		$template_tmp = $this->GetAttr('form_template','');
		$this->SetAttr('form_template',strlen($template_tmp).' characters');
		$field_tmp = $this->Fields;
		$this->Fields = 'Field Array: '.count($field_tmp);
		debug_display($this);
		$this->SetAttr('form_template',$template_tmp);
		$this->Fields = $field_tmp;
		foreach($this->Fields as &$fld)
		{
			$fld->DebugDisplay();
		}
		unset ($fld);
		$this->module_ptr = $tmp;
	}

	function SetAttr($attrname, $val)
	{
		$this->Attrs[$attrname] = $val;
	}

	function GetAttr($attrname, $default="")
	{
		if(isset($this->Attrs[$attrname]))
		{
			return $this->Attrs[$attrname];
		}
		else
		{
			return $default;
		}
	}

	function GetFieldCount()
	{
		return count($this->Fields);
	}

	//returns first match (formerly - the last match)
	function HasFieldNamed($name)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetName() == $name)
			{
				return $fld->GetId();
			}
		}
		unset ($fld);
		return -1;
	}

	function AddTemplateVariable($name,$def)
	{
		$theKey = '{$'.$name.'}';
		$this->templateVariables[$theKey] = $def;
	}

	//called only from AdminTemplateHelp()
	function createSampleTemplateJavascript($fieldName='opt_email_template', $button_text='', $suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix;
		$content = <<<EOS
<script type="text/javascript">
//<![CDATA[
function populate_{$fldAlias}(formname)
{
 var fname = 'IDfbrp_{$fieldName}';
 $(formname[fname]).val(|TEMPLATE|).change();
}
//]]>
</script>

	<input type="button" value="{$button_text}" onclick="javascript:populate_{$fldAlias}(this.form)" />
EOS;
		return $content;
	}

	function fieldValueTemplate()
	{
		$mod = $this->module_ptr;
		$ret = '<table class="module_fb_legend"><tr><th colspan="2">'.$mod->Lang('help_variables_for_computation').'</th></tr>';
		$ret .= '<tr><th>'.$mod->Lang('help_php_variable_name').'</th><th>'.$mod->Lang('help_form_field').'</th></tr>';
		$odd = false;
		foreach($this->Fields as &$fld)
		{
//			Removed by Stikki: BUT WHY?
//			if(!$others[$i]->HasMultipleFormComponents())
//			{
				$ret .= '<tr><td class="'.($odd?'odd':'even').'">$fld_'.$fld->GetId().'</td><td class="'.($odd?'odd':'even').'">' .$fld->GetName() . '</td></tr>';
//			}
			$odd = ! $odd;
		}
		unset ($fld);
		return $ret;
	}

	function createSampleTemplate($htmlish=false,$email=true, $oneline=false,$header=false,$footer=false)
	{
		$mod = $this->module_ptr;
		$ret = '';

		if($email)
		{
			if($htmlish)
			{
				$ret .= '<h1>'.$mod->Lang('email_default_template')."</h1>\n";
			}
			else
			{
				$ret .= $mod->Lang('email_default_template')."\n";
			}
			foreach($this->templateVariables as $thisKey=>$thisVal)
			{
				if($htmlish)
				{
					$ret .= '<strong>'.$thisVal.'</strong>: '.$thisKey.'<br />';
				}
				else
				{
					$ret .= $thisVal.': '.$thisKey;
				}
				$ret .= "\n";
			}
			if($htmlish)
			{
				$ret .= "\n<hr />\n";
			}
			else
			{
				$ret .= "\n-------------------------------------------------\n";
			}
		}
		elseif(!$oneline)
		{
			if($htmlish)
			{
				$ret .= '<h2>';
			}
			$ret .= $mod->Lang('thanks');
			if($htmlish)
			{
				$ret .= "</h2>\n";
			}
		}
		elseif($footer)
		{
			 $ret .= "------------------------------------------\n<!--EOF-->\n";
			 return $ret;
		}

		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				if($fld->GetAlias() != '')
				{
					$fldref = $fld->GetAlias();
				}
				else
				{
					$fldref = 'fld_'. $fld->GetId();
				}

				$ret .= '{if $'.$fldref.' != "" && $'.$fldref.' != "'.$this->GetAttr('unspecified',$mod->Lang('unspecified')).'"}';
				$fldref = '{$'.$fldref.'}';

				if($htmlish)
				{
					$ret .= '<strong>'.$fld->GetName() . '</strong>: ' . $fldref. '<br />';
				}
				elseif($oneline && !$header)
				{
					$ret .= $fldref. '{$TAB}';
				}
				elseif($oneline && $header)
				{
					$ret .= $fld->GetName().'{$TAB}';
				}
				else
				{
					$ret .= $fld->GetName() . ': ' .$fldref;
				}
				$ret .= "{/if}\n";
			}
		}
		unset ($fld);
		return $ret;
	}

/*	function AdminTemplateHelp($formDescriptor,$fieldStruct)
	{
		$mod = $this->module_ptr;

		$ret = '<table class="module_fb_legend"><tr><th colspan="2">'.$mod->Lang('help_variables_for_template').'</th></tr>';
		$ret .= '<tr><th>'.$mod->Lang('help_variable_name').'</th><th>'.$mod->Lang('help_form_field').'</th></tr>';
		$odd = false;
		foreach($this->templateVariables as $thisKey=>$thisVal)
		{
			$ret .= '<tr><td class="'.($odd?'odd':'even').
			'">'.$thisKey.'</td><td class="'.($odd?'odd':'even').
			'">'.$thisVal.'</td></tr>';
		 	$odd = ! $odd;
		}

		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				$ret .= '<tr><td class="'.($odd?'odd':'even').
				'">{$'.$fld->GetVariableName().
				'} / {$fld_'.$fld->GetId().'}';
				if($fld->GetAlias() != '')
				{
					$ret .= ' / {$'.$fld->GetAlias().'}';
				}
				$ret .= '</td><td class="'.($odd?'odd':'even').
				'">' .$fld->GetName() . '</td></tr>';
				$odd = ! $odd;
			}
		}
		unset ($fld);

		$ret .= '<tr><td colspan="2">'.$mod->Lang('help_array_fields').'</td></tr>';
		$ret .= '<tr><td colspan="2">'.$mod->Lang('help_other_fields').'</td></tr>';

		$sampleTemplateCode = '';
		foreach($fieldStruct as $key=>$val)
		{
			$html_button = (isset($val['html_button']) && $val['html_button']);
			$text_button = (isset($val['text_button']) && $val['text_button']);
			$is_oneline = (isset($val['is_oneline']) && $val['is_oneline']);
			$is_email = (isset($val['is_email']) && $val['is_email']);
			$is_header = (isset($val['is_header']) && $val['is_header']);
			$is_footer = (isset($val['is_footer']) && $val['is_footer']);

			if($html_button)
			{
				$button_text = $mod->Lang('title_create_sample_html_template');
			}
			elseif($is_header)
			{
				$button_text = $mod->Lang('title_create_sample_header_template');
			}
			elseif($is_footer)
			{
				$button_text = $mod->Lang('title_create_sample_footer_template');
			}
			else
			{
				$button_text = $mod->Lang('title_create_sample_template');
			}

			if($html_button && $text_button)
			{
				$sample = $this->createSampleTemplate(false, $is_email, $is_oneline, $is_header, $is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
					self::createSampleTemplateJavascript($key, $mod->Lang('title_create_sample_template'),'text'));
			}

			$sample = $this->createSampleTemplate($html_button, $is_email, $is_oneline, $is_header, $is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			$sampleTemplateCode .= str_replace("|TEMPLATE|","'".$sample."'",
				self::createSampleTemplateJavascript($key, $button_text));
		}

		$sampleTemplateCode = str_replace('ID', $formDescriptor, $sampleTemplateCode);
		$ret .= '<tr><td colspan="2">'.$sampleTemplateCode.'</td></tr>';
		$ret .= '</table>';

		return $ret;
	}
*/
	//called only from AdminTemplateActions()
	private function CreateAction($id, $fieldName='opt_email_template', $button_text='', $suffix='')
	{
		$fldAlias = preg_replace('/[^\w\d]/','_',$fieldName).$suffix;
		$msg = $this->module_ptr->Lang('confirm');
		$func = <<<EOS  
function populate_{$fldAlias}(formname) {
 if(confirm ('{$msg}')) {
  formname['{$id}fbrp_{$fieldName}'].value=|TEMPLATE|;
 }
}
EOS;
		$btn = <<<EOS
<input type="button" class="cms_submit" value="{$button_text}" onclick="javascript:populate_{$fldAlias}(this.form)" />
EOS;
		return (array($func,$btn));
	}

	function AdminTemplateActions($formDescriptor,$fieldStruct)
	{
		$mod = $this->module_ptr;
		$funcs = array();
		$buttons = array();
		foreach($fieldStruct as $key=>$val)
		{
			$html_button = !empty($val['html_button']);
			$text_button = !empty($val['text_button']);
			$gen_button = !empty($val['general_button']);
			$is_oneline = !empty($val['is_oneline']);
			$is_email = !empty($val['is_email']);
			$is_header = !empty($val['is_header']);
			$is_footer = !empty($val['is_footer']);

			if($html_button)
				$button_text = $mod->Lang('title_create_sample_html_template');
			elseif($is_header)
				$button_text = $mod->Lang('title_create_sample_header_template');
			elseif($is_footer)
				$button_text = $mod->Lang('title_create_sample_footer_template');
			else
				$button_text = $mod->Lang('title_create_sample_template');

			if($html_button && $text_button)
			{
				$sample = self::createSampleTemplate(false, $is_email, $is_oneline, $is_header, $is_footer);
				$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
				list($func,$btn) = self::CreateAction($formDescriptor, $key, $mod->Lang('title_create_sample_template'),'text');
				$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
				$buttons[] = $btn;
			}

			$sample = self::createSampleTemplate($html_button || $gen_button, $is_email, $is_oneline, $is_header, $is_footer);
			$sample = str_replace(array("'","\n"),array("\\'","\\n'+\n'"),$sample);
			list($func,$btn) = self::CreateAction($formDescriptor, $key, $button_text);
			$funcs[] = str_replace('|TEMPLATE|',"'".$sample."'", $func);
			$buttons[]= $btn;
		}
		return array($funcs,$buttons);
	}

	function SetupVarsHelp(&$mod, &$smarty)
	{
		$smarty->assign('help_vars_title',$mod->Lang('help_variables_for_template'));

		$sysfields = array();
		foreach($this->templateVariables as $thisKey=>$thisVal)
		{
			$oneset = new stdClass();
			$oneset->name = $thisKey;
			$oneset->title = $thisVal;
			$sysfields[] = $oneset;
		}
		$smarty->assign('sysfields',$sysfields);

		$subfields = array();
		foreach($this->Fields as &$fld)
		{
			if($fld->DisplayInSubmission())
			{
				$oneset = new stdClass();
				$oneset->name = $fld->GetVariableName();
				$oneset->id = $fld->GetId();
				$oneset->alias = $fld->GetAlias();
				$oneset->title = $fld->GetName();
				$oneset->escaped = str_replace("'","\\'",$oneset->title);
				$subfields[] = $oneset;
			}
		}
		unset ($fld);
		$smarty->assign('subfields',$subfields);

		$obfields = array();
		foreach(array ('name', 'type', 'id', 'value', 'valuearray') as $name)
		{
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->title = $mod->Lang('title_field_'.$name);
			$obfields[] = $oneset;
		}
		$smarty->assign('obfields',$obfields);
//		$oneset->title = $mod->Lang('title_field_id2');
		$smarty->assign('help_field_object',$mod->Lang('help_array_fields'));
		$smarty->assign('help_object_example',$mod->Lang('help_object_example'));
		$smarty->assign('help_other_fields',$mod->Lang('help_other_fields'));
		$smarty->assign('help_vars',$mod->ProcessTemplate('vars_help.tpl'));
	}

	function Validate()
	{
		$validated = true;
		$message = array();
		$formPageCount=1;
		$valPage = $this->Page - 1;
		$usertagops = cmsms()->GetUserTagOperations();
		$mod = $this->module_ptr;
		$udt = $this->GetAttr('validate_udt','');
		$unspec = $this->GetAttr('unspecified',$mod->Lang('unspecified'));

		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'PageBreakField')
			{
				$formPageCount++;
			}
			if($valPage != $formPageCount)
			{
				continue;
			}

			$deny_space_validation = ($mod->GetPreference('blank_invalid','0') == '1');
/*			debug_display($fld->GetName().' '.
				($fld->HasValue() === false?'False':'true'));
			if($fld->HasValue())
				debug_display($fld->GetValue());
*/
			if(//! $fld->IsNonRequirableField() &&
				$fld->IsRequired() && $fld->HasValue($deny_space_validation) === false)
			{
				$message[] = $mod->Lang('please_enter_a_value',$fld->GetName());
				$validated = false;
				$fld->SetOption('is_valid',false);
				$fld->validationErrorText = $mod->Lang('please_enter_a_value',$fld->GetName());
				$fld->validated = false;
			}
			else if($fld->GetValue() != $mod->Lang('unspecified'))
			{
				$res = $fld->Validate();
				if($res[0] != true)
				{
					$message[] = $res[1];
					$validated = false;
					$fld->SetOption('is_valid',false);
				}
				else
				{
					$fld->SetOption('is_valid',true);
				}
			}

			if($validated == true && !empty($udt) && "-1" != $udt)
			{
				$parms = $params;
				foreach($this->Fields as &$othr)
				{
					$replVal = '';
					if($othr->DisplayInSubmission())
					{
						$replVal = $othr->GetHumanReadableValue();
						if($replVal == '')
						{
							$replVal = $unspec;
						}
					}
					$name = $othr->GetVariableName();
					$parms[$name] = $replVal;
					$id = $othr->GetId();
					$parms['fld_'.$id] = $replVal;
					$alias = $othr->GetAlias();
					if(!empty($alias))
					{
						$parms[$alias] = $replVal;
					}
				}
				unset ($othr);
				$res = $usertagops->CallUserTag($udt,$parms);
				if($res[0] != true)
				{
					$message[] = $res[1];
					$validated = false;
				}
			}
		}
		unset ($fld);
		return array($validated, $message);
	}

	function HasDisposition()
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->IsDisposition())
				return true;
		}
		unset ($fld);
		return false;
	}

	// return an array: element 0 is true for success, false for failure
	// element 1 is an array of reasons, in the event of failure.
	function Dispose($returnid,$suppress_email=false)
	{
		// first, we run all field methods that will modify other fields
		$computes = array();
		$i = 0; //don't assume anything about fields-array key
		foreach($this->Fields as &$fld)
		{
			if($fld->ModifiesOtherFields())
			{
				$fld->ModifyOtherFields();
			}
			if($fld->ComputeOnSubmission())
			{
				$computes[$i] = $fld->ComputeOrder();
			}
			$i++;
		}

		asort($computes);
		foreach($computes as $cKey=>$cVal)
		{
			$this->Fields[$cKey]->Compute();
		}

		$resArray = array();
		$retCode = true;
		// for each form disposition pseudo-field, dispose the form results
		foreach($this->Fields as &$fld)
		{
			if($fld->IsDisposition() && $fld->DispositionIsPermitted())
			{
				if(!($suppress_email && $fld->IsEmailDisposition()))
				{
					$res = $fld->DisposeForm($returnid);
					if($res[0] == false)
					{
						$retCode = false;
						$resArray[] = $res[1];
					}
				}
			}
		}
		// handle any last cleanup functions
		foreach($this->Fields as &$fld)
		{
			$fld->PostDispositionAction();
		}
		unset ($fld);
		return array($retCode,$resArray);
	}

	function RenderFormHeader()
	{
		if($this->module_ptr->GetPreference('show_version',0) == 1)
		{
			return "\n<!-- Start FormBuilder Module (".$this->module_ptr->GetVersion().") -->\n";
		}
	}

	function RenderFormFooter()
	{
		if($this->module_ptr->GetPreference('show_version',0) == 1)
		{
			return "\n<!-- End FormBuilder Module -->\n";
		}
	}

	  // returns a string.
	function RenderForm($id, &$params, $returnid)
	{
		$parts = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
		array_splice ($parts,count($parts)-3,3,array('lib','replacement.php'));
		include(implode(DIRECTORY_SEPARATOR,$parts));

		// Check if form id given
		$mod = $this->module_ptr;

		if($this->Id == -1)
		{
			return "<!-- no form -->\n";
		}

		// Check if show full form
		if($this->loaded != 'full')
		{
			$this->Load($this->Id,$params,true);
		}

		// Usual crap
		$reqSymbol = $this->GetAttr('required_field_symbol','*');
		$smarty = cmsms()->GetSmarty();

		$smarty->assign('title_page_x_of_y',$mod->Lang('title_page_x_of_y',array($this->Page,$this->formTotalPages)));

		$smarty->assign('css_class',$this->GetAttr('css_class',''));
		$smarty->assign('total_pages',$this->formTotalPages);
		$smarty->assign('this_page',$this->Page);
		$smarty->assign('form_name',$this->Name);
		$smarty->assign('form_id',$this->Id);
		$smarty->assign('actionid',$id);

		// Build hidden
		$hidden = $mod->CreateInputHidden($id, 'form_id', $this->Id);
		if(isset($params['lang']))
		{
			$hidden .= $mod->CreateInputHidden($id, 'lang', $params['lang']);
		}
		$hidden .= $mod->CreateInputHidden($id, 'fbrp_continue', ($this->Page + 1));
		if(isset($params['fbrp_browser_id']))
		{
			$hidden .= $mod->CreateInputHidden($id,'fbrp_browser_id',$params['fbrp_browser_id']);
		}
		if(isset($params['response_id']))
		{
			$hidden .= $mod->CreateInputHidden($id,'response_id',$params['response_id']);
		}
		if($this->Page > 1)
		{
			$hidden .= $mod->CreateInputHidden($id, 'fbrp_previous', ($this->Page - 1));
		}
		if($this->Page == $this->formTotalPages)
		{
			$hidden .= $mod->CreateInputHidden($id, 'fbrp_done', 1);
		}

		// Start building fields
		$fields = array();
		$prev = array();
		$formPageCount = 1;

		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'PageBreakField')
			{
				$formPageCount++;
			}
			if($formPageCount != $this->Page)
			{
				$testIndex = 'fbrp__'.$fld->GetId();

				// Ryan's ugly fix for Bug 4307
				// We should figure out why this field wasn't populating its Smarty variable
				if($fld->GetFieldType() == 'FileUploadField')
				{
					$smarty->assign('fld_'.$fld->GetId(),$fld->GetHumanReadableValue());
					$hidden .= $mod->CreateInputHidden($id,
						$testIndex,
						$this->unmy_htmlentities($fld->GetHumanReadableValue()));
					$thisAtt = $fld->GetHumanReadableValue(false);
					$smarty->assign('test_'.$fld->GetId(), $thisAtt);
					$smarty->assign('value_fld'.$fld->GetId(), $thisAtt[0]);
				}

				if(!isset($params[$testIndex]))
				{
					// do we need to write something?
				}
				elseif(is_array($params[$testIndex]))
				{
					foreach($params[$testIndex] as $val)
					{
						$hidden .= $mod->CreateInputHidden($id,
									$testIndex.'[]',
									$this->unmy_htmlentities($val));
					}
				}
				else
				{
					$hidden .= $mod->CreateInputHidden($id,
							   $testIndex,
							   $this->unmy_htmlentities($params[$testIndex]));
				}

				if($formPageCount < $this->Page && $fld->DisplayInSubmission())
				{
					$oneset = new stdClass();
					$oneset->value = $fld->GetHumanReadableValue();

					$smarty->assign($fld->GetName(),$oneset);

					if($fld->GetAlias() != '')
					{
						$smarty->assign($fld->GetAlias(),$oneset);
					}

					$prev[] = $oneset;
				}
				continue;
			}
			$oneset = new stdClass();
			$oneset->display = $fld->DisplayInForm()?1:0;
			$oneset->required = $fld->IsRequired()?1:0;
			$oneset->required_symbol = $fld->IsRequired()?$reqSymbol:'';
			$oneset->css_class = $fld->GetOption('css_class');
			$oneset->helptext = $fld->GetOption('helptext');
			$oneset->field_helptext_id = 'fbrp_ht_'.$fld->GetID();
		//	$oneset->valid = $fld->GetOption('is_valid',true)?1:0;
			$oneset->valid = $fld->validated?1:0;
			$oneset->error = $fld->GetOption('is_valid',true)?'':$fld->validationErrorText;
			$oneset->hide_name = 0;
			if(((!$fld->HasLabel()) || $fld->HideLabel()) && ($fld->GetOption('fbr_edit','0') == '0' || $params['in_admin'] != 1))
			{
				$oneset->hide_name = 1;
			}
			$oneset->has_label = $fld->HasLabel();
			$oneset->needs_div = $fld->NeedsDiv();
			$oneset->name = $fld->GetName();
			$oneset->input = $fld->GetFieldInput($id, $params, $returnid);
			$oneset->logic = $fld->GetFieldLogic();
			$oneset->values = $fld->GetAllHumanReadableValues();
			$oneset->smarty_eval = $fld->GetSmartyEval()?1:0;

			$oneset->multiple_parts = $fld->HasMultipleFormComponents()?1:0;
			$oneset->label_parts = $fld->LabelSubComponents()?1:0;
			$oneset->type = $fld->GetDisplayType();
			$oneset->input_id = $fld->GetCSSId();
			$oneset->id = $fld->GetId();

			// Added by Stikki STARTS
			$name_alias = $fld->GetName();
			$name_alias = str_replace($toreplace, $replacement, $name_alias);
			$name_alias = strtolower($name_alias);
			$name_alias = preg_replace('/[^a-z0-9]+/i','_',$name_alias);

			$smarty->assign($name_alias,$oneset);
			// Added by Stikki ENDS

			if($fld->GetAlias() != '')
			{
				$smarty->assign($fld->GetAlias(),$oneset);
				$oneset->alias = $fld->GetAlias();
			}
			else
			{
				$oneset->alias = $name_alias;
			}

			$fields[$oneset->input_id] = $oneset;
			//$fields[] = $oneset;
		}
		unset ($fld);

		$smarty->assign_by_ref('fb_hidden',$hidden);
		$smarty->assign_by_ref('fields',$fields);
		$smarty->assign_by_ref('previous',$prev);

		$jsStr = '';
		$jsTrigger = '';
		if($this->GetAttr('input_button_safety','0') == '1')
		{
			$jsStr = <<<EOS
<script type="text/javascript">
//<![CDATA[
var submitted = 0;
function LockButton () {
 var ret = false;
 if(!submitted) {
  var item = document.getElementById("{$id}fbrp_submit");
  if(item != null) {
   setTimeout(function() {item.disabled = true}, 0);
  }
  submitted = 1;
  ret = true;
 }
 return ret;
}
//]]>
</script>
EOS;
			$jsTrigger = " onclick='return LockButton()'";
		}

		$js = $this->GetAttr('submit_javascript');

		if($this->Page > 1)
		{
			$smarty->assign('prev','<input class="cms_submit fbsubmit_prev" name="'.$id.'fbrp_prev" id="'.$id.'fbrp_prev" value="'.$this->GetAttr('prev_button_text').'" type="submit" '.$js.' />');
		}
		else
		{
			$smarty->assign('prev','');
		}

		$smarty->assign('has_captcha',0);
		if($this->Page < $formPageCount)
		{
			$smarty->assign('submit','<input class="cms_submit fbsubmit_next" name="'.$id.'fbrp_submit" id="'.$id.'fbrp_submit" value="'.$this->GetAttr('next_button_text').'" type="submit" '.$js.' />');
		}
		else
		{
			$captcha = $mod->getModuleInstance('Captcha');
			if($this->GetAttr('use_captcha','0') == '1' && $captcha != null)
			{
				$smarty->assign('graphic_captcha',$captcha->getCaptcha());
				$smarty->assign('title_captcha',$this->GetAttr('title_user_captcha',$mod->Lang('title_user_captcha')));
				$smarty->assign('input_captcha',$mod->CreateInputText($id, 'fbrp_captcha_phrase',''));
				$smarty->assign('has_captcha',1);
			}

			$smarty->assign('submit','<input class="cms_submit fbsubmit" name="'.$id.'fbrp_submit" id="'.$id.'fbrp_submit" value="'.$this->GetAttr('submit_button_text').'" type="submit" '.$js.' />');
		}
		return $mod->ProcessTemplateFromDatabase('fb_'.$this->Id);
	}

	function LoadForm($loadDeep=false)
	{
		return $this->Load($this->Id, array(), $loadDeep);
	}

	function unmy_htmlentities($val)
	{
		if($val == "")
		{
			return "";
		}
		$val = html_entity_decode($val);
		$val = str_replace("&amp;","&",$val);
		$val = str_replace("&#60;&#33;--","<!--",$val);
		$val = str_replace("--&#62;","-->",$val);
		$val = str_replace("&gt;",">", $val);
		$val = str_replace("&lt;","<",$val);
		$val = str_replace("&quot;","\"",$val);
		$val = str_replace("&#036;","\$",$val);
		$val = str_replace("&#33;","!",$val);
		$val = str_replace("&#39;","'",$val);

		// Uncomment if you need to convert unicode chars
		return $val;
	}

	function Load($formId, &$params, $loadDeep=false, $loadResp=false)
	{

		$mod = $this->module_ptr;
		$db = $mod->dbHandle;
		$pref = cms_db_prefix();

		//error_log("entering Form Load with usage ".memory_get_usage());
		$sql = 'SELECT * FROM '.$pref.'module_fb_form WHERE form_id=?';
		$result = $db->GetRow($sql, array($formId));
		if($result)
		{
			$this->Id = $result['form_id'];
			if(!isset($params['fbrp_form_name']) || empty($params['fbrp_form_name']))
			{
				$this->Name = $result['name'];
			}
			if(!isset($params['fbrp_form_alias']) || empty($params['fbrp_form_alias']))
			{
				$this->Alias = $result['alias'];
			}
		}
		else
		{
			return false;
		}

		$sql = 'SELECT name,value FROM '.$pref.'module_fb_form_attr WHERE form_id=?';
		$this->Attrs = $db->GetAssoc($sql, array($formId));
		$this->loaded = 'summary';

		if(isset($params['response_id']))
		{
			$loadDeep = true;
			$loadResp = true;
		}

		if($loadDeep)
		{
			if($loadResp)
			{
				// if it's a stored form, load the results -- but we need to manually merge them,
				// since $params[] should override the database value (say we're resubmitting a form)
				$fbf = $mod->GetFormBrowserField($formId);
				if($fbf != false)
				{
					// if we're binding to FEU, get the FEU ID, see if there's a response for
					// that user. If so, load it. Otherwise, bring up an empty form.
					if($fbf->GetOption('feu_bind','0')=='1')
					{
						$feu = $mod->GetModuleInstance('FrontEndUsers');
						if($feu == false)
						{
							debug_display("FAILED to instatiate FEU!");
							return;
						}
						if(!isset($_COOKIE['cms_admin_user_id']))
						{
							// Fix for Bug 5422. Adapted from Mike Hughesdon's code.
							$response_id = $mod->GetResponseIDFromFEUID($feu->LoggedInId(), $formId);
							if($response_id !== false)
							{
								$check = $db->GetOne('SELECT count(*) FROM '.$pref.
									'module_fb_formbrowser WHERE fbr_id=?',array($response_id));
								if($check == 1)
								{
									$params['response_id'] = $response_id;
								}
							}
						}
					}
				}
				if(isset($params['response_id']))
				{
					$loadParams = array('response_id'=>$params['response_id']);
					$loadTypes = array();
					$this->LoadResponseValues($loadParams, $loadTypes);
					foreach($loadParams as $thisParamKey=>$thisParamValue)
					{
						if(!isset($params[$thisParamKey]))
						{
							if($this->GetFormState() == 'update' && $loadTypes[$thisParamKey] == 'CheckboxField')
							{
								$params[$thisParamKey] = '';
							}
							else
							{
								$params[$thisParamKey] = $thisParamValue;
							}
						}
					}
				}
			}
			$sql = 'SELECT * FROM '.$pref.'module_fb_field WHERE form_id=? ORDER BY order_by';
			$result = $db->GetArray($sql, array($formId));
/*			$result = array();
			if($rs && $rs->RecordCount() > 0)
			{
				$result = $rs->GetArray();
			}
*/
			if($result)
			{
				foreach($result as &$fldArray)
				{
					//error_log("Instantiating Field. usage ".memory_get_usage());
					$className = $this->MakeClassName($fldArray['type'], '');
					// create the field object
					if((isset($fldArray['field_id']) && (isset($params['fbrp__'.$fldArray['field_id']]) || isset($params['fbrp___'.$fldArray['field_id']]))) ||
						(isset($fldArray['field_id']) && isset($params['value_'.$fldArray['name']])) || (isset($fldArray['field_id']) && isset($params['value_fld'.$fldArray['field_id']])) ||
						(isset($params['field_id']) && isset($fldArray['field_id']) && $params['field_id'] == $fldArray['field_id']))
					{
						$fldArray = array_merge($fldArray,$params);
					}

					$fld = $this->NewField($fldArray);
					$this->Fields[] = $fld;
					if($fld->Type == 'PageBreakField')
					{
						$this->formTotalPages++;
					}
				}
				unset ($fldArray);
			}
			$this->loaded = 'full';
		} //end of $loadDeep

		return true;
	}

	/* notable params:
	  fbrp_xml_file -- source file for the XML
	  xml_string -- source string for the XML
	*/
	function ImportXML(&$params)
	{
		// xml_parser_create, xml_parse_into_struct
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0); // was 1
		if(isset($params['fbrp_xml_file']) && ! empty($params['fbrp_xml_file']))
		{
			xml_parse_into_struct($parser, file_get_contents($params['fbrp_xml_file']), $values);
		}
		elseif(isset($params['xml_string']) && ! empty($params['xml_string']))
		{
			xml_parse_into_struct($parser, $params['xml_string'], $values);
		}
		else
		{
			return false;
		}
		xml_parser_free($parser);
		$elements = array();
		$stack = array();
		$fieldMap = array();
		foreach($values as $tag)
		{
			$index = count($elements);
			if($tag['type'] == 'complete' || $tag['type'] == 'open')
			{
				$elements[$index] = array();
				$elements[$index]['name'] = $tag['tag'];
				$elements[$index]['attributes'] = empty($tag['attributes']) ? "" : $tag['attributes'];
				$elements[$index]['content']	= empty($tag['value']) ? "" : $tag['value'];
				if($tag['type'] == 'open')
				{
					# push
					$elements[$index]['children'] = array();
					$stack[count($stack)] = &$elements;
					$elements = &$elements[$index]['children'];
				}
			}
			if($tag['type'] == 'close')
			{	# pop
				$elements = &$stack[count($stack) - 1];
				unset($stack[count($stack) - 1]);
			}
		}
		//debug_display($elements);
		if(!isset($elements[0]) || !isset($elements[0]) || !isset($elements[0]['attributes']))
		{
			//parsing failed, or invalid file.
			return false;
		}
		$params['form_id'] = -1; // override any form_id values that may be around
		$formAttrs = &$elements[0]['attributes'];

		if(isset($params['fbrp_import_formalias']) && !empty($params['fbrp_import_formalias']))
		{
			$this->SetAlias($params['fbrp_import_formalias']);
		}
		else if($this->inXML($formAttrs['alias']))
		{
			$this->SetAlias($formAttrs['alias']);
		}
		if(isset($params['fbrp_import_formname']) && !empty($params['fbrp_import_formname']))
		{
			$this->SetName($params['fbrp_import_formname']);
		}
		$foundfields = false;
		// populate the attributes and field name first. When we see a field, we save the form and then start adding the fields to it.

		foreach($elements[0]['children'] as $thisChild)
		{
			if($thisChild['name'] == 'form_name')
			{
				$curname =  $this->GetName();
				if(empty($curname))
				{
					$this->SetName($thisChild['content']);
				}
			}
			elseif($thisChild['name'] == 'attribute')
			{
				$this->SetAttr($thisChild['attributes']['key'], $thisChild['content']);
			}
			else
			{
				// we got us a field
				if(!$foundfields)
				{
					// first field
					$foundfields = true;
					if(isset($params['fbrp_import_formname']) &&
						trim($params['fbrp_import_formname']) != '')
					{
						$this->SetName(trim($params['fbrp_import_formname']));
					}
					if(isset($params['fbrp_import_formalias']) &&
						trim($params['fbrp_import_formname']) != '')
					{
						$this->SetAlias(trim($params['fbrp_import_formalias']));
					}
					$this->Store();
					$params['form_id'] = $this->GetId();
				}
				//debug_display($thisChild);
				$fieldAttrs = &$thisChild['attributes'];
				$className = $this->MakeClassName($fieldAttrs['type'], '');
				//debug_display($className);
				$newField = new $className($this, $params);
				$oldId = $fieldAttrs['id'];

				if($this->inXML($fieldAttrs['alias']))
				{
					$newField->SetAlias($fieldAttrs['alias']);
				}
				$newField->SetValidationType($fieldAttrs['validation_type']);
				if($this->inXML($fieldAttrs['order_by']))
				{
					$newField->SetOrder($fieldAttrs['order_by']);
				}
				if($this->inXML($fieldAttrs['required']))
				{
					$newField->SetRequired($fieldAttrs['required']);
				}
				if($this->inXML($fieldAttrs['hide_label']))
				{
					$newField->SetHideLabel($fieldAttrs['hide_label']);
				}
				foreach($thisChild['children'] as $thisOpt)
				{
					if($thisOpt['name'] == 'field_name')
					{
						$newField->SetName($thisOpt['content']);
					}
					if($thisOpt['name'] == 'options')
					{
						foreach($thisOpt['children'] as $thisOption)
						{
							$newField->OptionFromXML($thisOption);
						}
					}
				}
				$newField->Store(true);
				$this->Fields[] = $newField;
				$fieldMap[$oldId] = $newField->GetId();
			}
		}

		// clean up references

		if(isset($params['fbrp_xml_file']) && ! empty($params['fbrp_xml_file']))
		{
			// need to update mappings in templates.
			$tmp = $this->updateRefs($this->GetAttr('form_template',''), $fieldMap);
			$this->SetAttr('form_template',$tmp);
			$tmp = $this->updateRefs($this->GetAttr('submission_template',''), $fieldMap);
			$this->SetAttr('submission_template',$tmp);

			// need to update mappings in field templates.
			$options = array('email_template','file_template');
			foreach($this->Fields as &$fld)
			{
				$changes = false;
				foreach($options as $to)
				{
					$templ = $fld->GetOption($to,'');
					if(!empty($templ))
					{
						$tmp = $this->updateRefs($templ, $fieldMap);
						$fld->SetOption($to,$tmp);
						$changes = true;
					}
				}
				// need to update mappings in FormBrowser sort fields
				if($fld->GetFieldType() == 'DispositionFormBrowser')
				{
					for ($i=1;$i<6;$i++)
					{
						$old = $fld->GetOption('sortfield'.$i);
						if(isset($fieldMap[$old]))
						{
							$fld->SetOption('sortfield'.$i,$fieldMap[$old]);
							$changes = true;
						}
					}
				}
				if($changes)
				{
					$fld->Store(true);
				}
			}
			unset ($fld);

			$this->Store();
		}

		return true;
	}

	function updateRefs($text, &$fieldMap)
	{
		foreach($fieldMap as $k=>$v)
		{
			$text = preg_replace('/([\{\b\s])\$fld_'.$k.'([\}\b\s])/','$1\$fld_'.$v.'$2',$text);
		}
		return $text;
	 }

	function inXML(&$var)
	{
		return (isset($var) && strlen($var) > 0);
	}

	function newID($name = false, $alias = false)
	{
		$where = array();
		$vars = array();

		if($name)
		{
			$where[] = 'name=?';
			$vars[] = $name;
		}
		if($alias)
		{
			$where[] = 'alias=?';
			$vars[] = $alias;
		}
		if(count($where) > 0)
		{
			$db = $this->module_ptr->dbHandle;
			$sql = 'SELECT form_id FROM '.cms_db_prefix().'module_fb_form WHERE ';
			$sql .= implode(' OR ',$where);
			$dbresult = $db->GetOne($sql, $vars);
			if($dbresult)
				return false;
		}
		return true;
	}

	function Store()
	{
		$params = $this->module_params;
		// For new form, check for duplicate name and/or alias
		if($this->Id == -1 && !$this->newID ($params['fbrp_form_name'],$params['fbrp_form_alias']))
		{
			$params['fbrp_message'] = $this->module_ptr->Lang('duplicate_identifier');
			return false;
		}

		$db = $this->module_ptr->dbHandle;
		$pref = cms_db_prefix();
		// Check if new or old form
		if($this->Id == -1)
		{
			$this->Id = $db->GenID($pref.'module_fb_form_seq');
			$sql = 'INSERT INTO '.$pref.'module_fb_form (form_id, name, alias) VALUES (?,?,?)';
			$res = $db->Execute($sql, array($this->Id, $this->Name, $this->Alias));
		}
		else
		{
			$sql = 'UPDATE '.$pref.'module_fb_form set name=?, alias=? where form_id=?';
			$res = $db->Execute($sql, array($this->Name, $this->Alias, $this->Id));
		}
		if($res == false)
		{
			$params['fbrp_message'] = $this->module_ptr->Lang('database_error');
			return false;
		}

		// Save out the attrs
		$sql = 'DELETE FROM '.$pref.'module_fb_form_attr WHERE form_id=?';
		if($db->Execute($sql, array($this->Id)) == false)
		{
			$params['fbrp_message'] = $this->module_ptr->Lang('database_error');
			return false;
		}

		foreach($this->Attrs as $thisAttrKey=>$thisAttrValue)
		{
			$formAttrId = $db->GenID($pref.'module_fb_form_attr_seq');
			$sql = 'INSERT INTO '.$pref.'module_fb_form_attr (form_attr_id, form_id, name, value) VALUES (?,?,?,?)';
			if($db->Execute($sql, array($formAttrId, $this->Id, $thisAttrKey, $thisAttrValue)) != false)
			{
				if($thisAttrKey == 'form_template')
				{
					$this->module_ptr->SetTemplate('fb_'.$this->Id,$thisAttrValue);
				}
			}
			else
			{
				$params['fbrp_message'] = $this->module_ptr->Lang('database_error');
				return false;
			}
		}

		// Update field position
		$order_list = false;
		if(isset($params['fbrp_sort']))
		{
			$order_list = explode(',',$params['fbrp_sort']);
		}

		if(is_array($order_list) && count($order_list) > 0)
		{
			$count = 1;
			$sql = 'UPDATE '.$pref.'module_fb_field SET order_by=? WHERE field_id=?';

			foreach($order_list as $onefldid)
			{
				$fieldid = substr($onefldid,5);
				if($db->Execute($sql, array($count, $fieldid)) != false)
					$count++;
				else
				{
					$params['fbrp_message'] = $this->module_ptr->Lang('database_error');
					return false;
				}
			}
		}

		// Reload everything
		$this->Load($this->Id,$params,true);
		return true;
	}

	function Delete()
	{
		if($this->Id == -1)
		{
			return false;
		}
		if($this->loaded != 'full')
		{
			$this->Load($this->Id,array(),true);
		}
		foreach($this->Fields as &$fld)
		{
			$fld->Delete();
		}
		unset ($fld);
		$this->module_ptr->DeleteTemplate('fb_'.$this->Id);
		$pref = cms_db_prefix();
		$sql = 'DELETE FROM '. $pref.'module_fb_form where form_id=?';
		if($this->module_ptr->dbHandle->Execute($sql, array($this->Id)) == false)
			return false;
		$sql = 'DELETE FROM '.$pref.'module_fb_form_attr where form_id=?';
		$res = $this->module_ptr->dbHandle->Execute($sql, array($this->Id));
		return ($res != false);
	}

	//'hard' copy an existing field
	function CopyField($field_id, $newform = false, $neworder = false)
	{
		$pref = cms_db_prefix();
		$db = $this->module_ptr->dbHandle;
		$sql = 'SELECT * FROM ' .$pref. 'module_fb_field WHERE field_id=?';
		$row = $db->GetRow ($sql, array($field_id));
		if(!$row)
		{
			return false;
		}

		$fid = $db->GenID($pref.'module_fb_field_seq');
		if($newform === false)
		{
			$newform = intval($row['form_id']);
		}
		$row['field_id'] = $fid;
		$row['form_id'] = $newform;
//		$row['name'] .= ' '.$this->module_ptr->Lang('copy');
		if($row['validation_type'] == '')
		{
			$row['validation_type'] = null;
		}
		if($neworder === false)
		{
			$sql = 'SELECT MAX(order_by) AS last FROM '.$pref.'module_fb_field WHERE form_id=?';
			$neworder = $db->GetOne($sql, array($newform));
			if($neworder == false)
				$neworder = 0;
			$neworder++;
		}
		$row['order_by'] = $neworder;
		$sql = 'INSERT INTO ' .$pref.
		 'module_fb_field (field_id,form_id,name,type,validation_type,required,hide_label,order_by) VALUES (?,?,?,?,?,?,?,?)';
		$db->Execute ($sql, $row);

		$sql = 'SELECT * FROM ' .$pref. 'module_fb_field_opt WHERE field_id=?';
		$result = $db->Execute ($sql, array($field_id));
		if($result)
		{
			$sql = 'INSERT INTO ' .$pref.
			 'module_fb_field_opt (option_id,field_id,form_id,name,value) VALUES (?,?,?,?,?)';
			while ($row = $result->FetchRow())
			{
				$row['option_id'] = $db->GenID($pref.'module_fb_field_opt_seq');
				$row['field_id'] = $fid;
				$row['form_id'] = $newform;
				$db->Execute($sql, $row);
			}
			$result->Close();
		}
		return true;
	}

	//copy and store entire form
	function Copy()
	{
		if($this->Id == -1)
		{
			return false;
		}
		if($this->loaded != 'full')
		{
			$this->Load($this->Id,array(),true);
		}

		$pref = cms_db_prefix();
		$db = $this->module_ptr->dbHandle;
		$sql = 'INSERT INTO '.$pref.'module_fb_form (form_id,name,alias) VALUES (?,?,?)';
		$newform = $db->GenID($pref.'module_fb_form_seq');
		$name = $this->GetName();
		if($name)
			$name .= ' '.$this->module_ptr->Lang('copy');
		$alias = $this->GetAlias();
		if($alias)
			$alias .= '_'.$this->MakeAlias($this->module_ptr->Lang('copy'), true);
		$db->Execute ($sql, array($newform,$name,$alias));

		$res = true;
		$order = 1;
		foreach($this->Fields as &$fld)
		{
			if(!$this->CopyField(intval($fld->GetId()), $newform, $order))
				$res = false;
			$order++;
		}
		unset($fld);
		return $res;
	}

	// returns a class name, and makes sure the file where the class is
	// defined has been loaded.
	function MakeClassName($type, $classDirPrefix)
	{
		// perform rudimentary security, since Type could come in from a form
		$type = preg_replace('/[\W]|\.\./', '_', $type);
		if($type == '' || strlen($type) < 1)
		{
			$type = 'Field';
		}
		$classFile='';
		if(strlen($classDirPrefix) > 0)
		{
			$classFile = $classDirPrefix.DIRECTORY_SEPARATOR.$type.'.class.php';
		}
		else
		{
			$classFile = $type.'.class.php';
		}
		require_once (cms_join_path(dirname(__FILE__), $classFile));
		// class names are prepended with "fb" to prevent namespace clash.
		return ('fb'.$type);
	}

	function AddEditForm($id, $returnid, $tab, $message='')
	{
		$gCms = cmsms();
		$config = $gCms->GetConfig();
		$theme = $gCms->variables['admintheme'];
		$smarty = $gCms->GetSmarty();
		$mod = $this->module_ptr;

		if(!empty($message)) $smarty->assign('message',$mod->ShowMessage($message));

		$smarty->assign('backtomod_nav', $mod->CreateLink($id, 'defaultadmin', '', $mod->Lang('back_top'), array()));

		$smarty->assign('formstart', $mod->CreateFormStart($id, 'store_form', $returnid));
		$smarty->assign('formid', $mod->CreateInputHidden($id, 'form_id', $this->Id));
		$smarty->assign('tab_start',$mod->StartTabHeaders().
			$mod->SetTabHeader('maintab',$mod->Lang('tab_main'),($tab == 'maintab')).
			$mod->SetTabHeader('fieldstab',$mod->Lang('tab_fields'),($tab == 'fieldstab')).
			$mod->SetTabHeader('designtab',$mod->Lang('tab_design'),($tab == 'designtab')).
			$mod->SetTabHeader('templatelayout',$mod->Lang('tab_templatelayout'),($tab == 'templatelayout')).
			$mod->SetTabHeader('udttab',$mod->Lang('tab_udt'),($tab == 'udttab')).
			$mod->SetTabHeader('submittab',$mod->Lang('tab_submit'),($tab == 'submittab')).
			$mod->SetTabHeader('submittemplate',$mod->Lang('tab_submissiontemplate'),($tab == 'submittemplate')).
			$mod->EndTabHeaders() . $mod->StartTabContent());

		$smarty->assign('tabs_end',$mod->EndTabContent());
		$smarty->assign('maintab_start',$mod->StartTab('maintab'));
		$smarty->assign('fieldstab_start',$mod->StartTab('fieldstab'));
		$smarty->assign('designtab_start',$mod->StartTab('designtab'));
		$smarty->assign('templatetab_start',$mod->StartTab('templatelayout'));
		$smarty->assign('udttab_start',$mod->StartTab('udttab'));
		$smarty->assign('submittab_start',$mod->StartTab('submittab'));
		$smarty->assign('submittemplatetab_start',$mod->StartTab('submittemplate'));
		$smarty->assign('tab_end',$mod->EndTab());
		$smarty->assign('form_end',$mod->CreateFormEnd());
		$smarty->assign('title_form_name',$mod->Lang('title_form_name'));
		$smarty->assign('input_form_name', $mod->CreateInputText($id, 'fbrp_form_name', $this->Name, 50));

		$smarty->assign('title_load_template',$mod->Lang('title_load_template'));
		$modLink = $mod->CreateLink($id, 'get_template', $returnid, '', array(), '', true);
		$smarty->assign('security_key',CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);

		$templateList = array(''=>'',$mod->Lang('default_template')=>'RenderFormDefault.tpl',
			$mod->Lang('table_left_template')=>'RenderFormTableTitleLeft.tpl',
			$mod->Lang('table_top_template')=>'RenderFormTableTitleTop.tpl');

		$allForms = $mod->GetForms();
		foreach($allForms as $thisForm)
		{
			if($thisForm['form_id'] != $this->Id)
			{
				$templateList[$mod->Lang('form_template_name',$thisForm['name'])] =
					$thisForm['form_id'];
			}
		}

		$smarty->assign('input_load_template',$mod->CreateInputDropdown($id,
			'fbrp_fb_template_load', $templateList, -1, '', 'id="fb_template_load" onchange="jQuery(this).fb_get_template(\''.$mod->Lang('template_are_you_sure').'\',\''.$modLink.'\');"'));

		$globalfields = array();
		foreach(array(
			'total_pages',
			'this_page',
			'title_page_x_of_y',
			'css_class',
			'form_name',
			'form_id',
			'in_formbrowser',
			'in_admin',
			'fbr_id',
			'fb_hidden',
			'prev',
			'submit'
			) as $name)
		{
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->description = $mod->Lang('desc_'.$name);
			$globalfields[] = $oneset;
		}
		$smarty->assign('globalfields',$globalfields);

		$attrs = array();
		foreach(array(
			'alias',
			'css_class',
			'display',
			'error',
			'field_helptext_id',
			'has_label',
			'helptext',
			'hide_name',
			'id',
			'input_id',
			'input',
			'label_parts',
			'logic',
			'multiple_parts',
			'name',
			'needs_div',
			'required_symbol',
			'required',
			'smarty_eval',
			'type',
			'valid',
			'values'
			) as $name)
		{
			$oneset = new stdClass();
			$oneset->name = $name;
			$oneset->description = $mod->Lang('desc_'.$name);
			$attrs[] = $oneset;
		}
		$smarty->assign('attrs',$attrs);

		$smarty->assign('variable', $mod->Lang('variable'));
		$smarty->assign('attribute', $mod->Lang('attribute'));
		$smarty->assign('description', $mod->Lang('description'));
		$smarty->assign('globals_help1', $mod->Lang('globals_help1'));
		$smarty->assign('attrs_help1', $mod->Lang('attrs_help1'));
		$smarty->assign('attrs_help2', $mod->Lang('attrs_help2'));

		$smarty->assign('title_form_unspecified',$mod->Lang('title_form_unspecified'));
		$smarty->assign('input_form_unspecified',
			$mod->CreateInputText($id, 'fbrp_forma_unspecified',
				$this->GetAttr('unspecified',$mod->Lang('unspecified')),30));
		$smarty->assign('title_form_status', $mod->Lang('title_form_status'));
		$smarty->assign('text_ready', $mod->Lang('title_ready_for_deployment'));
		$smarty->assign('title_form_alias',$mod->Lang('title_form_alias'));
		$smarty->assign('input_form_alias',
			$mod->CreateInputText($id,'fbrp_form_alias',$this->Alias,50));
		$smarty->assign('title_form_css_class',$mod->Lang('title_form_css_class'));
		$smarty->assign('input_form_css_class',
				 $mod->CreateInputText($id, 'fbrp_forma_css_class',
							   $this->GetAttr('css_class','formbuilderform'),50,50));
		$smarty->assign('title_form_fields', $mod->Lang('title_form_fields'));
		$smarty->assign('title_form_main', $mod->Lang('title_form_main'));
		if($mod->GetPreference('show_fieldids',0) != 0)
		{
			$smarty->assign('title_field_id', $mod->Lang('title_field_id'));
		}
		if($mod->GetPreference('show_fieldaliases',1) != 0)
		{
			$smarty->assign('title_field_alias', $mod->Lang('title_field_alias_short'));
		}

		$smarty->assign('title_field_name', $mod->Lang('title_field_name'));
		$smarty->assign('title_field_type', $mod->Lang('title_field_type'));
		$smarty->assign('title_field_type', $mod->Lang('title_field_type'));
		$smarty->assign('title_form_template', $mod->Lang('title_form_template'));
		$smarty->assign('title_form_vars', $mod->Lang('title_form_vars'));
		$smarty->assign('title_list_delimiter', $mod->Lang('title_list_delimiter'));
		$smarty->assign('title_redirect_page', $mod->Lang('title_redirect_page'));

		$smarty->assign('title_submit_action', $mod->Lang('title_submit_action'));
		$smarty->assign('title_submit_template', $mod->Lang('title_submit_response'));
		$smarty->assign('title_can_drag', $mod->Lang('title_can_drag'));
		$smarty->assign('title_must_save_order', $mod->Lang('title_must_save_order'));

		$smarty->assign('title_inline_form', $mod->Lang('title_inline_form'));

		$smarty->assign('title_submit_actions', $mod->Lang('title_submit_actions'));
		$smarty->assign('title_submit_labels', $mod->Lang('title_submit_labels'));
		$smarty->assign('title_submit_javascript', $mod->Lang('title_submit_javascript'));
		$smarty->assign('title_submit_help',$mod->Lang('title_submit_help'));
		$smarty->assign('title_submit_template_help',$mod->Lang('title_submit_response_help'));

		$smarty->assign('icon_info',
			$theme->DisplayImage('icons/system/info.gif',$mod->Lang('info'),'','','systemicon'));
		$submitActions = array($mod->Lang('display_text')=>'text',
			 $mod->Lang('redirect_to_page')=>'redir');
		$smarty->assign('input_submit_action',
			  $mod->CreateInputRadioGroup($id, 'fbrp_forma_submit_action', $submitActions, $this->GetAttr('submit_action','text'), '', '&nbsp;&nbsp;'));

		$captcha = $mod->getModuleInstance('Captcha');
		if($captcha == null)
		{
			 $smarty->assign('title_install_captcha',$mod->Lang('title_captcha_not_installed'));
			 $smarty->assign('captcha_installed',0);
		}
		else
		{
			 $smarty->assign('title_use_captcha',$mod->Lang('title_use_captcha'));
			 $smarty->assign('captcha_installed',1);

			 $smarty->assign('input_use_captcha',$mod->CreateInputHidden($id,'fbrp_forma_use_captcha','0').
				   $mod->CreateInputCheckbox($id,'fbrp_forma_use_captcha','1',$this->GetAttr('use_captcha','0')).
					$mod->Lang('title_use_captcha_help'));
		}
		$smarty->assign('title_information',$mod->Lang('information'));
		$smarty->assign('title_order',$mod->Lang('order'));
		$smarty->assign('title_field_required_abbrev',$mod->Lang('title_field_required_abbrev'));
		$smarty->assign('hasdisposition',$this->HasDisposition()?1:0);
		$maxOrder = 1;
		if($this->Id > 0)
		{
			$smarty->assign('fb_hidden', $mod->CreateInputHidden($id, 'fbrp_form_op',$mod->Lang('updated')).
				$mod->CreateInputHidden($id, 'fbrp_sort','','class="fbrp_sort"').
				$mod->CreateInputHidden($id, 'fbr_atab'));
			$smarty->assign('adding',0);
			$smarty->assign('save', $mod->CreateInputSubmit($id, 'fbrp_submit', $mod->Lang('save')));
			$smarty->assign('apply', $mod->CreateInputSubmit($id, 'fbrp_submit', $mod->Lang('apply'),
					'title = "'.$mod->Lang('save_and_continue').'" onclick="jQuery(this).fb_set_tab()"'));
			$fieldList = array();
			$jsfuncs = array();
			$count = 1;
			$last = $this->GetFieldCount();
			
			$icontrue = $theme->DisplayImage('icons/system/true.gif',$mod->Lang('true'),'','','systemicon');
			$iconfalse = $theme->DisplayImage('icons/system/false.gif',$mod->Lang('false'),'','','systemicon');
			$iconedit = $theme->DisplayImage('icons/system/edit.gif',$mod->Lang('edit'),'','','systemicon');
			$iconcopy = $theme->DisplayImage('icons/system/copy.gif',$mod->Lang('copy'),'','','systemicon');
			$icondelete = $theme->DisplayImage('icons/system/delete.gif',$mod->Lang('delete'),'','','systemicon');
			$iconup = $theme->DisplayImage('icons/system/arrow-u.gif',$mod->Lang('moveup'),'','','systemicon');
			$icondown = $theme->DisplayImage('icons/system/arrow-d.gif',$mod->Lang('movedn'),'','','systemicon');

			foreach($this->Fields as &$fld)
			{
				$oneset = new stdClass();
				$oneset->name = $mod->CreateLink($id,'add_edit_field','',$fld->GetName(),array('field_id'=>$fld->GetId(),'form_id'=>$this->Id));
				if($mod->GetPreference('show_fieldids',0) != 0)
				{
					$oneset->id = $mod->CreateLink($id,'add_edit_field','',$fld->GetId(),array('field_id'=>$fld->GetId(),'form_id'=>$this->Id));
				}
				$oneset->type = $fld->GetDisplayType();
				$oneset->alias = $fld->GetAlias();
				$oneset->id = $fld->GetID();

				if(!$fld->DisplayInForm() || $fld->IsNonRequirableField())
				{
					$oneset->disposition = '';
					$no_avail = $mod->Lang('not_available');
				}
				else if($fld->IsRequired())
				{
					$oneset->disposition = $mod->CreateLink($id,'update_field_required','',
						$icontrue, array('form_id'=>$this->Id,'fbrp_active'=>'off','field_id'=>$fld->GetId()),'','','',
						'class="true" onclick="jQuery(this).fb_admin_update_field_required(); return false;"');
				}
				else
				{
					$oneset->disposition = $mod->CreateLink($id,'update_field_required','',
						$iconfalse, array('form_id'=>$this->Id,'fbrp_active'=>'on','field_id'=>$fld->GetId()),'','','',
						'class="false" onclick="jQuery(this).fb_admin_update_field_required(); return false;"');
				}

				$oneset->field_status = $fld->StatusInfo();
				$oneset->editlink = $mod->CreateLink($id,'add_edit_field','',$iconedit,array('field_id'=>$fld->GetId(),'form_id'=>$this->Id));
				$oneset->copylink = $mod->CreateLink($id,'copy_field','',$iconcopy,array('field_id'=>$fld->GetId(),'form_id'=>$this->Id));
				$oneset->deletelink = $mod->CreateLink($id,'delete_field','',$icondelete,array('field_id'=>$fld->GetId(),'form_id'=>$this->Id),'','','',
					'onclick="jQuery(this).fb_delete_field(\''.$mod->Lang('are_you_sure_delete_field',htmlspecialchars($fld->GetName())).'\'); return false;"');

				if($count > 1)
				{
					$oneset->up = $mod->CreateLink($id,'update_field_order','',$iconup,array('form_id'=>$this->Id,'fbrp_dir'=>'up','field_id'=>$fld->GetId()));
				}
				else
				{
					$oneset->up = '';
				}
				if($count < $last)
				{
					$oneset->down=$mod->CreateLink($id,'update_field_order','',$icondown,array('form_id'=>$this->Id,'fbrp_dir'=>'down','field_id'=>$fld->GetId()));
				}
				else
				{
					$oneset->down = '';
				}

				$count++;
				if($fld->GetOrder() >= $maxOrder)
				{
					$maxOrder = $fld->GetOrder() + 1;
				}
				$fieldList[] = $oneset;
			}
			unset ($fld);

			$smarty->assign('fields',$fieldList);
			$smarty->assign('add_field_link',
				$mod->CreateLink($id, 'add_edit_field', $returnid,
					$theme->DisplayImage('icons/system/newobject.gif',$mod->Lang('title_add_new_field'),'','','systemicon'),
					array('form_id'=>$this->Id, 'fbrp_order_by'=>$maxOrder), '', false).' '.
					$mod->CreateLink($id, 'add_edit_field', $returnid,$mod->Lang('title_add_new_field'),array('form_id'=>$this->Id, 'fbrp_order_by'=>$maxOrder), '', false));

			if($mod->GetPreference('enable_fastadd',1) == 1)
			{
				$smarty->assign('fastadd',1);
				$smarty->assign('title_fastadd',$mod->Lang('title_fastadd'));
				$link = $mod->CreateLink($id,'add_edit_field',$returnid,'',
					array('form_id'=>$this->Id, 'fbrp_order_by'=>$maxOrder),'',true,true);
				$link = str_replace('&amp;','&',$link);
				$typeFunc = <<<EOS
function fast_add(field_type)
{
 var type=field_type.options[field_type.selectedIndex].value;
 this.location='{$link}&{$id}fbrp_field_type='+type;
 return true;
}
EOS;
				$jsfuncs [] = $typeFunc; //TODO handle duplicates
				$mod->initialize();
				if($mod->GetPreference('show_field_level','basic') == 'basic')
				{
					$smarty->assign('input_fastadd',$mod->CreateInputDropdown($id, 'fbrp_field_type',
					array_merge(array($mod->Lang('select_type')=>''),$mod->std_field_types), -1,'', 'onchange="fast_add(this)"').
						$mod->Lang('title_switch_advanced').
						$mod->CreateLink($id,'add_edit_form',$returnid,$mod->Lang('title_switch_advanced_link'),
						array('form_id'=>$this->Id, 'fbrp_set_field_level'=>'advanced')));
				}
				else
				{
					$smarty->assign('input_fastadd',$mod->CreateInputDropdown($id, 'fbrp_field_type',
					array_merge(array($mod->Lang('select_type')=>''),$mod->field_types), -1,'', 'onchange="fast_add(this)"').
						$mod->Lang('title_switch_basic').
						$mod->CreateLink($id,'add_edit_form',$returnid,$mod->Lang('title_switch_basic_link'),
						array('form_id'=>$this->Id, 'fbrp_set_field_level'=>'basic')));
				}
			}
		}
		else
		{
			$smarty->assign('save','');
			$smarty->assign('apply',
					 $mod->CreateInputSubmit($id, 'fbrp_submit', $mod->Lang('add')));
			$smarty->assign('fb_hidden',
					 $mod->CreateInputHidden($id, 'fbrp_form_op',$mod->Lang('added')).$mod->CreateInputHidden($id, 'fbrp_sort','','id="fbrp_sort"'));
			$smarty->assign('adding',1);
		}
		$smarty->assign('cancel', $mod->CreateInputSubmit($id, 'fbrp_cancel', $mod->Lang('cancel')));

		$smarty->assign('link_notready','<strong>'.$mod->Lang('title_not_ready1').'</strong> '.
			$mod->Lang('title_not_ready2')." ".$mod->CreateLink($id, 'add_edit_field', $returnid,$mod->Lang('title_not_ready_link'),array('form_id'=>$this->Id, 'fbrp_order_by'=>$maxOrder,'fbrp_dispose_only'=>1), '', false, false,'class="module_fb_link"')." ".$mod->Lang('title_not_ready3')
		);

		$smarty->assign('input_inline_form',$mod->CreateInputHidden($id,'fbrp_forma_inline','0').
			$mod->CreateInputCheckbox($id,'fbrp_forma_inline','1',$this->GetAttr('inline','0')).
				$mod->Lang('title_inline_form_help'));

		$smarty->assign('title_form_submit_button',$mod->Lang('title_form_submit_button'));
		$smarty->assign('input_form_submit_button',
			$mod->CreateInputText($id, 'fbrp_forma_submit_button_text',
				$this->GetAttr('submit_button_text',$mod->Lang('button_submit')), 35, 35));
		$smarty->assign('title_submit_button_safety',$mod->Lang('title_submit_button_safety_help'));
		$smarty->assign('input_submit_button_safety',
			$mod->CreateInputHidden($id,'fbrp_forma_input_button_safety','0').
			$mod->CreateInputCheckbox($id,'fbrp_forma_input_button_safety','1',$this->GetAttr('input_button_safety','0')).
			$mod->Lang('title_submit_button_safety'));
		$smarty->assign('title_form_prev_button',$mod->Lang('title_form_prev_button'));
		$smarty->assign('input_form_prev_button',
			$mod->CreateInputText($id, 'fbrp_forma_prev_button_text',
				$this->GetAttr('prev_button_text',$mod->Lang('button_previous')), 35, 35));

		$smarty->assign('input_title_user_captcha',
			$mod->CreateInputText($id, 'fbrp_forma_title_user_captcha',
				$this->GetAttr('title_user_captcha',$mod->Lang('title_user_captcha')),50,80));
		$smarty->assign('title_title_user_captcha',$mod->Lang('title_title_user_captcha'));

		$smarty->assign('input_title_user_captcha_error',
			$mod->CreateInputText($id, 'fbrp_forma_captcha_wrong',
				$this->GetAttr('captcha_wrong',$mod->Lang('wrong_captcha')),50,80));
		$smarty->assign('title_user_captcha_error',$mod->Lang('title_user_captcha_error'));

		$smarty->assign('title_form_next_button', $mod->Lang('title_form_next_button'));
		$smarty->assign('input_form_next_button',
			$mod->CreateInputText($id, 'fbrp_forma_next_button_text',
				$this->GetAttr('next_button_text',$mod->Lang('button_continue')), 35, 35));
		$smarty->assign('title_form_predisplay_udt',$mod->Lang('title_form_predisplay_udt'));
		$smarty->assign('title_form_predisplay_each_udt',$mod->Lang('title_form_predisplay_each_udt'));

		$usertagops = $gCms->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		$usertaglist[$mod->lang('none')] = -1;
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;
		$smarty->assign('input_form_predisplay_udt',
			$mod->CreateInputDropdown($id,'fbrp_forma_predisplay_udt',$usertaglist,-1,
				$this->GetAttr('predisplay_udt',-1)));
		$smarty->assign('input_form_predisplay_each_udt',
			$mod->CreateInputDropdown($id,'fbrp_forma_predisplay_each_udt',$usertaglist,-1,
				$this->GetAttr('predisplay_each_udt',-1)));

		$smarty->assign('title_form_validate_udt',$mod->Lang('title_form_validate_udt'));
		$usertagops = $gCms->GetUserTagOperations();
		$usertags = $usertagops->ListUserTags();
		$usertaglist = array();
		$usertaglist[$mod->lang('none')] = -1;
		foreach($usertags as $key => $value)
			$usertaglist[$value] = $key;
		$smarty->assign('input_form_validate_udt',
			$mod->CreateInputDropdown($id,'fbrp_forma_validate_udt',$usertaglist,-1,
				$this->GetAttr('validate_udt',-1)));

		$smarty->assign('title_form_required_symbol',$mod->Lang('title_form_required_symbol'));
		$smarty->assign('input_form_required_symbol',
			 $mod->CreateInputText($id, 'fbrp_forma_required_field_symbol',
				$this->GetAttr('required_field_symbol','*'), 5));
		$smarty->assign('input_list_delimiter',
			$mod->CreateInputText($id, 'fbrp_forma_list_delimiter',
				$this->GetAttr('list_delimiter',','), 5));

		$contentops = $gCms->GetContentOperations();
		$smarty->assign('input_redirect_page',$contentops->CreateHierarchyDropdown('',$this->GetAttr('redirect_page','0'), $id.'fbrp_forma_redirect_page'));

		$smarty->assign('input_form_template',
			$mod->CreateTextArea(false, $id,
				$this->GetAttr('form_template',$this->DefaultTemplate()),
				'fbrp_forma_form_template',
				'module_fb_area_wide',
				'fb_form_template',
				'', '', 80, 15));

		$smarty->assign('input_submit_javascript',
			$mod->CreateTextArea(false, $id,
				$this->GetAttr('submit_javascript',''), 'fbrp_forma_submit_javascript','module_fb_area_short','fb_submit_javascript',
				'', '', 80, 15,'','').
				'<br />'.$mod->Lang('title_submit_javascript_long'));

		$attr_name = 'submission_template';
		$smarty->assign('input_submit_template',
			 $mod->CreateTextArea(false, $id,
				$this->GetAttr($attr_name,$this->createSampleTemplate(true,false)),
				'fbrp_forma_'.$attr_name,
				'module_fb_area_wide',
				'', '', '', 80, 15));

		self::SetupVarsHelp($mod,$smarty);

		$parms = array();
		$parms[$attr_name]['general_button'] = true;
		list ($popfuncs, $buttons) = self::AdminTemplateActions($id,$parms);

		$smarty->assign('incpath',$mod->GetModuleURLPath().'/include/');
		$smarty->assign('jsfuncs',array_merge($jsfuncs,$popfuncs));
		$smarty->assign('buttons',$buttons);

		return $mod->ProcessTemplate('AddEditForm.tpl');
	}

	function &Replicate(&$params)
	{
		$aefield = false;//need ref to this
		if(isset($params['field_id']) && $params['field_id'] != -1)
		{
			$last = -1;
			$orig = $params['field_id'];
			foreach($this->Fields as &$fld)
			{
				if($fld->GetId() == $orig)
				{
					$aefield = clone($fld);
					$aefield->Id = -1;
					$name = $aefield->GetName();
					$aefield->SetName($name.' '.$this->module_ptr->Lang('copy'));
				}
				if($fld->GetOrder() > $last)
					$last = $fld->GetOrder();
			}
			unset ($fld);
			if($aefield)
			{
				$aefield->SetOrder($last+1);
			}
		}
		return $aefield;
	}

	// Add new field
	function &NewField(&$params)
	{
		//$aefield = new fbFieldBase($this,$params);
		$aefield = false;
		if(isset($params['field_id']) && $params['field_id'] != -1)
		{
			// we're loading an extant field
			$sql = 'SELECT type FROM ' . cms_db_prefix() . 'module_fb_field WHERE field_id=?';
			$type = $this->module_ptr->dbHandle->GetOne($sql, array($params['field_id']));
			if($type != '')
			{
				$className = $this->MakeClassName($type, '');
				$aefield = new $className($this, $params);
				$aefield->LoadField($params);
			}
		}
		if($aefield === false)
		{
			// new field
			if(!isset($params['fbrp_field_type']))
			{
				// unknown field type
				$aefield = new fbFieldBase($this,$params);
			}
			else
			{
				// specified field type via params
				$className = $this->MakeClassName($params['fbrp_field_type'], '');
				$aefield = new $className($this, $params);
			}
		}
		return $aefield;
	}

	function AddEditField($id, &$aefield, $dispose_only, $returnid, $message='')
	{
		$mod = $this->module_ptr;
		$smarty = cmsms()->GetSmarty();

		if(!empty($message))
			$smarty->assign('message',$mod->ShowMessage($message)); //success message
		elseif(isset($params['fbrp_message']))
			$smarty->assign('message',$params['fbrp_message']); //probably an error message
		$smarty->assign('backtomod_nav', $mod->CreateLink($id,'defaultadmin','',$mod->Lang('back_top'), array()));
		$smarty->assign('backtoform_nav',$mod->CreateLink($id,'add_edit_form',$returnid, $mod->Lang('link_back_to_form'), array('form_id'=>$this->Id)));

		$mainList = array();
		$advList = array();
		$baseList = $aefield->PrePopulateBaseAdminForm($id, $dispose_only);
		if($aefield->GetFieldType() == '')
		{
			// still need type
			$fieldList = array();
		}
		else
		{
			// we have our type
			$fieldList = $aefield->PrePopulateAdminForm($id);
		}

		$hasmain = isset($baseList['main']) || isset($fieldList['main']);
		$hasadvanced = isset($baseList['adv']) || isset($fieldList['adv']);

		$smarty->assign('start_form',$mod->CreateFormStart($id,'add_edit_field',$returnid));
		$smarty->assign('end_form', $mod->CreateFormEnd());
		$tmp = $mod->StartTabHeaders();
		if($hasmain)
			$tmp .= $mod->SetTabHeader('maintab',$mod->Lang('tab_main'));
		if($hasadvanced)
			$tmp .= $mod->SetTabHeader('advancedtab',$mod->Lang('tab_advanced'));
		$tmp .= $mod->EndTabHeaders() . $mod->StartTabContent();
		$smarty->assign('tab_start',$tmp);
		$smarty->assign('tab_end',$mod->EndTab());
		$smarty->assign('tabs_end',$mod->EndTabContent());
		if($hasmain)
			$smarty->assign('maintab_start',$mod->StartTab('maintab'));
		if($hasadvanced)
			$smarty->assign('advancedtab_start',$mod->StartTab('advancedtab'));
		$smarty->assign('notice_select_type',$mod->Lang('notice_select_type'));

		if($aefield->GetId() != -1)
		{
			$smarty->assign('op',$mod->CreateInputHidden($id, 'fbrp_op',$mod->Lang('updated')));
			$smarty->assign('submit',$mod->CreateInputSubmit($id, 'fbrp_aef_upd', $mod->Lang('update')));
		}
		elseif($aefield->GetFieldType() != '')
		{
			$smarty->assign('op',$mod->CreateInputHidden($id, 'fbrp_op', $mod->Lang('added')));
			$smarty->assign('submit',$mod->CreateInputSubmit($id, 'fbrp_aef_add', $mod->Lang('add')));
		}
		$smarty->assign('cancel',$mod->CreateInputSubmit($id, 'fbrp_aef_cancel', $mod->Lang('cancel')));

		if($aefield->HasAddOp())
		{
			$smarty->assign('add',$mod->CreateInputSubmit($id,'fbrp_aef_optadd',$aefield->GetOptionAddButton()));
		}
		else
		{
			$smarty->assign('add','');
		}
		if($aefield->HasDeleteOp())
		{
			$smarty->assign('del',$mod->CreateInputSubmit($id,'fbrp_aef_optdel',$aefield->GetOptionDeleteButton()));
		}
		else
		{
			$smarty->assign('del','');
		}

		$smarty->assign('fb_hidden', $mod->CreateInputHidden($id, 'form_id', $this->Id) .
			$mod->CreateInputHidden($id, 'field_id', $aefield->GetId()) .
			$mod->CreateInputHidden($id, 'fbrp_order_by', $aefield->GetOrder()) .
			$mod->CreateInputHidden($id, 'fbrp_set_from_form','1'));

		if(/*!$aefield->IsDisposition() && */ !$aefield->IsNonRequirableField())
		{
			$smarty->assign('requirable',1);
		}
		else
		{
			$smarty->assign('requirable',0);
		}

		if(isset($baseList['main']))
		{
			foreach($baseList['main'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$mainList[] = $oneset;
			}
		}
		if(isset($baseList['adv']))
		{
			foreach($baseList['adv'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$advList[] = $oneset;
			}
		}
		if(isset($fieldList['main']))
		{
			foreach($fieldList['main'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$mainList[] = $oneset;
			}
		}
		if(isset($fieldList['adv']))
		{
			foreach($fieldList['adv'] as $item)
			{
				list ($titleStr, $inputStr, $helpStr) = $item + array (null, null, null);
				$oneset = new stdClass();
				if($titleStr) $oneset->title = $titleStr;
				if($inputStr) $oneset->input = $inputStr;
				if($helpStr) $oneset->help = $helpStr;
				$advList[] = $oneset;
			}
		}
		$aefield->PostPopulateAdminForm($mainList, $advList);

		$smarty->assign('mainList',$mainList);
		$smarty->assign('advList',$advList);
		if(isset($fieldList['table']))
			$smarty->assign('mainTable', $fieldList['table']);
		else
			$smarty->clear_assign('mainTable');
		if(isset($fieldList['funcs']))
			$smarty->assign('jsfuncs',$fieldList['funcs']);
		else
			$smarty->clear_assign('jsfuncs');
		if(isset($fieldList['extra']))
		{
			$showvars = false;
			switch ($fieldList['extra'])
			{
			 case 'varshelpadv':
				$showvars = true;
				$smarty->assign('advvarhelp',1);
				break;
			 case 'varshelpmain':
				$showvars = true;
				$smarty->assign('mainvarhelp',1);
				break;
			 case 'varshelpboth':
				$showvars = true;
				$smarty->assign('mainvarhelp',1);
				$smarty->assign('advvarhelp',1);
				break;
			}
			if($showvars)
				self::SetupVarsHelp($mod, $smarty);
		}
		$smarty->assign('incpath',$mod->GetModuleURLPath().'/include/');

		return $mod->ProcessTemplate('AddEditField.tpl');
	}

	function MakeAlias($string, $isForm=false)
	{
		$string = trim(htmlspecialchars($string));
		if($isForm)
		{
			return strtolower($string);
		}
		else
		{
			return 'fb'.strtolower($string);
		}
	}

	function SwapFieldsByIndex($src_field_index, $dest_field_index)
	{
		$srcField = $this->GetFieldByIndex($src_field_index);
		$destField = $this->GetFieldByIndex($dest_field_index);
		$tmpOrderBy = $destField->GetOrder();
		$destField->SetOrder($srcField->GetOrder());
		$srcField->SetOrder($tmpOrderBy);
		//it seems this makes php4 go crazy fixed by reloading form before showing it again
//		$this->Fields[$dest_field_index] = $srcField;
//		$this->Fields[$src_field_index] = $destField;
		$srcField->Store();
		$destField->Store();
	}

	function &GetFields()
	{
		return $this->Fields;
	}

	function &GetFieldById($field_id)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetId() == $field_id)
			{
				return $fld;
			}
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	//returns first matching alias (formerly - the last match)
	function &GetFieldByAlias($field_alias)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetAlias() == $field_alias)
			{
				return $fld;
			}
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	//returns first matching name (formerly - the last match)
	function &GetFieldByName($field_name)
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetName() == $field_name)
			{
				return $fld;
			}
		}
		unset ($fld);
		$fld = false; //need ref to this
		return $fld;
	}

	function &GetFieldByIndex($field_index)
	{
		return $this->Fields[$field_index];
	}

	//returns first matching id (formerly - the last match)
	function GetFieldIndexFromId($field_id)
	{
		$i = 0; //don't assume anything about fields-array key
		foreach($this->Fields as &$fld)
		{
			if($fld->GetId() == $field_id)
			{
				unset ($fld);
				return $i;
			}
			$i++;
		}
		unset ($fld);
		return -1;
	}

	function MergeEmails(&$params)
	{
		if($params['fbrp_opt_destination_address'])
		{
			if(!is_array($params['fbrp_opt_destination_address']))
				$params['fbrp_opt_destination_address'] = array($params['fbrp_opt_destination_address']);

			foreach($params['fbrp_opt_destination_address'] as $i => $to)
			{
				if(isset($params['fbrp_aef_to_'.$i]))
				{
					$totype = $params['fbrp_aef_to_'.$i];
					switch ($totype)
					{
					 case 'cc';
						$params['fbrp_opt_destination_address'][$i] = '|cc|'.$to;
						break;
					 case 'bc':
						$params['fbrp_opt_destination_address'][$i] = '|bc|'.$to;
						break;
					}
					unset($params['fbrp_aef_to_'.$i]);
				}
			}
		}
	}

	function DefaultTemplate()
	{
		return file_get_contents(cms_join_path(dirname(dirname(__FILE__)),'templates','RenderFormDefault.tpl'));
	}

	function DeleteField($field_id)
	{
		$index = $this->GetFieldIndexFromId($field_id);
		if($index != -1)
		{
			$this->Fields[$index]->Delete();
			array_splice($this->Fields,$index,1);
		}
	}

	function ResetFields()
	{
		foreach($this->Fields as &$fld)
		{
			$fld->ResetValue();
		}
		unset ($fld);
	}

	// FormBrowser >= 0.3 Response load method. This populates the Field values directly
	// (as opposed to LoadResponseValues, which places the values into the $params array)
	function LoadResponse($response_id)
	{
		$mod = $this->module_ptr;
		$db = $this->module_ptr->dbHandle;

		$oneset = new StdClass();
		$row = $db->GetRow('SELECT response, form_id FROM '.cms_db_prefix().
						'module_fb_formbrowser WHERE fbr_id=?', array($response_id));

		if($row)
		{
			if($row['form_id'] == $this->GetId())
			{
				$oneset->xml = $row['response'];
				$oneset->form_id = $row['form_id'];
			}
			else
				return false;
		}
		else
			return false;

		$fbField = $this->GetFormBrowserField();
		if($fbField == false)
		{
			// error handling goes here.
			echo($mod->Lang('error_has_no_fb_field'));
		}
		$mod->HandleResponseFromXML($fbField, $oneset);

		list($fnames, $aliases, $vals) = $mod->ParseResponseXML($oneset->xml, false);
		$this->ResetFields();
		foreach($vals as $id=>$val)
		{
			//error_log("setting value of field ".$id." to be ".$val);
			$index = $this->GetFieldIndexFromId($id);
			if($index != -1 &&  is_object($this->Fields[$index]))
			{
				$this->Fields[$index]->SetValue($val);
			}
		}
		return true;
	}

	// Check if FormBrowser field exists
	function &GetFormBrowserField()
	{
		foreach($this->Fields as &$fld)
		{
			if($fld->GetFieldType() == 'DispositionFormBrowser')
			{
				return $fld;
			}
		}
		unset ($fld);
		// error handling goes here.
		$fld = false; //needed reference
		return $fld;
	}

	function ReindexResponses()
	{
		@set_time_limit(0);
		$mod = $this->module_ptr;
		$db = $this->module_ptr->dbHandle;
		$sql = 'SELECT fbr_id FROM '.cms_db_prefix().'module_fb_formbrowser WHERE form_id=?';
		$responses = $db->GetAll($sql, array($this->Id));
		$fbr_field = $this->GetFormBrowserField();
		foreach($responses as &$this_resp)
		{
			if($this->LoadResponse($this_resp))
			{
				$this->StoreResponse($this_resp,'',$fbr_field);
			}
		}
		unset ($this_resp);
	}

	// FormBrowser >= 0.3 Response load method. This populates the $params array for later processing/combination
	// (as opposed to LoadResponse, which places the values into the Field values directly)
	function LoadResponseValues(&$params, &$types)
	{
		$mod = $this->module_ptr;
		$db = $this->module_ptr->dbHandle;
		$oneset = new StdClass();
		$form_id = -1;
		$sql = 'SELECT response, form_id FROM '.cms_db_prefix().'module_fb_formbrowser WHERE fbr_id=?';
		$row = $db->GetRow($sql, array($params['response_id']));

		if($row)
		{
			$oneset->xml = $row['response'];
			$form_id = $row['form_id'];
		}
		// loaded a response -- at this point, we check that the response
		// is for the correct form_id!
		if($form_id != $this->GetId())
		{
			return false;
		}
		$fbField = $mod->GetFormBrowserField($form_id);
		if($fbField == false)
		{
			// error handling goes here.
			echo($mod->Lang('error_has_no_fb_field'));
		}
		$mod->HandleResponseFromXML($fbField, $oneset);
		list($fnames, $aliases, $vals) = $mod->ParseResponseXML($oneset->xml, false);
		$types = $mod->ParseResponseXMLType($oneset->xml);
		foreach($vals as $id=>$val)
		{
			if(isset($params['fbrp__'.$id]) &&
			! is_array($params['fbrp__'.$id]))
			{
				$params['fbrp__'.$id] = array($params['fbrp__'.$id]);
				array_push($params['fbrp__'.$id], $val);
			}
			elseif(isset($params['fbrp__'.$id]))
			{
				array_push($params['fbrp__'.$id], $val);
			}
			else
			{
				$params['fbrp__'.$id] = $val;
			}
		}
		return true;
	}

	// FormBrowser < 0.3 Response load method
	function LoadResponseValuesOld(&$params)
	{
		$db = $this->module_ptr->dbHandle;
		// loading a response -- at this point, we check that the response
		// is for the correct form_id!
		$sql = 'SELECT form_id FROM ' . cms_db_prefix().
			'module_fb_resp where resp_id=?';
		if($result = $db->GetRow($sql, array($params['response_id'])))
		{
			if($result['form_id'] == $this->GetId())
			{
				$sql = 'SELECT field_id, value FROM '.cms_db_prefix().
				'module_fb_resp_val WHERE resp_id=? order by resp_val_id';
				$allrows = $db->GetAll($sql, array($params['response_id']));
				foreach($allrows as &$row)
				{ // was '__'
					$fid = 'fbrp__'.$row['field_id'];
					if(isset($params[$fid]))
					{
						if(!is_array($params[$fid]))
							$params[$fid] = array($params[$fid]);
						$params[$fid][] = $row['value'];
					}
					else
						$params[$fid] = $row['value'];
				}
				unset ($row);
				return true;
			}
		}
		return false;
	}

	// Validation stuff action.validate_form.php
	function CheckResponse($form_id, $response_id, $code)
	{
		$db = $this->module_ptr->dbHandle;
		$sql = 'SELECT secret_code FROM ' . cms_db_prefix(). 'module_fb_formbrowser WHERE form_id=? AND fbr_id=?';
		if($result = $db->GetRow($sql, array($form_id,$response_id)))
		{
			if($result['secret_code'] == $code)
			{
				return true;
			}
		}
		return false;
	}

	// Master response inputter
	function StoreResponse($response_id=-1,$approver='',&$formBuilderDisposition)
	{
		$mod = $this->module_ptr;
		$db = $mod->dbHandle;
		$newrec = false;
		$crypt = false;
		$hash_fields = false;
		$sort_fields = array();

		// Check if form has Database fields, do init
		if(is_object($formBuilderDisposition) &&
		  ($formBuilderDisposition->GetFieldType()=='DispositionFormBrowser' ||
		   $formBuilderDisposition->GetFieldType()=='DispositionDatabase'))
		{
			$crypt = ($formBuilderDisposition->GetOption('crypt','0') == '1');
			$hash_fields = ($formBuilderDisposition->GetOption('hash_sort','0') == '1');
			for ($i=0;$i<5;$i++)
			{
				$sort_fields[$i] = $formBuilderDisposition->getSortFieldVal($i+1);
			}
		}

		// If new field
		if($response_id == -1)
		{
			if(is_object($formBuilderDisposition) && $formBuilderDisposition->GetOption('feu_bind','0') == '1')
			{
				$feu = $mod->GetModuleInstance('FrontEndUsers');
				if($feu == false)
				{
					debug_display("FAILED to instatiate FEU!");
					return;
				}
				$feu_id = $feu->LoggedInId();
			}
			else
			{
				$feu_id = -1;
			}
			$response_id = $db->GenID(cms_db_prefix(). 'module_fb_formbrowser_seq');
			foreach($this->Fields as &$fld)
			{
				// set the response_id to be the attribute of the database disposition
				$type = $fld->GetFieldType();
				if($type == 'DispositionDatabase' || $type == 'DispositionFormBrowser')
				{
					$fld->SetValue($response_id);
				}
			}
			unset ($fld);
			$newrec = true;
			}
		else
		{
			$feu_id = $mod->getFEUIDFromResponseID($response_id);
		}

		// Convert form to XML
		$xml = $this->ResponseToXML();

		// Do the actual adding
		if(!$crypt)
		{
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		elseif(!$hash_fields)
		{
			list($res, $xml) = $mod->crypt($xml,$formBuilderDisposition);
			if(!$res)
			{
				return array(false, $xml);
			}
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$sort_fields[0]:'',
				isset($sort_fields[1])?$sort_fields[1]:'',
				isset($sort_fields[2])?$sort_fields[2]:'',
				isset($sort_fields[3])?$sort_fields[3]:'',
				isset($sort_fields[4])?$sort_fields[4]:'',
				$feu_id,
				$xml);
		}
		else
		{
			list($res, $xml) = $mod->crypt($xml,$formBuilderDisposition);
			if(!$res)
			{
				return array(false, $xml);
			}
			$output = $this->StoreResponseXML(
				$response_id,
				$newrec,
				$approver,
				isset($sort_fields[0])?$mod->getHashedSortFieldVal($sort_fields[0]):'',
				isset($sort_fields[1])?$mod->getHashedSortFieldVal($sort_fields[1]):'',
				isset($sort_fields[2])?$mod->getHashedSortFieldVal($sort_fields[2]):'',
				isset($sort_fields[3])?$mod->getHashedSortFieldVal($sort_fields[3]):'',
				isset($sort_fields[4])?$mod->getHashedSortFieldVal($sort_fields[4]):'',
				$feu_id,
				$xml);
		}
		//return array(true,''); Stikki replaced: instead of true, return actual data, didn't saw any side effects.
		return $output;
	}

	// Converts form to XML
	function &ResponseToXML()
	{
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xml .= "<response form_id=\"".$this->Id."\">\n";
		foreach($this->Fields as &$fld)
		{
			$xml .= $fld->ExportXML(true);
		}
		unset ($fld);
		$xml .= "</response>\n";
		return $xml;
	}

	// Inserts parsed XML data to database
	function StoreResponseXML($response_id=-1,$newrec=false,$approver='',$sortfield1,
	   $sortfield2,$sortfield3,$sortfield4,$sortfield5, $feu_id,$xml)
	{
		$db = $this->module_ptr->dbHandle;
		$secret_code = '';

		if($newrec)
		{
			// saving a new response
			$secret_code = substr(md5(session_id().'_'.time()),0,7);
			//$response_id = $db->GenID(cms_db_prefix(). 'module_fb_formbrowser_seq');
			$sql = 'INSERT INTO ' . cms_db_prefix().
				'module_fb_formbrowser (fbr_id, form_id, submitted, secret_code, index_key_1, index_key_2, index_key_3, index_key_4, index_key_5, feuid, response) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			$res = $db->Execute($sql,
				array($response_id,
					$this->GetId(),
					$this->clean_datetime($db->DBTimeStamp(time())),
					$secret_code,
					$sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,
					$feu_id,
					$xml
				));
		}
		else if($approver != '')
		{
			$sql = 'UPDATE ' . cms_db_prefix().
				'module_fb_formbrowser set user_approved=? where fbr_id=?';
			$res = $db->Execute($sql,
				array($this->clean_datetime($db->DBTimeStamp(time())),$response_id));
			audit(-1, $this->module_ptr->GetName(), $this->module_ptr->Lang('user_approved_submission',array($response_id,$approver)));
		}
		if(!$newrec)
		{
			$sql = 'UPDATE ' . cms_db_prefix().
				'module_fb_formbrowser set index_key_1=?, index_key_2=?, index_key_3=?, index_key_4=?, index_key_5=?, response=? where fbr_id=?';
			$res = $db->Execute($sql,
				array($sortfield1,$sortfield2,$sortfield3,$sortfield4,$sortfield5,$xml,$response_id));
		}
		return array($response_id,$secret_code);
	}

	// Some stupid date function
	function clean_datetime($dt)
	{
		return substr($dt,1,strlen($dt)-2);
	}

	// When downloading form.
	function ExportXML($exportValues = false)
	{
		$xmlstr = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		$xmlstr .= "<form id=\"".$this->Id."\"\n";
		$xmlstr .= "\talias=\"".$this->Alias."\">\n";
		$xmlstr .= "\t\t<form_name><![CDATA[".$this->Name."]]></form_name>\n";
		foreach($this->Attrs as $thisAttrKey=>$thisAttrValue)
		{
			$xmlstr .= "\t\t<attribute key=\"$thisAttrKey\"><![CDATA[$thisAttrValue]]></attribute>\n";
		}
		foreach($this->Fields as &$fld)
		{
			$xmlstr .= $fld->ExportXML($exportValues);
		}
		unset ($fld);
		$xmlstr .= "</form>\n";
		return $xmlstr;
	}

	function GetFormBrowsersForForm()
	{
		$fbr = $this->module_ptr->GetModuleInstance('FormBrowser');
		if($fbr != false)
		{
			$db = $this->module_ptr->dbHandle;
			$sql = 'SELECT browser_id from '. cms_db_prefix(). 'module_fbr_browser where form_id=?';
			$browsers = $db->GetAll(sql,array($this->GetId()));
		}
		else
			$browsers = array();
		return $browsers;
	}

	function AddToSearchIndex($response_id)
	{
		// find browsers keyed to this
		$browsers = $this->GetFormBrowsersForForm();
		if(count($browsers) < 1)
		{
			return;
		}

		$module = $this->module_ptr->GetModuleInstance('Search');
		if($module != FALSE)
		{
			$submitstring = '';
			foreach($this->Fields as &$fld)
			{
				if($fld->DisplayInSubmission())
				{
					$submitstring .= ' '.$fld->GetHumanReadableValue($as_string=true);
				}
			}
			unset ($fld);
			foreach($browsers as $thisBrowser)
			{
				$module->AddWords('FormBrowser', $response_id, 'sub_'.$thisBrowser, $submitstring, null);
			}
		}
	}

	function setFinishedFormSmarty($htmlemail=false)
	{
		$mod = $this->module_ptr;

		$unspec = $this->GetAttr('unspecified',$mod->Lang('unspecified'));
		$smarty = cmsms()->GetSmarty();

		$formInfo = array();

		foreach($this->Fields as &$fld)
		{
			$replVal = $unspec;
			$replVals = array();
			if($fld->DisplayInSubmission())
			{
				$replVal = $fld->GetHumanReadableValue();
				if($htmlemail)
				{
					// allow <BR> as delimiter or in content
					$replVal = preg_replace(
						array('/<br(\s)*(\/)*>/i','/[\n\r]+/'),array('|BR|','|BR|'),
						$replVal);
					$replVal = htmlspecialchars($replVal);
					$replVal = str_replace('|BR|','<br />',$replVal);
				}
				if($replVal == '')
				{
					$replVal = $unspec;
				}
			}

			$name = $fld->GetVariableName();
			$fldobj = $fld->ExportObject();
			$smarty->assign($name,$replVal);
			$smarty->assign($name.'_obj',$fldobj);
			$id = $fld->GetId();
			$smarty->assign('fld_'.$id,$replVal);
			$smarty->assign('fld_'.$id.'_obj',$fldobj);
			$alias = $fld->GetAlias();
			if($alias != '')
			{
				$smarty->assign($alias,$replVal);
				$smarty->assign($alias.'_obj',$fldobj);
			}
		}
		unset ($fld);

		// general form details
		$smarty->assign('sub_form_name',$this->GetName());
		$smarty->assign('sub_date',date('r'));
		$smarty->assign('sub_host',$_SERVER['SERVER_NAME']);
		$smarty->assign('sub_source_ip',$_SERVER['REMOTE_ADDR']);
		$smarty->assign('sub_url',(empty($_SERVER['HTTP_REFERER'])?$mod->Lang('no_referrer_info'):$_SERVER['HTTP_REFERER']));
		$smarty->assign('fb_version',$mod->GetVersion());
		$smarty->assign('TAB',"\t");
	}

	function manageFileUploads()
	{
		$config = cmsms()->GetConfig();
		$mod = $this->module_ptr;

		// build rename map
		$mapId = array();
		$eval_string = false;
		$i = 0;
		foreach($this->Fields as &$fld)
		{
			$mapId[$fld->GetId()] = $i;
			$i++;
		}

		foreach($this->Fields as &$fld)
		{
			if(strtolower(get_class($fld)) == 'fbfileuploadfield')
			{
			  // Handle file uploads
			  // if the uploads module is found, and the option is checked in
			  // the field, then the file is added to the uploads module
			  // and a link is added to the results
			  // if the option is not checked, then the file is merely uploaded to
			  // the "uploads" directory
				$_id = $mod->module_id.'fbrp__'.$fld->GetId();
				if(isset($_FILES[$_id]) && $_FILES[$_id]['size'] > 0)
				{
					$thisFile =& $_FILES[$_id];
					$thisExt = substr($thisFile['name'],strrpos($thisFile['name'],'.'));

					if($fld->GetOption('file_rename','') == '')
					{
						$destination_name = $thisFile['name'];
					}
					else
					{
						$flds = array();
						$destination_name = $fld->GetOption('file_rename');
						preg_match_all('/\$fld_(\d+)/', $destination_name, $flds);
						foreach($flds[1] as $tF)
						{
							if(isset($mapId[$tF]))
							{
								$ref = $mapId[$tF];
								$destination_name = str_replace('$fld_'.$tF,
									 $this->Fields[$ref]->GetHumanReadableValue(),$destination_name);
							}
						}
						$destination_name = str_replace('$ext',$thisExt,$destination_name);
					}

					if($fld->GetOption('sendto_uploads'))
					{
						// we have a file we can send to the uploads
						$uploads = $mod->GetModuleInstance('Uploads');
						if(!$uploads)
						{
							// no uploads module
							audit(-1, $mod->GetName(), $mod->Lang('submit_error'),$mail->GetErrorInfo());
							return array($res, $mod->Lang('nouploads_error'));
						}

						$parms = array();
						$parms['input_author'] = $mod->Lang('anonymous');
						$parms['input_summary'] = $mod->Lang('title_uploadmodule_summary');
						$parms['category_id'] = $fld->GetOption('uploads_category');
						$parms['field_name'] = $_id;
						$parms['input_destname'] = $destination_name;
						if($fld->GetOption('allow_overwrite','0') == '1')
						{
							$parms['input_replace'] = 1;
						}
						$res = $uploads->AttemptUpload(-1,$parms,-1);

						if($res[0] == false)
						{
							// failed upload kills the send.
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',$res[1]));
							return array($res[0], $mod->Lang('uploads_error',$res[1]));
						}

						$uploads_destpage = $fld->GetOption('uploads_destpage');
						$url = $uploads->CreateLink ($parms['category_id'], 'getfile', $uploads_destpage, '',
							array ('upload_id' => $res[1]), '', true);

						$url = str_replace('admin/moduleinterface.php?','index.php?',$url);

						$fld->ResetValue();
						$fld->SetValue($url);
					}
					else
					{
						// Handle the upload ourselves
						$src = $thisFile['tmp_name'];
						$dest_path = $fld->GetOption('file_destination',$config['uploads_path']);

						// validated message before, now do it for the file itself
						$valid = true;
						$ms = $fld->GetOption('max_size');
						$exts = $fld->GetOption('permitted_extensions','');
						if($ms != '' && $thisFile['size'] > ($ms * 1024))
						{
							$valid = false;
						}
						else if($exts != '')
						{
							$match = false;
							$legalExts = explode(',',$exts);
							foreach($legalExts as $thisExt)
							{
								if(preg_match('/\.'.trim($thisExt).'$/i',$thisFile['name']))
								{
									$match = true;
								}
								else if(preg_match('/'.trim($thisExt).'/i',$thisFile['type']))
								{
									$match = true;
								}
							}
							if(!$match)
							{
								$valid = false;
							}
						}
						if(!$valid)
						{
							unlink($src);
							audit(-1, $mod->GetName(), $mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
							return array(false, '');
						}
						$dest = $dest_path.DIRECTORY_SEPARATOR.$destination_name;
						if(file_exists($dest) && $fld->GetOption('allow_overwrite','0')=='0')
						{
							unlink($src);
							return array(false,$mod->Lang('file_already_exists', array($destination_name)));
						}
						if(!move_uploaded_file($src,$dest))
						{
							audit(-1, $mod->GetName(), $mod->Lang('submit_error',''));
							return array(false, $mod->Lang('uploads_error',''));
						}
						else
						{
							if(strpos($dest_path,$config['root_path']) !== FALSE)
							{
								$url = str_replace($gCms->config['root_path'],'',$dest_path).DIRECTORY_SEPARATOR.$destination_name;
							}
							else
							{
								$url = $mod->Lang('uploaded_outside_webroot',$destination_name);
							}
							//$fld->ResetValue();
							//$fld->SetValue(array($dest,$url));
						}
					}
				}
			}
		}
		unset ($fld);
		return array(true,'');
	}

}

?>
