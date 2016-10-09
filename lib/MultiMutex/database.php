<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

/*
The database table must have (at least) two fields, the first of which will
contain 64-bit integers (i.e. I(8) or better) and not allow duplicate values (KEY),
and the second field will contain the result of $db->Timestamp() (T)
*/

namespace MultiMutex;

class Mutex_database implements iMutex
{
	private $field1;
	private $field2;
	private $table;
	private $pause;
	private $maxtries;

	public function __construct($config)
	{
		if (!empty($config['table'])) {
			$db = \cmsms()->GetDb();
			$tbl = $config['table'];
			$rs = $db->Execute('SELECT * FROM '.$tbl);
			if (!$rs || $rs->FieldCount() < 2)
				throw new \Exception('no database mutex');
			$rs->Close();
			$all = $db->GetCol('SELECT column_name FROM information_schema.columns WHERE table_name=\''.$tbl.'\'');
			$this->field1 = $all[0];
			$this->field2 = $all[1];
			$this->table = $tbl;
		} else
			throw new \Exception('no database mutex');
		$this->pause = (!empty($config['timeout'])) ? $config['timeout'] : 500;
		$this->maxtries = (!empty($config['tries'])) ? $config['tries'] : 200;
	}

	public function lock($token)
	{
		$db = \cmsms()->GetDb();
		$flid = abs(crc32($token.'.mx.lock'));
		$stamp = $db->sysTimeStamp;
		$sql = 'INSERT INTO '.$this->table.' ('.$this->field1.','.$this->field2.') VALUES ('.$flid.','.$stamp.')';
		$count = 0;
		do {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if ($db->CompleteTrans())
				return TRUE; //success
/*TODO		$sql = 'SELECT '.$this->field1'. FROM '.$this->table.' WHERE '.$this->field2.' < '.$stamp + 15;
			if ($db->GetOne($sql))
				$db->Execute('DELETE FROM '.$this->table);
*/
			usleep($this->pause);
		} while (/*$this->maxtries == 0 || */$count++ < $this->maxtries);
		return FALSE; //failed
	}

	public function unlock($token)
	{
		$db = \cmsms()->GetDb();
		$sql = 'DELETE FROM '.$this->table.' WHERE '.$this->field1.'='.abs(crc32($token.'.mx.lock'));
		while (1) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if ($db->CompleteTrans())
				return;
		}
	}

	public function reset()
	{
		$db = \cmsms()->GetDb();
		$sql = 'DELETE FROM '.$this->table;
		while (1) {
			$db->Execute('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
			$db->StartTrans();
			$db->Execute($sql);
			if ($db->CompleteTrans())
				return;
		}
	}
}
