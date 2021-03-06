<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright(C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/
if (!empty($message)) {
	$tplvars['message'] = $message;
}

$tplvars['backtomod_nav'] = $this->CreateLink($id, 'defaultadmin', '', '&#171; '.$this->Lang('back_top'));
$tplvars['backtoform_nav'] = $this->CreateLink($id, 'open_form', $returnid, '&#171; '.$this->Lang('back_form'),
	['form_id'=>$params['form_id'], 'datakey'=>$params['datakey']]);

if ($obfld) { //field data are loaded
	$fid = $obfld->GetId(); //maybe <= 0, if adding
	$nm = 'submit'; //submit-control name

	$ob = new stdClass();
	$ob->jsincs = [];
	$ob->jsfuncs = [];
	$ob->jsloads = [];
	$obfld->Jscript = &$ob;
	$baseurl = $this->GetModuleURLPath();

	$populators = $obfld->AdminPopulate($id);
	$hasmain = (isset($populators['main']) && count($populators['main']) > 0);
	$hasadv = (isset($populators['adv']) && count($populators['adv']) > 0);

	$tab = $this->_GetActiveTab($params);
	$t = $this->StartTabHeaders();
	if ($hasmain) {
		$t .= $this->SetTabHeader('maintab', $this->Lang('tab_field'), ($tab == 'maintab'));
	}
	if ($hasadv) {
		$t .= $this->SetTabHeader('advancedtab', $this->Lang('tab_advanced'), ($tab == 'advancedtab'));
	}
	$t .= $this->EndTabHeaders().$this->StartTabContent();
	$tplvars['tabs_start'] = $t;
	//workaround CMSMS2 crap 'auto-end', EndTab() & EndTabContent() before [1st] StartTab()
	$tplvars['tab_end'] = $this->EndTab();
	$tplvars['tabs_end'] = $this->EndTabContent();

	if ($hasmain) {
		$tplvars['maintab_start'] = $this->StartTab('maintab');
	}
	if ($hasadv) {
		$tplvars['advancedtab_start'] = $this->StartTab('advancedtab');
	}

	$tplvars['requirable'] = (/*!$obfld->IsDisposition() && */!$obfld->GetChangeRequirement())?1:0;

	$mainList = [];
	if ($hasmain) {
		foreach ($populators['main'] as $item) {
			$oneset = new stdClass();
			$oneset->title = (isset($item[0]))?$item[0]:'';
			if (!empty($item[1])) {
				$oneset->input = $item[1];
			} //optional
			if (!empty($item[2])) {
				$oneset->help = $item[2];
			}
			$mainList[] = $oneset;
		}
	}
	$tplvars['mainList'] = $mainList;

	$advList = [];
	if ($hasadv) {
		foreach ($populators['adv'] as $item) {
			$oneset = new stdClass();
			$oneset->title = (isset($item[0]))?$item[0]:'';
			if (!empty($item[1])) {
				$oneset->input = $item[1];
			} //optional
			if (!empty($item[2])) {
				$oneset->help = $item[2];
			}
			$advList[] = $oneset;
		}
	}
	$tplvars['advList'] = $advList;

	if (isset($populators['table'])) {
		$tplvars['multiControls'] = $populators['table'];
		if (count($populators['table']) > 2) { //titles + >1 options-row
			$tplvars['dndhelp'] = $this->Lang('help_can_drag');
			$ob->jsincs[] = <<<EOS
<script type="text/javascript" src="{$baseurl}/lib/js/jquery.tablednd.min.js"></script>
EOS;
			$ob->jsloads[] = <<<'EOS'
 $('#helpdnd').show();
 $('#controls').addClass('table_drag').tableDnD({
  dragClass: 'row1hover',
  onDrop: function(table,droprows) {
   var $tbl = $(table),
	odd = true,
	oddclass = 'row1',
	evenclass = 'row2';
   $tbl.find('tbody tr').each(function() {
	var name = odd ? oddclass : evenclass;
	if (this === droprows[0]) {
	 name = name+'hover';
	}
	$(this).removeClass().addClass(name);
	odd = !odd;
   });
  }
 }).find('tbody tr').removeAttr('onmouseover').removeAttr('onmouseout').mouseover(function() {
  var now = $(this).attr('class');
  $(this).attr('class', now+'hover');
 }).mouseout(function() {
  var now = $(this).attr('class');
  var to = now.indexOf('hover');
  $(this).attr('class', now.substring(0,to));
 });
EOS;
		}
	}

	if (isset($populators['extra'])) {
		switch ($populators['extra']) {
		 case 'varshelpmain':
			if ($hasmain) {
				$tplvars['mainvarhelp'] = 1;
			}
			break;
		 case 'varshelpadv':
			if ($hasadv) {
				$tplvars['advvarhelp'] = 1;
				PWForms\Utils::SetupSubTemplateVarsHelp($formdata, $this, $tplvars);
			}
			break;
		 case 'varshelpboth':
			if ($hasmain) {
				$tplvars['mainvarhelp'] = 1;
			}
			if ($hasadv) {
				$tplvars['advvarhelp'] = 1;
				PWForms\Utils::SetupSubTemplateVarsHelp($formdata, $this, $tplvars);
			}
			break;
		}
	}

	if ($obfld->HasComponentAdd()) {
		$tplvars['add'] = $this->CreateInputSubmit($id, 'compadd', $obfld->ComponentAddLabel());
	} else {
		$tplvars['add'] = NULL;
	}

	if ($obfld->HasComponentDelete()) {
		$tplvars['del'] = $this->CreateInputSubmit($id, 'compdel', $obfld->ComponentDeleteLabel(),
			'onclick="return confirm_selected(this)"');
		$prompt = $this->Lang('confirm');
		$ob->jsfuncs['optiondel'] = <<<EOS
function confirm_selected(btn) {
 var sel = $(btn).closest('div').find('input[name^="{$id}selected"]:checked');
 if (sel.length > 0) {
   return confirm('{$prompt}');
 } else {
  return false;
 }
}
EOS;
	} else {
		$tplvars['del'] = NULL;
	}
} else { //no field
	$fid = 0;
	$nm = 'add';
	//setup to select a type, then come back to edit it
	$ob = new stdClass();
	$ob->title = $this->Lang('title_add_new_field');
	PWForms\Utils::Collect_Fields($this);
	$ob->input = $this->CreateInputDropdown($id, 'field_type',
		array_merge([$this->Lang('select_type')=>''], $this->field_types), -1, '');
//	$ob->help = ;
	$tplvars['mainitem'] = $ob;

	$ob = new stdClass();
	$ob->jsincs = NULL;
	$ob->jsfuncs = NULL;
	$ob->jsloads = NULL;
	$obfld->Jscript = &$ob;
}

$tplvars['form_start'] = $this->CreateFormStart($id, 'open_field', $returnid,
	'POST', '', FALSE, '', [
	'form_id'=>$params['form_id'],
	'datakey'=>$params['datakey'],
	'field_id'=>$fid,
	'selectfields'=>$params['selectfields'],
	'selectdispos'=>$params['selectdispos']
	]);
$tplvars['form_end'] = $this->CreateFormEnd();

$t = ($fid != 0) ? 'close':'add'; //field use or add
$tplvars['submit'] = $this->CreateInputSubmit($id, $nm, $this->Lang($t), 'onclick="alert(\''.$this->Lang('save_form').'\')"');
$tplvars['cancel'] = $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel'));
