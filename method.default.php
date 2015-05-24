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

// Build hidden
//$hidden = $this->CreateInputHidden($id,'form_id',$formdata->Id);
//if(isset($params['lang'])) //TODO
//	$hidden .= $this->CreateInputHidden($id,'lang',$params['lang']);
$hidden = '';

//TODO how/when should these be originally set ?
if(!empty($params['in_browser']))
{
	$hidden .= $this->CreateInputHidden($id,'in_browser',1).
//	$this->CreateInputHidden($id,'browser_id',$params['browser_id']);
//	$smarty->assign('in_browser',1);
//	$smarty->assign('browser_id',(int)$params['browser_id']);
	$in_browser = (int)$params['in_browser'];
}
else
	$in_browser = 0;
$smarty->assign('in_browser',$in_browser);

//if(isset($params['pwfp_browser_id']))
//	$hidden .= $this->CreateInputHidden($id,'pwfp_browser_id',$params['pwfp_browser_id']);
//if(isset($params['response_id'])) //TODO
//	$hidden .= $this->CreateInputHidden($id,'response_id',$params['response_id']);

if($formdata->Page > 1)
	$hidden .= $this->CreateInputHidden($id,'pwfp_previous',($formdata->Page - 1));

if($formdata->Page == $formdata->FormPagesCount)
	$hidden .= $this->CreateInputHidden($id,'pwfp_done',1);
else
	$hidden .= $this->CreateInputHidden($id,'pwfp_continue',($formdata->Page + 1));

//$inline = (isset($params['inline']) && preg_match('/t(rue)?|y(es)?|1/i',$params['inline']));
//if(!($inline || pwfUtils::GetFormOption($formdata,'inline',0)))
//	$id = 'cntnt01'; //TODO generalise
$inline = (!$in_browser && pwfUtils::GetFormOption($formdata,'inline',0));

$smarty->assign('form_start',$this->CreateFormStart($id,'default',$returnid,
	'POST','multipart/form-data',$inline,'',array(
	'form_id'=>$form_id,
	'pwfp_formpage'=>$formdata->Page,
	'pwfp_callcount'=>$callcount+1)));
$smarty->assign('form_end',$this->CreateFormEnd());

$reqSymbol = pwfUtils::GetFormOption($formdata,'required_field_symbol','*');
// Start building fields
$fields = array();
//$prev = array(); //make other-page field-values available to templates
$formPageCount = 1;

foreach($formdata->Fields as &$one)
{
	$alias = $one->ForceAlias();

	if($one->GetFieldType() == 'PageBreak')
		$formPageCount++;

	if($formPageCount != $formdata->Page)
	{
		// not processing the 'current' form-page
		// remember other-page field-values which haven't yet been saved
		$valueindx = 'pwfp__'.$one->GetId();
		if(isset($params[$valueindx]))
		{
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
/*			//TODO this may be rubbish ! how do we get past the last page ?
			if($formPageCount < $formdata->Page && $one->DisplayInSubmission())
			{
				$oneset = new stdClass();
				$oneset->value = $one->GetHumanReadableValue();
				$smarty->assign_by_ref($one->GetName(),$oneset);
				$smarty->assign_by_ref($one->ForceAlias(),$oneset); //CHECKME by ref ? persistence!
				$prev[] = $oneset;
			}
*/
			if($one->DisplayInSubmission())
			{
				$oneset = new stdClass();
				if(is_array($params[$valueindx]))
					$oneset->values = $params[$valueindx]; //CHECKME readable-version?
				else
					$oneset->values = array($params[$valueindx]);
				$smarty->assign_by_ref($alias,$oneset); //CHECKME by ref ? persistence!
			}
		}
		continue; //only current-page fields get the full monty
	}

	$oneset = new stdClass();
	$oneset->alias = $alias;
	$oneset->css_class = $one->GetOption('css_class');
	$oneset->display = $one->DisplayInForm()?1:0;
	$oneset->error = $one->GetOption('is_valid',TRUE)?'':$one->ValidationMessage;
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
	$oneset->valid = $one->validated?1:0;
	$oneset->values = $one->GetAllHumanReadableValues();
//	$oneset->valid = $one->GetOption('is_valid',TRUE)?1:0;

	$smarty->assign($alias,$oneset); //CHECKME by ref ?
	$fields[$oneset->input_id] = $oneset;
//	$fields[] = $oneset;
}
unset($one);

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

if($formdata->Page > 1)
	$smarty->assign('prev',
	'<input class="cms_submit submit_prev" name="'.$id.'pwfp_prev" id="'.$id.'pwfp_prev" value="'.
	pwfUtils::GetFormOption($formdata,'prev_button_text',$this->Lang('previous')).'" type="submit" '.$js.' />');
else
	$smarty->assign('prev','');

if($formdata->Page < $formPageCount)
{
	$smarty->assign('submit',
	'<input class="cms_submit submit_next" name="'.$id.'pwfp_submit" id="'.$id.'submit" value="'.
	pwfUtils::GetFormOption($formdata,'next_button_text',$this->Lang('next')).'" type="submit" '.$js.' />');
}
else
{
	$smarty->assign('submit',
	'<input class="cms_submit submit_current" name="'.$id.'pwfp_submit" id="'.$id.'submit" value="'.
	pwfUtils::GetFormOption($formdata,'submit_button_text',$this->Lang('submit')).'" type="submit" '.$js.' />');
}
