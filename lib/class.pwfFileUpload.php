<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfFileUpload extends pwfFieldBase
{
	function __construct(&$formdata,&$params)
	{
		parent::__construct($formdata,$params);
		$this->IsSortable = FALSE;
		$this->Type = 'FileUpload';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
	}

	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->GetOption('suppress_filename',0))
			return '';
		if($as_string && is_array($this->Value) && isset($this->Value[1]))
			return $this->Value[1];
		else
			return $this->Value;
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		if(!pwfUtils::GetUploadsPath())
			return $mod->Lang('error_uploads_dir');

		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$ret = '';
		if($ms)
			$ret .= $mod->Lang('maximum_size').': '.$ms.'kb,';
		if($exts)
			$ret .= $mod->Lang('permitted_extensions') . ': '.$exts.',';
//		if($this->GetOption('file_destination'))
//			$ret .= $this->GetOption('file_destination');
		$ret .= $ud;
		if($this->GetOption('allow_overwrite',0))
			$ret .= ' '.$mod->Lang('overwrite');
		else
			$ret .= ' '.$mod->Lang('nooverwrite');
		return $ret;
	}

	function AdminPopulate($id)
	{
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$show = $this->GetOption('show_details',0);
		$sendto_uploads = $this->GetOption('sendto_uploads',0);
		$uploads_category = $this->GetOption('uploads_category');
		$uploads_destpage = $this->GetOption('uploads_destpage');

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_maximum_size'),
				$mod->CreateInputText($id,'opt_max_size',$ms,5,5),
				$mod->Lang('help_maximum_size'));
		$main[] = array($mod->Lang('title_permitted_extensions'),
				$mod->CreateInputText($id,'opt_permitted_extensions',$exts,25,80),
				$mod->Lang('help_permitted_extensions'));
		$main[] = array($mod->Lang('title_show_limitations'),
				$mod->CreateInputHidden($id,'opt_show_details',0).
				$mod->CreateInputCheckbox($id,'opt_show_details',1,$show),
				$mod->Lang('help_show_limitations'));
		$main[] = array($mod->Lang('title_allow_overwrite'),
				$mod->CreateInputHidden($id,'opt_allow_overwrite',0).
				$mod->CreateInputCheckbox($id,'opt_allow_overwrite',1,
					$this->GetOption('allow_overwrite',0)),
				$mod->Lang('help_allow_overwrite'));

		$uploads = $mod->GetModuleInstance('Uploads');
		$sendto_uploads_list = array($mod->Lang('no')=>0,$mod->Lang('yes')=>1);

		$help_file_rename = $mod->Lang('help_file_rename').
		pwfUtils::FormFieldsHelp($this->formdata,array('$ext'=>$mod->Lang('original_file_extension')));

		$adv[] = array($mod->Lang('title_file_rename'),
						$mod->CreateInputText($id,'opt_file_rename',
						$this->GetOption('file_rename'),60,255),
						$help_file_rename);
		$adv[] = array($mod->Lang('title_suppress_filename'),
						$mod->CreateInputHidden($id,'opt_suppress_filename',0).
						$mod->CreateInputCheckbox($id,'opt_suppress_filename',1,
							$this->GetOption('suppress_filename',0)));
		$adv[] = array($mod->Lang('title_suppress_attachment'),
						$mod->CreateInputHidden($id,'opt_suppress_attachment',0).
						$mod->CreateInputCheckbox($id,'opt_suppress_attachment',1,
							$this->GetOption('suppress_attachment',1)));
		$adv[] = array($mod->Lang('title_remove_file_from_server'),
						$mod->CreateInputHidden($id,'opt_remove_file',0).
						$mod->CreateInputCheckbox($id,'opt_remove_file',1,
							$this->GetOption('remove_file',0)),
						$mod->Lang('help_ignored_if_upload'));
/*		$config = cmsms()->GetConfig();
		$adv[] = array($mod->Lang('title_file_destination'),
							$mod->CreateInputText($id,'opt_file_destination',
							$this->GetOption('file_destination',$config['uploads_path']),60,255),
							$mod->Lang('help_ignored_if_upload'));
*/
		if($uploads)
		{
			$categorylist = $uploads->getCategoryList();
			$adv[] = array($mod->Lang('title_sendto_uploads'),
				 			$mod->CreateInputDropdown($id,'opt_sendto_uploads',$sendto_uploads_list,
							 $sendto_uploads));
			$adv[] = array($mod->Lang('title_uploads_category'),
							$mod->CreateInputDropdown($id,'opt_uploads_category',$categorylist,'',
							$uploads_category));
			$adv[] = array($mod->Lang('title_uploads_destpage'),
							self::CreatePageDropdown($id,'opt_uploads_destpage',$uploads_destpage));
		}

		return array('main'=>$main,'adv'=>$adv);
	}

	function Load($id,&$params)
	{
		$ret = parent::Load($id,$params);
		if(isset($_FILES))
		{
			$key = $id.$this->formdata->current_prefix.$this->Id;
			if(!isset($_FILES[$key]))
				$key = $id.$this->formdata->prior_prefix.$this->Id;
			if(isset($_FILES[$key]) && $_FILES[$key]['size'] > 0) // file was uploaded
				$this->SetValue($_FILES[$key]['name']);
		}
		return $ret;
	}

	function CreatePageDropdown($id,$name,$current='',$addtext='',$markdefault=TRUE)
	{
		// we get here (hopefully) when the template is changed in the dropdown
		$defaultid = '';
		if($markdefault)
		{
			$contentops = cmsms()->GetContentOperations();
			$defaultid = $contentops->GetDefaultPageID();
		}

		// get a list of the pages used by this template
		$db = cmsms()->GetDb();
		$sql = 'SELECT content_name,content_id FROM '.cms_db_prefix().
			'content WHERE type = \'content\' AND active = 1 ORDER BY content_name';
		$allpages = $db->GetAssoc($sql);
		if($allpages && $defaultid)
		{
			$key = array_search($defaultid,$allpages);
			if($key !== FALSE)
			{
				unset($allpages[$key]);
				$allpages = array($key.' (*)' => $defaultid) + $allpages;
			}
		}
		return $this->formdata->formsmodule->CreateInputDropdown($id,$name,$allpages,-1,$current,$addtext);
	}

	function Populate($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$txt = '';
		if($this->Value)
			$txt .= $this->GetHumanReadableValue().'<br />'; // Value line
		$txt .= $mod->CreateFileUploadInput(
			$id,$this->formdata->current_prefix.$this->Id,
			'id="'.$this->GetInputId().'"'.$this->GetScript()); // Input line
		if($this->Value)
			$txt .= $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.'delete__'.$this->Id,-1). //TODO is this used?
				'&nbsp;'.$mod->Lang('delete').'<br />'; // Delete line

		// Extras
		if($this->GetOption('show_details',0))
		{
			$ms = $this->GetOption('max_size');
			if($ms)
				$txt .= ' '.$mod->Lang('maximum_size').': '.$ms.'kB';
			$exts = $this->GetOption('permitted_extensions');
			if($exts)
				$txt .= ' '.$mod->Lang('permitted_extensions').': '.$exts;
		}
		return $txt;
	}

/* TODO
	// Ryan's ugly fix for Bug 4307
	// We should figure out why this field wasn't populating its Smarty variable
	if($one->GetFieldType() == 'FileUpload') //TODO
	{
		$smarty->assign('fld_'.$one->GetId(),$one->GetHumanReadableValue());
		$hidden .= $this->CreateInputHidden($id,
			$testIndex,
			pwfUtils::html_myentities_decode($one->GetHumanReadableValue()));
		$thisAtt = $one->GetHumanReadableValue(FALSE);
		$smarty->assign('test_'.$one->GetId(),$thisAtt);
		$smarty->assign('value_fld'.$one->GetId(),$thisAtt[0]);
	}
*/

	function Validate($id)
	{
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		$_id = $id.$this->formdata->current_prefix.$this->Id;
		if(empty($_FILES[$_id]))
			$_id = $id.$this->formdata->prior_prefix.$this->Id;
		if(empty($_FILES[$_id]))
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('file'));
			return array($this->validated,$this->ValidationMessage);
		}
		if($_FILES[$_id]['size'] < 1 && ! $this->Required)
			return array(TRUE,'');

		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		if($_FILES[$_id]['size'] < 1 && $this->Required)
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('required_field_missing');
		}
		elseif($ms && $_FILES[$_id]['size'] > ($ms * 1024))
		{
			$this->ValidationMessage = $mod->Lang('error_large_file'). ' '.$ms.'kb';//($ms * 1024).'kb'; // Stikki mods
			$this->validated = FALSE;
		}
		elseif($exts)
		{
			$match = FALSE;
			$legalExts = explode(',',$exts);
			foreach($legalExts as $thisExt)
			{
				if(preg_match('/\.'.trim($thisExt).'$/i',$_FILES[$_id]['name']))
					$match = TRUE;
				else if(preg_match('/'.trim($thisExt).'/i',$_FILES[$_id]['type']))
					$match = TRUE;
			}
			if(!$match)
			{
				$this->ValidationMessage = $mod->Lang('illegal_file_type');
				$this->validated = FALSE;
			}
		}
		return array($this->validated,$this->ValidationMessage);
	}

	/*
	If the 'uploads' module is present,and the option is checked in the field,
	then the file is added to the uploads module and a link is added to the results.
	Otherwise, upload the file to the "uploads" directory.
	*/
	function Dispose($id,$returnid)
	{
		$_id = $id.$this->formdata->current_prefix.$this->Id;
		if(empty($_FILES[$_id]))
			$_id = $id.$this->formdata->prior_prefix.$this->Id;
		if(isset($_FILES[$_id]) && $_FILES[$_id]['size'] > 0)
		{
			$config = cmsms()->GetConfig();
			$mod = $this->formdata->formsmodule;

			$thisFile =& $_FILES[$_id];
			$thisExt = substr($thisFile['name'],strrpos($thisFile['name'],'.'));

			if($this->GetOption('file_rename') == '')
				$destination_name = $thisFile['name'];
			else
			{
				$fids = array();
				$destination_name = $this->GetOption('file_rename');
				//TODO if fields not named like '$fld_N' in the string ?
				preg_match_all('/\$fld_(\d+)/',$destination_name,$fids);
				foreach($fids[1] as $field_id)
				{
					if(array_key_exists($field_id,$this->formdata->Fields))
					{
						$destination_name = str_replace('$fld_'.$field_id,
							 $this->formdata->Fields[$field_id]->GetHumanReadableValue(),$destination_name);
					}
				}
				$destination_name = str_replace('$ext',$thisExt,$destination_name);
			}

			if($this->GetOption('sendto_uploads'))
			{
				// we have a file we can send to the uploads
				$uploads = $mod->GetModuleInstance('Uploads');
				if(!$uploads)
				{
					// no uploads module
					return array(FALSE,$mod->Lang('error_module_upload'));
				}

				$parms = array();
				$parms['input_author'] = $mod->Lang('anonymous');
				$parms['input_summary'] = $mod->Lang('title_uploadmodule_summary');
				$parms['category_id'] = $this->GetOption('uploads_category');
				$parms['field_name'] = $_id;
				$parms['input_destname'] = $destination_name;
				if($this->GetOption('allow_overwrite',0))
				{
					$parms['input_replace'] = 1;
				}
				$res = $uploads->AttemptUpload(-1,$parms,-1);

				if($res[0] == FALSE)
				{
					// failed upload kills the send
					return array(FALSE,$mod->Lang('uploads_error',$res[1]));
				}

				$uploads_destpage = $this->GetOption('uploads_destpage');
				$url = $uploads->CreateLink($parms['category_id'],'getfile',$uploads_destpage,'',
					array ('upload_id' => $res[1]),'',TRUE);

				$url = str_replace('admin/moduleinterface.php?','index.php?',$url);

				$this->ResetValue();
				$this->SetValue($url);
			}
			else //we will upload
			{
				$ud = pwfUtils::GetUploadsPath();
				if(!$ud)
					return array(FALSE,'error_uploads_dir');
				
				$src = $thisFile['tmp_name'];
				//$dest_path = $this->GetOption('file_destination',$config['uploads_path']);
				// validated message before,now do it for the file itself
				$valid = TRUE;
				$ms = $this->GetOption('max_size');
				$exts = $this->GetOption('permitted_extensions');
				if($ms && $thisFile['size'] > ($ms * 1024))
				{
					$valid = FALSE;
				}
				elseif($exts)
				{
					$match = FALSE;
					$legalExts = explode(',',$exts);
					foreach($legalExts as $thisExt)
					{
						if(preg_match('/\.'.trim($thisExt).'$/i',$thisFile['name']))
						{
							$match = TRUE;
						}
						else if(preg_match('/'.trim($thisExt).'/i',$thisFile['type']))
						{
							$match = TRUE;
						}
					}
					if(!$match)
					{
						$valid = FALSE;
					}
				}

				if(!$valid)
				{
					unlink($src);
					return array(FALSE,$mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
				}

				$dest = $ud.DIRECTORY_SEPARATOR.$destination_name;
				if(file_exists($dest) && !$this->GetOption('allow_overwrite',0))
				{
					unlink($src);
					return array(FALSE,$mod->Lang('error_file_exists',array($destination_name)));
				}

				if(move_uploaded_file($src,$dest))
				{
/*
//$TODO $config = ?
					if(strpos($ud,$config['root_path']) !== FALSE)
					{
						$url = str_replace($config['root_path'],'',$ud).DIRECTORY_SEPARATOR.$destination_name;
					}
					else
					{
						$url = $mod->Lang('uploaded_outside_webroot',$destination_name);
					}
					//$this->ResetValue();
					//$this->SetValue(array($dest,$url));
*/
				}
				else
				{
					return array(FALSE,$mod->Lang('uploads_error',''));
				}
			}
		}

		return array(TRUE,'');
	}

	function PostDisposeAction()
	{
		if($this->GetOption('remove_file',0))
		{
			if(is_array($this->Value))
			{
				$dest = $this->Value[0];
				if(is_file($dest))
					unlink($dest);
			}
		}
	}

}

?>
