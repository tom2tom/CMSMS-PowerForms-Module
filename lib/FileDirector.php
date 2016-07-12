<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com> 
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FileDirector extends FieldBase
{
	var $fileAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'FileDirector';
	}

	public function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_file');
	}

	public function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_file');
	}

	public function DoOptionAdd(&$params)
	{
		$this->fileAdd = TRUE;
	}

	public function DoOptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemoveOptionElement('destination_filename',$indx);
				$this->RemoveOptionElement('destination_displayname',$indx);
			}
		}
	}

	public function CreateSampleHeader()
	{
		$fields = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInSubmission())
				$fields[] = $one->GetName();
		}
		unset($one);
		return implode("\t",$fields);
	}

	public function CreateDefaultTemplate()
	{
		$fields = array();
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInSubmission())
				$fields[] = '{$'.$one->GetVariableName().'}';
		}
		unset($one);
		return implode("\t",$fields);
	}

	public function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = $this->GetOptionElement('destination_displayname',$this->Value); //TODO
		if ($as_string)
			return array($ret);
		else
			return $ret;
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath())
			return $mod->Lang('error_uploads_dir');
		$opt = $this->GetOptionRef('destination_filename');
		if ($opt)
			$fileCount = count($opt);
		else
			$fileCount = 0;
		return $mod->Lang('file_count',$fileCount);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath())
			return array('main'=>array('<span style="color:red">'.$mod->Lang('error').'</span>',
				'',$mod->Lang('error_uploads_dir')));

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,
			'opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
/*		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($id,'opt_newlinechar',
					$this->GetOption('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));
*/
		if ($this->fileAdd) {
			$this->AddOptionElement('destination_displayname','');
			$this->AddOptionElement('destination_filename','');
			$this->fileAdd = FALSE;
		}
		$names = $this->GetOptionRef('destination_filename');
		if ($names) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_selection_displayname'),
				$mod->Lang('title_destination_filename'),
				$mod->Lang('title_select')
				);
			foreach ($names as $i=>&$one) {
				$dests[] = array(
				$mod->CreateInputText($id,'opt_destination_displayname'.$i,$this->GetOptionElement('destination_displayname',$i),30,128),
				$mod->CreateInputText($id,'opt_destination_filename'.$i,$one,30,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//			$main[] = array($mod->Lang('title_director_details'),$dests);
		} else {
			$dests = FALSE;
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('file')));
		}

		//setup sample-template buttons and scripts
		$ctldata = array();
		$ctldata['opt_file_template']['is_oneline'] = TRUE;
		$ctldata['opt_file_header']['is_oneline'] = TRUE;
		$ctldata['opt_file_header']['is_header'] = TRUE;
		$ctldata['opt_file_footer']['is_oneline'] = TRUE;
		$ctldata['opt_file_footer']['is_footer'] = TRUE;
		list($buttons,$revertscripts) = Utils::TemplateActions($this->formdata,$id,$ctldata);

		$adv[] = array($mod->Lang('title_file_template'),
			$mod->CreateTextArea(FALSE,$id,
				htmlspecialchars($this->GetOption('file_template')),
				'opt_file_template','pwf_tallarea','','','',50,15).
				'<br /><br />'.$buttons[0]);
		$adv[] = array($mod->Lang('title_file_header'),
			$mod->CreateTextArea(FALSE,$id,
				htmlspecialchars($this->GetOption('file_header')),
				'opt_file_header','pwf_shortarea','','','',50,8).
				'<br /><br />'.$buttons[1]);
		$adv[] = array($mod->Lang('title_file_footer'),
			$mod->CreateTextArea(FALSE,$id,
				htmlspecialchars($this->GetOption('file_footer')),
				'opt_file_footer','pwf_shortarea','','','',50,8).
				'<br /><br />'.$buttons[2]);

		if ($dests)
			return array(
				'main'=>$main,
				'table'=>$dests,
				'adv'=>$adv,
				'funcs'=>$revertscripts,
				'extra'=>'varshelpadv');//show variables-help on advanced tab
		else
			return array(
				'main'=>$main,
				'adv'=>$adv,
				'funcs'=>$revertscripts,
				'extra'=>'varshelpadv');
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetOptionRef('destination_filename');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetOptionElement('destination_displayname',$i)) {
					$this->RemoveOptionElement('destination_filename',$i);
					$this->RemoveOptionElement('destination_displayname',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$names = $this->GetOptionRef('destination_displayname');
		if ($names) {
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one'))=>-1)
				+ array_flip($names);
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		$ud = Utils::GetUploadsPath();
		if (!$ud)
			return array(FALSE,$mod->Lang('error_uploads_dir'));
/*MUTEX
		try {
			$mx = Utils::GetMutex($mod);
		} catch (Exception $e) {
			return array(FALSE,$this->Lang('error_system'));
		}
*/
		$fn = preg_replace('/[^\w\d\.]|\.\./','_',
			   $this->GetOptionElement('destination_filename',$this->Value));
		$token = abs(crc32($fn.'mutex'));
/*MUTEX
		if (!$mx->lock($token))
			return array(FALSE,$mod->Lang('error_lock'));
*/
		$tplvars = array();
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		Utils::SetupFormVars($this->formdata,$tplvars);

		$footer = $this->GetOption('file_footer');
		if ($footer)
			$footer = Utils::ProcessTemplateFromData($mod,$footer,$tplvars);

		$template = $this->GetOption('file_template');
		if (!$template)
			$template = $this->CreateDefaultTemplate();

		$newline = Utils::ProcessTemplateFromData(mod,$template,$tplvars);
/*		$replchar = $this->GetOption('newlinechar');
		if ($replchar) {
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]+/',$replchar,$newline);
		}
*/
		$l = strlen(PHP_EOL);
		if (substr($newline,-$l) != PHP_EOL)
			$newline .= PHP_EOL;

		$first = !file_exists($fp);
		$fh = fopen($fp,'w');
		if ($first) {
			$header = $this->GetOption('file_header');
			if (!$header)
				$header = $this->CreateSampleHeader();
			$header = Utils::ProcessTemplateFromData($mod,$header,$tplvars);
			fwrite($fh,$header.PHP_EOL.$newline.$footer);
		} else {
			//seek to footer
			if ($footer) {
				$rows = explode(PHP_EOL,$footer);
				$target = $rows[0];
			} else
				$target = '';
			$rows = file($fp);
			foreach ($rows as &$line) {
				$l = strlen($line);
				if (strncmp($line,$target,$l) != 0)
					fwrite($fh,$line);
				else
					break;
			}
			unset($line);
			fwrite($fh,$newline.$footer);
		}
		fclose($fh);

/*MUTEX
		$mx->unlock($token);
*/
		return array(TRUE,'');
	}

}
