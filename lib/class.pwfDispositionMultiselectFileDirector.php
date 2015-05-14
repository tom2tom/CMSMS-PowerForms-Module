<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/* This file: Copyright (c) 2007 Robert Campbell <calguy1000@hotmail.com> */

class pwfDispositionMultiselectFileDirector extends pwfFieldBase
{
	var $fileCount;
	var $fileAdd;
	var $sampleTemplateCode;
	var $sampleHeader;
	var $dflt_filepath;

	function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->Type = 'DispositionMultiselectFileDirector';
		$this->IsDisposition = true;
		$this->DisplayInForm = true;
		$this->DisplayInSubmission = false;
		$this->DisplayInSubmission = true;
		$this->HasAddOp = true;
		$this->HasDeleteOp = true;
		$this->hasMultipleFormComponents = true;
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
		$displaynames = $this->GetOptionRef('destination_displayname');
		$displayfiles = $this->GetOptionRef('destination_filename');
		$displayvalues = $this->GetOptionRef('destination_value');

		$fields = array();
		for($i = 0; $i < count($displaynames); $i++)
		{
			$label = '';
			$ctrl = new stdClass();
			$ctrl->name = '<label for="'.$this->GetCSSId('_'.$i).'">'.$displaynames[$i].'</label>';
			$ctrl->title = $displaynames[$i];
			$ctrl->input = $mod->CreateInputCheckbox($id,
							 'pwfp__'.$this->Id.'[]',
							 $displayvalues[$i],
							 (is_array($this->Value) && in_array($displayvalues[$i],$this->Value))?$displayvalues[$i]:'-1',
							 $this->GetCSSIdTag('_'.$i).$js);
			$fields[] = $ctrl;
		}
		return $fields;
	}

	function GetHumanReadableValue($as_string=true)
	{
		$form = $this->formdata;
		$tmp = array();
		if(is_array($this->Value))
		{
			foreach($this->Value as $onevalue)
			{
				if(empty($onevalue)) continue;
				$tmp[] = $onevalue;
			}
		}

		if($as_string)
		{
			$tmp = join($form->GetAttr('list_delimiter',','),$tmp);
		}
		return $tmp;
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

		$mod=$this->formdata->pwfmodule;
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

		$this->formdata->setFinishedFormSmarty();
		$header = $this->GetOption('file_header','');
		if($header == '')
		{
			$header = $this->formdata->createSampleTemplate(false,false,false,true);
		}
		$header .= "\n";

		$template = $this->GetOption('file_template','');
		if($template == '')
		{
			$template = $this->formdata->createSampleTemplate();
		}

		// Begin output to files
		if(is_array($this->Value))
		{
			$displayfiles = $this->GetOptionRef('destination_filename');
			$displayvalues = $this->GetOptionRef('destination_value');

			foreach($this->Value as $onevalue)
			{
				// I dunno why it's empty sometimes, but...
				if(empty($onevalue)) continue;

				$idx = array_search($onevalue,$displayvalues);

				$tmp = $displayfiles[$idx];
				// get the filename
				$filespec = $dir.preg_replace("/[^\w\d\.]|\.\./", "_", $tmp);

				$line = $template;
				if(!file_exists($filespec))
				{
					$line = $header.$template;
				}

				$newline = $mod->ProcessTemplateFromData($line);
				if(substr($newline,-1,1) != "\n")
				{
					$newline .= "\n";
				}

				$f2 = fopen($filespec,"a");
				fwrite($f2,$newline);
				fclose($f2);

			}
		}
		$mod->ReturnFileLock();
		return array(true,'');
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
			$mod->CreateInputText($formDescriptor,
			'pwfp_opt_select_one',
			$this->GetOption('select_one',$mod->Lang('select_one')),25,128));
//	    $main[] = array($mod->Lang('title_director_details'),$dests);
		$dests = array();
		$dests[] = array(
			$mod->Lang('title_selection_displayname'),
			$mod->Lang('title_selection_value'),
			$mod->Lang('title_destination_filename'),
			$mod->Lang('title_select')
			);
		$num = ($this->fileCount>1) ? $this->fileCount:1;
		for ($i=0;$i<$num;$i++)
		{
			$dests[] = array(
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_displayname[]',$this->GetOptionElement('destination_displayname',$i),30,128),
			$mod->CreateInputText($formDescriptor, 'pwfp_opt_destination_value[]',$this->GetOptionElement('destination_value',$i),30,128),
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