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
		parent::__construct($formdata,$params);
		$this->Type = 'FileUploadField';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array($mod->Lang('validation_none')=>'none');
		$this->IsSortable = FALSE;
	}

	function GetFieldInput($id,&$params)
	{
		$mod = $this->formdata->formsmodule;
		$js = $this->GetOption('javascript');
		$txt = '';
		if($this->Value)
			$txt .= $this->GetHumanReadableValue().'<br />';	// Value line
		$txt .= $mod->CreateFileUploadInput($id,'pwfp_'.$this->Id,$js.$this->GetCSSIdTag()); // Input line
		if($this->Value)
			$txt .= $mod->CreateInputCheckbox($id,'pwfp_delete__'.$this->Id,-1). //TODO is this used?
			'&nbsp;'.$mod->Lang('delete').'<br />'; // Delete line

		// Extras
		if($this->GetOption('show_details','0') == '1')
		{
			$ms = $this->GetOption('max_size');
			if($ms)
				$txt .= ' '.$mod->Lang('maximum_size').': '.$ms.'kB';
			$exts = $this->GetOption('permitted_extensions');
			if($exts)
				$txt .= ' '.$mod->Lang('permitted_extensions') . ': '.$exts;

		}
		return $txt;
	}

	function Load($id,&$params,$loadDeep=FALSE)
	{
		$mod = $this->formdata->formsmodule;
		parent::Load($id,$params,$loadDeep);

TODO ID		if(isset($_FILES) && isset($_FILES['m1_pwfp__'.$this->Id]) && $_FILES['m1_pwfp__'.$this->Id]['size'] > 0)
		{
			// Okay,a file was uploaded
			$this->SetValue($_FILES['m1_pwfp__'.$this->Id]['name']);
		}
	}

/* TODO
		// Ryan's ugly fix for Bug 4307
		// We should figure out why this field wasn't populating its Smarty variable
		if($one->GetFieldType() == 'FileUploadField') //TODO
		{
			$smarty->assign('fld_'.$one->GetId(),$one->GetHumanReadableValue());
			$hidden .= $this->CreateInputHidden($id,
				$testIndex,
				pwfUtils::unmy_htmlentities($one->GetHumanReadableValue()));
			$thisAtt = $one->GetHumanReadableValue(FALSE);
			$smarty->assign('test_'.$one->GetId(),$thisAtt);
			$smarty->assign('value_fld'.$one->GetId(),$thisAtt[0]);
		}

*/
	function GetHumanReadableValue($as_string=TRUE)
	{
		if($this->GetOption('suppress_filename','0'))
			return '';
		if($as_string && is_array($this->Value) && isset($this->Value[1]))
			return $this->Value[1];
		else
			return $this->Value;
	}

	function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$ret = '';
		if($ms)
			$ret .= $mod->Lang('maximum_size').': '.$ms.'kb,';
		if($exts)
			$ret .= $mod->Lang('permitted_extensions') . ': '.$exts.',';
		if($this->GetOption('file_destination'))
			$ret .= $this->GetOption('file_destination');
		if($this->GetOption('allow_overwrite','0') != '0')
			$ret .= ' '.$mod->Lang('overwrite');
		else
			$ret .= ' '.$mod->Lang('nooverwrite');
		return $ret;
	}

	function PrePopulateAdminForm($module_id)
	{
		$config = cmsms()->GetConfig();
		$mod = $this->formdata->formsmodule;
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$show = $this->GetOption('show_details','0');
		$sendto_uploads = $this->GetOption('sendto_uploads','FALSE');
		$uploads_category = $this->GetOption('uploads_category');
		$uploads_destpage = $this->GetOption('uploads_destpage');

		$main = array(
			array($mod->Lang('title_maximum_size'),
				$mod->CreateInputText($module_id,'opt_max_size',$ms,5,5),
				$mod->Lang('help_maximum_size')),
			array($mod->Lang('title_permitted_extensions'),
				$mod->CreateInputText($module_id,'opt_permitted_extensions',$exts,25,80),
				$mod->Lang('help_permitted_extensions')),
			array($mod->Lang('title_show_limitations'),
				$mod->CreateInputHidden($module_id,'opt_show_details','0').
				$mod->CreateInputCheckbox($module_id,
					'opt_show_details','1',$show),
				$mod->Lang('help_show_limitations')),
			array($mod->Lang('title_allow_overwrite'),
				$mod->CreateInputHidden($module_id,'opt_allow_overwrite','0').
				$mod->CreateInputCheckbox($module_id,
					'opt_allow_overwrite','1',$this->GetOption('allow_overwrite','0')),
				$mod->Lang('help_allow_overwrite'))
			);

		$uploads = $mod->GetModuleInstance('Uploads');
		$sendto_uploads_list = array($mod->Lang('no')=>0,$mod->Lang('yes')=>1);
		$adv = array();

		$help_file_rename = $mod->Lang('help_file_rename').
		pwfUtils::fieldValueTemplate($this->formdata,array('$ext'=>$mod->Lang('original_file_extension')));

		$adv[] = array($mod->Lang('title_file_rename'),
			$mod->CreateInputText($module_id,'opt_file_rename',
				$this->GetOption('file_rename'),60,255),
			$help_file_rename);
		$adv[] = array($mod->Lang('title_suppress_filename'),
			$mod->CreateInputHidden($module_id,'opt_suppress_filename','0').
			$mod->CreateInputCheckbox($module_id,
				'opt_suppress_filename','1',
				$this->GetOption('suppress_filename','0')));

		$adv[] = array($mod->Lang('title_suppress_attachment'),
			$mod->CreateInputHidden($module_id,'opt_suppress_attachment',0).
				$mod->CreateInputCheckbox($module_id,'opt_suppress_attachment',1,$this->GetOption('suppress_attachment',1)));

		$main[] = array($mod->Lang('title_remove_file_from_server'),
			$mod->CreateInputHidden($module_id,'opt_remove_file','0').
				$mod->CreateInputCheckbox($module_id,
				'opt_remove_file','1',
				$this->GetOption('remove_file','0')),
				$mod->Lang('help_ignored_if_upload'));
		$main[] = array($mod->Lang('title_file_destination'),
			$mod->CreateInputText($module_id,'opt_file_destination',
				$this->GetOption('file_destination',$config['uploads_path']),60,255),
				$mod->Lang('help_ignored_if_upload'));

		if($uploads)
		{
			$categorylist = $uploads->getCategoryList();
			$adv[] = array($mod->Lang('title_sendto_uploads'),
				 $mod->CreateInputDropdown($module_id,
					'opt_sendto_uploads',$sendto_uploads_list,
					 $sendto_uploads));
			$adv[] = array($mod->Lang('title_uploads_category'),
				$mod->CreateInputDropdown($module_id,
					'opt_uploads_category',$categorylist,'',
					$uploads_category));
			$adv[] = array($mod->Lang('title_uploads_destpage'),
				self::CreatePageDropdown($module_id,'opt_uploads_destpage',$uploads_destpage));
		}

		return array('main'=>$main,'adv'=>$adv);
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
TODO MOD	return $mod->CreateInputDropdown($id,$name,$allpages,-1,$current,$addtext);
	}

	/*
	If the 'uploads' module is present,and the option is checked in the field,
	then the file is added to the uploads module and a link is added to the results
	If the option is not checked,then the file is uploaded to the "uploads" directory
	*/
	function DisposeForm($returnid)
	{
		$_id = 'm1_pwfp__'.$this->GetId();
		if(isset($_FILES[$_id]) && $_FILES[$_id]['size'] > 0)
		{
			$config = cmsms()->GetConfig();
			$mod = $this->formdata->formsmodule;

			$thisFile =& $_FILES[$_id];
			$thisExt = substr($thisFile['name'],strrpos($thisFile['name'],'.'));

			if($this->GetOption('file_rename') == '')
			{
				$destination_name = $thisFile['name'];
			}
			else
			{
				// build rename map
				$mapId = array();
				$eval_string = FALSE;
				$i = 0;
				foreach($this->formdata->Fields as &$one)
				{
					$mapId[$one->GetId()] = $i;
					$i++;
				}
				unset($one);

				$flds = array();
				$destination_name = $this->GetOption('file_rename');
				preg_match_all('/\$fld_(\d+)/',$destination_name,$flds);
				foreach($flds[1] as $tF)
				{
					if(isset($mapId[$tF]))
					{
						$ref = $mapId[$tF];
						$destination_name = str_replace('$fld_'.$tF,
							 $this->Fields[$ref]->GetHumanReadableValue(),$destination_name);
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
					audit(-1,$mod->GetName(),$mod->Lang('submit_error'),$mail->GetErrorInfo());
					return array($res,$mod->Lang('error_module_upload'));
				}

				$parms = array();
				$parms['input_author'] = $mod->Lang('anonymous');
				$parms['input_summary'] = $mod->Lang('title_uploadmodule_summary');
				$parms['category_id'] = $this->GetOption('uploads_category');
				$parms['field_name'] = $_id;
				$parms['input_destname'] = $destination_name;
				if($this->GetOption('allow_overwrite','0') == '1')
				{
					$parms['input_replace'] = 1;
				}
				$res = $uploads->AttemptUpload(-1,$parms,-1);

				if($res[0] == FALSE)
				{
					// failed upload kills the send.
					audit(-1,$mod->GetName(),$mod->Lang('submit_error',$res[1]));
					return array($res[0],$mod->Lang('uploads_error',$res[1]));
				}

				$uploads_destpage = $this->GetOption('uploads_destpage');
				$url = $uploads->CreateLink ($parms['category_id'],'getfile',$uploads_destpage,'',
					array ('upload_id' => $res[1]),'',TRUE);

				$url = str_replace('admin/moduleinterface.php?','index.php?',$url);

				$this->ResetValue();
				$this->SetValue($url);
			}
			else
			{
				// Handle the upload ourselves
				$src = $thisFile['tmp_name'];
				$dest_path = $this->GetOption('file_destination',$config['uploads_path']);

				// validated message before,now do it for the file itself
				$valid = TRUE;
				$ms = $this->GetOption('max_size');
				$exts = $this->GetOption('permitted_extensions');
				if($ms && $thisFile['size'] > ($ms * 1024))
				{
					$valid = FALSE;
				}
				else if($exts)
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
					audit(-1,$mod->GetName(),$mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
					return array(FALSE,'');
				}
				$dest = $dest_path.DIRECTORY_SEPARATOR.$destination_name;
				if(file_exists($dest) && $this->GetOption('allow_overwrite','0')=='0')
				{
					unlink($src);
					return array(FALSE,$mod->Lang('error_file_exists',array($destination_name)));
				}
				if(!move_uploaded_file($src,$dest))
				{
					audit(-1,$mod->GetName(),$mod->Lang('submit_error',''));
					return array(FALSE,$mod->Lang('uploads_error',''));
				}
				else
				{
					if(strpos($dest_path,$config['root_path']) !== FALSE)
					{
						$url = str_replace($gCms->config['root_path'],'',$dest_path).DIRECTORY_SEPARATOR.$destination_name;
					}
					else
					{
						$url = $mod->Lang('uploaded_outside_webroot',$destination_name);
					}
					//$this->ResetValue();
					//$this->SetValue(array($dest,$url));
				}
			}
		}

		return array(TRUE,'');
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
		$this->validated = TRUE;
		$this->ValidationMessage = '';
		$ms = $this->GetOption('max_size');
		$exts = $this->GetOption('permitted_extensions');
		$mod = $this->formdata->formsmodule;
		//$fullAlias = $this->GetValue(); -- Stikki modifys: Now gets correct alias
		$fullAlias = 'm1_pwfp__'.$this->Id;
		if($_FILES[$fullAlias]['size'] < 1 && ! $this->Required)
		{
			return array(TRUE,'');
		}
		if($_FILES[$fullAlias]['size'] < 1 && $this->Required)
		{
			$this->validated = FALSE;
			$this->ValidationMessage = $mod->Lang('required_field_missing');
		}
		else if($ms && $_FILES[$fullAlias]['size'] > ($ms * 1024))
		{
			$this->ValidationMessage = $mod->Lang('error_large_file'). ' '.$ms.'kb';//($ms * 1024).'kb'; // Stikki mods
			$this->validated = FALSE;
		}
		else if($exts)
		{
			$match = FALSE;
			$legalExts = explode(',',$exts);
			foreach($legalExts as $thisExt)
			{
				if(preg_match('/\.'.trim($thisExt).'$/i',$_FILES[$fullAlias]['name']))
					$match = TRUE;
				else if(preg_match('/'.trim($thisExt).'/i',$_FILES[$fullAlias]['type']))
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

}

?>
