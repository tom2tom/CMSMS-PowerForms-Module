<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/
$in_browser = !empty($params['in_browser']); //TODO deprecated
$inline = (!$in_browser && PWForms\Utils::GetFormProperty($formdata, 'inline', 0));
$fmhidden = [
'form_id'=>$form_id,
$formdata->current_prefix.'datakey'=>$cache_key,
$formdata->current_prefix.'formpage'=>$formdata->Page,
$formdata->current_prefix.'in_browser'=>$in_browser]; //TODO deprecated
if (isset($params['resume'])) {
	$fmhidden[$formdata->current_prefix.'resume'] = $params['resume'];
	if (isset($params['passthru'])) {
		$fmhidden[$formdata->current_prefix.'passthru'] = $params['passthru'];
	}
}
$form_start = $this->CreateFormStart($id, 'show_form', $returnid, 'POST',
	'multipart/form-data', $inline, '', $fmhidden);
$form_end = $this->CreateFormEnd();

$tplvars += [
	'actionid' => $id,
	'css_class' => PWForms\Utils::GetFormProperty($formdata, 'css_class'),
	'form_id' => $formdata->Id,
	'form_name' => $formdata->Name
];

$togglehelp = FALSE;
// Hidden-controls accumulator (see also the form hidden-parameters, above)
$hidden = '';
$reqSymbol = PWForms\Utils::GetFormProperty($formdata, 'required_field_symbol', '*');
// Start building fields
$fields = [];
//$prev = array(); //make other-page field-values available to templates
$formPage = 1; //'current' page for field-walk purposes

foreach ($formdata->FieldOrders as $field_id) {
	$obfld = $formdata->Fields[$field_id];
	$type = $obfld->GetFieldType();
	$alias = $obfld->ForceAlias();

	if ($type == 'PageBreak') {
		++$formPage;
	}

	if ($formPage == $formdata->Page) {
		$oneset = new stdClass();
		$oneset->alias = $alias;
		$oneset->css_class = $obfld->GetProperty('css_class');
		$oneset->display = $obfld->DisplayInForm();
		$oneset->valid = $obfld->IsValid();
		$oneset->error = $oneset->valid?'':$obfld->ValidationMessage;
		$oneset->has_label = $obfld->HasLabel();
		$oneset->helptext = $obfld->GetProperty('helptext');
		if ($oneset->helptext && $obfld->GetProperty('helptoggle')) {
			$togglehelp = TRUE;
		}
		$oneset->helptext_id = 'ht_'.$field_id;
		if (!$oneset->has_label || $obfld->GetHideLabel()
// && (!$obfld->GetProperty('browser_edit',0) || empty($params['in_admin']))
		) {
			$oneset->hide_name = 1;
		} else {
			$oneset->hide_name = 0;
		}
		$oneset->id = $obfld->GetId();
		$oneset->input = $obfld->Populate($id, $params); //text or flat xhtml or array of objects
		$oneset->input_id = $obfld->GetInputId();
		$oneset->label_parts = $obfld->LabelSubComponents();
		$oneset->logic = $obfld->GetLogic();
		$oneset->multiple_parts = $obfld->GetMultiPopulate();
		$oneset->name = $obfld->GetName();
		$oneset->needs_div = $obfld->NeedsDiv();
		$oneset->required = $obfld->IsRequired();
		$oneset->required_symbol = $oneset->required?$reqSymbol:'';
		$oneset->smarty_eval = $obfld->GetSmartyEval();
		$oneset->type = $obfld->GetDisplayType();
		$oneset->values = $obfld->GetIndexedValues(); //array of allowed values for multi-element field

		$tplvars[$alias] = $oneset;
		$fields[$oneset->input_id] = $oneset;
	} else { // not processing the 'current' form-page
		if ($obfld->IsInput && $obfld->DisplayInForm) {
			//TODO populate relevant smarty variables for use in template formulae
		}
	}
} //foreach FieldOrders[]

if ($togglehelp) {
	$formdata->Jscript->jsfuncs[] = <<<'EOS'
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

$formdata->PagesCount = $formPage;
if ($formdata->Page > $formPage) {
	$formdata->Page = $formPage;
} //maybe page-redisplay goof

$tplvars += [
	'fields' => $fields,
	'this_page' => $formdata->Page,
	'total_pages' => $formdata->PagesCount,
	'title_page_x_of_y' => $this->Lang('title_page_x_of_y', [$formdata->Page, $formdata->PagesCount]),
];

$baseurl = $this->GetModuleURLPath();

$t = PWForms\Utils::GetFormProperty($formdata, 'help_icon');
if ($t) {
	$fp = ''.PWForms\Utils::GetUploadsPath($this).DIRECTORY_SEPARATOR.$t;
	if (is_file($fp)) {
		$url = PWForms\Utils::GetUploadURL($this, $t);
	} else {
		$url = $baseurl.'/images/info-small.png';
	}
} else {
	$url = $baseurl.'/images/info-small.png';
}
$tplvars['help_icon'] = '<img src="'.$url.'" alt="'.$this->Lang('help').
	'" title="'.$this->Lang('help_help').'" />';

if ($formdata->Page > 1) {
	$tplvars['prev'] = '<input type="submit" id="submit_prev" class="cms_submit submit_prev" name="'.
	$id.$formdata->current_prefix.'previous" value="'.
	PWForms\Utils::GetFormProperty($formdata, 'prev_button_text', $this->Lang('previous')).'"/>';
} else {
	$tplvars['prev'] = NULL;
}

if ($formdata->Page < $formdata->PagesCount) {
	$tplvars['submit'] = '<input type="submit" id="submit_next" class="cms_submit submit_next" name="'.
	$id.$formdata->current_prefix.'continue" value="'.
	PWForms\Utils::GetFormProperty($formdata, 'next_button_text', $this->Lang('next')).'"/>';
} else {
	$tplvars['submit'] = '<input type="submit" id="submit_current" class="cms_submit submit_current" name="'.
	$id.$formdata->current_prefix.'done" value="'.
	PWForms\Utils::GetFormProperty($formdata, 'submit_button_text', $this->Lang('submit')).'"/>';
}

if (isset($params['resume'])) {
	$tplvars['cancel'] = '<input type="submit" id="submit_cancel" class="cms_submit" name="'.
	$id.$formdata->current_prefix.'cancel" value="'.$this->Lang('cancel').'"/>';
} else {
	$tplvars['cancel'] = NULL;
}

$usersafejs = PWForms\Utils::GetFormProperty($formdata, 'submit_javascript');
if ($usersafejs) {
	$usersafejs = PHP_EOL.'   '.$usersafejs;
}
if (PWForms\Utils::GetFormProperty($formdata, 'input_button_safety')) {
	$safejs = <<<EOS

   setTimeout(function() {
    $('input[id^="submit_"]').each(function() {
     this.disabled = true;
    });
   },10);
EOS;
} else {
	$safejs = '';
}

if ($usersafejs || $safejs) {
	$formdata->Jscript->jsfuncs[] =<<<EOS
var submitted = false;
EOS;
	$formdata->Jscript->jsloads[] =<<<EOS
 $('input[class*=" submit_"]').click(function() {
  if (!submitted) {{$usersafejs}
   submitted = true;{$safejs}
   return true;
  }
  return false;
 });
EOS;
}
