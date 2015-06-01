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
		$this->IsComputedOnSubmission = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->NonRequirableField = TRUE;
		$this->Type = 'UniqueFile';
	}

	function ComputeOrder()
	{
		return $this->GetOption('order',1);
	}

	function Compute()
	{
		$config = cmsms()->GetConfig();
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;

		pwfUtils::SetFinishedFormSmarty($this->formdata);

		$filespec = $this->GetOption('filespec');
		if($filespec == '')
		{
			$filespec = 'form_submission_'.date("Y-m-d_His").'.txt';
		}
		//all smarty processing done without cacheing (smarty->fetch() fails)
		$evald_filename = preg_replace("/[^\w\d\.]|\.\./","_",$mod->ProcessTemplateFromData($filespec));
		$filespec = $this->GetOption('fileroot',$config['uploads_path']).DIRECTORY_SEPARATOR.$evald_filename;
		if(strpos($filespec,$config['root_path']) !== FALSE)
		{
			$relurl = str_replace($config['root_path'],'',$filespec);
		}
		$url = $config['root_url'].$relurl;
		$url = str_replace("\\",DIRECTORY_SEPARATOR,$url);
		$this->SetValue(array($filespec,$url,$relurl,$evald_filename));
	}

	function GetFieldStatus()
	{
		$mod=$this->formdata->formsmodule;
		return $this->GetOption('filespec',$mod->Lang('unspecified'));
	}

	function SetValue($valStr)
	{
		//error_log($this->GetName().':'.print_r($valStr,TRUE));
		$fm = $this->formdata;
		if($this->Value === FALSE)
		{
			if(is_array($valStr))
			{
				$this->Value = $valStr;
				for ($i=0; $i<count($this->Value); $i++)
					$this->Value[$i] = pwfUtils::unmy_htmlentities($this->Value[$i]);
			}
			else
				$this->Value = pwfUtils::unmy_htmlentities($valStr);
		}
		else
		{
			if(is_array($valStr))
			{
				for ($i=0; $i<count($valStr); $i++)
					$valStr[$i] = pwfUtils::unmy_htmlentities($valStr[$i]);
				$this->Value = $valStr;
			}
			else
			{
				if(!is_array($this->Value))
					$this->Value = array($this->Value);
				$this->Value[] = pwfUtils::unmy_htmlentities($valStr);
			}
		}
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
		$file_type = $this->GetOption('file_type','FALSE'); //TODO string or boolean
		$rtf_template_type = $this->GetOption('rtf_template_type','FALSE');

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
		// array("Text displayed in option tag" => "Value of option tag");
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

		pwfUtils::SetFinishedFormSmarty($this->formdata);

		$filespec = $this->GetOption('filespec');
		if(!$filespec)
			$filespec = 'form_submission_'.date('Y-m-d_His').'.txt';

		$evald_filename = preg_replace('/[^\w\d\.]|\.\./','_',$mod->ProcessTemplateFromData($filespec));

		$filespec = $ud.DIRECTORY_SEPARATOR.$evald_filename;

		$line = '';
		if($this->GetOption('file_type','FALSE') == 0) //TODO string or boolean
		{ // If File Type is "TXT"
			// Check if first time,write header
			if(!file_exists($filespec))
			{
				$header = $this->GetOption('file_header');
				if($header)
					$header = $mod->ProcessTemplateFromData($header);
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
			$footer = $this->GetOption('file_footer'); //TODO what is this

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
		}
		else if($this->GetOption('file_type','FALSE') == 1)//TODO string or boolean
		{ // If File Type is "RTF"
			$header = $this->GetOption('file_header'); //TODO
			if($header)
				$header = $mod->ProcessTemplateFromData($header);
			$header = preg_replace('/(\r\n)/','\par$1',$header);
			if($this->GetOption('rtf_template_type') == 0)
			{ // If the RTF Template Type is Basic
				$template = $this->GetOption('file_template');
				if($template == '')
					$template = pwfUtils::CreateSampleTemplate($this->formdata);
				$template = $mod->ProcessTemplateFromData($template);
				$template = preg_replace('/(\r\n)/','\par$1',$template);
			}
			else if($this->GetOption('rtf_template_type') == 1)
			{ // If the RTF Template Type is Advanced
				$template = file_get_contents(cms_join_path(dirname(dirname(__FILE__)),'templates',$this->GetOption('rtf_file_template','RTF_TemplateAdvanced.rtf')));

				// To avoid the Smarty Parser eating the RTF Tags (which also use curly braces),we need to swap the curly braces temporarily
				// to parse "Smarty" tags in the RTF Template. To use Smarty tags in the template,we'll have to use a unique enclosure of
				// percent sign and square bracket (%[TAG]%) instead of curly braces.
				$search = array("{","}","%[","]%");
				$replace = array("<RTF_TAG>","</RTF_TAG>","{","}");
				$template = str_replace($search,$replace,$template);
				$template = $mod->ProcessTemplateFromData($template);
				$search = array("<RTF_TAG>","</RTF_TAG>");
				$replace = array("{","}");
				$template = str_replace($search,$replace,$template);
			}
			$footer = $this->GetOption('file_footer'); //TODO
			if($footer)
				$footer = $mod->ProcessTemplateFromData($footer);

			$footer = preg_replace('/(\r\n)/','\par$1',$footer);

			if($this->GetOption('rtf_template_type') == 0)
			{ // Basic
				$rtf_template = file_get_contents(cms_join_path(dirname(dirname(__FILE__)),'templates',$this->GetOption('rtf_file_template','RTF_TemplateBasic.rtf')));
				$search = array("%%HEADER%%","%%FIELDS%%","%%FOOTER%%");
				$replace = array($header,$template,$footer);
				$rtf_content = str_replace($search,$replace,$rtf_template);
			}
			else if($this->GetOption('rtf_template_type') == 1)
			{ // Advanced
				$search = array("%%HEADER%%","%%FOOTER%%");
				$replace = array($header,$footer);
				$rtf_content = str_replace($search,$replace,$template);
			}

			$put = file_put_contents($filespec,$rtf_content);
		}

		if(strpos($filespec,$config['root_path']) !== FALSE)
		{
			$relurl = str_replace($config['root_path'],'',$filespec);
		}
		$url = $config['root_url'].$relurl;
		$url = str_replace("\\","/",$url);

		$this->SetValue(array($filespec,$url,$relurl,$evald_filename));

		pwfUtils::ClearFileLock();
		return array(TRUE,'');
	}

}

?>
