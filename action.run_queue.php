<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2011-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerBrowse.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerbrowse

#action to be asynchronously initiated by curl, to process the form-dispose queue

try
{
	$cache = pwfUtils::GetCache();
}
catch (Exception $e)
{
	echo $this->Lang('error_system');
	$this->Audit(0,'Failed to initiate a data cache','run_queue');
	exit;
}
try
{
	$mx = pwfUtils::GetMutex($this);
}
catch (Exception $e)
{
	unset($cache);
	echo $this->Lang('error_system');
	$this->Audit(0,'Failed to initiate a queue mutex','run_queue');
	exit;
}

$token = abs(crc32($this->GetName().'Qmutex')); //same token as in action.default disposer
$cache->set('pwfQrunning',TRUE,1200); //flag that Q is being processed, 20-minute max retention
if(!$mx->lock($token))
{
	$cache->delete('pwfQrunning');
	echo $this->Lang('error_lock');
	exit;
}

$queue = $cache->get('pwfQarray');
if($queue)
{
	$cache->delete('pwfQarray');
	while($data = reset($queue))
	{
		$datakey = key($queue);
		//each Q-item = array('formid'=>$this->formdata->Id,'submitted'=>time(),'data'=>$formdata)
		$form_id = (int)$data['formid'];

		//TODO dispatch it

		unset($queue[$datakey],$data);
		//allow update
		$mx->unlock($token);
		do
		{
			usleep(mt_rand(10000,60000));
		} while(!$mx->lock($token));

		$newq = $cache->get('pwfQarray');
		if($newq)
		{
			$cache->delete('pwfQarray');
			$queue = array_merge($queue,$newq);
		}
	}
}
$mx->unlock($token);
$cache->delete('pwfQrunning');

exit;

?>
