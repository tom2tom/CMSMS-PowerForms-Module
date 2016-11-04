<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class MultiselectFileDirector extends FieldBase
{
	private $fileAdd = FALSE;

	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->MultiPopulate = TRUE;
		$this->Type = 'MultiselectFileDirector';
	}

	public function GetOptionAddLabel()
	{
		return $this->formdata->formsmodule->Lang('add_file');
	}

	public function GetOptionDeleteLabel()
	{
		return $this->formdata->formsmodule->Lang('delete_file');
	}

	public function OptionAdd(&$params)
	{
		$this->fileAdd = TRUE;
	}

	public function OptionDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx) {
				$this->RemovePropIndexed('destination_filename',$indx);
				$this->RemovePropIndexed('destination_displayname',$indx);
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

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = array();
		if (is_array($this->Value)) {
			foreach ($this->Value as $one) {
				if ($one)
					$ret[] = $one;
			}
		}

		if ($as_string)
			$ret = implode($this->GetFormProperty('list_delimiter',','),$ret);

		return $ret;
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath($mod))
			return $mod->Lang('err_uploads_dir');
		$opt = $this->GetPropArray('destination_filename');
		if ($opt)
			$fileCount = count($opt);
		else
			$fileCount = 0;
		return $mod->Lang('file_count',$fileCount);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath($mod))
			return array('main'=>array($this->GetErrorMessage('err_uploads_dir')));

		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE,TRUE,FALSE);
		$main[] = array($mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,
			'fp_select_one',
			$this->GetProperty('select_one',$mod->Lang('select_one')),25,128));
/*		$main[] = array($mod->Lang('title_newline_replacement'),
			$mod->CreateInputText($id,'fp_newlinechar',
				$this->GetProperty('newlinechar'),5,15),
			$mod->Lang('help_newline_replacement'));
*/
		if ($this->fileAdd) {
			$this->AddPropIndexed('destination_displayname','');
			$this->AddPropIndexed('destination_value','');
			$this->AddPropIndexed('destination_filename','');
			$this->fileAdd = FALSE;
		}
		$names = $this->GetPropArray('destination_filename');
		if ($names) {
			$dests = array();
			$dests[] = array(
				$mod->Lang('title_selection_displayname'),
				$mod->Lang('title_selection_value'),
				$mod->Lang('title_destination_filename'),
				$mod->Lang('title_select')
				);
			foreach ($names as $i=>&$one) {
				$dests[] = array(
				$mod->CreateInputText($id,'fp_destination_displayname'.$i,$this->GetPropIndexed('destination_displayname',$i),30,128),
				$mod->CreateInputText($id,'fp_destination_value'.$i,$this->GetPropIndexed('destination_value',$i),30,128),
				$mod->CreateInputText($id,'fp_destination_filename'.$i,$one,30,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
//		$main[] = array($mod->Lang('title_director_details'),$dests);
		} else {
			$dests = FALSE;
			$main[] = array('','',$mod->Lang('missing_type',$mod->Lang('file')));
		}

		//setup sample-template buttons and scripts
		$ctldata = array();
		$ctldata['fp_file_template']['is_oneline']=TRUE;
		$ctldata['fp_file_header']['is_oneline']=TRUE;
		$ctldata['fp_file_header']['is_header']=TRUE;
		$ctldata['fp_file_footer']['is_oneline']=TRUE;
		$ctldata['fp_file_footer']['is_footer']=TRUE;
		list($buttons,$jsfuncs) = Utils::TemplateActions($this->formdata,$id,$ctldata);

		$adv[] = array($mod->Lang('title_file_template'),
			$mod->CreateTextArea(FALSE,$id,
			htmlspecialchars($this->GetProperty('file_template')),
			'fp_file_template','pwf_tallarea','','','',50,15).
			'<br /><br />'.$buttons[0]);
		$adv[] = array($mod->Lang('title_file_header'),
			$mod->CreateTextArea(FALSE,$id,
			htmlspecialchars($this->GetProperty('file_header')),
			'fp_file_header','pwf_shortarea','','','',50,8).
			'<br /><br />'.$buttons[1]);
		$adv[] = array($mod->Lang('title_file_footer'),
			$mod->CreateTextArea(FALSE,$id,
			htmlspecialchars($this->GetProperty('file_footer')),
			'fp_file_footer','pwf_shortarea','','','',50,8).
			'<br /><br />'.$buttons[2]);

		if ($dests)
			return array('main'=>$main,'adv'=>$adv,'table'=>$dests,'funcs'=>$jsfuncs,
				'extra'=>'varshelpadv');//show variables-help on advanced tab
		else
			return array('main'=>$main,'adv'=>$adv,'funcs'=>$jsfuncs,'extra'=>'varshelpadv');
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('destination_filename');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('destination_displayname',$i)) {
					$this->RemovePropIndexed('destination_filename',$i);
					$this->RemovePropIndexed('destination_displayname',$i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id,&$params)
	{
		$names = $this->GetPropArray('destination_displayname');
		if ($names) {
			$mod = $this->formdata->formsmodule;
			$js = $this->GetScript();
			$ret = array();

			foreach ($names as $i=>&$one) {
				$oneset = new \stdClass();
				$oneset->title = $one;
				$tmp = '<label for="'.$this->GetInputId('_'.$i).'">'.$one.'</label>';
				$oneset->name = $this->SetClass($tmp);
				$value = $this->GetPropIndexed('destination_value',$i);
				$tmp = $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.$this->Id.'[]',$value,
					(is_array($this->Value) && in_array($value,$this->Value))?$value:-1,
					'id="'.$this->GetInputId('_'.$i).'"'.$js);
				$oneset->input = $this->SetClass($tmp);
				$ret[] = $oneset;
			}
			unset($one);
			$this->MultiPopulate = TRUE;
			return $ret;
		}
		$this->MultiPopulate = FALSE;
		return '';
	}

	public function Dispose($id,$returnid)
	{
		$mod = $this->formdata->formsmodule;
		$ud = Utils::GetUploadsPath($mod);
		if (!$ud)
			return array(FALSE,$mod->Lang('err_uploads_dir'));
/*MUTEX
		try {
			$mx = Utils::GetMutex($mod);
		} catch (Exception $e) {
			return array(FALSE,$this->Lang('err_system'));
		}
*/
		$fn = reset($this->XtraProps['destination_filename']);
/*MUTEX
		$token = abs(crc32($fn.'mutex'));
		if (!$mx->lock($token))
			return array(FALSE,$mod->Lang('err_lock'));
*/
		$tplvars = array();
		Utils::SetupFormVars($this->formdata,$tplvars);

		$header = $this->GetProperty('file_header');
		if (!$header)
			$header = $this->CreateSampleHeader();
		$header = Utils::ProcessTemplateFromData($mod,$header,$tplvars);

		$template = $this->GetProperty('file_template');
		if (!$template)
			$template = $this->CreateDefaultTemplate();
		$newline = Utils::ProcessTemplateFromData($mod,$template,$tplvars);
/*		$replchar = $this->GetProperty('newlinechar');
		if ($replchar) {
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]+/',$replchar,$newline);
		}
*/
		$l = strlen(PHP_EOL);
		if (substr($newline,-$l) != PHP_EOL)
			$newline .= PHP_EOL;

		$footer = $this->GetProperty('file_footer');
		if ($footer)
			$footer = Utils::ProcessTemplateFromData($mod,$footer,$tplvars);

		// output to files
		if (is_array($this->Value)) {
			$values = $this->GetPropArray('destination_value');

			foreach ($this->Value as $indx) {
				$fn = preg_replace('/[^\w\d\.]|\.\./','_',
					$this->GetPropIndexed('destination_filename',$indx));
				if (!$fn)
					continue;
				$fp = $ud.DIRECTORY_SEPARATOR.$fn;

				$first = !file_exists($fp);
				$fh = fopen($fp,'w');
				if ($first) {
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
			}
		}

/*MUTEX
		$mx->unlock($token);
*/
		return array(TRUE,'');
	}
}
