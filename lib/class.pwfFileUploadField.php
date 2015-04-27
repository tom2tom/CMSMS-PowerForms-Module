<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileUploadField extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata, $params);
		$mod = $formdata->pwfmodule;
		$this->Type = 'FileUploadField';
//		$this->DisplayType = $mod->Lang('field_type_file_upload');
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->sortable = false;
	}

	function GetFieldInput($id,&$params,$returnid)
	{
		$mod = $this->formdata->pwfmodule;
		$js = $this->GetOption('javascript','');
		$txt = '';
		if($this->Value != '') $txt .= $this->GetHumanReadableValue()."<br />";	// Value line
		$txt .= $mod->CreateFileUploadInput($id,'pwfp__'.$this->Id,$js.$this->GetCSSIdTag()); // Input line
		if($this->Value != '') $txt .= $mod->CreateInputCheckbox($id, 'pwfp_delete__'.$this->Id, -1).'&nbsp;'.$mod->Lang('delete')."<br />"; // Delete line

		// Extras
		if($this->GetOption('show_details','0') == '1')
		{
			$ms = $this->GetOption('max_size');
			if($ms != '')
			{
				$txt .= ' '.$mod->Lang('maximum_size').': '.$ms.'kb';
			}
			$exts = $this->GetOption('permitted_extensions');
			if($exts != '')
			{
				$txt .= ' '.$mod->Lang('permitted_extensions') . ': '.$exts;
			}
		}
		return $txt;
	}

	function Load($id,&$params,$loadDeep=false)
	{
		$mod = $this->formdata->pwfmodule;
		parent::Load($id,$params,$loadDeep);

		if(isset($_FILES) && isset($_FILES['m1_pwfp__'.$this->Id]) && $_FILES['m1_pwfp__'.$this->Id]['size'] > 0)
		{
			// Okay, a file was uploaded
			$this->SetValue($_FILES['m1_pwfp__'.$this->Id]['name']);
		}
	}

	function GetHumanReadableValue($as_string=true)
	{
		if($this->GetOption('suppress_filename','0') != '0')
		{
			return '';
		}
		if($as_string && is_array($this->Value) && isset($this->Value[1]))
		{
			return $this->Value[1];
		}
		else
		{
			return $this->Value;
		}
	}

	function StatusInfo()
	{
		$mod = $this->formdata->pwfmodule;
		$ms = $this->GetOption('max_size','');
		$exts = $this->GetOption('permitted_extensions','');
		$ret = '';
		if($ms != '')
		{
			$ret .= $mod->Lang('maximum_size').': '.$ms.'kb, ';
		}
		if($exts != '')
		{
			$ret .= $mod->Lang('permitted_extensions') . ': '.$exts.', ';
		}
		if($this->GetOption('file_destination','') != '')
		{
			$ret .= $this->GetOption('file_destination','');
		}
		if($this->GetOption('allow_overwrite','0') != '0')
		{
			$ret .= ' '.$mod->Lang('overwrite');
		}
		else
		{
			$ret .= ' '.$mod->Lang('nooverwrite');
		}
		return $ret;
	}

	function PrePopulateAdminForm($formDescriptor)
	{
		$config = cmsms()->GetConfig();
		$mod = $this->formdata->pwfmodule;
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$show = $this->GetOption('show_details','0');
		$sendto_uploads = $this->GetOption('sendto_uploads','false');
		$uploads_category = $this->GetOption('uploads_category');
		$uploads_destpage = $this->GetOption('uploads_destpage');

		$main = array(
			array($mod->Lang('title_maximum_size'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_max_size', $ms, 5, 5),
				$mod->Lang('title_maximum_size_long')),
			array($mod->Lang('title_permitted_extensions'),
				$mod->CreateInputText($formDescriptor, 'pwfp_opt_permitted_extensions', $exts, 25, 80),
				$mod->Lang('title_permitted_extensions_long')),
			array($mod->Lang('title_show_limitations'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_show_details','0').
				$mod->CreateInputCheckbox($formDescriptor,
					'pwfp_opt_show_details', '1', $show),
				$mod->Lang('title_show_limitations_long')),
			array($mod->Lang('title_allow_overwrite'),
				$mod->CreateInputHidden($formDescriptor,'pwfp_opt_allow_overwrite','0').
				$mod->CreateInputCheckbox($formDescriptor,
					'pwfp_opt_allow_overwrite', '1', $this->GetOption('allow_overwrite','0')),
				$mod->Lang('help_allow_overwrite'))
			);

		$uploads = $mod->GetModuleInstance('Uploads');
		$sendto_uploads_list = array($mod->Lang('no')=>0,$mod->Lang('yes')=>1);
		$adv = array();

		$file_rename_help = $mod->Lang('file_rename_help').
			$this->formdata->fieldValueTemplate(array('$ext'=>$mod->Lang('original_file_extension')));

		$adv[] = array($mod->Lang('title_file_rename'),
			$mod->CreateInputText($formDescriptor,'pwfp_opt_file_rename',
				$this->GetOption('file_rename',''),60,255),
			$file_rename_help);
		$adv[] = array($mod->Lang('title_suppress_filename'),
			$mod->CreateInputHidden($formDescriptor,'pwfp_opt_suppress_filename','0').
			$mod->CreateInputCheckbox($formDescriptor,
				'pwfp_opt_suppress_filename', '1',
				$this->GetOption('suppress_filename','0')));

		$adv[] = array($mod->Lang('title_suppress_attachment'),
			$mod->CreateInputHidden($formDescriptor,'pwfp_opt_suppress_attachment',0).
				$mod->CreateInputCheckbox($formDescriptor, 'pwfp_opt_suppress_attachment', 1, $this->GetOption('suppress_attachment',1)));

		$main[] = array($mod->Lang('title_remove_file_from_server'),
			$mod->CreateInputHidden($formDescriptor,'pwfp_opt_remove_file','0').
				$mod->CreateInputCheckbox($formDescriptor,
				'pwfp_opt_remove_file', '1',
				$this->GetOption('remove_file','0')),
				$mod->Lang('help_ignored_if_upload'));
		$main[] = array($mod->Lang('title_file_destination'),
			$mod->CreateInputText($formDescriptor,'pwfp_opt_file_destination',
				$this->GetOption('file_destination',$config['uploads_path']),60,255),
				$mod->Lang('help_ignored_if_upload'));

		if($uploads)
		{
			$categorylist = $uploads->getCategoryList();
			$adv[] = array($mod->Lang('title_sendto_uploads'),
				 $mod->CreateInputDropdown($formDescriptor,
					'pwfp_opt_sendto_uploads',$sendto_uploads_list,
					 $sendto_uploads));
			$adv[] = array($mod->Lang('title_uploads_category'),
				$mod->CreateInputDropdown($formDescriptor,
					'pwfp_opt_uploads_category',$categorylist,'',
					$uploads_category));
			$adv[] = array($mod->Lang('title_uploads_destpage'),
				$mod->CreatePageDropdown($formDescriptor,
					'opt_uploads_destpage',$uploads_destpage));
		}

		return array('main'=>$main,'adv'=>$adv);
	}

	function PostDispositionAction()
	{
		if($this->GetOption('remove_file','0') == '1')
		{
			if(is_array($this->Value))
			{
				$dest = $this->Value[0];
				if(file_exists($dest))
				{
					unlink($dest);
				}
			}
		}
	}

	function Validate()
	{
		$this->validated = true;
		$this->validationErrorText = '';
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions','');
		$mod = $this->formdata->pwfmodule;
		//$fullAlias = $this->GetValue(); -- Stikki modifys: Now gets correct alias
		$fullAlias = 'm1_pwfp__'.$this->Id;
		if($_FILES[$fullAlias]['size'] < 1 && ! $this->Required)
		{
			return array(true,'');
		}
		if($_FILES[$fullAlias]['size'] < 1 && $this->Required)
		{
			$this->validated = false;
			$this->validationErrorText = $mod->Lang('required_field_missing');
		}
		else if($ms != '' && $_FILES[$fullAlias]['size'] > ($ms * 1024))
		{
			$this->validationErrorText = $mod->Lang('file_too_large'). ' '.$ms.'kb';//($ms * 1024).'kb'; // Stikki mods
			$this->validated = false;
		}
		else if($exts != '')
		{
			$match = false;
			$legalExts = explode(',',$exts);
			foreach($legalExts as $thisExt)
			{
				if(preg_match('/\.'.trim($thisExt).'$/i',$_FILES[$fullAlias]['name']))
				{
					$match = true;
				}
				else if(preg_match('/'.trim($thisExt).'/i',$_FILES[$fullAlias]['type']))
				{
					$match = true;
				}
			}
			if(!$match)
			{
				$this->validationErrorText = $mod->Lang('illegal_file_type');
				$this->validated = false;
			}
		}
		return array($this->validated, $this->validationErrorText);
	}

}

?>
