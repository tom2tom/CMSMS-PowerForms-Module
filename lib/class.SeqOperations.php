<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2015-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class SeqOperations
{
	//iterates over FieldOrders[], cuz shortcut-approaches can be confounded by sub-sequences
	//returns: ordered array of FieldOrders[] keys, for all the sequence starts/ends matching $actor
	private function LogMarkers(&$actor)
	{
		$nm = $actor->GetProperty('privatename'); //sequence identifier
		$fdata = $actor->formdata;
		$log = [];
		foreach ($fdata->FieldOrders as $o=>$fid) {
			$obfld = $fdata->Fields[$fid];
			$t = $obfld->GetFieldType();
			if ($t == 'SequenceStart' || $t == 'SequenceEnd') {
				if ($obfld->GetProperty('privatename') == $nm) {
					$log[] = $o;
				}
			}
		}
		return $log;
	}

	/**
	CopySequenceFields:
	@actor: reference to a SequenceStart/End object wherein a button-click triggered the operation
	@after: optional boolean, whether to insert the copied sequence after @actor, default TRUE
	@times: optional no. of repetitions of the sequence, default 1
	*/
	public function CopySequenceFields(&$actor, $after=TRUE, $times=1)
	{
		$members = $this->LogMarkers($actor);
		$fdata = $actor->formdata;
		$fid = $fdata->FieldOrders[$members[0]];
		$starter = $fdata->Fields[$fid];
		$c = $starter->GetProperty('maxcount');
		if ($c > 0) {
			$cn = count($members) - 1;
			if ($cn >= $c) {
				return;
			} //TODO advice for user
		}

		$aid = $actor->GetID();
		$ao = array_search($aid, $fdata->FieldOrders);
		$mo = array_search($ao, $members);
		if ($after && isset($members[$mo + 1])) {
			$f0 = $members[$mo];//pre-sequence starter/ender field-order
			$f1 = $members[$mo + 1]; //post-sequence ender
		} else {
			$f0 = $members[$mo - 1];
			$f1 = $members[$mo];
		}

		//field_id's to be reproduced (including ender)
		$batch = array_slice($fdata->FieldOrders, $f0+1, $f1-$f0);
		//field_id offset
		$c = max($fdata->FieldOrders) + count($fdata->FieldOrders);
		$c = (int)ceil($c / 1000) * 1000;
		//field order offset
		$o = $ao + 1;
		//members offset
		$x = $mo + 1;
		$xfields = [];
		$xords = [];
		$xmembers = [];

		while ($times > 0) {
			foreach ($batch as $fid) {
				$obfld = clone($fdata->Fields[$fid]);
				$i = $fid + $c + $times;
				$obfld->Id = $i;
				$obfld->Alias .= '_'.$aid.$times.count($members); //unique alias required
				$obfld->Value = NULL; //OK?
				$xfields[$i] = $obfld;
				$xords[$o++] = $i;
			}
			$xmembers[$x++] = $i; //interim, pending new FieldOrders[]
			--$times;
		}

		$pos = ($after) ? $ao+1:$ao;
		$fdata->Fields = array_slice($fdata->Fields, 0, $pos, TRUE) + $xfields + array_slice($fdata->Fields, $pos, NULL, TRUE);

		$tail = [];
		$c = count($xords);
		$rest = array_slice($fdata->FieldOrders, $pos, NULL, TRUE);
		foreach ($rest as $i=>$fid) {
			$tail[$i+$c] = $fid;
		}
		$fdata->FieldOrders = array_slice($fdata->FieldOrders, 0, $pos, TRUE) + $xords + $tail;

		foreach ($xmembers as $i=>$fid) {
			$xmembers[$i] = array_search($fid, $fdata->FieldOrders);
		}
		$pos = ($after) ? $mo+1:$mo;
		$tail = [];
		$x = count($xmembers);
		$rest = array_slice($members, $pos, NULL, TRUE);
		foreach ($rest as $i=>$o) {
			$tail[$i+$x] = $o + $c;
		}
		$members = array_slice($members, 0, $pos, TRUE) + $xmembers + $tail;

		foreach ($members as $i=>$o) {
			if ($i > 0) {
				$fid = $fdata->FieldOrders[$o];
				$obfld = $fdata->Fields[$fid];
				$obfld->SetLast(FALSE);
			}
		}
		$obfld->SetLast(TRUE);
	}

	/**
	DeleteSequenceFields:
	@actor: reference to a SequenceStart/End object wherein a button-click triggered the delete
	@after: optional boolean, whether to insert the copied sequence after @actor, default TRUE
	*/
	public function DeleteSequenceFields(&$actor, $after=TRUE)
	{
		$members = $this->LogMarkers($actor);
		if (count($members) <= 2) {
			return;
		} //no more deletions
		$fdata = $actor->formdata;
		$fid = $fdata->FieldOrders[$members[0]];
		$starter = $fdata->Fields[$fid];
		$c = $starter->GetProperty('mincount');
		if ($c > 1) {
			$cn = $this->CountSequences();
			if ($cn <= $c) {
				return;
			} //TODO advice for user
		}

		$aid = $actor->GetID();
		$ao = array_search($aid, $fdata->FieldOrders);
		$mo = array_search($ao, $members);
		if ($after) {
			$f0 = $members[$mo];//pre-sequence starter/ender field-order
			$f1 = $members[$mo + 1]; //post-sequence ender
		} else {
			$f0 = $members[$mo - 1];
			$f1 = $members[$mo];
		}

		//field_id's to be removed (excluding ender)
		$batch = array_slice($fdata->FieldOrders, $f0+1, $f1-$f0-1, TRUE);
		foreach ($batch as $o=>$fid) {
			unset($fdata->Fields[$fid]);
			unset($fdata->FieldOrders[$o]);
		}

		if ($mo == 0) { //$actor == $starter
			if (count($members) > 2) { //can delete next breaker
				$c = $members[1];
				$fid = $fdata->FieldOrders[$c];
				unset($fdata->Fields[$fid]);
				unset($fdata->FieldOrders[$c]);
				unset($members[1]); //exclude from further processing
			}
		} else { //delete $actor
			unset($fdata->Fields[$aid]);
			$c = ($after) ? $f0:$f1;
			unset($fdata->FieldOrders[$c]);
			unset($members[$mo]);
		}

		foreach ($members as $i=>$o) {
			if ($i > 0) {
				$fid = $fdata->FieldOrders[$o];
				$obfld = $fdata->Fields[$fid];
				$obfld->SetLast(FALSE);
			}
		}
		$obfld->SetLast(TRUE);

		$fdata->FieldOrders = array_values($fdata->FieldOrders);
	}
}
