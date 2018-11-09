<?php

namespace App\Services;

class PDF
{
	
	private $pdf;

	private $pages = array();

	//ColorPalette [Main Color, Second Color]
	private $colorPalette = array();

	private $footer = false;

	private $outputPath = BASEDIR.'/output/2018-01.pdf';

	public function __construct()
	{
		$format = $this->getPageFormat();

		$this->pdf = new \TCPDF('P','mm',$format);
		
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);

		$this->pdf->SetAutoPageBreak(FALSE);

		

		//$this->colorPalette = array([100,100,100,100],[99,70,24,7],[0,0,0,30]);
		$this->colorPalette = [[0,0,0,80],[0,0,0,100],[0,0,0,30]];
		
		//$this->pdf->addSpotColor('blue',$c=99,$m=70,$y=24,$k=7);

		//echo K_PATH_FONTS;
		//$this->pdf->SetFont('fa400');

		$this->setMetaData();
	}

	private function getPageFormat()
	{
		$x = 105;
		$y = 148;

		$beschnitt = 3;

		$format = array
		(
			'MediaBox' => array
			(
				'llx' => 0,
				'lly' => 0,
				'urx' => $x+2*$beschnitt,
				'ury' => $y+2*$beschnitt
			),

			'TrimBox' => array
			(
				'llx' => $beschnitt,
				'lly' => $beschnitt,
				'urx' => $x+$beschnitt,
				'ury' => $y+$beschnitt
			)
		);

		return $format;
	}

	private function setMetaData()
	{
		$this->pdf->SetAuthor('ETG Scheppach');
		$this->pdf->SetTitle('Gemeindeadressbuch ETG Scheppach');
	}

	public function addPage($person)
	{
		
		$this->pdf->setMargins($this->getLeftMargin(),10,10,true);

		$this->pdf->addPage();
		$this->pdf->setTextColorArray($this->colorPalette[0]);
		
		//Überschrift Familienname
		$this->pdf->setFontSize(18);
		$this->pdf->setFont('opensansb');
		$this->pdf->Write(0,$person->name);
		$this->pdf->Ln(10);
		

		$this->writePersonName($person);
		$this->writeContactDetails($person);


		if($person->partner){
			$this->writePersonName($person->partner,$person);

			if($person->telefonprivat != $person->partner->telefonprivat)
			{
				$this->writeLineWithIcon($person->partner->telefonprivat,'phone');
			}
			
			if($person->telefonhandy != $person->partner->telefonhandy)
			{
				$this->writeLineWithIcon($person->partner->telefonhandy,'mobile');
			}
			
			if($person->email != $person->partner->email)
			{
				$this->writeLineWithIcon($person->partner->email,'email');
			}

			$this->pdf->Ln(4);
		}

		//Adresse
		$this->writeLineWithIcon('Adresse','address','opensansb');
		
		$this->pdf->setFont('opensans');
		$this->pdf->Write(0,$person->strasse);
		$this->pdf->Ln(6);
		if($person->zusatz)
		{
			$this->pdf->Write(0, $person->zusatz);
			$this->pdf->Ln(6);
		}	
		$this->pdf->Write(0,$person->plz. ' ' .$person->ort);
		
			
		$this->pdf->Ln(10);
		
		if($person->children)
		{
			$this->pdf->setFont('opensansb');
			$this->pdf->Write(0,"Kinder");
			$this->pdf->Ln(6);	
	
			$this->pdf->setFont('opensans');
			usort($person->children,function($a,$b){return $a->age < $b->age;});
			foreach($person->children as $child){
				$this->pdf->Write(5,$child->vorname.' ('.$child->birthday->format("d.m.Y").')');
				$this->pdf->Ln(6);
			}
		}

		$this->writeRegistry($person->name);

		/*if($this->footer)
		{
			$this->pdf->setFillColorArray($this->colorPalette[1]);
			$this->pdf->setTextColor(0,0,0,0);
			$this->pdf->setFontSize(12);
			$this->pdf->MultiCell($w=111,$h=18,'',$border=0,$align='R',$fill='dedede',$ln=1,$x=0,$y=136);
		}*/		
	}

	public function addTitlePage(){
		$this->pdf->setMargins($this->getLeftMargin(),10,10,true);
		$this->pdf->addPage();
		
		$this->pdf->setTextColorArray($this->colorPalette[0]);
		$this->pdf->setFontSize(36);
		$this->pdf->setFont('opensansb');
		$this->pdf->Write(0,"Adressbuch");
		$this->pdf->Ln(18);

		$this->pdf->setFontSize(9);
		$this->pdf->setCellHeightRatio(1.444444);
		$this->pdf->setFont('opensans');
		$this->pdf->Write(0,"Dieses Gemeindeadressbuch ist zum Austausch innerhalb der ETG Scheppach bestimmt. Durch deine Zugehörigkeit zur ETG erhältst du dieses Exemplar des Adressbuches.\n\nBitte nutze die Daten ausschließlich für den Zweck der Kontaktaufnahme bezüglich Angelegenheiten der ETG Scheppach.\n\nDie Veröffentlichung, die Weitergabe oder die Vervielfältigung dieser Daten ist untersagt.\n\nFalls du Fragen zum Adressbuch oder eine Änderung der Daten hast, wende dich bitte an Siegfried Heer.\n\nDie mit einem Stern (*) markierten Personen sind Mitglieder der Gemeinde.\n\n");

		$this->pdf->setFillColor(0,0,0,20);
		$this->pdf->setTextColorArray($this->colorPalette[0]); 
		$this->pdf->MultiCell($w=111,$h=40,'',$border=0,$align='R',$fill='dedede',$ln=1,$x=0,$y=116);
		$this->pdf->ImageSVG(BASEDIR.'/assets/svg/logo-etg.svg',21.5, 125, $w=45, $h=8);
		$this->pdf->setY(135);
		$this->pdf->Write(0,"Rappacher Str. 24\n74626 Bretzfeld-Scheppach");
		

		$this->pdf->addPage();
		$date = new \DateTime();
		$this->pdf->setY(132);
		$this->pdf->setTextColorArray($this->colorPalette[0]); 
		$this->pdf->Write(0,"Stand: ".$date->format('d.m.Y'));
	}

	public function output()
	{
		$this->pdf->Output($this->outputPath, 'F');
	}

	public function writeLineWithIcon($text, $icon='', $font='opensans')
	{
		if($text){
			$this->pdf->setFontSize(11);
			$this->pdf->setFont($font);
			$this->pdf->ImageSVG(BASEDIR.'/assets/svg/'.$icon.'-grey.svg', $x=$this->pdf->getMargins()['left']+1, $y=$this->pdf->getY(), $w=3, $h=5);
			$this->pdf->setX($this->pdf->getMargins()['left']+4);
			$this->pdf->Write(5,$text);
			$this->pdf->Ln(6);
		}
	}

	public function writePersonName($person)
	{
		$this->pdf->setFontSize(14);
		$this->pdf->setFont('opensansb');
		$this->pdf->Write(0,$person->vorname);

		//Status 2 = verheiratet
		if($person->status_id == 2)
		{
			$this->pdf->Write(0, "*");
		}

		if(!is_null($person->birthday))
		{
			$this->pdf->setFontSize(9);
			$this->pdf->setFont('opensans');
			$this->pdf->Write(7.5,' ('.$person->birthday->format('d.m.').')');
		}

		$this->pdf->Ln(8);

	}

	public function writeContactDetails($person)
	{
		$this->writeLineWithIcon($person->telefonprivat,'phone');
		$this->writeLineWithIcon($person->telefonhandy,'mobile');	
		$this->writeLineWithIcon($person->email,'email');
		
		$this->pdf->Ln(4);
	}

	public function getLeftMargin()
	{
		if($this->pdf->getPage()%2)
		{
			$leftMargin = 25;
		}
		else
		{
			$leftMargin = 25;
		}
		return $leftMargin;
	}

	public function writeRegistry($name)
	{
		$registry = '';
		$alphabet = range('A', 'Z');

		$addToMarginLeft = -10;
		if($this->pdf->getPage()%2)
		{
			$addToMarginLeft = 70; 
		}
		
		for ($i = 0; $i < count($alphabet); $i++) {
			$height = ($i * 4.7) + 10;
			$this->pdf->SetXY($this->pdf->getMargins()['left']+$addToMarginLeft,$height);
			if ($alphabet[$i] === substr($name, 0, 1)) {
				$this->pdf->setFont('opensansb');
				$this->pdf->setTextColorArray($this->colorPalette[1]);
				$this->pdf->Cell(4,4,$alphabet[$i], 0, false, 'C');
			} else {
				$this->pdf->setFont('opensans');
				$this->pdf->setTextColorArray($this->colorPalette[2]);
				$this->pdf->Cell(4,4,$alphabet[$i], 0, false, 'C');
			}
		}
	}

	public function getOutputPath(){
		return $this->outputPath;
	}
}