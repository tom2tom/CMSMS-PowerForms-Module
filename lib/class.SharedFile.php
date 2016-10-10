<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

//This class stores form data in a pre-specified file

namespace PWForms;

class SharedFile extends FieldBase
{
	public function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->ChangeRequirement = FALSE;
		$this->Type = 'SharedFile';
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

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath($mod))
			return $mod->Lang('error_uploads_dir');
		return $this->GetOption('filespec',$mod->Lang('unspecified'));
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath($mod))
			return array('main'=>array($this->GetErrorMessage('error_uploads_dir')));

		list($main,$adv) = $this->AdminPopulateCommon($id,FALSE);

		$main[] = array($mod->Lang('title_file_name'),
			$mod->CreateInputText($id,'opt_filespec',
				$this->GetOption('filespec','form_submissions.txt'),50,128));

/*		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($id,'opt_newlinechar',
						$this->GetOption('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));
*/
		//setup sample-template buttons and scripts
		$ctldata = array();
		$ctldata['opt_file_template']['is_oneline'] = TRUE;
		$ctldata['opt_file_header']['is_oneline'] = TRUE;
		$ctldata['opt_file_header']['is_header'] = TRUE;
		$ctldata['opt_file_footer']['is_oneline'] = TRUE;
		$ctldata['opt_file_footer']['is_footer'] = TRUE;
		list($buttons,$jsfuncs) = Utils::TemplateActions($this->formdata,$id,$ctldata);

		$adv[] = array($mod->Lang('title_file_template'),
						$mod->CreateTextArea(FALSE,$id,
							htmlspecialchars($this->GetOption('file_template')),
							'opt_file_template','pwf_tallarea','','','',50,15),
						'<br /><br />'.$buttons[0]);

		$adv[] = array($mod->Lang('title_file_header'),
						$mod->CreateTextArea(FALSE,$id,
							htmlspecialchars($this->GetOption('file_header')),
							'opt_file_header','pwf_shortarea','','','',50,8),
						'<br /><br />'.$buttons[1]);

		$adv[] = array($mod->Lang('title_file_footer'),
						$mod->CreateTextArea(FALSE,$id,
							htmlspecialchars($this->GetOption('file_footer')),
							'opt_file_footer','pwf_shortarea','','','',50,8),
						'<br /><br />'.$buttons[2]);

		//show variables-help on advanced tab
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$jsfuncs,'extra'=>'varshelpadv');
	}

	public function Dispose($id,$returnid)
	{
		$mod = $formdata->formsmodule;
		$ud = $Utils::GetUploadsPath($mod);
		if (!$ud)
			return array(FALSE,$mod->Lang('error_uploads_dir'));
/*MUTEX
		try {
			$mx = Utils::GetMutex($mod);
		} catch (Exception $e) {
			return array(FALSE,$this->Lang('error_system'));
		}
*/
		$tplvars = array();
		Utils::SetupFormVars($this->formdata,$tplvars);

		$filespec = $this->GetOption('filespec');
		if ($filespec)
			$fn = preg_replace('/[^\w\d\.]|\.\./','_',Utils::ProcessTemplateFromData($mod,$filespec,$tplvars));
		else
			$fn = 'form_submissions.txt';
/*MUTEX
		$token = abs(crc32($fn.'mutex'));
		if (!$mx->lock($token))
			return array(FALSE,$mod->Lang('error_lock'));
*/
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		$footer = $this->GetOption('file_footer');
		if ($footer)
			$footer = Utils::ProcessTemplateFromData($mod,$footer,$tplvars);

		$template = $this->GetOption('file_template');
		if (!$template)
			$template = $this->CreateDefaultTemplate();

		$newline = Utils::ProcessTemplateFromData($mod,$template,$tplvars);
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
