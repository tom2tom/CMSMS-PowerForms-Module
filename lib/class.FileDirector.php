<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class FileDirector extends FieldBase
{
	private $fileAdd = FALSE;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->DisplayInSubmission = FALSE;
		$this->IsDisposition = TRUE;
		$this->IsInput = TRUE;
		$this->MultiChoice = TRUE;
		$this->Type = 'FileDirector';
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'select_label' => 12,
		'header_template' => 13,
		'file_template' => 13,
		'footer_template' => 13,
		];

		$mkey1 = 'destination_filename';
		$mkey2 = 'destination_displayname';
		if ($actual) {
			$opt = $this->GetPropArray($mkey1);
			if ($opt) {
				$suff = array_keys($opt);
			} else {
				return $ret;
			}
		} else {
			$suff = ['*']; //any numeric suffix
		}
		foreach ($suff as $one) {
			$ret[$mkey1.$one] = 12;
		}
		foreach ($suff as $one) {
			$ret[$mkey2.$one] = 12;
		}
		return $ret;
	}

	public function GetSynopsis()
	{
		$mod = $this->formdata->pwfmod;
		if (!Utils::GetUploadsPath($mod)) {
			return $mod->Lang('err_uploads_dir');
		}
		$opt = $this->GetPropArray('destination_filename');
		if ($opt) {
			$fileCount = count($opt);
		} else {
			$fileCount = 0;
		}
		return $mod->Lang('file_count', $fileCount);
	}

	public function ComponentAddLabel()
	{
		return $this->formdata->pwfmod->Lang('add_file');
	}

	public function ComponentDeleteLabel()
	{
		return $this->formdata->pwfmod->Lang('delete_file');
	}

	public function HasComponentAdd()
	{
		return TRUE;
	}

	public function ComponentAdd(&$params)
	{
		$this->fileAdd = TRUE;
	}

	public function HasComponentDelete()
	{
		return $this->GetPropArray('destination_filename') != FALSE;
	}

	public function ComponentDelete(&$params)
	{
		if (isset($params['selected'])) {
			foreach ($params['selected'] as $indx=>$val) {
				$this->RemovePropIndexed('destination_filename', $indx);
				$this->RemovePropIndexed('destination_displayname', $indx);
			}
		}
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

	public function DisplayableValue($as_string=TRUE)
	{
		$ret = $this->GetPropIndexed('destination_displayname', $this->Value); //TODO
		if ($as_string) {
			return [$ret];
		} else {
			return $ret;
		}
	}

	public function AdminPopulate($id)
	{
		$mod = $this->formdata->pwfmod;
		if (!Utils::GetUploadsPath($mod)) {
			return ['main'=>[$this->GetErrorMessage('err_uploads_dir')]];
		}

		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE, FALSE);
		$main[] = [$mod->Lang('title_select_one_message'),
			$mod->CreateInputText($id,
			'fp_select_label',
			$this->GetProperty('select_label', $mod->Lang('select_one')), 25, 128)];
/*		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($id,'fp_newlinechar',
					$this->GetProperty('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));
*/
		if ($this->fileAdd) {
			$this->AddPropIndexed('destination_displayname', '');
			$this->AddPropIndexed('destination_filename', '');
			$this->fileAdd = FALSE;
		}
		$names = $this->GetPropArray('destination_filename');
		if ($names) {
			$dests = [];
			$dests[] = [
				$mod->Lang('title_selection_displayname'),
				$mod->Lang('title_destination_filename'),
				$mod->Lang('title_select')
				];
			foreach ($names as $i=>&$one) {
				$arf = '['.$i.']';
				$dests[] = [
				$mod->CreateInputText($id, 'fp_destination_displayname'.$arf, $this->GetPropIndexed('destination_displayname', $i), 30, 128),
				$mod->CreateInputText($id, 'fp_destination_filename'.$arf, $one, 30, 128),
				$mod->CreateInputCheckbox($id, 'selected'.$arf, 1, -1, 'style="display:block;margin:auto;"')
				];
			}
			unset($one);
			$this->MultiComponent = TRUE;
		} else {
			$dests = FALSE;
			$this->MultiComponent = FALSE;
			$main[] = ['','',$mod->Lang('missing_type', $mod->Lang('file'))];
		}

		$button = Utils::SetTemplateButton('file_template',
			$mod->Lang('title_create_sample_template'));
		$adv[] = [$mod->Lang('title_file_template'),
				$mod->CreateTextArea(FALSE, $id,
					htmlspecialchars($this->GetProperty('file_template')),
					'fp_file_template', 'pwf_shortarea', '', '', '', 45, 3),
				$button];
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
		if ($dests) {
			return ['main'=>$main,'adv'=>$adv,'table'=>$dests,'extra'=>'varshelpadv'];
		} else {
			return ['main'=>$main,'adv'=>$adv,'extra'=>'varshelpadv'];
		}
	}

	public function PostAdminAction(&$params)
	{
		//cleanup empties
		$names = $this->GetPropArray('destination_filename');
		if ($names) {
			foreach ($names as $i=>&$one) {
				if (!$one || !$this->GetPropIndexed('destination_displayname', $i)) {
					$this->RemovePropIndexed('destination_filename', $i);
					$this->RemovePropIndexed('destination_displayname', $i);
				}
			}
			unset($one);
		}
	}

	public function Populate($id, &$params)
	{
		$names = $this->GetPropArray('destination_displayname');
		if ($names) {
			$mod = $this->formdata->pwfmod;
			$choices = [' '.$this->GetProperty('select_label', $mod->Lang('select_one'))=>-1]
				+ array_flip($names);
			$tmp = $mod->CreateInputDropdown(
				$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
				'id="'.$this->GetInputId().'"'.$this->GetScript());
			return $this->SetClass($tmp);
		}
		return '';
	}

	public function Dispose($id, $returnid)
	{
		$mod = $this->formdata->pwfmod;
		$ud = Utils::GetUploadsPath($mod);
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
		$fn = preg_replace('/[^\w\d\.]|\.\./', '_',
			   $this->GetPropIndexed('destination_filename', $this->Value));
		$token = abs(crc32($fn.'mutex'));
/*MUTEX
		if (!$mx->lock($token))
			return array(FALSE,$mod->Lang('err_lock'));
*/
		$tplvars = [];
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		Utils::SetupFormVars($this->formdata, $tplvars);

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

		$newline = Utils::ProcessTemplateFromData(mod, $template, $tplvars);
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
