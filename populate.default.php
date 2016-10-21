<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$tplvars = $tplvars + array(
	'total_pages' => $formdata->PagesCount,
	'this_page' => $formdata->Page,
	'title_page_x_of_y' => $this->Lang('title_page_x_of_y',array($formdata->Page,$formdata->PagesCount)),
	'css_class' => PWForms\Utils::GetFormProperty($formdata,'css_class'),
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

$inline = (!$in_browser && PWForms\Utils::GetFormProperty($formdata,'inline',0));
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

$reqSymbol = PWForms\Utils::GetFormProperty($formdata,'required_field_symbol','*');
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
								PWForms\Utils::html_myentities_decode($val));
				}
			} else {
				//hide the value
				$hidden .= $this->CreateInputHidden($id,
						   $valueindx,
						   PWForms\Utils::html_myentities_decode($params[$valueindx]));
			}
*/
			if ($one->DisplayInSubmission()) {
/*TODO			if ($WalkPage < $formdata->Page) {
					$oneset = new stdClass();
					$oneset->value = $one->GetDisplayableValue();
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

	$oneset = new stdClass();
	$oneset->alias = $alias;
//	$oneset->css_class = $one->GetProperty('css_class');
	$oneset->display = $one->DisplayInForm()?1:0;
	$oneset->valid = $one->IsValid()?1:0;
	$oneset->error = $oneset->valid?'':$one->ValidationMessage;
	$oneset->has_label = $one->HasLabel();
	$oneset->helptext = $one->GetProperty('helptext');
	if ($oneset->helptext) {
		if (!isset($formdata->jsfuncs['helptoggle'])) {
/*TODO func*/	$formdata->jsfuncs['helptoggle'] = <<<EOS
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
		}
	}
	$oneset->helptext_id = 'pwfp_ht_'.$one->GetID();
	if ((!$one->HasLabel() || $one->GetHideLabel())
/*	 && (!$one->GetProperty('browser_edit',0) || empty($params['in_admin']))*/)
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
	$oneset->required = $one->IsRequired()?1:0;
	$oneset->required_symbol = $oneset->required?$reqSymbol:'';
	$oneset->smarty_eval = $one->GetSmartyEval()?1:0;
	$oneset->type = $one->GetDisplayType();
	$oneset->values = $one->GetIndexedValues(); //TODO multi-element field, not really values?

	$tplvars[$alias] = $oneset;
	$fields[$oneset->input_id] = $oneset;
} //foreach

$formdata->PagesCount = $WalkPage;

$tplvars['fields'] = $fields;
//$tplvars['previous'] = $prev;
$baseurl = $this->GetModuleURLPath();

$tplvars['help_icon'] = '<img src="'.$baseurl.'/images/info-small.gif" alt="'.
	$this->Lang('help').'" title="'.$this->Lang('help_help').'" />';

//TODO id="*pwfp_prev" NOW id="*prev"
if ($formdata->Page > 1)
	$tplvars['prev'] = '<input type="submit" id="'.$id.'prev" class="cms_submit submit_prev" name="'.
	$id.$formdata->current_prefix.'prev" value="'.
	PWForms\Utils::GetFormProperty($formdata,'prev_button_text',$this->Lang('previous')).'"/>';
else
	$tplvars['prev'] = NULL;

if ($formdata->Page < $formdata->PagesCount) {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_next" name="'.
	$id.$formdata->current_prefix.'submit" value="'.
	PWForms\Utils::GetFormProperty($formdata,'next_button_text',$this->Lang('next')).'"/>';
} else {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_current" name="'.
	$id.$formdata->current_prefix.'done" value="'.
	PWForms\Utils::GetFormProperty($formdata,'submit_button_text',$this->Lang('submit')).'"/>';
}

$usersafejs = PWForms\Utils::GetFormProperty($formdata,'submit_javascript');
if ($usersafejs)
	$usersafejs = PHP_EOL.'   '.$usersafejs;
if (PWForms\Utils::GetFormProperty($formdata,'input_button_safety')) {
	$safejs = <<<EOS

   setTimeout(function() {
    $('input[class*=" submit_"]').each(function() {
     this.disabled = true;
    });
   },0);
EOS;
} else {
	$safejs = '';
}

if ($usersafejs || $safejs) {
	$formdata->jsfuncs[] =<<<EOS
var submitted = false;
EOS;
	$formdata->jsloads[] =<<<EOS
 $('input[class*=" submit_"]').click(function() {
  if (!submitted) {{$usersafejs}
   submitted = true;{$safejs}
   return true;
  }
  return false;
 });
EOS;
}
//don't bother pushing $js* to $tplvars - will echo directly
