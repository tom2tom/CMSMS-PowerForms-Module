<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Derived in part from FormBuilder-module file (C) 2005-2012 Samuel Goldstein <sjg@cmsmodules.com>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

if(!$this->CheckAccess()) exit;

$this->SetPreference('hide_errors',isset($params['pwfp_hide_errors'])?$params['pwfp_hide_errors']:0);
$this->SetPreference('show_version',isset($params['pwfp_show_version'])?$params['pwfp_show_version']:0);
$this->SetPreference('relaxed_email_regex',isset($params['pwfp_relaxed_email_regex'])?$params['pwfp_relaxed_email_regex']:0);

$this->SetPreference('require_fieldnames',isset($params['pwfp_require_fieldnames'])?$params['pwfp_require_fieldnames']:0);

$this->SetPreference('unique_fieldnames',isset($params['pwfp_unique_fieldnames'])?$params['pwfp_unique_fieldnames']:0);

$this->SetPreference('enable_fastadd',isset($params['pwfp_enable_fastadd'])?$params['pwfp_enable_fastadd']:0);
$this->SetPreference('enable_antispam',isset($params['pwfp_enable_antispam'])?$params['pwfp_enable_antispam']:0);

$this->SetPreference('show_fieldids',isset($params['pwfp_show_fieldids'])?$params['pwfp_show_fieldids']:0);
$this->SetPreference('show_fieldaliases',isset($params['pwfp_show_fieldaliases'])?$params['pwfp_show_fieldaliases']:0);

$this->SetPreference('blank_invalid',isset($params['pwfp_blank_invalid'])?$params['pwfp_blank_invalid']:0);

$params['pwfp_message'] = $this->Lang('configuration_updated');
$this->Redirect($id, 'defaultadmin', '', $params);

?>
