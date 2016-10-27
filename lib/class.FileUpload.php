<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class FileUpload extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata,$params);
		$this->Type = 'FileUpload';
		$mod = $formdata->formsmodule;
		$this->ValidationTypes = array(); //$mod->Lang('validation_none')=>'none');
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		if ($this->GetProperty('suppress_filename',0))
			return '';
		if ($as_string && is_array($this->Value) && isset($this->Value[1]))
			return $this->Value[1];
		else
			return $this->Value;
	}

	public function GetFieldStatus()
	{
		$mod = $this->formdata->formsmodule;
		if (!Utils::GetUploadsPath($mod))
			return $mod->Lang('err_uploads_dir');

		$ms = $this->GetProperty('max_size');
		$exts = $this->GetProperty('permitted_extensions');
		$ret = '';
		if ($ms)
			$ret .= $mod->Lang('maximum_size').': '.$ms.'kb,';
		if ($exts)
			$ret .= $mod->Lang('permitted_extensions') . ': '.$exts.',';
//		if ($this->GetProperty('file_destination'))
//			$ret .= $this->GetProperty('file_destination');
		$ret .= $ud;
		if ($this->GetProperty('allow_overwrite',0))
			$ret .= ' '.$mod->Lang('overwrite');
		else
			$ret .= ' '.$mod->Lang('nooverwrite');
		return $ret;
	}

	public function AdminPopulate($id)
	{
		$ms = $this->GetProperty('max_size');
		$exts = $this->GetProperty('permitted_extensions');
		$show = $this->GetProperty('show_details',0);
		$sendto_uploads = $this->GetProperty('sendto_uploads',0);
		$uploads_category = $this->GetProperty('uploads_category');
		$uploads_destpage = $this->GetProperty('uploads_destpage');

		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$main[] = array($mod->Lang('title_maximum_size'),
				$mod->CreateInputText($id,'pdt_max_size',$ms,5,5),
				$mod->Lang('help_maximum_size'));
		$main[] = array($mod->Lang('title_permitted_extensions'),
				$mod->CreateInputText($id,'pdt_permitted_extensions',$exts,25,80),
				$mod->Lang('help_permitted_extensions'));
		$main[] = array($mod->Lang('title_show_limitations'),
				$mod->CreateInputHidden($id,'pdt_show_details',0).
				$mod->CreateInputCheckbox($id,'pdt_show_details',1,$show),
				$mod->Lang('help_show_limitations'));
		$main[] = array($mod->Lang('title_allow_overwrite'),
				$mod->CreateInputHidden($id,'pdt_allow_overwrite',0).
				$mod->CreateInputCheckbox($id,'pdt_allow_overwrite',1,
					$this->GetProperty('allow_overwrite',0)),
				$mod->Lang('help_allow_overwrite'));

		$uploads = $mod->GetModuleInstance('Uploads');
		$sendto_uploads_list = array($mod->Lang('no')=>0,$mod->Lang('yes')=>1);

		$help_file_rename = $mod->Lang('help_file_rename').
		Utils::FormFieldsHelp($this->formdata,array('$ext'=>$mod->Lang('original_file_extension')));

		$adv[] = array($mod->Lang('title_file_rename'),
						$mod->CreateInputText($id,'pdt_file_rename',
						$this->GetProperty('file_rename'),60,255),
						$help_file_rename);
		$adv[] = array($mod->Lang('title_suppress_filename'),
						$mod->CreateInputHidden($id,'pdt_suppress_filename',0).
						$mod->CreateInputCheckbox($id,'pdt_suppress_filename',1,
							$this->GetProperty('suppress_filename',0)));
		$adv[] = array($mod->Lang('title_suppress_attachment'),
						$mod->CreateInputHidden($id,'pdt_suppress_attachment',0).
						$mod->CreateInputCheckbox($id,'pdt_suppress_attachment',1,
							$this->GetProperty('suppress_attachment',1)));
		$adv[] = array($mod->Lang('title_remove_file_from_server'),
						$mod->CreateInputHidden($id,'pdt_remove_file',0).
						$mod->CreateInputCheckbox($id,'pdt_remove_file',1,
							$this->GetProperty('remove_file',0)),
						$mod->Lang('help_ignored_if_upload'));
/*		$config = \cmsms()->GetConfig();
		$adv[] = array($mod->Lang('title_file_destination'),
							$mod->CreateInputText($id,'pdt_file_destination',
							$this->GetProperty('file_destination',$config['uploads_path']),60,255),
							$mod->Lang('help_ignored_if_upload'));
*/
		if ($uploads) {
			$categorylist = $uploads->getCategoryList();
			$adv[] = array($mod->Lang('title_sendto_uploads'),
				 			$mod->CreateInputDropdown($id,'pdt_sendto_uploads',$sendto_uploads_list,
							$sendto_uploads));
			$adv[] = array($mod->Lang('title_uploads_category'),
							$mod->CreateInputDropdown($id,'pdt_uploads_category',$categorylist,'',
							$uploads_category));
			$adv[] = array($mod->Lang('title_uploads_destpage'),
							self::CreatePageDropdown($id,'pdt_uploads_destpage',$uploads_destpage));
		}

		return array('main'=>$main,'adv'=>$adv);
	}

	public function Load($id, &$params)
	{
		$ret = parent::Load($id,$params);
		if (isset($_FILES)) {
			$key = $id.$this->formdata->current_prefix.$this->Id;
			if (!isset($_FILES[$key]))
				$key = $id.$this->formdata->prior_prefix.$this->Id;
			if (isset($_FILES[$key]) && $_FILES[$key]['size'] > 0) // file was uploaded
				$this->SetValue($_FILES[$key]['name']);
		}
		return $ret;
	}

	public function CreatePageDropdown($id, $name, $current='', $addtext='', $markdefault=TRUE)
	{
		// we get here (hopefully) when the template is changed in the dropdown
		$defaultid = '';
		if ($markdefault) {
			$contentops = \cmsms()->GetContentOperations();
			$defaultid = $contentops->GetDefaultPageID();
		}

		// get a list of the pages used by this template
		$pre = \cms_db_prefix();
		$sql = 'SELECT content_name,content_id FROM '.$pre.
			'content WHERE type = \'content\' AND active = 1 ORDER BY content_name';
		$db = \cmsms()->GetDb();
		$allpages = $db->GetAssoc($sql);
		if ($allpages && $defaultid) {
			$key = array_search($defaultid,$allpages);
			if ($key !== FALSE) {
				unset($allpages[$key]);
				$allpages = array($key.' (*)' => $defaultid) + $allpages;
			}
		}
		return $this->formdata->formsmodule->CreateInputDropdown($id,$name,$allpages,-1,$current,$addtext);
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->formsmodule;
		if ($this->Value)
			$ret = $this->GetDisplayableValue().'<br />'; // Value line
		else
			$ret = '';
		$tmp = $mod->CreateFileUploadInput(
			$id,$this->formdata->current_prefix.$this->Id,
			'id="'.$this->GetInputId().'"'.$this->GetScript()); // Input line
		$ret .= $this->SetClass($tmp);
		if ($this->Value) {
			$tmp = $mod->CreateInputCheckbox($id,$this->formdata->current_prefix.'delete__'.$this->Id,-1). //TODO is this used?
				'&nbsp;'.$mod->Lang('delete').'<br />'; // Delete line
			$ret .= $this->SetClass($tmp);
		}

		// Extras
		if ($this->GetProperty('show_details',0)) {
			$opt = $this->GetProperty('max_size');
			if ($opt)
				$ret .= ' '.$mod->Lang('maximum_size').': '.$opt.'kB';
			$opt = $this->GetProperty('permitted_extensions');
			if ($opt)
				$ret .= ' '.$mod->Lang('permitted_extensions').': '.$opt;
		}
		return $ret;
	}

/* TODO
	// Ryan's ugly fix for Bug 4307
	// We should figure out why this field wasn't populating its Smarty variable
	if ($one->GetFieldType() == 'FileUpload') { //TODO
		$tplvars['fld_'.$one->GetId()] = $one->GetDisplayableValue();
		$hidden .= $this->CreateInputHidden($id,
			$testIndex,
			Utils::html_myentities_decode($one->GetDisplayableValue()));
		$thisAtt = $one->GetDisplayableValue(FALSE);
		$tplvars['test_'.$one->GetId()] = $thisAtt;
		$tplvars['value_fld'.$one->GetId()] = $thisAtt[0];
	}
*/

	public function Validate($id)
	{
		$this->valid = TRUE;
		$this->ValidationMessage = '';
		$mod = $this->formdata->formsmodule;
		$_id = $id.$this->formdata->current_prefix.$this->Id;
		if (empty($_FILES[$_id]))
			$_id = $id.$this->formdata->prior_prefix.$this->Id;
		if (empty($_FILES[$_id])) {
			$this->valid = FALSE;
			$this->ValidationMessage = $mod->Lang('missing_type',$mod->Lang('file'));
			return array($this->valid,$this->ValidationMessage);
		}
		if ($_FILES[$_id]['size'] < 1 && ! $this->Required)
			return array(TRUE,'');

		$ms = $this->GetProperty('max_size');
		$exts = $this->GetProperty('permitted_extensions');
		if ($_FILES[$_id]['size'] < 1 && $this->Required) {
			$this->valid = FALSE;
			$this->ValidationMessage = $mod->Lang('required_field_missing');
		} elseif ($ms && $_FILES[$_id]['size'] > ($ms * 1024)) {
			$this->ValidationMessage = $mod->Lang('err_large_file'). ' '.$ms.'kb';//($ms * 1024).'kb'; // Stikki mods
			$this->valid = FALSE;
		} elseif ($exts) {
			$match = FALSE;
			$legalExts = explode(',',$exts);
			foreach ($legalExts as $thisExt) {
				if (preg_match('/\.'.trim($thisExt).'$/i',$_FILES[$_id]['name']))
					$match = TRUE;
				else if (preg_match('/'.trim($thisExt).'/i',$_FILES[$_id]['type']))
					$match = TRUE;
			}
			if (!$match) {
				$this->ValidationMessage = $mod->Lang('illegal_file_type');
				$this->valid = FALSE;
			}
		}
		return array($this->valid,$this->ValidationMessage);
	}

	/*
	If the 'uploads' module is present,and the option is checked in the field,
	then the file is added to the uploads module and a link is added to the results.
	Otherwise, upload the file to the "uploads" directory.
	*/
	public function Dispose($id, $returnid)
	{
		$_id = $id.$this->formdata->current_prefix.$this->Id;
		if (empty($_FILES[$_id]))
			$_id = $id.$this->formdata->prior_prefix.$this->Id;
		if (isset($_FILES[$_id]) && $_FILES[$_id]['size'] > 0) {
			$config = \cmsms()->GetConfig();
			$mod = $this->formdata->formsmodule;

			$thisFile =& $_FILES[$_id];
			$thisExt = substr($thisFile['name'],strrpos($thisFile['name'],'.'));

			if ($this->GetProperty('file_rename') == '')
				$destination_name = $thisFile['name'];
			else {
				$fids = array();
				$destination_name = $this->GetProperty('file_rename');
				//TODO if fields not named like '$fld_N' in the string ?
				preg_match_all('/\$fld_(\d+)/',$destination_name,$fids);
				foreach ($fids[1] as $field_id) {
					if (array_key_exists($field_id,$this->formdata->Fields)) {
						$destination_name = str_replace('$fld_'.$field_id,
							 $this->formdata->Fields[$field_id]->GetDisplayableValue(),$destination_name);
					}
				}
				$destination_name = str_replace('$ext',$thisExt,$destination_name);
			}

			if ($this->GetProperty('sendto_uploads')) {
				// we have a file we can send to the uploads
				$uploads = $mod->GetModuleInstance('Uploads');
				if (!$uploads) {
					// no uploads module
					return array(FALSE,$mod->Lang('err_module_upload'));
				}

				$parms = array();
				$parms['input_author'] = $mod->Lang('anonymous');
				$parms['input_summary'] = $mod->Lang('title_uploadmodule_summary');
				$parms['category_id'] = $this->GetProperty('uploads_category');
				$parms['field_name'] = $_id;
				$parms['input_destname'] = $destination_name;
				if ($this->GetProperty('allow_overwrite',0)) {
					$parms['input_replace'] = 1;
				}
				$res = $uploads->AttemptUpload(-1,$parms,-1);

				if ($res[0] == FALSE) {
					// failed upload kills the send
					return array(FALSE,$mod->Lang('uploads_error',$res[1]));
				}

				$uploads_destpage = $this->GetProperty('uploads_destpage');
				$url = $uploads->CreateLink($parms['category_id'],'getfile',$uploads_destpage,'',
					array ('upload_id' => $res[1]),'',TRUE);

				$url = str_replace('admin/moduleinterface.php?','index.php?',$url);

				$this->ResetValue();
				$this->SetValue($url);
			} else { //we will upload
				$ud = Utils::GetUploadsPath($mod);
				if (!$ud)
					return array(FALSE,'err_uploads_dir');

				$src = $thisFile['tmp_name'];
				//$dest_path = $this->GetProperty('file_destination',$config['uploads_path']);
				// validated message before,now do it for the file itself
				$valid = TRUE;
				$ms = $this->GetProperty('max_size');
				$exts = $this->GetProperty('permitted_extensions');
				if ($ms && $thisFile['size'] > ($ms * 1024)) {
					$valid = FALSE;
				} elseif ($exts) {
					$match = FALSE;
					$legalExts = explode(',',$exts);
					foreach ($legalExts as $thisExt) {
						if (preg_match('/\.'.trim($thisExt).'$/i',$thisFile['name'])) {
							$match = TRUE;
						} else if (preg_match('/'.trim($thisExt).'/i',$thisFile['type'])) {
							$match = TRUE;
						}
					}
					if (!$match) {
						$valid = FALSE;
					}
				}

				if (!$valid) {
					unlink($src);
					return array(FALSE,$mod->Lang('illegal_file',array($thisFile['name'],$_SERVER['REMOTE_ADDR'])));
				}

				$dest = $ud.DIRECTORY_SEPARATOR.$destination_name;
				if (file_exists($dest) && !$this->GetProperty('allow_overwrite',0)) {
					unlink($src);
					return array(FALSE,$mod->Lang('err_file_exists',array($destination_name)));
				}

				if (move_uploaded_file($src,$dest)) {
/*
//$TODO $config = ?
					if (strpos($ud,$config['root_path']) !== FALSE) {
						$url = str_replace($config['root_path'],'',$ud).DIRECTORY_SEPARATOR.$destination_name;
					} else {
						$url = $mod->Lang('uploaded_outside_webroot',$destination_name);
					}
					//$this->ResetValue();
					//$this->SetValue(array($dest,$url));
*/
				} else {
					return array(FALSE,$mod->Lang('uploads_error',''));
				}
			}
		}

		return array(TRUE,'');
	}

	public function PostDisposeAction()
	{
		if ($this->GetProperty('remove_file',0)) {
			if (is_array($this->Value)) {
				$dest = $this->Value[0];
				if (is_file($dest))
					unlink($dest);
			}
		}
	}
}
