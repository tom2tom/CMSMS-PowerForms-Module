<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->IsDisposition = TRUE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'FileField';
		$this->IsSortable = FALSE;
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		return $this->GetOption('filespec',$mod->Lang('unspecified'));
	}

	function DisposeForm($returnid)
	{
		$config = cmsms()->GetConfig();
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;

		$count = 0;
		while (!pwfUtils::GetFileLock() && $count<200)
		{
			$count++;
			usleep(500);
		}
		if($count == 200)
			return array(FALSE,$mod->Lang('submission_error_file_lock'));

		$filespec = $this->GetOption('fileroot',$config['uploads_path']).DIRECTORY_SEPARATOR.
		  preg_replace("/[^\w\d\.]|\.\./","_",$this->GetOption('filespec','form_submissions.txt'));

		pwfUtils::SetFinishedFormSmarty($this->formdata);

		$line = '';

		// Check if first time,write header
		if(!file_exists($filespec))
		{
			$header = $this->GetOption('file_header');

			if($header)
				//all smarty processing done without cacheing (smarty->fetch() fails)
				$header = $mod->ProcessTemplateFromData($header);
			if(substr($header,-1,1) != "\n")
				$header .= "\n";
		}

		// Make newline
		$template = $this->GetOption('file_template');
		if($template == '')
			$template = pwfUtils::CreateSampleTemplate($this->formdata);

		$line = $template;

		$newline = $mod->ProcessTemplateFromData($line);
		$replchar = $this->GetOption('newlinechar');
		if($replchar)
		{
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]/',$replchar,$newline);
		}
		if(substr($newline,-1,1) != "\n")
		  $newline .= "\n";

		// Get footer
		$footer = $this->GetOption('file_footer');

		if($footer)
			$footer = $mod->ProcessTemplateFromData($footer);

		// Write file
		if(file_exists($filespec))
		{
			$rows = file($filespec);
			$fp = fopen($filespec,'w');

			foreach($rows as $oneline)
			{
				if(substr($footer,0,strlen($oneline)) == $oneline)
				{
					break;
				}

				fwrite($fp,$oneline);
			}

		}
		else
		{
			$fp = fopen($filespec,'w');
		}

		fwrite($fp,$header.$newline.$footer);
		fclose($fp);

		/*  Stikki removed: due new rewrite method
		$f2 = fopen($filespec,"a");
		fwrite($f2,$header.$newline);
		fclose($f2);*/
		pwfUtils::ClearFileLock();
		return array(TRUE,'');
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;
		$config = cmsms()->GetConfig();

		$main = array();
		$main[] = array($mod->Lang('title_file_root'),
				$mod->CreateInputText($module_id,'opt_fileroot',
						$this->GetOption('fileroot',$config['uploads_path']),45,255),
				$mod->Lang('help_file_root'));
//		$mod->CreateInputFile($module_id,'opt_fileroot','',60)
		$main[] = array($mod->Lang('title_file_name'),
				   $mod->CreateInputText($module_id,'opt_filespec',
						$this->GetOption('filespec','form_submissions.txt'),25,128));

		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($module_id,'opt_newlinechar',
						$this->GetOption('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_header'] = TRUE;
		$parmMain['opt_file_footer']['is_oneline'] = TRUE;
		$parmMain['opt_file_footer']['is_footer'] = TRUE;
		list ($funcs,$buttons) = pwfUtils::AdminTemplateActions($this->formdata,$module_id,$parmMain);

		$adv = array();
		$adv[] = array($mod->Lang('title_file_template'),
				  $mod->CreateTextArea(FALSE,$module_id,
						htmlspecialchars($this->GetOption('file_template')),
						'opt_file_template','pwf_tallarea','','',50,15).
						'<br /><br />'.$buttons[0]);

		$adv[] = array($mod->Lang('title_file_header'),
				  $mod->CreateTextArea(FALSE,$module_id,
						htmlspecialchars($this->GetOption('file_header')),
						'opt_file_header','pwf_shortarea','','',50,8).
						'<br /><br />'.$buttons[1]);

		$adv[] = array($mod->Lang('title_file_footer'),
				  $mod->CreateTextArea(FALSE,$module_id,
						htmlspecialchars($this->GetOption('file_footer')),
						'opt_file_footer','pwf_shortarea','','',50,8).
						'<br /><br />'.$buttons[2]);
		/*show variables-help on advanced tab*/
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}
}

?>
