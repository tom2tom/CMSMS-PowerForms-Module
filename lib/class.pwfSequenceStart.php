<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2015-2016 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms

class pwfSequenceStart extends pwfFieldBase
{
	function __construct(&$formdata, &$params)
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

	function GetHumanReadableValue($as_string=TRUE)
	{
		$ret = '[Begin FieldSequence: '.$this->GetOption('privatename').']';
		if($as_string)
			return $ret;
		else
			return array($ret);
	}

	function AdminPopulate($id)
	{
		list($main,$adv) = $this->AdminPopulateCommon($id);
		$mod = $this->formdata->formsmodule;
		$this->RemoveAdminField($main,$mod->Lang('title_field_validation'));
		$this->RemoveAdminField($main,$mod->Lang('title_field_helptext'));

		$main[] = array($mod->Lang('title_privatename'),
						$mod->CreateInputText($id,'opt_privatename',
							$this->GetOption('privatename',htmlentities($this->Name)),20,50));
		//paired end
/*		$choices = array of current ender fields + notyet
		$main[] = array($mod->Lang(''),
						$mod->CreateInputDropdown($id,'opt_starter',$choices,
							-1,$this->GetOption('starter')));
*/
		$main[] = array($mod->Lang('title_endername'),
						$mod->CreateInputText($id,'opt_ender',
							$this->GetOption('ender'),20,50));
		$main[] = array($mod->Lang('title_initial_count'),
						$mod->CreateInputText($id,'opt_repeatcount',
							$this->GetOption('repeatcount',1),2,2));
		$main[] = array($mod->Lang('title_max_count'),
						$mod->CreateInputText($id,'opt_maxcount',
							$this->GetOption('maxcount',0),2,2));

		$this->RemoveAdminField($adv,$mod->Lang('title_field_javascript'));
		$this->RemoveAdminField($adv,$mod->Lang('title_field_resources'));

		return array('main'=>$main,'adv'=>$adv);
	}

/*	function PostAdminAction(&$params)
	{
		conform any enderfield::opt_starter
	}

	function AdminValidate($id)
	{
		TODO
		unique privatename
		max adds 0..20?
		repeatcount 1..Max
	}
*/
	function Populate($id,&$params)
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
		$name = $this->GetOption('privatename'); //htmlentities?
		$repeats = $this->GetOption('repeatcount');
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

?>
