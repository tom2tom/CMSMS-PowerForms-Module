<?php
# This file is part of CMS Made Simple module: PowerBrowse
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
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
while(1)
{
	if(!$mx->lock($token))
	{
		$cache->delete('pwfQrunning');
		echo $this->Lang('error_lock');
		exit;
	}
	$queue = $cache->get('pwfQarray');
	$mx->unlock($token);

	if($queue)
	{
		$cache->delete('pwfQarray');
		while($data = reset($queue))
		{
			$datakey = key($queue);
			//each Q-item = array('data'=>$formdata(sans-module),'submitted'=>time(),'pageid'=>$id
			$formdata = $data['data']; //maybe decrypt?
			$formdata->formsmodule = &$this;

			// run all field methods that modify other fields
			$computes = array();
			foreach($formdata->FieldOrders as $one)
			{
				$obfld = $formdata->Fields($one);
				$obfld->PreDisposeAction();
				if($obfld->ComputeOnSubmission())
					$computes[$one] = $obfld->ComputeOrder();
			}

			if($computes)
			{
				asort($computes);
				foreach($computes as $fid=>$one)
					$formdata->Fields[$fid]->Compute();
			}

			$alldisposed = TRUE;
			$message = array();
			// dispose TODO handle 'blocked' notices
			foreach($formdata->FieldOrders as $one)
			{
				$one = $formdata->Fields($one);
				if($one->IsDisposition() && $one->DispositionIsPermitted())
				{
					$res = $one->Dispose($id,$returnid);
					if(!$res[0])
					{
						$alldisposed = FALSE;
						$message[] = $res[1];
					}
				}
			}
			// cleanups
			foreach($formdata->FieldOrders as $one)
			{
				$one = $formdata->Fields($one);
				$one->PostDisposeAction();
			}

			$parms = array('form_id' => $formdata->Id,'form_name' => $formdata->Name);

			$smarty->assign('form_done',1); //TODO
			if($alldisposed)
			{
				$cache->delete($cache_key);
				$act = pwfUtils::GetFormOption($formdata,'submit_action','text');
/*
$content = cmsms()->GetContentOperations()->LoadContentFromId($data['pageid']);
$pageurl = $content->GetURL();
*/
				switch ($act)
				{
				 case 'text':
					$this->SendEvent('OnFormSubmit',$parms);
					//TODO or from templates
//						$message = pwfUtils::GetFormOption($formdata,'submission_template','');
					pwfUtils::setFinishedFormSmarty($formdata,TRUE);
//						echo $this->ProcessTemplateFromData($message);
					echo $this->ProcessTemplateFromDatabase('pwf_sub_'.$form_id);
					return;
				 case 'redir':
					$this->SendEvent('OnFormSubmit',$parms);
					$ret = pwfUtils::GetFormOption($formdata,'redirect_page',0);
					if($ret > 0)
						$this->RedirectContent($ret); //TODO
					else
					{
						$smarty->assign('title',$this->Lang('missing_type',$this->Lang('page')));
						$smarty->assign('message',$this->Lang('cannot_show_TODO'));
						$smarty->assign('error',1);
						echo $this->ProcessTemplate('message.tpl');
					}
					break;
				 case 'confirm':
				 	//confirmation needed before submission
					//after confirmation, formdata will be different
					$smarty->assign('title',$this->Lang('title_confirm'));
					$smarty->assign('message',$this->Lang('help_confirm'));
					echo $this->ProcessTemplate('message.tpl');
					break;
				 default:
$this->Crash4();
					exit;
				}
			}
			else
			{
				$this->SendEvent('OnFormSubmitError',$parms);
				$smarty->assign('submission_error',$this->Lang('error_submission'));
				$smarty->assign('submission_error_list',$message);
				$smarty->assign('show_submission_errors',!$this->GetPreference('hide_errors'));
				echo $this->ProcessTemplate('TODO');
				break;
			}
			unset($parms);

			unset($queue[$datakey],$data);
		} //end of current-array loop

		//grab anything new in Q
		$mx->lock($token);
		$queue = $cache->get('pwfQarray');
		if($queue)
		{
			$cache->delete('pwfQarray');
			$mx->unlock($token);
		}
		else
		{
			$mx->unlock($token);
			break; //nothing added, we're done
		}	
	}
	else
		break; //nothing [more] in the Q
} //end of while-loop

$cache->delete('pwfQrunning');

exit;

?>
