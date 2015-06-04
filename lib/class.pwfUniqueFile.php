<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfUniqueFile extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInForm = FALSE;
		$this->DisplayInSubmission = FALSE;
//		$this->IsComputedOnSubmission = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'UniqueFile';
	}

/*USELESS	function ComputeOrder()
	{
		return $this->GetOption('order',1);
	}

	function Compute()
	{
		$ud = pwfUtils::GetUploadsPath();
		if(!$ud)
			return;

		$type = $this->GetOption('file_type',0) ? 'rtf':'txt';
		$filespec = $this->GetOption('filespec');
		if($filespec)
		{
			pwfUtils::SetupFormVars($this->formdata);
			$fn = preg_replace('/[^\w\d\.]|\.\./','_',
				$this->formdata->formsmodule->ProcessTemplateFromData($filespec));
			//conform extension
			$ext = '.'.$type;
			if(substr($fn,-4) != $ext)
				$fn .= $ext;
		}
		else
			$fn = 'form_submission_'.date('Y-m-d_His').'.'$type;
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;
		$config = cmsms()->GetConfig();
		$rl = strlen($config['root_path']);
		if($rl && strncmp($fp,$config['root_path'],$rl) == 0)
			$relurl = str_replace('\\','/',substr($fp,$rl));
		else
			$relurl = '';
		$url = $config['root_url'].$relurl;

		$this->SetValue(array($fp,$url,$relurl,$fn));
	}
*/
	function SetValue($value)
	{
		if($this->Value === FALSE)
		{
			if(is_array($value))
			{
				$this->Value = $value;
				for ($i=0; $i<count($this->Value); $i++)
					$this->Value[$i] = pwfUtils::unmy_htmlentities($this->Value[$i]);
			}
			else
				$this->Value = pwfUtils::unmy_htmlentities($value);
		}
		else
		{
			if(is_array($value))
			{
				for ($i=0; $i<count($value); $i++)
					$value[$i] = pwfUtils::unmy_htmlentities($value[$i]);
				$this->Value = $value;
			}
			else
			{
				if(!is_array($this->Value))
					$this->Value = array($this->Value);
				$this->Value[] = pwfUtils::unmy_htmlentities($value);
			}
		}
	}

	function GetFieldStatus()
	{
		return $this->GetOption('filespec',
			$this->formdata->formsmodule->Lang('unspecified'));
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		$mod = $this->formdata->formsmodule;
		if($as_string && is_array($this->Value) && isset($this->Value[1]))
		{
			return $this->Value[1];
		}
		else
		{
			return $this->Value;
		}
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$config = cmsms()->GetConfig();
		$file_type = $this->GetOption('file_type',0);
		$rtf_template_type = $this->GetOption('rtf_template_type');

		$main = array();
		$main[] = array($mod->Lang('title_file_name'),
			$mod->CreateInputText($id,'opt_filespec',
				$this->GetOption('filespec'),80,255));
//		$mod->CreateInputFile($id,'opt_filespec','',60)
		$main[] = array($mod->Lang('title_newline_replacement'),
			$mod->CreateInputText($id,'opt_newlinechar',
				$this->GetOption('newlinechar'),5,15),
			$mod->Lang('help_newline_replacement'));

		$adv = array();
// 			array("Text displayed in option tag" => "Value of option tag");
		$file_type_list = array('TXT'=>0,'RTF'=>1);
		$adv[] = array($mod->Lang('title_file_type'),
			$mod->CreateInputDropdown($id,
			'opt_file_type',$file_type_list,$file_type));

		$adv[] = array($mod->Lang('title_rtf_file_template'),
			$mod->CreateInputText($id,'opt_rtf_file_template',
				$this->GetOption('rtf_file_template','RTF_TemplateBasic.rtf'),50,255),
			$mod->Lang('help_rtf_file_template'));
//		$mod->CreateInputFile($id,'opt_rtf_file_template','',60)

		$rtf_template_type_list = array($mod->Lang('basic')=>0,$mod->Lang('advanced')=>1);
		$adv[] = array($mod->Lang('title_rtf_template_type'),
			$mod->CreateInputDropdown($id,
				'opt_rtf_template_type',$rtf_template_type_list,$rtf_template_type),
			$mod->Lang('help_rtf_template_type'));

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_header'] = TRUE;
		$parmMain['opt_file_footer']['is_oneline'] = TRUE;
		$parmMain['opt_file_footer']['is_footer'] = TRUE;
		list($funcs,$buttons) = pwfUtils::AdminTemplateActions($this->formdata,$id,$parmMain);

		$adv[] = array($mod->Lang('title_unique_file_template'),
			$mod->CreateTextArea(FALSE,$id,$this->GetOption('file_template'),
			'opt_file_template','pwf_tallarea','','','',50,15),
			$mod->Lang('help_unique_file_template').'<br /><br />'.$buttons[0]);

		$adv[] = array($mod->Lang('title_file_header'),
			$mod->CreateTextArea(FALSE,$id,$this->GetOption('file_header'),
				'opt_file_header','pwf_shortarea','','','',50,8),
			$mod->Lang('help_file_header_template').'<br /><br />'.$buttons[1]);

		$adv[] = array($mod->Lang('title_file_footer'),
			$mod->CreateTextArea(FALSE,$id,$this->GetOption('file_footer'),
				'opt_file_footer','pwf_shortarea','','','',50,8),
			$mod->Lang('help_file_footer_template').'<br /><br />'.$buttons[2]);
		/*show variables-help on advanced tab*/
		return array('main'=>$main,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

	function PostPopulateAdminForm(&$mainArray,&$advArray)
	{
		$this->OmitAdminCommon($mainArray,$advArray);
	}

	function Dispose($id,$returnid)
	{
		$ud = pwfUtils::GetUploadsPath();
		if(!$ud)
			return array(FALSE,$mod->Lang('error'));
	
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;
//TODO mutex
		$count = 0;
		while (!pwfUtils::GetFileLock() && $count<200)
		{
			$count++;
			usleep(500);
		}
		if($count == 200)
			return array(FALSE,$mod->Lang('submission_error_file_lock'));

		pwfUtils::SetupFormVars($this->formdata);

		$type = $this->GetOption('file_type',0) ? 'rtf':'txt';
		$filespec = $this->GetOption('filespec');
		if($filespec)
		{
			$fn = preg_replace('/[^\w\d\.]|\.\./','_',$mod->ProcessTemplateFromData($filespec));
			//conform extension
			$ext = '.'.$type;
			if(substr($fn,-4) != $ext)
				$fn .= $ext;
		}
		else
			$fn = 'form_submission_'.date('Y-m-d_His').'.'$type;
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		if($type == 'txt')
		{
			$footer = $this->GetOption('file_footer');
			if($footer)
				$footer = $mod->ProcessTemplateFromData($footer);

			$template = $this->GetOption('file_template');
			if(!$template)
				$template = pwfUtils::CreateSampleTemplate($this->formdata);

			$newline = $mod->ProcessTemplateFromData($template);
			$replchar = $this->GetOption('newlinechar');
			if($replchar)
			{
				$newline = rtrim($newline,"\r\n");
				$newline = preg_replace('/[\n\r]/',$replchar,$newline);
			}
			if(substr($newline,-1) != "\n")
				$newline .= "\n";

			$first = !file_exists($fp);
			$fh = fopen($fp,'w');
			if($first)
			{
				$header = $this->GetOption('file_header');
				if($header)
					$header = $mod->ProcessTemplateFromData($header)."\n";
				fwrite($fh,$header.$newline.$footer);
			}
			else
			{
				//seek to footer
				$rows = file($fp);
				foreach($rows as $oneline)
				{
					if(substr($footer,0,strlen($oneline)) == $oneline)
						break;
					fwrite($fh,$oneline);
				}
				fwrite($fh,$newline.$footer);
			}
			fclose($fh);
		}
		else //$type = 'rtf'
		{
			$header = $this->GetOption('file_header');
			if($header)
			{
				$header = $mod->ProcessTemplateFromData($header);
				$header = preg_replace('/(\r\n)/','\par$1',$header);
			}
			$footer = $this->GetOption('file_footer');
			if($footer)
			{
				$footer = $mod->ProcessTemplateFromData($footer);
				$footer = preg_replace('/(\r\n)/','\par$1',$footer);
			}

			if($this->GetOption('rtf_template_type') == 0) //Basic template
			{
				$template = $this->GetOption('file_template');
				if($template == '')
					$template = pwfUtils::CreateSampleTemplate($this->formdata);
				$template = $mod->ProcessTemplateFromData($template);
				$template = preg_replace('/(\r\n)/','\par$1',$template);

				$search = array("%%HEADER%%","%%FIELDS%%","%%FOOTER%%");
				$replace = array($header,$template,$footer);
				$tp = cms_join_path($mod->GetModulePath(),'templates',
					$this->GetOption('rtf_file_template','RTF_TemplateBasic.rtf')));
				$rtf_template = @file_get_contents($tp);
				$rtf_content = str_replace($search,$replace,$rtf_template);
			}
			else //Advanced template
			{
				$tp = cms_join_path($mod->GetModulePath(),'templates',
					$this->GetOption('rtf_file_template','RTF_TemplateAdvanced.rtf'));
				$template = @file_get_contents($tp);
				/*
				To prevent the Smarty parser eating the RTF Tags (which also use curly braces),
				we swap the curly braces temporarily.
				The template is expected to use percent sign and square bracket
				(like %[TAG]%) instead of curly braces.
				*/
				$search = array('{','}','%[',']%');
				$replace = array('<RTF_TAG>','</RTF_TAG>','{','}');
				$template = str_replace($search,$replace,$template);
				$template = $mod->ProcessTemplateFromData($template);
				$search = array('<RTF_TAG>','</RTF_TAG>');
				$replace = array('{','}');
				$template = str_replace($search,$replace,$template);

				$search = array("%%HEADER%%","%%FOOTER%%");
				$replace = array($header,$footer);
				$rtf_content = str_replace($search,$replace,$template);
			}

			$put = file_put_contents($fp,$rtf_content);
		}
//TODO mutex
		pwfUtils::ClearFileLock();
		return array(TRUE,'');
	}

}

?>
