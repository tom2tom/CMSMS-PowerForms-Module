<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com> 
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileDirector extends pwfFieldBase
{
	var $fileCount;
	var $fileAdd;
	var $sampleTemplateCode;
	var $sampleHeader;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'FileDirector';
		$this->fileAdd = FALSE;
	}

	function DoOptionAdd(&$params)
	{
		$this->fileAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('destination_filename',$thisVal - $delcount); //TODO
				$this->RemoveOptionElement('destination_displayname',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countFiles()
	{
		$opt = $this->GetOptionRef('destination_filename');
		if(is_array($opt))
			$this->fileCount = count($opt);
		elseif($opt !== FALSE)
			$this->fileCount = 1;
		else
			$this->fileCount = 0;
	}

	function createSampleHeader()
	{
		$fields = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->DisplayInSubmission())
				$fields[] = $one->GetName();
		}
		unset($one);
		return implode("\t",$fields);
	}

	function CreateSampleTemplate() //TODO
	{
		$fields = array();
		foreach($this->formdata->Fields as &$one)
		{
			if($one->DisplayInSubmission())
				$fields[] = '{$'.$one->GetVariableName().'}';
		}
		unset($one);
		return implode("\t",$fields);
	}

	function GetFieldStatus()
	{
		$this->countFiles();
		return $this->formdata->formsmodule->Lang('file_count',$this->fileCount);
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		$choices = array();
		$choices[' '.$this->GetOption('select_one',$mod->Lang('select_one'))]='';

		$displaynames = $this->GetOptionRef('destination_displayname');
		$c = count($displaynames;
		if($c > 1)
		{
			for($i=0; $i<$c; $i++)
				$choices[$displaynames[$i]] = ($i+1);
		}
		else
			$choices[$displaynames] = '1';

		return $mod->CreateInputDropdown($id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;

		$this->countFiles();
		if($this->fileAdd)
		{
			$this->fileCount++;
			$this->fileAdd = FALSE;
		}
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id,'opt_select_one',
					$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($id,'opt_newlinechar',
					$this->GetOption('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_displayname'),
			$mod->Lang('title_destination_filename'),
			$mod->Lang('title_select')
			);
		$num = ($this->fileCount>1) ? $this->fileCount:1;
		for ($i=0; $i<$num; $i++)
		{
			$dests[] = array(
			$mod->CreateInputText($id,'opt_destination_displayname[]',$this->GetOptionElement('destination_displayname',$i),30,128),
			$mod->CreateInputText($id,'opt_destination_filename[]',$this->GetOptionElement('destination_filename',$i),30,128),
			$mod->CreateInputCheckbox($id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}

		$adv = array();

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_header'] = TRUE;
		$parmMain['opt_file_footer']['is_oneline'] = TRUE;
		$parmMain['opt_file_footer']['is_footer'] = TRUE;
		list ($funcs,$buttons) = pwfUtils::AdminTemplateActions($this->formdata,$id,$parmMain);

		$adv[] = array($mod->Lang('title_file_template'),
				  $mod->CreateTextArea(FALSE,$id,
					htmlspecialchars($this->GetOption('file_template')),
					'opt_file_template','pwf_tallarea','','',50,15).
					'<br /><br />'.$buttons[0]);
		$adv[] = array($mod->Lang('title_file_header'),
				  $mod->CreateTextArea(FALSE,$id,
					htmlspecialchars($this->GetOption('file_header')),
					'opt_file_header','pwf_shortarea','','',50,8).
					'<br /><br />'.$buttons[1]);
		$adv[] = array($mod->Lang('title_file_footer'),
				  $mod->CreateTextArea(FALSE,$id,
				  htmlspecialchars($this->GetOption('file_footer')),
				  'opt_file_footer','pwf_shortarea','','',50,8).
				  '<br /><br />'.$buttons[2]);
		/*show variables-help on advanced tab*/
		return array('main'=>$main,'table'=>$dests,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

	function Dispose($id,$returnid)
	{
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;
		$ud = pwfUtils::GetUploadsPath();
		if(!$ud)
			return array(FALSE,$mod->Lang('error'));
//TODO mutex
		$count = 0;
		while (!pwfUtils::GetFileLock() && $count<200)
		{
			$count++;
			usleep(500);
		}
		if($count == 200)
			return array(FALSE,$mod->Lang('submission_error_file_lock'));

		$filespec = $ud.DIRECTORY_SEPARATOR.preg_replace("/[^\w\d\.]|\.\./","_",
			   $this->GetOptionElement('destination_filename',($this->Value - 1)));

		$line = '';
		if(!file_exists($filespec))
		{
			$header = $this->GetOption('file_header');
			if($header == '')
				$header = pwfUtils::CreateSampleTemplate($this->formdata,FALSE,FALSE,FALSE,TRUE);

			$header .= "\n";
		}
		$template = $this->GetOption('file_template');
		if($template == '')
			$template = pwfUtils::CreateSampleTemplate($this->formdata);
		$line = $template;

		pwfUtils::SetFinishedFormSmarty($this->formdata);
		//process without cacheing (->fetch() fails)
		$newline = $mod->ProcessTemplateFromData($line);
		$replchar = $this->GetOption('newlinechar');
		if($replchar)
		{
			$newline = rtrim($newline,"\r\n");
			$newline = preg_replace('/[\n\r]/',$replchar,$newline);
		}
		if(substr($newline,-1,1) != "\n")
			$newline .= "\n";

		$f2 = fopen($filespec,"a");
		fclose($f2);
		pwfUtils::ClearFileLock();
		return array(TRUE,'');
	}

}

?>
