<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Services\Person; 
use App\Services\Page; 
use App\Services\PDF; 


class CreateAddressBook extends Command
{
    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('create')

        // the short description shown while running "php bin/console list"
        ->setDescription('Creates the Address Book')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allows you to create the address book...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdf = new PDF();

        $output->writeln("Get All Persons from Churchtools");

        $client = new \GuzzleHttp\Client(['cookies'=>true]);
        $res = $client->request('POST', 'https://etg.church.tools/index.php?q=login/ajax', 
            [ 
                'form_params' => [
                    'func'  => 'loginWithToken',
                    'id'    => '1',
                    'token' => '123',
                    'directtool' => 'cli-tool'
                    ]
            ]);
        $output->writeln(
            $res->getBody()
        );

        $res = $client->request('POST','https://etg.church.tools/index.php?q=churchdb/ajax',
            ['form_params' => 
                [
                    'func'  => 'getAllPersonData'
                ]
            ]);

        $output->writeln("Start juggling persons and get details from Churchtools...");
        
        $allPersonData = json_decode($res->getBody())->data;

        $progressBar = new ProgressBar($output, count((array) $allPersonData));

        $objPersons = array();
        foreach($allPersonData as $person){

            $progressBar->advance();
            
            if(array_key_exists($person->p_id,$objPersons))
            {
                
                $objPerson = $objPersons[$person->p_id];
                $objPerson->setDetails($person);
            }
            else
            {
                $objPerson = new Person($person);
            }

            //Filter nach Personen, die nicht in Adressbuch sein wollen (Gruppe ID 49)
            if(!$objPerson->isInGroup(49))
            {  
                if(array_key_exists($person->p_id,$objPersons))
                {
                    $objPersons[$person->p_id]->skip = true;
                }
                else{
                    $objPerson->skip = true; 
                    $objPersons[$person->p_id] = $objPerson;
                }
                continue;
            }
           
            $res = $client->request('POST','https://etg.church.tools/index.php?q=churchdb/ajax',
                ['form_params' => 
                    [
                   'func'  => 'getPersonDetails',
                   'id'    => $person->p_id
                 ]
              ]);

            $objPerson->setDetails(json_decode($res->getBody())->data);

            $objPerson->checkPersonType();

            if($objPerson->type == "owner")
            {
                $objPersons[$person->p_id] = $objPerson;

            }
            if($objPerson->type == "partner")
            {
                if(array_key_exists($objPerson->partnerId,$objPersons))
                {
                    $objPersons[$objPerson->partnerId]->partner = $objPerson;
                }
                else
                {
                    $newPersonArray = [ 'p_id' => $objPerson->partnerId];
                    $newPersonArray['partner'] = $objPerson;
                    $objPersons[$objPerson->partnerId] = new Person($newPersonArray);
                }

                //Entferne Person aus objPerson, da kein SiteOwner!
                if(array_key_exists($objPerson->p_id,$objPersons))
                {
                    unset($objPersons[$objPerson->p_id]);
                }

            }
            if($objPerson->type == "child")
            {
                $personNotInObjPersons = true;
                                
                //Ist ein Elternteil schon in $objPersons?
                foreach($objPerson->parentIds as $parentId)
                {
                    if(array_key_exists($parentId,$objPersons))
                    {        
                        //$output->writeln("Verschiebe $objPerson->vorname in $parentId");
                        $objPersons[$parentId]->addChildren($objPerson);
                        $personNotInObjPersons = false;
                    }
                }

                //Erstelle beide Elternteile in $objPersons
                if($personNotInObjPersons)
                {
                    foreach($objPerson->parentIds as $parentId)
                    {
                       // $output->writeln("Erstelle Elternteil in objPerson");
                        $newPersonArray = ['p_id' => $parentId];
                        $objPersons[$parentId] = new Person($newPersonArray);
                        $objPersons[$parentId]->addChildren($objPerson);
                    }
                    
                }
            }          
        }


        $progressBar->finish();
        $output->writeln("Done.");

        $output->writeln("\n Start Writing PDF file...");



        foreach($objPersons as $person)
        {
            if($person->skip)
            {
                if(isset($person->partner))
                {
                    if($person->partner->isInGroup(49)){
                        $person->partner->type= 'owner';
                        $person->partner->children = $person->children;
                        $objPersons[$person->partner->p_id] = $person->partner;
                    }
                }
                unset($objPersons[$person->p_id]);
            }
            
        }

        usort($objPersons, 
            function($a, $b)
            { 
                $name = strcmp($a->name, $b->name); 
                if($name === 0)
                {
                    return strcmp($a->vorname, $b->vorname);
                }

                return $name;
            }
        );

        foreach($objPersons as $pid=>$person)
        {
            //$output->writeln("(".$person->p_id.") ".$person->name.", ".$person->vorname." (Geschlecht: $person->sex / $person->type)");
            $pdf->addPage($person);
        }  

        $pdf->output();

        $output->writeln("Finish. PDF file written to PATH");
    }
}