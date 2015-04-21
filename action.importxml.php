<?php
/*
FormBuilder. Copyright (c) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
More info at http://dev.cmsmadesimple.org/projects/formbuilder

A Module for CMS Made Simple, Copyright (c) 2004-2012 by Ted Kulp (wishy@cmsmadesimple.org)
This project's homepage is: http://www.cmsmadesimple.org
*/
if (! $this->CheckAccess()) exit;

$params['fbrp_xml_file'] = $_FILES[$id.'fbrp_xmlfile']['tmp_name'];

$aeform = new fbForm($this, $params, true);
if ($aeform->newID ($params['fbrp_import_formname'],$params['fbrp_import_formalias']))
  {
	if ($aeform->ImportXML($params))
		$params['fbrp_message'] = $this->Lang('form_imported');
	else
		$params['fbrp_message'] = $this->Lang('form_import_failed');
  }
else
	$params['fbrp_message'] = $this->Lang('duplicate_identifier');

$this->Redirect($id, 'defaultadmin', '', $params);
?>
