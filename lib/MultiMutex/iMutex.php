<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace MultiMutex;

interface iMutex
{
	public function __construct($config=array());

	public function lock($token);

	public function unlock($token);

	public function reset();
}
