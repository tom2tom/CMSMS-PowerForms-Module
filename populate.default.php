<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$tplvars = $tplvars + array(
	'total_pages' => $formdata->PagesCount,
	'this_page' => $formdata->Page,
	'title_page_x_of_y' => $this->Lang('title_page_x_of_y',array($formdata->Page,$formdata->PagesCount)),
	'css_class' => PowerForms\Utils::GetFormOption($formdata,'css_class'),
	'form_name' => $formdata->Name,
	'form_id' => $formdata->Id,
	'actionid' => $id
);

// Build hidden (see also the form parameters, below)
$hidden = '';
//TODO how/when should these be originally set ?
if (!empty($params['in_browser'])) {
	$in_browser = 1;
//	$tplvars['browser_id'] = (int)$params['browser_id'];
	$hidden .= $this->CreateInputHidden($id,'in_browser',1);
//	.$this->CreateInputHidden($id,'browser_id',$params['browser_id']);
} else
	$in_browser = 0;
$tplvars['in_browser'] = $in_browser;
$tplvars['in_admin'] = $in_browser; //deprecated template var

$inline = (!$in_browser && PowerForms\Utils::GetFormOption($formdata,'inline',0));
$form_start = $this->CreateFormStart($id,'default',$returnid,
	'POST','multipart/form-data',$inline,'',array(
	'form_id'=>$form_id,
	$formdata->current_prefix.'formdata'=>$cache_key,
	$formdata->current_prefix.'formpage'=>$formdata->Page));
$form_end = $this->CreateFormEnd();

//if ($formdata->Page > 1)
//	$hidden .= $this->CreateInputHidden($id,$formdata->current_prefix.'previous',($formdata->Page - 1)); //c.f. pwfp_NNN_prev for the button
//if ($formdata->Page < $formdata->PagesCount) //TODO c.f. $WalkPage in field-walker
//	$hidden .= $this->CreateInputHidden($id,$formdata->current_prefix.'continue',($formdata->Page + 1));

$reqSymbol = PowerForms\Utils::GetFormOption($formdata,'required_field_symbol','*');
// Start building fields
$fields = array();
//$prev = array(); //make other-page field-values available to templates
$WalkPage = 1; //'current' page for field-walk purposes

foreach ($formdata->FieldOrders as $one) {
	$one = $formdata->Fields[$one];
	$alias = $one->ForceAlias();

	if ($one->GetFieldType() == 'PageBreak')
		$WalkPage++;

	if ($WalkPage != $formdata->Page) {
		// not processing the 'current' form-page
		// remember other-page field-values which haven't yet been saved ?
		//TODO checkme double-underscore use ?
		//FormBuilder uses 'fbrp__' lots (apparently for all 'input' fields)
//		$valueindx = 'pwfp__'.$one->GetId();
//		if (isset($params[$valueindx]))
		if ($one->IsInputField()) { //TODO check logic
/*			$valueindx = $formdata->current_prefix.$one->GetId();
			if (empty($params[$valueindx])) {
				$valueindx2 = $formdata->prior_prefix.$one->GetId();
				if (empty($params[$valueindx2]))
					$params[$valueindx] = 0; //assume an unchecked checkbox
				else
					$params[$valueindx] = $params[$valueindx2]; //prior-period-form value
			}
			if (is_array($params[$valueindx])) {
				//hide all members of the value
				foreach ($params[$valueindx] as $val) {
					$hidden .= $this->CreateInputHidden($id,
								$valueindx.'[]',
								PowerForms\Utils::html_myentities_decode($val));
				}
			} else {
				//hide the value
				$hidden .= $this->CreateInputHidden($id,
						   $valueindx,
						   PowerForms\Utils::html_myentities_decode($params[$valueindx]));
			}
*/
			if ($one->DisplayInSubmission()) {
/*TODO			if ($WalkPage < $formdata->Page) {
					$oneset = new stdClass();
					$oneset->value = $one->GetHumanReadableValue();
					$tplvars[$one->GetName()] = $oneset;
					$tplvars[$one->ForceAlias()] = $oneset;
					$prev[] = $oneset;
				}
*/
				$oneset = new stdClass();
				if (is_array($params[$valueindx]))
					$oneset->values = $params[$valueindx]; //CHECKME readable-version?
				else
					$oneset->values = array($params[$valueindx]);
				$tplvars[$alias] = $oneset;
			}
		}
		continue; //only current-page fields get the full suite of data
	}

	$formdata->jscripts = array(); //for accumulating js, during Populate()

	$oneset = new stdClass();
	$oneset->alias = $alias;
//	$oneset->css_class = $one->GetOption('css_class');
	$oneset->display = $one->DisplayInForm()?1:0;
//	$oneset->error = $one->GetOption('is_valid',TRUE)?'':$one->ValidationMessage;
	$oneset->error = $one->validated?'':$one->ValidationMessage;
	$oneset->has_label = $one->HasLabel();
	$oneset->helptext = $one->GetOption('helptext');
	if ($oneset->helptext)
		$formdata->jscripts['helptoggle'] = 'construct';
	$oneset->helptext_id = 'pwfp_ht_'.$one->GetID();
	if ((!$one->HasLabel() || $one->GetHideLabel())
/*	 && (!$one->GetOption('browser_edit',0) || empty($params['in_admin']))*/)
		$oneset->hide_name = 1;
	else
		$oneset->hide_name = 0;
	$oneset->id = $one->GetId();
	$oneset->input = $one->Populate($id,$params); //flat xhtml or array of objects
	$oneset->input_id = $one->GetInputId();
	$oneset->label_parts = $one->LabelSubComponents()?1:0;
	$oneset->logic = $one->GetFieldLogic();
	$oneset->multiple_parts = $one->GetMultiPopulate()?1:0;
	$oneset->name = $one->GetName();
	$oneset->needs_div = $one->NeedsDiv();
	$oneset->required = $one->GetRequired()?1:0;
	$oneset->required_symbol = $one->GetRequired()?$reqSymbol:'';
	$oneset->smarty_eval = $one->GetSmartyEval()?1:0;
	$oneset->type = $one->GetDisplayType();
//	$oneset->valid = $one->GetOption('is_valid',TRUE)?1:0;
	$oneset->valid = $one->validated?1:0;
	$oneset->values = $one->GetAllHumanReadableValues();

	$tplvars[$alias] = $oneset;
	$fields[$oneset->input_id] = $oneset;
}

$formdata->PagesCount = $WalkPage;

$tplvars['fields'] = $fields;
//$tplvars['previous'] = $prev;
$baseurl = $this->GetModuleURLPath();

$tplvars['help_icon'] = '<img src="'.$baseurl.'/images/info-small.gif" alt="'.
	$this->Lang('help').'" title="'.$this->Lang('help_help').'" />';

//script accumulators
$jsincs = array();
$jsfuncs = array();
$jsloads = array();

foreach ($formdata->jscripts as $key=>$val) {
	if ($val != 'construct')
		$jsfuncs[] = $val;
	else {
		switch ($key) {
		 case 'helptoggle':
			$jsfuncs[] =<<<EOS
function help_toggle(htid) {
 var help_container=document.getElementById(htid);
 if (help_container) {
  if (help_container.style.display == 'none') {
   help_container.style.display = 'inline';
  } else {
   help_container.style.display = 'none';
  }
 }
}
EOS;
			break;
		case 'cloak':
			$jsincs[] =<<< EOS
<script type="text/javascript" src="{$baseurl}/include/jquery-inputCloak.min.js"></script>
EOS;
			break;
		case 'mailcheck':
			$jsincs[] =<<< EOS
<script type="text/javascript" src="{$baseurl}/include/mailcheck.min.js"></script>
<script type="text/javascript" src="{$baseurl}/include/levenshtein.min.js"></script>
EOS;
			if (!function_exists('ConvertDomains')) {
			 function ConvertDomains($pref)
			 {
				if (!$pref)
					return '""';
				$v3 = array();
				$v2 = explode(',',$pref);
				foreach ($v2 as $one) {
					$v3[] = '\''.trim($one).'\'';
				}
				return implode(',',$v3);
			 }
			}
			$pref = $this->GetPreference('email_topdomains');
			$topdomains = ConvertDomains($pref);
			if ($topdomains)
				$topdomains = '  topLevelDomains: ['.$topdomains.'],'.PHP_EOL;
			else
				$topdomains = '';
			$pref = $this->GetPreference('email_domains');
			$domains = ConvertDomains($pref);
			if ($domains)
				$domains = '  domains: ['.$domains.'],'.PHP_EOL;
			else
				$domains = '';
			$pref = $this->GetPreference('email_subdomains');
			$l2domains = ConvertDomains($pref);
			if ($l2domains)
				$l2domains = '  secondLevelDomains: ['.$l2domains.'],'.PHP_EOL;
			else
				$l2domains = '';
			$intro = $this->Lang('suggest');
			$empty = $this->Lang('missing_type',$this->Lang('destination'));

			$jsloads[] =<<<EOS
 $('.emailaddr').blur(function() {
  $(this).mailcheck({
{$domains}{$l2domains}{$topdomains}
   distanceFunction: function(string1,string2) {
    var lv = Levenshtein;
    return lv.get(string1,string2);
   },
   suggested: function(element,suggestion) {
    if (confirm('{$intro} <strong><em>' + suggestion.full + '</em></strong>?')) {
     element.innerHTML = suggestion.full;
    } else {
     element.focus();
    }
   },
   empty: function(element) {
    alert('{$empty}');
    element.focus();
   }
  });
 });

EOS;
			break;
		}
	}
}
unset($formdata->jscripts); //finished with this

$buttonjs = PowerForms\Utils::GetFormOption($formdata,'submit_javascript');

if (PowerForms\Utils::GetFormOption($formdata,'input_button_safety')) {
	$buttonjs .= ' onclick="return LockButton();"';
	$jsfuncs[] = <<<EOS
var submitted = false;
function LockButton () {
 if (!submitted) {
  submitted = true;
  var item = document.getElementById("{$id}submit");
  if (item != NULL) {
   setTimeout(function() {item.disabled = true},0);
  }
  return true;
 }
 return false;
}
EOS;
}

//TODO id="*pwfp_prev" NOW id="*prev"
if ($formdata->Page > 1)
	$tplvars['prev'] = '<input type="submit" id="'.$id.'prev" class="cms_submit submit_prev" name="'.
	$id.$formdata->current_prefix.'prev" value="'.
	PowerForms\Utils::GetFormOption($formdata,'prev_button_text',$this->Lang('previous')).'" '.
	$buttonjs.' />';
else
	$tplvars['prev'] = NULL;

if ($formdata->Page < $formdata->PagesCount) {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_next" name="'.
	$id.$formdata->current_prefix.'submit" value="'.
	PowerForms\Utils::GetFormOption($formdata,'next_button_text',$this->Lang('next')).'" '.
	$buttonjs.' />';
} else {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_current" name="'.
	$id.$formdata->current_prefix.'done" value="'.
	PowerForms\Utils::GetFormOption($formdata,'submit_button_text',$this->Lang('submit')).'" '.
	$buttonjs.' />';
}

if ($jsloads) {
	$jsfuncs[] = '$(document).ready(function() {
';
	$jsfuncs = array_merge($jsfuncs,$jsloads);
	$jsfuncs[] = '});
';
}
//don't bother pushing $js* to $tplvars - will echo directly
