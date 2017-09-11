<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

if (!empty($params['selected'])) {
	$funcs = new PWForms\FormOperations();
	if (isset($params['clone'])) {
		if (!$this->_CheckAccess('ModifyPFForms')) {
			exit;
		}
		foreach ($params['selected'] as $fid) {
			$funcs->Copy($this, $id, $params, $fid);
		}
	} elseif (isset($params['delete'])) {
		if (!$this->_CheckAccess('ModifyPFForms')) {
			exit;
		}
		foreach ($params['selected'] as $fid) {
			$funcs->Delete($this, $fid);
		}
	} elseif (isset($params['export'])) {
		sort($params['selected']);
		$fn = $this->GetName().$this->Lang('export').'-Forms_'.
			implode('_',$params['selected']).'-'.date('Y-m-d-H-i').'.xml';
		$fn = preg_replace('/[^\w\-.]/', '_', $fn);

		$xmlstr = $funcs->CreateXML($this, $params['selected'], date('Y-m-d H:i:s'));
		if ($xmlstr) {
			@ob_clean();
			@ob_clean();
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private', FALSE);
			header('Content-Description: File Transfer');
			header('Content-Type: application/force-download; charset=utf-8');
			header('Content-Length: '.strlen($xmlstr));
			header('Content-Disposition: attachment; filename='.$fn);
			echo $xmlstr;
			exit;
		}
	}
}

$this->Redirect($id, 'defaultadmin');
