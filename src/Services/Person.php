<?php

namespace App\Services;

class Person
{

	/**
	 * 
	 * 
	 * Types: owner, partner, child
	 * 
	 */ 
	public $type = NULL; 

	public $partner = NULL;

	public $partnerId = NULL;

	public $children = array();

	public $parentIds = array();

	public $sex = NULL;

	public $skip = false;

	public function __construct($objPerson=[])
	{
		$this->setPropertyValues($objPerson);
	}

	public function setDetails($jsonDetails)
	{
		$this->setPropertyValues($jsonDetails);
	}

	public function setPropertyValues($jsonObject)
	{
		foreach($jsonObject as $key => $value)
		{
			if($key == 'geschlecht_no')
			{
				if($value == 1)
				{
					$this->sex = 'm';	
				} 
				elseif($value == 2)
				{
					$this->sex = 'w';	
				}
				else
				{
					$this->sex = 'u';	
				} 
				
				$this->sex = ($value == 1)? 'm' : 'w';
			}
			$this->{"$key"} = $value;
		}
	}

	public function addChildren(Person $child)
	{

		$child->age = NULL;
		$child->birthday = NULL;
		if(isset($child->geburtsdatum))
		{
			$child->birthday = new \DateTime($child->geburtsdatum);
			$today = new \DateTime();
			$age = $today->diff($child->birthday);
			$child->age = $age->format("%y"); 	
		}
	
		$this->children[] = $child;
	}

	public function isInGroup(int $groupId){
		if(property_exists($this,'groupmembers')){
			return property_exists($this->groupmembers, $groupId);	
		}
		else
		{
			return false;
		}
	}

	public function checkPersonType()
	{
		$this->checkRelationships();

		$this->type = 'owner';
	
		if($this->hasPartner() && $this->sex == 'w')
		{
			$this->type = 'partner'; 	
		}

		if($this->hasParents()){
			$this->type = 'child';
		}	
	}

	public function hasPartner(){
		
		if($this->partnerId == NULL)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public function hasParents()
	{
		if(empty($this->parentIds))
		{
			return false;
		}
		else
		{
			return true;
		}
	}


	private function checkRelationships(){
		foreach($this->rels as $rel)
		{
			//Partner
			if($rel->beziehungstyp_id == "2")
			{
				$this->partnerId = ($rel->vater_id == $this->p_id)? $rel->kind_id : $rel->vater_id;		
			}

			//Eltern->Kind
			if($rel->beziehungstyp_id == "1" || $rel->beziehungstyp_id == "3")
			{
				if($this->p_id != $rel->vater_id)
				{
					$this->parentIds[] = $rel->vater_id;
				}

			}
		}
	}
}