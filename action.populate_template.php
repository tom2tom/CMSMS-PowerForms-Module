<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/
//get default template for a form (main or submission) or for a field
//this action processes ajax-calls

if (isset($params['datakey'])) {
	$cache = PWForms\Utils::GetCache();
	if (!$cache) {
		echo '0';
		exit;
	}
	$formdata = $cache->get(PWForms::CACHESPACE, $params['datakey']);
	if (is_null($formdata) || !$formdata->Fields) {
		echo '0';
		exit;
	}
	if (is_null($formdata->pwfmod)) {
		$formdata->pwfmod = &$this;
	}
}

switch ($params['type']) {
 case 'form':
	if (empty($params['revert'])) {
		if (empty($params['name'])) {
			$name = 'pwf_'.$formdata->Id;
		} else {
			$name = $params['name'];
		}
		if (strncmp($name, 'pwf_', 4) == 0) {
			if ($this->oldtemplates) {
				$tplstr = $this->GetTemplate($name);
			} else {
				$ob = CmsLayoutTemplate::load($name);
				$tplstr = $ob->get_content();
			}
		} elseif (preg_match('/\.tpl$/', $name)) {
			$tplstr = ''.@file_get_contents(cms_join_path(__DIR__, 'templates', $params['tid']));
		} else {
			$tplstr = '0';
		}
	} else {
		$tplstr = $formdata->XtraProps['form_template'];
	}
	break;
 case 'submission':
	if (empty($params['revert'])) {
		$tplstr = PWForms\Utils::CreateDefaultTemplate($formdata, TRUE, FALSE);
	} else {
		$tplstr = $formdata->XtraProps['submission_template'];
	}
	break;
 case 'email':
	if (empty($params['revert'])) {
		$tplstr = PWForms\Utils::CreateDefaultTemplate($formdata, !empty($params['html']), TRUE);
	} else {
		$obfld = $formdata->Fields[$params['field_id']];
		if ($obfld) {
			$tplstr = $obfld->XtraProps['email_template'];
		} else {
			$tplstr = '0';
		}
	}
	break;
 case 'captcha':
	$obfld = $formdata->Fields[$params['field_id']];
	if ($obfld) {
		if (empty($params['revert'])) {
			$tplstr = $obfld->GetDefaultTemplate();
		} else {
			$tplstr = $obfld->XtraProps['captcha_template'];
		}
	} else {
		$tplstr = '0';
	}
	break;
 case 'file':
	$obfld = $formdata->Fields[$params['field_id']];
	if ($obfld) {
		if (empty($params['revert'])) {
			if (isset($params['main'])) {
				$tplstr = $obfld->CreateDefaultTemplate();
			} elseif (isset($params['header'])) {
				$tplstr = $obfld->CreateDefaultHeader();
			} elseif (isset($params['footer'])) {
				$tplstr = $obfld->CreateDefaultFooter();
			} else {
				$tplstr = '0';
			}
		} else {
			if (isset($params['main'])) {
				$tplstr = $obfld->XtraProps['file_template'];
			} elseif (isset($params['header'])) {
				$tplstr = $obfld->XtraProps['header_template'];
			} elseif (isset($params['footer'])) {
				$tplstr = $obfld->XtraProps['footer_template'];
			} else {
				$tplstr = '0';
			}
		}
	} else {
		$tplstr = '0';
	}
	break;
}

echo $tplstr;
exit;
