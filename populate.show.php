<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$in_browser = !empty($params['in_browser']); //TODO deprecated
$inline = (!$in_browser && PWForms\Utils::GetFormProperty($formdata,'inline',0));
$fmhidden = array(
'form_id'=>$form_id,
$formdata->current_prefix.'datakey'=>$cache_key,
$formdata->current_prefix.'formpage'=>$formdata->Page,
$formdata->current_prefix.'in_browser'=>$in_browser); //TODO deprecated
if (isset($params['resume'])) {
	$fmhidden[$formdata->current_prefix.'resume'] = $params['resume'];
	if (isset($params['passthru']))
		$fmhidden[$formdata->current_prefix.'passthru'] = $params['passthru'];
}
$form_start = $this->CreateFormStart($id,'show_form',$returnid,'POST',
	'multipart/form-data',$inline,'',$fmhidden);
$form_end = $this->CreateFormEnd();

$tplvars = $tplvars + array(
	'actionid' => $id,
	'css_class' => PWForms\Utils::GetFormProperty($formdata,'css_class'),
	'form_id' => $formdata->Id,
	'form_name' => $formdata->Name
);

if (!$firsttime && ($matches=preg_grep('/^pwfp_\d{3}_\d+_Se[DIWX]$/',$pkeys))) {
	//add or delete a sequence
	$key = reset($matches);
	preg_match('/_(\d+)_Se([DIWX])/',$key,$matches);
	switch ($matches[2]) {
	 case 'D': //delete before
	 	$del = TRUE;
	 	$after = FALSE;
		break;
	 case 'I': //insert before
	 	$del = FALSE;
	 	$after = FALSE;
		break;
	 case 'W': //delete after
	 	$del = TRUE;
	 	$after = TRUE;
		break;
	 case 'X': //insert after
	 	$del = FALSE;
	 	$after = TRUE;
		break;
	}
	$seqs = new PWForms\SeqOperations();
	$obfld = $formdata->Fields[$matches[1]];
	if ($del) {
		$seqs->DeleteSequenceFields($obfld,$after);
	} else {
		$seqs->CopySequenceFields($obfld,$after);
	}
} else {
	$seqs = FALSE;
}

// Hidden-controls accumulator (see also the form hidden-parameters, above)
$hidden = '';
$reqSymbol = PWForms\Utils::GetFormProperty($formdata,'required_field_symbol','*');
// Start building fields
$fields = array();
//$prev = array(); //make other-page field-values available to templates
$WalkPage = 1; //'current' page for field-walk purposes

$total = count($formdata->FieldOrders);
for ($o=0; $o<$total; $o++) { //NOT foreach, cuz the array can change during the loop
	$field_id = $formdata->FieldOrders[$o];
	$obfld = $formdata->Fields[$field_id];
	$type = $obfld->GetFieldType();

	if ($type == 'SequenceStart' && $firsttime) {
		$times = $obfld->GetProperty('repeatcount');
		if ($times > 1) {
			if (!$seqs) {
				$seqs = new PWForms\SeqOperations();
			}
			$seqs->CopySequenceFields($obfld,TRUE,$times-1); //adjusts various parameters
			$total = count($formdata->FieldOrders);
		}
	}

	$alias = $obfld->ForceAlias();

	if ($type == 'PageBreak')
		$WalkPage++;

	if ($WalkPage != $formdata->Page) {
		// not processing the 'current' form-page
		// remember other-page field-values which haven't yet been saved ?
		//TODO checkme double-underscore use ?
		//FormBuilder uses 'fbrp__' lots (apparently for all 'input' fields)
//		$valueindx = 'pwfp__'.$obfld->GetId();
//		if (isset($params[$valueindx]))
		if ($obfld->IsInputField()) { //TODO check logic
/*			$valueindx = $formdata->current_prefix.$obfld->GetId();
			if (empty($params[$valueindx])) {
				$valueindx2 = $formdata->prior_prefix.$obfld->GetId();
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
			if ($obfld->DisplayInSubmission()) {
/*TODO			if ($WalkPage < $formdata->Page) {
					$oneset = new stdClass();
					$oneset->value = $obfld->DisplayableValue();
					$tplvars[$obfld->GetName()] = $oneset;
					$tplvars[$obfld->ForceAlias()] = $oneset;
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
//	$oneset->css_class = $obfld->GetProperty('css_class');
	$oneset->display = $obfld->DisplayInForm();
	$oneset->valid = $obfld->IsValid();
	$oneset->error = $oneset->valid?'':$obfld->ValidationMessage;
	$oneset->has_label = $obfld->HasLabel();
	$oneset->helptext = $obfld->GetProperty('helptext');
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
	$oneset->helptext_id = 'pwfp_ht_'.$obfld->GetID();
	if (!$oneset->has_label || $obfld->GetHideLabel()
/*	 && (!$obfld->GetProperty('browser_edit',0) || empty($params['in_admin']))*/)
		$oneset->hide_name = 1;
	else
		$oneset->hide_name = 0;
	$oneset->id = $obfld->GetId();
	$oneset->input = $obfld->Populate($id,$params); //text or flat xhtml or array of objects
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
	$oneset->values = $obfld->GetIndexedValues(); //TODO multi-element field, not really values?

	$tplvars[$alias] = $oneset;
	$fields[$oneset->input_id] = $oneset;
} //FieldOrders[] loop

$formdata->PagesCount = $WalkPage;

$tplvars = $tplvars + array(
	'fields' => $fields,
	'this_page' => $formdata->Page,
	'total_pages' => $formdata->PagesCount,
	'title_page_x_of_y' => $this->Lang('title_page_x_of_y',array($formdata->Page,$formdata->PagesCount)),
);

//$tplvars['previous'] = $prev;
$baseurl = $this->GetModuleURLPath();

$t = PWForms\Utils::GetFormProperty($formdata,'help_icon');
if ($t) {
	$fp = ''.PWForms\Utils::GetUploadsPath($this).DIRECTORY_SEPARATOR.$t;
	if (is_file($fp)) {
		$url = PWForms\Utils::GetUploadURL($this,$t);
	} else {
		$url = $baseurl.'/images/info-small.gif';
	}
} else {
	$url = $baseurl.'/images/info-small.gif';
}
$tplvars['help_icon'] = '<img src="'.$url.'" alt="'.$this->Lang('help').
	'" title="'.$this->Lang('help_help').'" />';
//TODO id="*pwfp_prev" NOW id="*prev"
if ($formdata->Page > 1)
	$tplvars['prev'] = '<input type="submit" id="'.$id.'prev" class="cms_submit submit_prev" name="'.
	$id.$formdata->current_prefix.'previous" value="'.
	PWForms\Utils::GetFormProperty($formdata,'prev_button_text',$this->Lang('previous')).'"/>';
else
	$tplvars['prev'] = NULL;

if ($formdata->Page < $formdata->PagesCount) {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_next" name="'.
	$id.$formdata->current_prefix.'continue" value="'.
	PWForms\Utils::GetFormProperty($formdata,'next_button_text',$this->Lang('next')).'"/>';
} else {
	$tplvars['submit'] = '<input type="submit" id="'.$id.'submit" class="cms_submit submit_current" name="'.
	$id.$formdata->current_prefix.'done" value="'.
	PWForms\Utils::GetFormProperty($formdata,'submit_button_text',$this->Lang('submit')).'"/>';
}

if (isset($params['resume'])) {
	$tplvars['cancel'] = '<input type="submit" id="'.$id.'cancel" class="cms_submit" name="'.
	$id.$formdata->current_prefix.'cancel" value="'.$this->Lang('cancel').'"/>';
} else {
	$tplvars['cancel'] = NULL;
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
