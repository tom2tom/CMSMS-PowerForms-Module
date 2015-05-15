<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module files (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

$smarty->assign('title_page_x_of_y',$this->Lang('title_page_x_of_y',array($formdata->Page,$formdata->FormPagesCount)));
$smarty->assign('css_class',pwfUtils::GetAttr($formdata,'css_class',''));
$smarty->assign('total_pages',$formdata->FormPagesCount);
$smarty->assign('this_page',$formdata->Page);
$smarty->assign('form_name',$formdata->Name);
$smarty->assign('form_id',$formdata->Id);
$smarty->assign('actionid',$id);

// Build hidden
$hidden = $this->CreateInputHidden($id, 'form_id', $formdata->Id);
if(isset($params['lang']))
	$hidden .= $this->CreateInputHidden($id, 'lang', $params['lang']);

$hidden .= $this->CreateInputHidden($id, 'pwfp_continue', ($formdata->Page + 1));
if(isset($params['pwfp_browser_id']))
	$hidden .= $this->CreateInputHidden($id,'pwfp_browser_id',$params['pwfp_browser_id']);

if(isset($params['response_id']))
	$hidden .= $this->CreateInputHidden($id,'response_id',$params['response_id']);

if($formdata->Page > 1)
	$hidden .= $this->CreateInputHidden($id, 'pwfp_previous', ($formdata->Page - 1));

if($formdata->Page == $formdata->FormPagesCount)
	$hidden .= $this->CreateInputHidden($id, 'pwfp_done', 1);

$reqSymbol = pwfUtils::GetAttr($formdata,'required_field_symbol','*');
// Start building fields
$fields = array();
$prev = array();
$formPageCount = 1;

foreach($formdata->Fields as &$one)
{
	if($one->GetFieldType() == 'PageBreakField')
		$formPageCount++;

	if($formPageCount != $formdata->Page)
	{
		$testIndex = 'pwfp__'.$one->GetId();

		// Ryan's ugly fix for Bug 4307
		// We should figure out why this field wasn't populating its Smarty variable
TODO	if($one->GetFieldType() == 'FileUploadField')
		{
			$smarty->assign('fld_'.$one->GetId(),$one->GetHumanReadableValue());
			$hidden .= $this->CreateInputHidden($id,
				$testIndex,
				pwfUtils::unmy_htmlentities($one->GetHumanReadableValue()));
			$thisAtt = $one->GetHumanReadableValue(FALSE);
			$smarty->assign('test_'.$one->GetId(), $thisAtt);
			$smarty->assign('value_fld'.$one->GetId(), $thisAtt[0]);
		}

		if(!isset($params[$testIndex]))
		{
			// do we need to write something?
		}
		elseif(is_array($params[$testIndex]))
		{
			foreach($params[$testIndex] as $val)
			{
				$hidden .= $this->CreateInputHidden($id,
							$testIndex.'[]',
							pwfUtils::unmy_htmlentities($val));
			}
		}
		else
		{
			$hidden .= $this->CreateInputHidden($id,
					   $testIndex,
					   pwfUtils::unmy_htmlentities($params[$testIndex]));
		}

		if($formPageCount < $formdata->Page && $one->DisplayInSubmission())
		{
			$oneset = new stdClass();
			$oneset->value = $one->GetHumanReadableValue();

			$smarty->assign($one->GetName(),$oneset);

			if($one->GetAlias() != '')
			{
				$smarty->assign($one->GetAlias(),$oneset);
			}

			$prev[] = $oneset;
		}
		continue;
	}
	$oneset = new stdClass();
	
	if($one->GetAlias() != '')
	{
		$smarty->assign($one->GetAlias(),$oneset);
		$oneset->alias = $one->GetAlias();
	}
	else
	{
		$oneset->alias = $name_alias;
	}

	$oneset->css_class = $one->GetOption('css_class');
	$oneset->display = $one->DisplayInForm()?1:0;
	$oneset->error = $one->GetOption('is_valid',TRUE)?'':$one->validationErrorText;
	$oneset->field_helptext_id = 'pwfp_ht_'.$one->GetID();
	$oneset->has_label = $one->HasLabel();
	$oneset->helptext = $one->GetOption('helptext');
	if(((!$one->HasLabel()) || $one->HideLabel()) && ($one->GetOption('fbr_edit','0') == '0' || $params['in_admin'] != 1))
		$oneset->hide_name = 1;
	else
		$oneset->hide_name = 0;
	$oneset->id = $one->GetId();
	$oneset->input = $one->GetFieldInput($id, $params, $returnid);
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

	$name_alias = $one->GetName();
	$name_alias = str_replace($toreplace, $replacement, $name_alias);
	$name_alias = strtolower($name_alias);
	$name_alias = preg_replace('/[^a-z0-9]+/i','_',$name_alias);
	$smarty->assign($name_alias,$oneset);

	$fields[$oneset->input_id] = $oneset;
//	$fields[] = $oneset;
}
unset($one);

$smarty->assign('hidden',$hidden);
$smarty->assign_by_ref('fields',$fields);
$smarty->assign_by_ref('previous',$prev);

$jsStr = '';
$jsTrigger = '';
if(pwfUtils::GetAttr($formdata,'input_button_safety','0') == '1')
{
	$jsStr = <<<EOS
<script type="text/javascript">
//<![CDATA[
var submitted = 0;
function LockButton () {
 var ret = false;
 if(!submitted) {
  var item = document.getElementById("{$id}submit");
  if(item != NULL) {
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

	$jsTrigger = ' onclick="return LockButton()"';
}

$js = pwfUtils::GetAttr($formdata,'submit_javascript');

if($formdata->Page > 1)
	$smarty->assign('prev',
	'<input class="cms_submit submit_prev" name="'.$id.'pwfp_prev" id="'.$id.'pwfp_prev" value="'.
	pwfUtils::GetAttr($formdata,'prev_button_text').'" type="submit" '.$js.' />');
else
	$smarty->assign('prev','');

$smarty->assign('has_captcha',0);
if($formdata->Page < $formPageCount)
{
	$smarty->assign('submit',
	'<input class="cms_submit submit_next" name="'.$id.'submit" id="'.$id.'submit" value="'.
	pwfUtils::GetAttr($formdata,'next_button_text').'" type="submit" '.$js.' />');
}
else
{
	$captcha = $this->getModuleInstance('Captcha');
	if(pwfUtils::GetAttr($formdata,'use_captcha','0') == '1' && $captcha != NULL)
	{
		$smarty->assign('graphic_captcha',$captcha->getCaptcha());
		$smarty->assign('title_captcha',pwfUtils::GetAttr($formdata,'title_user_captcha',$this->Lang('title_user_captcha')));
		$smarty->assign('input_captcha',$this->CreateInputText($id, 'pwfp_captcha_phrase',''));
		$smarty->assign('has_captcha',1);
	}

	$smarty->assign('submit',
	'<input class="cms_submit submit_current" name="'.$id.'submit" id="'.$id.'submit" value="'.
	pwfUtils::GetAttr($formdata,'submit_button_text').'" type="submit" '.$js.' />');
}
