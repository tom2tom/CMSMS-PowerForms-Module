<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$smarty->assign('total_pages',$formdata->FormPagesCount);
$smarty->assign('this_page',$formdata->Page);
$smarty->assign('title_page_x_of_y',$this->Lang('title_page_x_of_y',array($formdata->Page,$formdata->FormPagesCount)));
$smarty->assign('css_class',pwfUtils::GetFormOption($formdata,'css_class'));
$smarty->assign('form_name',$formdata->Name);
$smarty->assign('form_id',$formdata->Id);
$smarty->assign('actionid',$id);

// Build hidden (see also the form parameters, below)
$hidden = '';
//TODO how/when should these be originally set ?
if(!empty($params['in_browser']))
{
	$in_browser = 1;
//	$smarty->assign('browser_id',(int)$params['browser_id']);
	$hidden .= $this->CreateInputHidden($id,'in_browser',1);
//	.$this->CreateInputHidden($id,'browser_id',$params['browser_id']);
}
else
	$in_browser = 0;
$smarty->assign('in_browser',$in_browser);
$smarty->assign('in_admin',$in_browser); //deprecated template var

$inline = (!$in_browser && pwfUtils::GetFormOption($formdata,'inline',0));
$smarty->assign('form_start',$this->CreateFormStart($id,'default',$returnid,
	'POST','multipart/form-data',$inline,'',array(
	'form_id'=>$form_id,
	$formdata->current_prefix.'formpage'=>$formdata->Page,
	$formdata->current_prefix.'formdata'=>$cache_key)));
$smarty->assign('form_end',$this->CreateFormEnd());


//if($formdata->Page > 1)
//	$hidden .= $this->CreateInputHidden($id,$formdata->current_prefix.'previous',($formdata->Page - 1)); //c.f. pwfp_NNN_prev for the button
//if($formdata->Page < $formdata->FormPagesCount) //TODO c.f. $WalkPage in field-walker
//	$hidden .= $this->CreateInputHidden($id,$formdata->current_prefix.'continue',($formdata->Page + 1));

$reqSymbol = pwfUtils::GetFormOption($formdata,'required_field_symbol','*');
// Start building fields
$fields = array();
//$prev = array(); //make other-page field-values available to templates
$WalkPage = 1; //'current' page for field-walk purposes

foreach($formdata->Fields as &$one)
{
	$alias = $one->ForceAlias();

	if($one->GetFieldType() == 'PageBreak')
		$WalkPage++;

	if($WalkPage != $formdata->Page)
	{
		// not processing the 'current' form-page
		// remember other-page field-values which haven't yet been saved ?
		//TODO checkme double-underscore use ?
		//FormBuilder uses 'fbrp__' lots (apparently for all 'input' fields)
//		$valueindx = 'pwfp__'.$one->GetId();
//		if(isset($params[$valueindx]))
		if($one->IsInputField()) //TODO check logic
		{
/*			$valueindx = $formdata->current_prefix.$one->GetId();
			if(empty($params[$valueindx]))
			{
				$valueindx2 = $formdata->prior_prefix.$one->GetId();
				if(empty($params[$valueindx2]))
					$params[$valueindx] = 0; //assume an unchecked checkbox
				else
					$params[$valueindx] = $params[$valueindx2]; //prior-period-form value
			}
			if(is_array($params[$valueindx]))
			{
				//hide all members of the value
				foreach($params[$valueindx] as $val)
				{
					$hidden .= $this->CreateInputHidden($id,
								$valueindx.'[]',
								pwfUtils::unmy_htmlentities($val));
				}
			}
			else
			{
				//hide the value
				$hidden .= $this->CreateInputHidden($id,
						   $valueindx,
						   pwfUtils::unmy_htmlentities($params[$valueindx]));
			}
*/
			if($one->DisplayInSubmission())
			{
/*TODO			if($WalkPage < $formdata->Page)
				{
					$oneset = new stdClass();
					$oneset->value = $one->GetHumanReadableValue();
					$smarty->assign_by_ref($one->GetName(),$oneset);
					$smarty->assign_by_ref($one->ForceAlias(),$oneset); //CHECKME by ref ? persistence!
					$prev[] = $oneset;
				}
*/
				$oneset = new stdClass();
				if(is_array($params[$valueindx]))
					$oneset->values = $params[$valueindx]; //CHECKME readable-version?
				else
					$oneset->values = array($params[$valueindx]);
				$smarty->assign_by_ref($alias,$oneset); //CHECKME by ref ? persistence!
			}
		}
		continue; //only current-page fields get the full suite of data
	}

	$oneset = new stdClass();
	$oneset->alias = $alias;
	$oneset->css_class = $one->GetOption('css_class');
	$oneset->display = $one->DisplayInForm()?1:0;
//	$oneset->error = $one->GetOption('is_valid',TRUE)?'':$one->ValidationMessage;
	$oneset->error = $one->validated?'':$one->ValidationMessage;
	$oneset->field_helptext_id = 'pwfp_ht_'.$one->GetID();
	$oneset->has_label = $one->HasLabel();
	$oneset->helptext = $one->GetOption('helptext');
	if ((!$one->HasLabel() || $one->GetHideLabel())
/*	 && (!$one->GetOption('browser_edit',0) || empty($params['in_admin']))*/)
		$oneset->hide_name = 1;
	else
		$oneset->hide_name = 0;
	$oneset->id = $one->GetId();
	$oneset->input = $one->GetFieldInput($id,$params);
	$oneset->input_id = $one->GetCSSId();
	$oneset->label_parts = $one->LabelSubComponents()?1:0;
	$oneset->logic = $one->GetFieldLogic();
	$oneset->multiple_parts = $one->HasMultipleFormComponents()?1:0;
	$oneset->name = $one->GetName();
	$oneset->needs_div = $one->NeedsDiv();
	$oneset->required = $one->IsRequired()?1:0;
	$oneset->required_symbol = $one->IsRequired()?$reqSymbol:'';
	$oneset->smarty_eval = $one->GetSmartyEval()?1:0;
	$oneset->type = $one->GetDisplayType();
//	$oneset->valid = $one->GetOption('is_valid',TRUE)?1:0;
	$oneset->valid = $one->validated?1:0;
	$oneset->values = $one->GetAllHumanReadableValues();

	$smarty->assign_by_ref($alias,$oneset); //CHECKME by ref ?
	$fields[$oneset->input_id] = $oneset;
}
unset($one);

$formdata->FormPagesCount = $WalkPage;

$smarty->assign('hidden',$hidden);
$smarty->assign('fields',$fields);
//$smarty->assign('previous',$prev);
$smarty->assign('help_icon',
'<img src="'.$this->GetModuleURLPath().'/images/info-small.gif" alt="'.
	$this->Lang('help').'" title="'.$this->Lang('help_help').'" />');

$jsfuncs = <<<EOS
<script type="text/javascript">
//<![CDATA[{literal}
function help_toggle(htid) {
 var help_container=document.getElementById(htid);
 if(help_container) {
  if(help_container.style.display == 'none') {
	help_container.style.display = 'inline';
  } else {
	help_container.style.display = 'none';
  }
 }
}

EOS;

$js = pwfUtils::GetFormOption($formdata,'submit_javascript');
if(!$js) //TODO make both js options work
{
	if(pwfUtils::GetFormOption($formdata,'input_button_safety',0))
	{
		$js = ' onclick="return LockButton();"';
		$jsfuncs .= <<<EOS
var submitted = 0;
function LockButton () {
 var ret = false;
 if(!submitted) {
  var item = document.getElementById("{$id}submit");
  if(item != NULL) {
   setTimeout(function() {item.disabled = true},0);
  }
  submitted = 1;
  ret = true;
 }
 return ret;
}
EOS;
	}
}

$jsfuncs .= <<<EOS
//]]>{/literal}
</script>
EOS;
$smarty->assign('jscript',$jsfuncs);

//TODO id="*pwfp_prev" NOW id="*prev"
if($formdata->Page > 1)
	$smarty->assign('prev',
	'<input type="submit" id="'.$id.'prev" class="cms_submit submit_prev" name="'.
	$id.$formdata->current_prefix.'prev" value="'.
	pwfUtils::GetFormOption($formdata,'prev_button_text',$this->Lang('previous')).'" '.
	$js.' />');
else
	$smarty->assign('prev','');

if($formdata->Page < $formdata->FormPagesCount)
{
	$smarty->assign('submit',
	'<input type="submit" id="'.$id.'submit" class="cms_submit submit_next" name="'.
	$id.$formdata->current_prefix.'submit" value="'.
	pwfUtils::GetFormOption($formdata,'next_button_text',$this->Lang('next')).'" '.
	$js.' />');
}
else
{
	$smarty->assign('submit',
	'<input type="submit" id="'.$id.'submit" class="cms_submit submit_current" name="'.
	$id.$formdata->current_prefix.'done" value="'.
	pwfUtils::GetFormOption($formdata,'submit_button_text',$this->Lang('submit')).'" '.
	$js.' />');
}

$formdata->formsmodule = NULL; //no need to cache this
$cache->driver_set($cache_key,serialize($formdata));
