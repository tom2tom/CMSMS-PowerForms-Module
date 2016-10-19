<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

namespace PWForms;

class SequenceStart extends FieldBase
{
	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->ChangeRequirement = FALSE;
		$this->DisplayInSubmission = FALSE;
		$this->HasLabel = FALSE;
//?		$this->HasUserAddOp = TRUE;
//?		$this->HasUserDeleteOp = TRUE;
		$this->IsSortable = FALSE;
//?		$this->MultiPopulate = TRUE;
		$this->NeedsDiv = FALSE;
		$this->Type = 'SequenceStart';
	}

	public function GetDisplayableValue($as_string=TRUE)
	{
		$ret = '[Begin FieldSequence: '.$this->GetProperty('privatename').']';
		if ($as_string)
			return $ret;
		else
			return array($ret);
	}

	public function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id,TRUE); // !$visible?
		$mod = $this->formdata->formsmodule;
		$this->RemoveAdminField($main,$mod->Lang('title_field_helptext'));

		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'pdt_privatename',
							$this->GetProperty('privatename',htmlentities($this->Name)),20,50));
		//paired end
/*		$choices = array of current ender fields + notyet
		$main[] = array($mod->Lang(''),
						$mod->CreateInputDropdown($id,'pdt_starter',$choices,
							-1,$this->GetProperty('starter')));
*/
		$main[] = array($mod->Lang('title_endername'),
						$mod->CreateInputText($id,'pdt_ender',
							$this->GetProperty('ender'),20,50));
		$main[] = array($mod->Lang('title_initial_count'),
						$mod->CreateInputText($id,'pdt_repeatcount',
							$this->GetProperty('repeatcount',1),2,2));
		$main[] = array($mod->Lang('title_max_count'),
						$mod->CreateInputText($id,'pdt_maxcount',
							$this->GetProperty('maxcount',0),2,2));

		$this->RemoveAdminField($adv,$mod->Lang('title_field_javascript'));
		$this->RemoveAdminField($adv,$mod->Lang('title_field_resources'));

		return array('main'=>$main,'adv'=>$adv);
	}

/*	public function PostAdminAction(&$params)
	{
		conform any enderfield::opt_starter
	}

	public function AdminValidate($id)
	{
		TODO
		unique privatename
		max adds 0..20?
		repeatcount 1..Max
	}
*/
	public function Populate($id,&$params)
	{
		/*
		setup js:
		$(BTNSHOW).css('display','none').click({
			$(DIV).css('display','block');
			$(this).css('display','none');
			$(BTNHIDE).css('display','inline-block');
		});
		$(BTNHIDE).click({
			$(DIV).css('display','none');
			$(this).css('display','none');
			$(BTNSHOW).css('display','inline-block');
		});
		if only 1 sequence
			$(BTNREM).css('display','none');
		*/
		$name = $this->GetProperty('privatename'); //htmlentities?
		$repeats = $this->GetProperty('repeatcount');
		$ret = '';
		for ($i=0; $i<$repeats; $i++) //OR support looping in form constructor
		{
			$ret = 'BTNHIDE BTNSHOW BTNADD BTNREM';
			$ret .= '<br />'.htmlentities('>>>>').PHP_EOL;
			$ret .= '<div id="'.$name.'" class="sequence"><!-- start sequence '.$name.' -->'.PHP_EOL;
			//FIELD(S) STUFF
			//?? HOWTO if NESTED ???
			$ret .= '</div><!-- end sequence '.$name.' -->'.PHP_EOL;
			$ret .= htmlentities('<<<<').PHP_EOL;
		}
		return $ret;
		//TODO skip $formdata->Fields to relevant SequenceEnd
	}
}
