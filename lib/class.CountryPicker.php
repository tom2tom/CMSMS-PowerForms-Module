<?php
/*
This file is part of CMS Made Simple module: PWForms
Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
Refer to licence and other details at the top of file PWForms.module.php
More info at http://dev.cmsmadesimple.org/projects/powerforms
*/

namespace PWForms;

class CountryPicker extends FieldBase
{
	private $Countries;

	public function __construct(&$formdata, &$params)
	{
		parent::__construct($formdata, $params);
		$this->IsInput = TRUE;
		$this->Type = 'CountryPicker';
		$this->InitCountries();
	}

	public function GetMutables($nobase=TRUE, $actual=TRUE)
	{
		return parent::GetMutables($nobase) + [
		'default_country' => 12,
		'select_label' => 12,
		];
	}

	protected function InitCountries()
	{
		$mod = $this->formdata->pwfmod;
		$this->Countries = [
			$mod->Lang('AF') =>'AF',$mod->Lang('AX') =>'AX',$mod->Lang('AL') =>'AL',
			$mod->Lang('DZ') =>'DZ',$mod->Lang('AS') =>'AS',$mod->Lang('AD') =>'AD',
			$mod->Lang('AO') =>'AO',$mod->Lang('AI') =>'AI',$mod->Lang('AQ') =>'AQ',
			$mod->Lang('AG') =>'AG',$mod->Lang('AR') =>'AR',$mod->Lang('AM') =>'AM',
			$mod->Lang('AW') =>'AW',$mod->Lang('AU') =>'AU',$mod->Lang('AT') =>'AT',
			$mod->Lang('AZ') =>'AZ',$mod->Lang('BS') =>'BS',$mod->Lang('BH') =>'BH',
			$mod->Lang('BB') =>'BB',$mod->Lang('BD') =>'BD',$mod->Lang('BY') =>'BY',
			$mod->Lang('BE') =>'BE',$mod->Lang('BZ') =>'BZ',$mod->Lang('BJ') =>'BJ',
			$mod->Lang('BM') =>'BM',$mod->Lang('BT') =>'BT',$mod->Lang('BW') =>'BW',
			$mod->Lang('BO') =>'BO',$mod->Lang('BA') =>'BA',$mod->Lang('BV') =>'BV',
			$mod->Lang('BR') =>'BR',$mod->Lang('IO') =>'IO',$mod->Lang('BN') =>'BN',
			$mod->Lang('BG') =>'BG',$mod->Lang('BF') =>'BF',$mod->Lang('BI') =>'BI',
			$mod->Lang('KH') =>'KH',$mod->Lang('CM') =>'CM',$mod->Lang('CA') =>'CA',
			$mod->Lang('CV') =>'CV',$mod->Lang('KY') =>'KY',$mod->Lang('CF') =>'CF',
			$mod->Lang('TD') =>'TD',$mod->Lang('CL') =>'CL',$mod->Lang('CN') =>'CN',
			$mod->Lang('CX') =>'CX',$mod->Lang('CC') =>'CC',$mod->Lang('CO') =>'CO',
			$mod->Lang('KM') =>'KM',$mod->Lang('CG') =>'CG',$mod->Lang('CD') =>'CD',
			$mod->Lang('CK') =>'CK',$mod->Lang('CR') =>'CR',$mod->Lang('CI') =>'CI',
			$mod->Lang('HR') =>'HR',$mod->Lang('CU') =>'CU',$mod->Lang('CY') =>'CY',
			$mod->Lang('CZ') =>'CZ',$mod->Lang('DK') =>'DK',$mod->Lang('DJ') =>'DJ',
			$mod->Lang('DM') =>'DM',$mod->Lang('DO') =>'DO',$mod->Lang('TP') =>'TP',
			$mod->Lang('EC') =>'EC',$mod->Lang('EG') =>'EG',$mod->Lang('SV') =>'SV',
			$mod->Lang('GQ') =>'GQ',$mod->Lang('ER') =>'ER',$mod->Lang('EE') =>'EE',
			$mod->Lang('ET') =>'ET',$mod->Lang('FK') =>'FK',$mod->Lang('FO') =>'FO',
			$mod->Lang('FJ') =>'FJ',$mod->Lang('FI') =>'FI',$mod->Lang('FR') =>'FR',
			$mod->Lang('FX') =>'FX',$mod->Lang('GF') =>'GF',$mod->Lang('PF') =>'PF',
			$mod->Lang('TF') =>'TF',$mod->Lang('MK') =>'MK',$mod->Lang('GA') =>'GA',
			$mod->Lang('GM') =>'GM',$mod->Lang('GE') =>'GE',$mod->Lang('DE') =>'DE',
			$mod->Lang('GH') =>'GH',$mod->Lang('GI') =>'GI',$mod->Lang('GB') =>'GB',
			$mod->Lang('GR') =>'GR',$mod->Lang('GL') =>'GL',$mod->Lang('GD') =>'GD',
			$mod->Lang('GP') =>'GP',$mod->Lang('GU') =>'GU',$mod->Lang('GT') =>'GT',
			$mod->Lang('GF') =>'GF',$mod->Lang('GN') =>'GN',$mod->Lang('GW') =>'GW',
			$mod->Lang('GY') =>'GY',$mod->Lang('HT') =>'HT',$mod->Lang('HM') =>'HM',
			$mod->Lang('HN') =>'HN',$mod->Lang('HK') =>'HK',$mod->Lang('HU') =>'HU',
			$mod->Lang('IS') =>'IS',$mod->Lang('IN') =>'IN',$mod->Lang('ID') =>'ID',
			$mod->Lang('IR') =>'IR',$mod->Lang('IQ') =>'IQ',$mod->Lang('IE') =>'IE',
			$mod->Lang('IL') =>'IL',$mod->Lang('IM') =>'IM',$mod->Lang('IT') =>'IT',
			$mod->Lang('JE') =>'JE',$mod->Lang('JM') =>'JM',$mod->Lang('JP') =>'JP',
			$mod->Lang('JO') =>'JO',$mod->Lang('KZ') =>'KZ',$mod->Lang('KE') =>'KE',
			$mod->Lang('KI') =>'KI',$mod->Lang('KP') =>'KP',$mod->Lang('KR') =>'KR',
			$mod->Lang('KW') =>'KW',$mod->Lang('KG') =>'KG',$mod->Lang('LA') =>'LA',
			$mod->Lang('LV') =>'LV',$mod->Lang('LB') =>'LB',$mod->Lang('LI') =>'LI',
			$mod->Lang('LR') =>'LR',$mod->Lang('LY') =>'LY',$mod->Lang('LS') =>'LS',
			$mod->Lang('LT') =>'LT',$mod->Lang('LU') =>'LU',$mod->Lang('MO') =>'MO',
			$mod->Lang('MG') =>'MG',$mod->Lang('MW') =>'MW',$mod->Lang('MY') =>'MY',
			$mod->Lang('MV') =>'MV',$mod->Lang('ML') =>'ML',$mod->Lang('MT') =>'MT',
			$mod->Lang('MH') =>'MH',$mod->Lang('MQ') =>'MQ',$mod->Lang('MR') =>'MR',
			$mod->Lang('MU') =>'MU',$mod->Lang('YT') =>'YT',$mod->Lang('MX') =>'MX',
			$mod->Lang('FM') =>'FM',$mod->Lang('MC') =>'MC',$mod->Lang('MD') =>'MD',
			$mod->Lang('MA') =>'MA',$mod->Lang('MN') =>'MN',$mod->Lang('MS') =>'MS',
			$mod->Lang('MZ') =>'MZ',$mod->Lang('MM') =>'MM',$mod->Lang('NA') =>'NA',
			$mod->Lang('NR') =>'NR',$mod->Lang('NP') =>'NP',$mod->Lang('NL') =>'NL',
			$mod->Lang('AN') =>'AN',$mod->Lang('NT') =>'NT',$mod->Lang('NC') =>'NC',
			$mod->Lang('NZ') =>'NZ',$mod->Lang('NI') =>'NI',$mod->Lang('NE') =>'NE',
			$mod->Lang('NG') =>'NG',$mod->Lang('NU') =>'NU',$mod->Lang('NF') =>'NF',
			$mod->Lang('MP') =>'MP',$mod->Lang('NO') =>'NO',$mod->Lang('OM') =>'OM',
			$mod->Lang('PK') =>'PK',$mod->Lang('PW') =>'PW',$mod->Lang('PS') =>'PS',
			$mod->Lang('PA') =>'PA',$mod->Lang('PG') =>'PG',$mod->Lang('PY') =>'PY',
			$mod->Lang('PE') =>'PE',$mod->Lang('PH') =>'PH',$mod->Lang('PN') =>'PN',
			$mod->Lang('PL') =>'PL',$mod->Lang('PT') =>'PT',$mod->Lang('PR') =>'PR',
			$mod->Lang('QA') =>'QA',$mod->Lang('RE') =>'RE',$mod->Lang('RO') =>'RO',
			$mod->Lang('RU') =>'RU',$mod->Lang('RW') =>'RW',$mod->Lang('GS') =>'GS',
			$mod->Lang('KN') =>'KN',$mod->Lang('LC') =>'LC',$mod->Lang('VC') =>'VC',
			$mod->Lang('WS') =>'WS',$mod->Lang('SM') =>'SM',$mod->Lang('ST') =>'ST',
			$mod->Lang('SA') =>'SA',$mod->Lang('SN') =>'SN',$mod->Lang('SC') =>'SC',
			$mod->Lang('SL') =>'SL',$mod->Lang('SG') =>'SG',$mod->Lang('SI') =>'SI',
			$mod->Lang('SK') =>'SK',$mod->Lang('SB') =>'SB',$mod->Lang('SO') =>'SO',
			$mod->Lang('ZA') =>'ZA',$mod->Lang('ES') =>'ES',$mod->Lang('LK') =>'LK',
			$mod->Lang('SH') =>'SH',$mod->Lang('PM') =>'PM',$mod->Lang('SD') =>'SD',
			$mod->Lang('SR') =>'SR',$mod->Lang('SJ') =>'SJ',$mod->Lang('SZ') =>'SZ',
			$mod->Lang('SE') =>'SE',$mod->Lang('CH') =>'CH',$mod->Lang('SY') =>'SY',
			$mod->Lang('TW') =>'TW',$mod->Lang('TJ') =>'TJ',$mod->Lang('TZ') =>'TZ',
			$mod->Lang('TH') =>'TH',$mod->Lang('TG') =>'TG',$mod->Lang('TK') =>'TK',
			$mod->Lang('TO') =>'TO',$mod->Lang('TT') =>'TT',$mod->Lang('TN') =>'TN',
			$mod->Lang('TR') =>'TR',$mod->Lang('TM') =>'TM',$mod->Lang('TC') =>'TC',
			$mod->Lang('TV') =>'TV',$mod->Lang('UG') =>'UG',$mod->Lang('UA') =>'UA',
			$mod->Lang('AE') =>'AE',$mod->Lang('UK') =>'UK',$mod->Lang('US') =>'US',
			$mod->Lang('UM') =>'UM',$mod->Lang('UY') =>'UY',$mod->Lang('UZ') =>'UZ',
			$mod->Lang('VU') =>'VU',$mod->Lang('VA') =>'VA',$mod->Lang('VE') =>'VE',
			$mod->Lang('VN') =>'VN',$mod->Lang('VG') =>'VG',$mod->Lang('VI') =>'VI',
			$mod->Lang('WF') =>'WF',$mod->Lang('EH') =>'EH',$mod->Lang('YE') =>'YE',
			$mod->Lang('YU') =>'YU',$mod->Lang('ZM') =>'ZM',$mod->Lang('ZW') =>'ZW'
			];
		ksort($this->Countries); //TODO mb_ compatible sort
	}

	public function DisplayableValue($as_string=TRUE)
	{
		if (!$this->Countries) {
			$this->InitCountries();
		}
		$ret = array_search($this->Value, $this->Countries);
		if ($as_string) {
			return $ret;
		} else {
			return [$ret];
		}
	}

	public function AdminPopulate($id)
	{
		if (!$this->Countries) {
			$this->InitCountries();
		}
		list($main, $adv) = $this->AdminPopulateCommon($id, FALSE, FALSE);
		$mod = $this->formdata->pwfmod;
		$choices = array_merge([$mod->Lang('no_default')=>''], $this->Countries);
		$main[] = [$mod->Lang('title_select_default_country'),
						$mod->CreateInputDropdown($id, 'fp_default_country', $choices, -1,
							$this->GetProperty('default_country'))];
		$main[] = [$mod->Lang('title_select_one_message'),
						$mod->CreateInputText($id, 'fp_select_label',
							$this->GetProperty('select_label', $mod->Lang('select_one')))];
		return ['main'=>$main,'adv'=>$adv];
	}

	public function Populate($id, &$params)
	{
		$mod = $this->formdata->pwfmod;
		$choices = array_merge([$this->GetProperty('select_label', $mod->Lang('select_one'))=>-1],
			$this->Countries);

		if (!$this->HasValue() && $this->GetProperty('default_country')) {
			$this->SetValue($this->GetProperty('default_country'));
		}
		$tmp = $mod->CreateInputDropdown(
			$id, $this->formdata->current_prefix.$this->Id, $choices, -1, $this->Value,
			'id="'.$this->GetInputId().'"'.$this->GetScript());
		return $this->SetClass($tmp);
	}
}
