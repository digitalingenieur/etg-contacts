<?php

namespace App\Services;

class PDF
{
	
	private $pdf;

	private $pages = array();

	public function __construct()
	{
		$format = $this->getPageFormat();

		$this->pdf = new \TCPDF('P','mm',$format);
		
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);

		$this->pdf->SetAutoPageBreak(FALSE);

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
		$this->pdf->addPage();

		$this->pdf->setTextColor(100,100,100,100);
		$this->pdf->setFontSize(18);
		$this->pdf->setFont('opensansb');

		$this->pdf->Write(0,$person->name);
		$this->pdf->Ln(10);

		$this->pdf->setFontSize(14);
	
		$this->pdf->Write(0,$person->vorname);

		//Status 2 = verheiratet
		if($person->status_id == 2)
		{
			$this->pdf->Write(0, "*");
		}

		
		$printPartner = true;
		if($person->partner)
		{
			if(($person->email == $person->partner->email || $person->partner->email=='') &&
				($person->telefonhandy == $person->partner->telefonhandy || $person->partner->telefonhandy=='' ) &&
				($person->telefonprivat == $person->partner->telefonprivat || $person->partner->telefonprivat==''))
			{
				$this->pdf->Write(0,' und '. $person->partner->vorname);
				if($person->partner->status_id == 2)
				{
					$this->pdf->Write(0, "*");
				}
				$printPartner = false;
			}
		}

		$this->pdf->Ln(8);
		
		
		$this->writeLnWIcon($person->telefonprivat,'phone');
		$this->writeLnWIcon($person->telefonhandy,'mobile');	
		$this->writeLnWIcon($person->email,'email');
		
		$this->pdf->Ln(4);

		
		if($person->partner && $printPartner){
			$this->pdf->setFont('opensansb');
			$this->pdf->setFontSize(14);
			$this->pdf->Write(0,$person->partner->vorname);
			if($person->partner->status_id == 2)
			{
				$this->pdf->Write(0, "*");
			}

			if($person->partner->name != $person->name)
			{
				$this->pdf->Write(0,' '.$person->partner->name);
				
			}
			$this->pdf->Ln(8);
			
			if($person->telefonhandy != $person->partner->telefonhandy)
			{
				$this->writeLnWIcon($person->partner->telefonhandy,'mobile');
			}
			
			if($person->email != $person->partner->email)
			{
				$this->writeLnWIcon($person->partner->email,'email');
			}
			
			
			$this->pdf->Ln(4);
		}
		//$this->pdf->ImageSVG(BASEDIR.'/assets/svg/address-grey.svg', $x=50, $y=$this->pdf->getY()-5, $w=30, $h=35);
		$this->pdf->setFont('opensansb');
		$this->pdf->ImageSVG(BASEDIR.'/assets/svg/address.svg', $x=11, $y=$this->pdf->getY(), $w=3, $h=5);
		$this->pdf->setX(14);
		$this->pdf->Write(0,'Adresse');
		$this->pdf->setFont('opensans');
		$this->pdf->Ln(6);
		$this->pdf->setFontSize(11);
		$this->pdf->Write(0,$person->strasse);
		$this->pdf->Ln(6);
		$this->pdf->Write(0,$person->plz. ' ' .$person->ort);
		//if($person->zusatz)	$address[] = $person->zusatz;
			
		//$this->pdf->addFont('fa400');
		///$this->pdf->setFont('fa400','');
		//$this->pdf->writeHTML('&#xf0c9;');
		
		//$this->pdf->ImageSVG(BASEDIR.'/assets/svg/phone.svg', $x=10, $y=40, $w=4, $h=4);
		
		$this->pdf->Ln(10);
		
		if($person->children)
		{
			$this->pdf->setFont('opensansb');
			$this->pdf->Write(0,"Kinder");
			$this->pdf->Ln(6);	

			$singleColumn = true;
			if(count($person->children)>3){
				$singleColumn = false;
			}

			$firstColumn = ceil(count($person->children)/2);
		
			$this->pdf->setFont('opensans');
			usort($person->children,function($a,$b){return $a->age < $b->age;});
			foreach($person->children as $child){
				$this->pdf->Write(5,$child->vorname.' ('.$child->birthday->format("d.m.Y").')');
				$this->pdf->Ln(6);
			}
		}

		$registry = '';
		$alphabet = range('A', 'Z');
		
		for ($i = 0; $i < count($alphabet); $i++) {
			$height = ($i * 4.7) + 8;
			$this->pdf->SetXY(100,$height);
			if ($alphabet[$i] === substr($person->name, 0, 1)) {
				$this->pdf->setFillColor(0,0,0,0);
				$this->pdf->setFont('opensansb',8);
				$this->pdf->setTextColor(99,70,24,7);
				$this->pdf->Cell(4,4,$alphabet[$i], 0, false, 'C');
			} else {
				$this->pdf->setFillColor(0,0,0,0);
				$this->pdf->setFont('opensans',6);
				$this->pdf->setTextColor(0,0,0,30);
				$this->pdf->Cell(4,4,$alphabet[$i], 0, false, 'C');
			}
		}

		$this->pdf->setFillColor(99,70,24,7);
		$this->pdf->setTextColor(0,0,0,0);
		$this->pdf->setFontSize(12);
		$this->pdf->MultiCell($w=111,$h=18,'',$border=0,$align='R',$fill='dedede',$ln=1,$x=0,$y=136);

	}

	public function output()
	{

		$this->pdf->Output(BASEDIR.'/output/test.pdf', 'F');
	}

	public function writeLnWIcon($text, $icon='')
	{
		if($text){
			$this->pdf->setFontSize(11);
			$this->pdf->setFont('opensans');
			$this->pdf->ImageSVG(BASEDIR.'/assets/svg/'.$icon.'.svg', $x=11, $y=$this->pdf->getY(), $w=3, $h=5);
			$this->pdf->setX(15);
			$this->pdf->Write(5,$text);
			$this->pdf->Ln(6);
		}
	}
}