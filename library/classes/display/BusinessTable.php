<?php
include_once(dirname(__FILE__)."/../../../interface/globals.php");
require_once(dirname(__FILE__) ."/../../sql.inc");
require_once(dirname(__FILE__) ."/TableDataDisplay.php");

class BusinessTable extends TableDataDisplay {


	// 	protected $_fromTable;
	// 	protected $_fromTableIDField;
	// 	protected $_fromTableIDValue;

	// 	protected $_table;
	// 	protected $_tableIDField;
	// 	protected $_tableIDValue;



	private $_fres;
	private $_dataLoaded;
	private $_data;

	public function __construct($fromTable = NULL) {
		//If from table is business return something differnt.

		$this->_fres = null;
		$this->_idColumn = "business_id";
		if($fromTable === NULL) {
			$this->_fromTable = "person";
			$this->_fromTableIDField = "person_id";
		}
		else if(strcasecmp($fromTable, "insurance_company") ===0) {
			//Add join clause information here.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "business_id";
		}
		//Insert other clauses here.
		else {
			//Throw exception or perhaps try a basic table match.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "business_id";
		}
		$this->tableSetupValues();
		parent::__construct("business");
	}

	private function tableSetupValues() {
		$this->_tableIDField = "business_id";
	}

// 	protected function getSQLString() {
// 		$sql = "select * ".
// 				" FROM `".$this->_fromTable."` \n".
// 				" inner join contact on  `".$this->_fromTable."`.contact_id = contact.contact_id ".
// 				" WHERE `".$this->_fromTable."`.`".$this->_fromTableIDField."` = '$this->_fromTableIDValue'";
// 		return $sql;
// 	}

	public function setIdValue($val) {
		//Throw exception.
	}




	public function setFromTableIDField($val) {
		$this->_fromTableIDValue = $val;
	}

	public function getID($insertIfNotFound = false, $extraParameters = null) {
		//Find current id, if none then insert, return ID.
		//Insert Statement

		//GetID
		$sql1 = $this->getSQLString();
		$lineVal = SQLQuery($sql1);
		if($lineVal !== false) {
			//We got an id.
				
			return $lineVal[$this->_idColumn];
		}
		else if($insertIfNotFound) {
			//create contact table with -1.
			//insert business
			//update contact with correct information.
			$contactTable = DataDisplayObjectFactory::build("ContactTable","business");
			$contactTable->setSourceIdValue(-1);
			$contactID = $contactTable->getId(true);
			
			$newBusinessId = sqlInsert("insert into `business` ".
					" (`contact_id`) ".
					" VALUES (?)",array($contactID));	
			
			
			$newId = sqlInsert("insert into `business_to_source_table` ".
					" (`source_table`, `foreign_key_id`, `business_id`) ".
					" VALUES (?,?,?)",array($this->_fromTable,$this->_fromTableIDValue, $newBusinessId));
			
			$contactTable->updateData($contactID,array("source_table_id"=>$newBusinessId));
			return $newBusinessId;
		}
		else {
			return false;
		}



	}
	//Probably need a function for doing the innner join.
	public function getDataAllArray() {
		//excute lookup and return results.
		$retrunVal = array();
		$sql = $this->getSQLstring();
		$fres = sqlStatement($sql);
		while($frow = sqlFetchArray($fres)) {
			$retrunVal[] = $from['1'];
		}
		return $retrunVal;

	}

	public function getData($id) {
		//excute lookup and return results.
		if($this->_fres === null) {
			$sql = $this->getSQLstring();
			$this->_fres = sqlStatement($sql);
		}
		return sqlFetchArray($this->_fres);
	}





	//General notes, should add somehting about

	public function save($val) {
		//Should throw error since you don't need to save this.
		//Do Query to find matches
		//If 0 items then update
		//If 1 then skip, since it is the same.
		//Does not check for bad data.
		//Add check for no data.
		$valueArray =array();
		$sql = "Select count(*) as `count` from `".$this->_table."` where ";
		
		foreach($val as $key => $value) {
			if($value!== NULL) {
				
				$sql .= "`$key` = ? and ";
				$valueArray[] = $value; 
			}
		}
		//Remove ", "
		$sql = substr_replace($sql,"",-4);
		$count = 1;
		$fres = sqlStatement($sql, $valueArray);
		while($frow = sqlFetchArray($fres)) {
			$count = $frow['count'];
		}
		if($count == 0) { //Do update, otherwise no action.
			$i = 0;
		}
	}

	public function addNew() {
		//adds new and returns an id.
		//Also needs to add source table on the insert.

	}
	public function delete() {
		//throws error since you shouldn't delete this.
	}
	public function insert() {
		//just to be consistent with SQL terms.
		return $this->addNew();
	}

}

