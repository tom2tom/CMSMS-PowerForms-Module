<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfDispositionFile extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionFile';
		$this->IsDisposition = true;
		$this->NonRequirableField = true;
		$this->DisplayInForm = false;
		$this->sortable = false;
	}

	function StatusInfo()
	{
		$mod=$this->formdata->pwfmodule;
		return $this->GetOption('filespec',$mod->Lang('unspecified'));
	}

	function DisposeForm($returnid)
	{
		$config = cmsms()->GetConfig();
		$form = $this->formdata;
		$mod = $form->pwfmodule;

		$count = 0;
		while (! $mod->GetFileLock() && $count<200)
		{
			$count++;
			usleep(500);
		}
		if($count == 200)
		{
			return array(false, $mod->Lang('submission_error_file_lock'));
		}

		$filespec = $this->GetOption('fileroot',$config['uploads_path']).DIRECTORY_SEPARATOR.
		  preg_replace("/[^\w\d\.]|\.\./", "_", $this->GetOption('filespec','form_submissions.txt'));

		$form->setFinishedFormSmarty();

		$line = '';

		// Check if first time, write header
		if(!file_exists($filespec))
		{
			$header = $this->GetOption('file_header','');

			if($header != '')
			{
				//all smarty processing done without cacheing (smarty->fetch() fails)
				$header = $mod->ProcessTemplateFromData($header);
			}
			if(substr($header,-1,1) != "\n")
			{
				$header .= "\n";
			}
		}

		// Make newline
		$template = $this->GetOption('file_template','');
		if($template == '')
		{
			$template = $form->createSampleTemplate();
		}
		$line = $template;

		$newline = $mod->ProcessTemplateFromData($line);
		$replchar = $this->GetOption('newlinechar','');
		if($replchar != '')
		{
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]/',$replchar,$newline);
		}
		if(substr($newline,-1,1) != "\n")
		{
		  $newline .= "\n";
		}

		// Get footer
		$footer = $this->GetOption('file_footer','');

		if($footer != '')
		{
			$footer = $mod->ProcessTemplateFromData($footer);
		}

		// Write file
		if(file_exists($filespec))
		{
			$rows = file($filespec);
			$fp = fopen($filespec, 'w');

			foreach($rows as $oneline)
			{
				if(substr($footer, 0, strlen($oneline)) == $oneline)
				{
					break;
				}

				fwrite($fp,$oneline);
			}

		}
		else
		{
			$fp = fopen($filespec, 'w');
		}

		fwrite($fp,$header.$newline.$footer);
		fclose($fp);

		/*  Stikki removed: due new rewrite method
		$f2 = fopen($filespec,"a");
		fwrite($f2,$header.$newline);
		fclose($f2);*/
		$mod->ReturnFileLock();
		return array(true,'');
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;
		$config = cmsms()->GetConfig();

		$main = array();
		$main[] = array($mod->Lang('title_file_root'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_fileroot',
						$this->GetOption('fileroot',$config['uploads_path']),45,255),
				$mod->Lang('help_file_root'));
//		$mod->CreateInputFile($formDescriptor, 'pwfp_opt_fileroot', '', 60)
		$main[] = array($mod->Lang('title_file_name'),
				   $mod->CreateInputText($formDescriptor, 'pwfp_opt_filespec',
						$this->GetOption('filespec','form_submissions.txt'),25,128));

		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_newlinechar',
						$this->GetOption('newlinechar',''),5,15),
				$mod->Lang('title_newline_replacement_help'));

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline']=true;
		$parmMain['opt_file_header']['is_oneline']=true;
		$parmMain['opt_file_header']['is_header']=true;
		$parmMain['opt_file_footer']['is_oneline']=true;
		$parmMain['opt_file_footer']['is_footer']=true;
		list ($funcs, $buttons) = $this->formdata->AdminTemplateActions($formDescriptor,$parmMain);

		$adv = array();
		$adv[] = array($mod->Lang('title_file_template'),
				  $mod->CreateTextArea(false, $formDescriptor,
						htmlspecialchars($this->GetOption('file_template','')),
						'pwfp_opt_file_template', 'pwf_tallarea', '','',80,15).
						'<br /><br />'.$buttons[0]);

		$adv[] = array($mod->Lang('title_file_header'),
				  $mod->CreateTextArea(false, $formDescriptor,
						htmlspecialchars($this->GetOption('file_header','')),
						'pwfp_opt_file_header', 'pwf_shortarea', '','',80,8).
						'<br /><br />'.$buttons[1]);

		$adv[] = array($mod->Lang('title_file_footer'),
				  $mod->CreateTextArea(false, $formDescriptor,
						htmlspecialchars($this->GetOption('file_footer','')),
						'pwfp_opt_file_footer', 'pwf_shortarea', '','',80,8).
						'<br /><br />'.$buttons[2]);
		/*show variables-help on advanced tab*/
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

	function PostPopulateAdminForm(&$mainArray, &$advArray)
	{
		$this->HiddenDispositionFields($mainArray, $advArray);
	}
}

?>
