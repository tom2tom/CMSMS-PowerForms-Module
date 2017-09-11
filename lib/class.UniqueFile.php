<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

//This class stores form data in a new/unique file

namespace PWForms;

class UniqueFile extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
		$this->IsDisposition = TRUE;
		$this->Type = 'UniqueFile';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'filespec' => 12,
		'header_template' => 13,
		'file_template' => 13,
		'footer_template' => 13,
		];
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		if (!Utils::GetUploadsPath($mod)) {
			return $mod->Lang('err_uploads_dir');
		}
		return $this->GetProperty('filespec', $mod->Lang('unspecified'));
	}

	public function CreateDefaultHeader()
	{
		$fields = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInSubmission()) {
				$fields[] = $one->GetName();
			}
		}
		unset($one);
		return implode("\t", $fields);
	}

	public function CreateDefaultFooter()
	{
		return 'none'; //TODO
	}

	public function CreateDefaultTemplate()
	{
		$fields = [];
		foreach ($this->formdata->Fields as &$one) {
			if ($one->DisplayInSubmission()) {
				$fields[] = '{$'.$one->GetVariableName().'}';
			}
		}
		unset($one);
		return implode("\t", $fields);
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->pwfmod;
		if (!Utils::GetUploadsPath($mod)) {
			return ['main'=>[$this->GetErrorMessage('err_uploads_dir')]];
		}

		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE, FALSE);

		$main[] = [$mod->Lang('title_file_name'),
			$mod->CreateInputText($id, 'fp_filespec',
				$this->GetProperty('filespec',
				'form_submission_'.date('Y-m-d_His').'.txt'), 50, 128)];

/*		$main[] = array($mod->Lang('title_newline_replacement'),
			$mod->CreateInputText($id,'fp_newlinechar',
				$this->GetProperty('newlinechar'),5,15),
			$mod->Lang('help_newline_replacement'));
*/
		$button = Utils::SetTemplateButton('file_template',
			$mod->Lang('title_create_sample_template'));
		$adv[] = [$mod->Lang('title_unique_file_template'),
				$mod->CreateTextArea(FALSE, $id,
					htmlspecialchars($this->GetProperty('file_template')),
					'fp_file_template', 'pwf_shortarea', '', '', '', 45, 3),
				$mod->Lang('help_unique_file_template').'<br />'.$button];

		$button = Utils::SetTemplateButton('header_template',
			$mod->Lang('title_create_sample_header_template'));
		$adv[] = [$mod->Lang('title_file_header'),
				$mod->CreateTextArea(FALSE, $id,
					htmlspecialchars($this->GetProperty('header_template')),
					'fp_file_header', 'pwf_shortarea', '', '', '', 45, 8),
				$mod->Lang('help_file_header_template').'<br />'.$button];

		$button = Utils::SetTemplateButton('footer_template',
			$mod->Lang('title_create_sample_footer_template'));
		$adv[] = [$mod->Lang('title_file_footer'),
				$mod->CreateTextArea(FALSE, $id,
					htmlspecialchars($this->GetProperty('footer_template')),
					'fp_file_footer', 'pwf_shortarea', '', '', '', 45, 8),
				$mod->Lang('help_file_footer_template').'<br />'.$button];
		$this->Jscript->jsloads[] = <<<EOS
 $('#get_file_template').click(function() {
  populate_template('{$id}fp_file_template',{main:1});
 });
 $('#get_file_header').click(function() {
  populate_template('{$id}fp_file_header',{header:1});
 });
 $('#get_file_footer').click(function() {
  populate_template('{$id}fp_file_footer',{footer:1});
 });
EOS;
		$this->Jscript->jsfuncs[] = Utils::SetTemplateScript($mod, $id, ['type'=>'file', 'field_id'=>$this->Id]);

		//show variables-help on advanced tab
		return ['main'=>$main,'adv'=>$adv,'extra'=>'varshelpadv'];
	}

	public function Dispose($id, $returnid)
	{
		$mod = $formdata->pwfmod;
		$ud = $Utils::GetUploadsPath($mod);
		if (!$ud) {
			return [FALSE,$mod->Lang('err_uploads_dir')];
		}
/*MUTEX
		try {
			$mx = Utils::GetMutex($mod);
		} catch (Exception $e) {
			return array(FALSE,$this->Lang('err_system'));
		}
*/
		$tplvars = [];
		Utils::SetupFormVars($this->formdata, $tplvars);

		$filespec = $this->GetProperty('filespec');
		if ($filespec) {
			$fn = preg_replace('/[^\w\d\.]|\.\./', '_', Utils::ProcessTemplateFromData($mod, $filespec, $tplvars));
		} else {
			$fn = 'form_submission_'.date('Y-m-d_His').'.txt';
		}
/*MUTEX
		$token = abs(crc32($fn.'mutex'));
		if (!$mx->lock($token))
			return array(FALSE,$mod->Lang('err_lock'));
*/
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		$footer = $this->GetProperty('footer_template');
		if ($footer != 'none') {
			if (!$footer) {
				$footer = $this->CreateDefaultFooter();
			}
			if ($footer) {
				$footer = Utils::ProcessTemplateFromData($mod, $footer, $tplvars).PHP_EOL;
			}
		} else {
			$footer = '';
		}

		$template = $this->GetProperty('file_template');
		if (!$template) {
			$template = $this->CreateDefaultTemplate();
		}

		$newline = Utils::ProcessTemplateFromData($mod, $template, $tplvars);
/*		$replchar = $this->GetProperty('newlinechar');
		if ($replchar) {
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]+/',$replchar,$newline);
		}
*/
		$l = strlen(PHP_EOL);
		if (substr($newline, -$l) != PHP_EOL) {
			$newline .= PHP_EOL;
		}

		$first = !file_exists($fp);
		$fh = fopen($fp, 'w');
		if ($first) {
			$header = $this->GetProperty('header_template');
			if ($header != 'none') {
				if (!$header) {
					$header = $this->CreateDefaultHeader();
				}
				if ($header) {
					$header = Utils::ProcessTemplateFromData($mod, $header, $tplvars).PHP_EOL;
				}
			} else {
				$header = '';
			}
			fwrite($fh, $header.$newline.$footer);
		} else {
			//seek to footer
			if ($footer) {
				$rows = explode(PHP_EOL, $footer);
				$target = $rows[0];
			} else {
				$target = '';
			}
			$rows = file($fp);
			foreach ($rows as &$line) {
				$l = strlen($line);
				if (strncmp($line, $target, $l) != 0) {
					fwrite($fh, $line);
				} else {
					break;
				}
			}
			unset($line);
			fwrite($fh, $newline.$footer);
		}
		fclose($fh);
/*MUTEX
		$mx->unlock($token);
*/
		return [TRUE,''];
	}
}
