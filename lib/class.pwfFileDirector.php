<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com> 
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileDirector extends pwfFieldBase
{
	var $fileAdd = FALSE;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->IsSortable = FALSE;
		$this->Type = 'FileDirector';
	}

	function GetOptionAddButton()
	{
		return $this->formdata->formsmodule->Lang('add_file'); //TODO trans
	}

	function GetOptionDeleteButton()
	{
		return $this->formdata->formsmodule->Lang('delete_file'); //TODO trans
	}

	function DoOptionAdd(&$params)
	{
		$this->fileAdd = TRUE;
	}

	function DoOptionDelete(&$params)
	{
		if(isset($params['selected']))
		{
			foreach($params['selected'] as $indx)
			{
				$this->RemoveOptionElement('destination_filename',$indx);
				$this->RemoveOptionElement('destination_displayname',$indx);
			}
		}
	}

	function CreateSampleHeader()
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

	function CreateSampleTemplate()
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
		$ud = pwfUtils::GetUploadsPath();
		if(!$ud)
			return $mod->Lang('err_TODO');
		$opt = $this->GetOptionRef('destination_filename');
		if($opt)
			$fileCount = count($opt);
		else
			$fileCount = 0;
		return $this->formdata->formsmodule->Lang('file_count',$fileCount);
	}

	function PrePopulateAdminForm($id)
	{
		$mod = $this->formdata->formsmodule;
		$ud = pwfUtils::GetUploadsPath();
		if(!$ud)
			return array('main'=>array($mod->Lang('err_TODO'),''));

		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($id,'opt_select_one',
					$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($id,'opt_newlinechar',
					$this->GetOption('newlinechar'),5,15),
				$mod->Lang('help_newline_replacement'));

		$dests = array();
		if($this->fileAdd)
		{
			$this->AddOptionElement('destination_filename','');
			$this->AddOptionElement('destination_displayname','');
			$this->fileAdd = FALSE;
		}
		$opt = $this->GetOptionRef('destination_filename');
		if($opt)
		{
			$dests[] = array(
				$mod->Lang('title_selection_displayname'),
				$mod->Lang('title_destination_filename'),
				$mod->Lang('title_select')
				);
			foreach($opt as $i=>&$one)
			{
				$dests[] = array(
				$mod->CreateInputText($id,'opt_destination_displayname'.$i,$this->GetOptionElement('destination_displayname',$i),30,128),
				$mod->CreateInputText($id,'opt_destination_filename'.$i,$one,30,128),
				$mod->CreateInputCheckbox($id,'selected[]',$i,-1,'style="margin-left:1em;"')
				);
			}
			unset($one);
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

	function Populate($id,&$params)
	{
		$names = $this->GetOptionRef('destination_displayname');
		if($names)
		{
			$mod = $this->formdata->formsmodule;
			$choices = array(' '.$this->GetOption('select_one',$mod->Lang('select_one'))=>'')
				+ array_flip($names);
			$tmp = $mod->CreateInputDropdown(
				$id,$this->formdata->current_prefix.$this->Id,$choices,-1,$this->Value,
				$this->GetScript());
			return preg_replace('/id="\S+"/','id="'.$this->GetInputId().'"',$tmp);
		}
		return '';
	}

	function Dispose($id,$returnid)
	{
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

		pwfUtils::SetupFormVars($this->formdata);

		$fn = preg_replace('/[^\w\d\.]|\.\./','_',
			   $this->GetOptionElement('destination_filename',$this->Value));
		$fp = $ud.DIRECTORY_SEPARATOR.$fn;

		$footer = $this->GetOption('file_footer');
		if($footer)
			$footer = $mod->ProcessTemplateFromData($footer);

		$template = $this->GetOption('file_template');
		if(!$template)
			$template = $this->CreateSampleTemplate();

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
				$header = $mod->ProcessTemplateFromData($header);
			else
				$header = $this->CreateSampleHeader();
			fwrite($fh,$header."\n".$newline.$footer);
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
//TODO mutex
		pwfUtils::ClearFileLock();
		return array(TRUE,'');
	}

}

?>
