<?php
// display/CommonDatabaseQueries.php

//Import 
include_once(dirname(__FILE__)."/../../../interface/globals.php");
require_once(dirname(__FILE__) ."/../../sql.inc");


function getInsuranceDataForDemographics($val) {

	//Need patient insurance information
	//Get Subscriber from relationship
	//Using an Id passed in.	
	$fres = sqlStatement("select person.person_id, person_name.person_first_name, ".
	" person_name.person_last_name, person_secondary_contact.relationship ".
	" from person_secondary_contact ".
	" inner join person on person.person_id = person_secondary_contact.source_person_id ".
	" inner join person_name on person.person_id = person_name.person_id ".
	" where person_secondary_contact.secondary_contact_person_id = ? ".
	" order by person.person_id ",array($val));
	$returnVal = array();
	$nextIds = array();
	$resultSet = array();
	while($frow = sqlFetchArray($fres)) {
		$frow['dirty'] = false;
		$resultSet[] = $frow;
		
		$nextIds[] = $frow['person_id'];
	}
	//Get current insurance information.
	
	if(count($nextIds) > 0) { //Have results
		$fres = sqlStatement(" select person.person_id, contact_address.* from person ".
				" inner join contact on person.contact_id = contact.contact_id ".
				" inner join contact_to_contact_address on contact.contact_id = contact_to_contact_address.contact_id ".
				" inner join contact_address on contact_address.contact_address_id = contact_to_contact_address.contact_address_id ".
				" where person.person_id IN (".createInClause($nextIds).") ".
				" order by person.person_id ", $nextIds);
		$personLine = 0;
		while($frow = sqlFetchArray($fres)) {
			//perhaps add lines to unset.
			//unset($frow['abc']);
			if($resultSet[$personLine]['person_id'] != $frow['person_id']) {
				for($i = $personLine; $i < count($nextIds); $i++) {
					if($resultSet[$i]['person_id'] == $frow['person_id']) {
						$personLine = $i;
						break;
					}
				}
			}
			$frow['dirty'] = false;
			$resultSet[$personLine]['address'][] = $frow;
		}
		//Get Employment
		$fres = sqlStatement(" select person.person_id, business.*, business_name.*  from person ".
		" inner join person_employment on person.person_id = person_employment.person_id ".
		" inner join business on business.business_id = person_employment.business_id ".
		" inner join business_name on business.business_id = business_name.business_id".
		" where person.person_id in (".createInClause($nextIds).") and business_name.type ='Legal'", $nextIds);
		$businessIds = array();
		$oldBusinessId = -1;
		$personLine = 0;
		while($frow = sqlFetchArray($fres)) {
			//perhaps add lines to unset.
			//unset($frow['abc']);
			
			if($resultSet[$personLine]['person_id'] != $frow['person_id']) {
				for($i = $personLine; $i < count($nextIds); $i++) {
					if($resultSet[$i]['person_id'] == $frow['person_id']) {
						$personLine = $i;
						break;
					}
				}
			}
			if($oldBusinessId != $frow['business_id']) {
				$oldBusinessId = $frow['business_id'];
				$businessIds[] = $frow['business_id'];
				$resultSet[$personLine]['employer'][] = array(
						"business_id" => $frow['business_id'],
						"business_name" => $frow['business_name'],
						"dirty" => false);
			}
			
		}
		
		//create an array of
		//Get Subscriber Employeer Address
		if(count($businessIds) > 0) {
			$fres = sqlStatement(" select * from business ".
				" inner join contact on business.contact_id = contact.contact_id ".
				" inner join contact_to_contact_address on contact.contact_id = contact_to_contact_address.contact_id ".
				" inner join contact_address on contact_address.contact_address_id = contact_address.contact_address_id ".
				" where business.business_id in (".createInClause($businessIds).") ".
				" order by business.business_id", $businessIds);
			$oldBusinessId = -1;
			while($frow = sqlFetchArray($fres)) {
				//perhaps add lines to unset.
				//unset($frow['abc']);
				//Search for values to match
				
				//Find business.
				for($i =0; $i < count($resultSet); $i++) {
					for($j = 0; $j < count($resultSet[$i]['employer']); $j++) {
						if($resultSet[$i]['employer'][$j]['business_id'] == $frow['business_id']) {
							$frow['dirty'] = false;
							$resultSet[$i]['employer'][$j]['address'][] = $frow;
						}
					}
				}
				
			}
		}
	}
	
	return $resultSet;	
}

function setInsuranceDataForDemographics($val) {
	$actionLine = "Actions: ";
	foreach($val as $line) {
		//list of contacts.
		//need to add dirty line.
		if($line['dirty'] == true && $line['person_id'] < 0) {
			//delete value.
			$actionLine .= " Deleting Person Value. ";
		}

		else if ($line['dirty'] == false && $line['person_id'] < 0) {
			//Insert
			$actionLine .= " Inserting Person Value. ";
		}
		else if($line['dirty'] == true && $line['person_id'] > -1) {
			//update values.
			$actionLine .= " Updating Person Value. ";
		}
		foreach($line['address'] as $person_address) {
			if($person_address['dirty'] == true && $person_address['contact_address_id'] < 0) {
				//delete value.
				$actionLine .= " Deleting Person Address Value. ";
			}
				
			else if ($person_address['dirty'] == false && $person_address['contact_address_id'] < 0) {
				//Insert
				$actionLine .= " Inserting Person Address Value. ";
			}
			else if($person_address['dirty'] == true && $person_address['contact_address_id'] > -1) {
				//update values.
				$actionLine .= " Updating Person Address Value. ";
			}
				
			
		}
		foreach($line['business'] as $person_business){
			if($person_business['dirty'] == true && $person_business['business_id'] < 0) {
				//delete value.
				$actionLine .= " Deleting business Value. ";
			}
			
			else if ($person_business['dirty'] == false && $person_business['business_id'] < 0) {
				//Insert
				$actionLine .= " Inserting business Value. ";
			}
			else if($person_business['dirty'] == true && $person_business['business_id'] > -1) {
				//update values.
				$actionLine .= " Updating business Value. ";
			}
			
			
			foreach($person_business['address'] as $business_address){
				if($business_address['dirty'] == true && $business_address['contact_address_id'] < 0) {
					//delete value.
					$actionLine .= " Deleting business address. ";
				}
					
				else if ($business_address['dirty'] == false && $business_address['contact_address_id'] < 0) {
					//Insert
					$actionLine .= " Inserting business address. ";
				}
				else if($business_address['dirty'] == true && $business_address['contact_address_id'] > -1) {
					//update values.
					$actionLine .= " Updating business address. ";
				}
			}
		}
		
	}
	return $actionLine;
}


function createInClause($val) {
	$returnString = "";
	foreach($val as $value) {
		$returnString .= "?, ";
	}
	$returnString = substr_replace($returnString,"",-2);
	
	return $returnString;
}


function otherSQL() {
	//Actually don't need this.
	
	//Add list of person,id
	
	
	" select person.person_id, person_name.person_first_name, ".
	" person_name.person_last_name, person_secondary_contact.relationship ".
	" from person_secondary_contact ".
	" inner join person on person.person_id = person_secondary_contact.source_person_id ".
	" inner join person_name on person.person_id = person_name.person_id ".
	" where person_secondary_contact.secondary_contact_person_id = ";
	
	" select * from person ".
	" inner join contact on person.contact_id = contact.contact_id ".
	" inner join contact_to_contact_address on contact.contact_id = contact_to_contact_address.contact_id ".
	" inner join contact_address on contact_address.contact_address_id = contact_address.contact_address_id ".
	" where person.person_id = 24; ";
	
			" select * from person ".
			" inner join person_employement on person.person_id = person_employment.person_id ".
			" inner join business on business.business_id = person_employment.business_id ".
			" inner join business_name on business.business_id = business_name.business_id";
	// from business_id
	
	// get business address for employer.
	
	// get insurance address
	" select insurance_company.business_id  from person ".
	" inner join patient on patient.person_id = patient.person_id ".
	" inner join patient_insurance on patient.patient_id = patient_insurance.patient_id ".
	" inner join insurance_company on patient_insurance.insurance_company_id = insurance_company.insurance_company_id ";
}