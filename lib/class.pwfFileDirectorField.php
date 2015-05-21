<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This file: Copyright (C) 2007 Robert Campbell <calguy1000@hotmail.com> */

class pwfDispositionFileDirector extends pwfFieldBase
{
	var $fileCount;
	var $fileAdd;
	var $sampleTemplateCode;
	var $sampleHeader;
	var $dflt_filepath;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionFileDirector';
		$this->IsDisposition = true;
		$this->DisplayInSubmission = false;
		$this->DisplayInForm = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->sortable = false;
		$this->fileAdd = 0;

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
			if(substr($thisKey,0,9) == 'pwfp_sel_')
			{
				$this->RemoveOptionElement('destination_filename', $thisVal - $delcount);
				$this->RemoveOptionElement('destination_displayname', $thisVal - $delcount);
				$delcount++;
			}
		}
	}

	function countFiles()
	{
		$tmp = $this->GetOptionRef('destination_filename');
		if(is_array($tmp))
		{
			$this->fileCount = count($tmp);
		}
		elseif($tmp !== false)
		{
			$this->fileCount = 1;
		}
		else
		{
			$this->fileCount = 0;
		}
	}

	function GetFieldInput($id, &$params, $returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');

		// why all this? Associative arrays are not guaranteed to preserve
		// order, except in "chronological" creation order.
		$sorted =array();
		if($this->GetOption('select_one','') != '')
		{
			$sorted[' '.$this->GetOption('select_one','')]='';
		}
		else
		{
			$sorted[' '.$mod->Lang('select_one')]='';
		}
		$displaynames = $this->GetOptionRef('destination_displayname');

		if(count($displaynames) > 1)
		{
			for($i=0;$i<count($displaynames);$i++)
			{
				$sorted[$displaynames[$i]]=($i+1);
			}
		}
		else
		{
			$sorted[$displaynames] = '1';
		}
		return $mod->CreateInputDropdown($id, 'pwfp__'.$this->Id, $sorted, -1, $this->Value, $js.$this->GetCSSIdTag());
	}

	function StatusInfo()
	{
		$this->countFiles();
		$mod=$this->formdata->pwfmodule;
		$ret= $mod->Lang('file_count',$this->fileCount);
		return $ret;
	}

	function DisposeForm($returnid)
	{
		$form=$this->formdata;
		$mod=$form->pwfmodule;

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


		$dir = $this->GetOption('file_path',$this->dflt_filepath).DIRECTORY_SEPARATOR;
		$filespec = $dir.
			preg_replace("/[^\w\d\.]|\.\./", "_",
			   $this->GetOptionElement('destination_filename',($this->Value - 1)));

		$line = '';
		if(!file_exists($filespec))
		{
			$header = $this->GetOption('file_header','');
			if($header == '')
			{
				$header = $form->createSampleTemplate(false,false,false,true);
			}
			$header .= "\n";
		}
		$template = $this->GetOption('file_template','');
		if($template == '')
		{
			$template = $form->createSampleTemplate();
		}
		$line = $template;

		$form->setFinishedFormSmarty();
		//process without cacheing (->fetch() fails)
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
		$f2 = fopen($filespec,"a");
		fclose($f2);
		$mod->ReturnFileLock();
		return array(true,'');
	}

	function createSampleHeader()
	{
		$mod = $this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		$fields = array();
		for($i=0;$i<count($others);$i++)
		{
			if($others[$i]->DisplayInSubmission())
			{
				$fields[] = $others[$i]->GetName();
			}
		}
		return implode('{$TAB}',$fields);
	}

	function createSampleTemplate()
	{
		$mod = $this->formdata->pwfmodule;
		$others = $this->formdata->GetFields();
		$fields = array();
		for($i=0;$i<count($others);$i++)
		{
			if($others[$i]->DisplayInSubmission())
			{
				$fields[] = '{$' . $others[$i]->GetVariableName() . '}';
			}
		}
		return implode('{$TAB}',$fields);
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$mod = $this->formdata->pwfmodule;

		$this->countFiles();
		if($this->fileAdd > 0)
		{
			$this->fileCount += $this->fileAdd;
			$this->fileAdd = 0;
		}
		$main = array();
		$main[] = array($mod->Lang('title_select_one_message'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_select_one',
					$this->GetOption('select_one',$mod->Lang('select_one')),30,128));
		$main[] = array($mod->Lang('title_newline_replacement'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_newlinechar',
					$this->GetOption('newlinechar',''),5,15),
				$mod->Lang('title_newline_replacement_help'));
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_displayname'),
			$mod->Lang('title_destination_filename'),
			$mod->Lang('title_select')
			);
		$num = ($this->fileCount>1) ? $this->fileCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_displayname[]',$this->GetOptionElement('destination_displayname',$i),30,128),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_filename[]',$this->GetOptionElement('destination_filename',$i),30,128),
			$mod->CreateInputCheckbox($formDescriptor, 'pwfp_sel_'.$i, $i,-1,'style="margin-left:1em;"')
			);
		}

		$adv = array();
		$adv[] = array($mod->Lang('title_file_path'),
				  $mod->CreateInputText($formDescriptor,
					'pwfp_opt_file_path',
					$this->GetOption('file_path',$this->dflt_filepath),40,128));

		$parmMain = array();
		$parmMain['opt_file_template']['is_oneline']=true;
		$parmMain['opt_file_header']['is_oneline']=true;
		$parmMain['opt_file_header']['is_header']=true;
		$parmMain['opt_file_footer']['is_oneline']=true;
		$parmMain['opt_file_footer']['is_footer']=true;
		list ($funcs, $buttons) = $this->formdata->AdminTemplateActions($formDescriptor,$parmMain);

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
		return array('main'=>$main,'table'=>$dests,'adv'=>$adv,'funcs'=>$funcs,'extra'=>'varshelpadv');
	}

}

?>
