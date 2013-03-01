<?php
class PatientLookup
{
	private $_patientId;
	private $_personId;
	private $_objectReady;
	
	//Construct, object ready no.
	
	public function setPatientID($val) {
		$this->_objectReady = true;
	}
	
	public function setPersonID($val) {
		$this->_objectReady = true;
	}
	
	
	//Probably want to create a referenced table function for
	//look items up.
	
	public function getConnection($toTable, $fromTable = NULL) {
		//find if table contains person_id or patient_id.
		//List of tables with no obvious connection types.
		//Perhaps store in XML.
		//make lower case for comparisons.
		$toTable = strtolower($toTable);
		
		switch($toTable) {
			case "contact_address":
				
				break;
			//default:  perhaps throw error
			
			
			
		}//end case statement.
		
			
	}
	
	
}