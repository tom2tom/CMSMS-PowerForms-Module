<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com> 
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileDirectorField extends pwfFieldBase
{
	var $fileCount;
	var $fileAdd;
	var $sampleTemplateCode;
	var $sampleHeader;
	var $dflt_filepath;

	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->DisplayInSubmission = FALSE;
		$this->HasAddOp = TRUE;
		$this->HasDeleteOp = TRUE;
		$this->IsDisposition = TRUE;
		$this->Type = 'FileDirectorField';
		$this->fileAdd = 0;
		$this->IsSortable = FALSE;

		$config = cmsms()->getConfig();
		$this->dflt_filepath = $config['uploads_path'];
	}

	function DoOptionAdd(&$params)
	{
		$this->fileAdd = 1;
	}

	function DoOptionDelete(&$params)
	{
		$delcount = 0;
		foreach($params as $thisKey=>$thisVal)
		{
			if(substr($thisKey,0,8) == 'opt_sel_')
			{
				$this->RemoveOptionElement('destination_filename',$thisVal - $delcount);
				$this->RemoveOptionElement('destination_displayname',$thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countFiles()
	{
		$tmp = $this->GetOptionRef('destination_filename');
		if(is_array($tmp))
			$this->fileCount = count($tmp);
		elseif($tmp !== FALSE)
			$this->fileCount = 1;
		else
			$this->fileCount = 0;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');

		// why all this? Associative arrays are not guaranteed to preserve
		// order,except in "chronological" creation order.
		$sorted =array();
		if($this->GetOption('select_one'))
			$sorted[' '.$this->GetOption('select_one')]='';
		else
			$sorted[' '.$mod->Lang('select_one')]='';

		$displaynames = $this->GetOptionRef('destination_displayname');

		if(count($displaynames) > 1)
		{
			for($i=0; $i<count($displaynames); $i++)
			{
				$sorted[$displaynames[$i]] = ($i+1);
			}
		}
		else
		{
			$sorted[$displaynames] = '1';
		}
		return $mod->CreateInputDropdown($id,'pwfp_'.$this->Id,$sorted,-1,$this->Value,$js.$this->GetCSSIdTag());
	}

	function GetFieldStatus()
	{
		$this->countFiles();
		$mod=$this->formdata->formsmodule;
		$ret= $mod->Lang('file_count',$this->fileCount);
		return $ret;
	}

	function DisposeForm($returnid)
	{
		$formdata = $this->formdata;
		$mod = $formdata->formsmodule;

		$count = 0;
		while (!pwfUtils::GetFileLock() && $count<200)
		{
			$count++;
			usleep(500);
		}
		if($count == 200)
		{
			return array(FALSE,$mod->Lang('submission_error_file_lock'));
		}


		$dir = $this->GetOption('file_path',$this->dflt_filepath).DIRECTORY_SEPARATOR;
		$filespec = $dir.
			preg_replace("/[^\w\d\.]|\.\./","_",
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

	function createSampleHeader()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
		$fields = array();
		for($i=0; $i<count($others); $i++)
		{
			if($others[$i]->DisplayInSubmission())
			{
				$fields[] = $others[$i]->GetName();
			}
		}
		return implode('{$TAB}',$fields);
	}

TODO	function CreateSampleTemplate()
	{
		$mod = $this->formdata->formsmodule;
		$others = $this->formdata->Fields;
		$fields = array();
		for($i=0; $i<count($others); $i++)
		{
			if($others[$i]->DisplayInSubmission())
			{
				$fields[] = '{$' . $others[$i]->GetVariableName() . '}';
			}
		}
		return implode('{$TAB}',$fields);
	}

	function PrePopulateAdminForm($module_id)
	{
		$mod = $this->formdata->formsmodule;

		$this->countFiles();
		if($this->fileAdd > 0)
		{
			$this->fileCount += $this->fileAdd;
			$this->fileAdd = 0;
		}
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($module_id,'opt_select_one',
					$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($module_id,'opt_newlinechar',
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
			$mod->CreateInputText($module_id,'opt_destination_displayname[]',$this->GetOptionElement('destination_displayname',$i),30,128),
			$mod->CreateInputText($module_id,'opt_destination_filename[]',$this->GetOptionElement('destination_filename',$i),30,128),
			$mod->CreateInputCheckbox($module_id,'opt_sel_'.$i,$i,-1,'style="margin-left:1em;"')
			);
		}

		$adv = array();
		$adv[] = array($mod->Lang('title_file_path'),
				  $mod->CreateInputText($module_id,
					'opt_file_path',
					$this->GetOption('file_path',$this->dflt_filepath),40,128));

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_oneline'] = TRUE;
		$parmMain['opt_file_header']['is_header'] = TRUE;
		$parmMain['opt_file_footer']['is_oneline'] = TRUE;
		$parmMain['opt_file_footer']['is_footer'] = TRUE;
		list ($funcs,$buttons) = pwfUtils::AdminTemplateActions($this->formdata,$module_id,$parmMain);

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
		return array('main'=>$main,'table'=>$dests,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

}

?>
