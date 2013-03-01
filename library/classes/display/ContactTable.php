<?php
include_once(dirname(__FILE__)."/../../../interface/globals.php");
require_once(dirname(__FILE__) ."/../../sql.inc");
require_once(dirname(__FILE__) ."/TableDataDisplay.php");

class ContactTable extends TableDataDisplay {
	

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
		$this->_idColumn = "contact_id";
		if($fromTable === NULL) {
			$this->_fromTable = "person";
			$this->_fromTableIDField = "person_id";
		}
		else if(strcasecmp($fromTable, "person") ===0) {
			//Add join clause information here.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "person_id";
		}
		else if(strcasecmp($fromTable, "business") ===0) {
			//Add join clause information here.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "business_id";
		}
		//Insert other clauses here.
		else {
			//Throw exception or perhaps try a basic table match.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "person_id";
		}
		$this->tableSetupValues();
		parent::__construct("contact");
	}

	private function tableSetupValues() {
		$this->_tableIDField = "contact_id";
	}

	protected function getSQLString() {
		$sql = "select * ".
				" FROM `".$this->_fromTable."` \n".
				" inner join contact on  `".$this->_fromTable."`.contact_id = contact.contact_id ".
				" WHERE `".$this->_fromTable."`.`".$this->_fromTableIDField."` = '$this->_fromTableIDValue'";
		return $sql;
	}
	
	public function setIdValue($val) {
		//Throw exception.
	}

	public function setFromTableIDField($val) {
		$this->_fromTableIDValue = $val;
	}

	public function getID($insertIfNotFound) {
		//Find current id, if none then insert, return ID.
		//Insert Statement
		
		//GetID
		$lineVal = SQLQuery($this->getSQLString());
		if($lineVal !== false) {
			//We got an id.
			
			return $lineVal[$this->_idColumn];
		}
		else if($insertIfNotFound) {
			$newId = sqlInsert("insert into `contact` ". 
      				" (`source_table`, `source_table_id`) ".
  					" VALUES (?,?)",array($this->_fromTable,$this->_fromTableIDValue));
			return $newId;	
		}
	}
	public function updateData($sourceId, $val) {
		$query = $this->createUpdateQuery($this->_fromTable,$val,$sourceId);
		sqlStatement($query[0],$query[1]);
	}
	
	private function createUpdateQuery($tableName, $val,$sourceId) {
		$startString = "UPDATE `".$this->_table."` SET \n";
		$start = true;
		$retVal = array();
		foreach($val as $key => $value) {
			$startString .= "`$key` = ?, ";
			$retVal[] = $value;
		}
		$startString = substr_replace($startString,"",-2);
		$startString .= " Where ".$this->_idColumn." = ?";
		$retVal[] = $sourceId;
	
		return array($startString, $retVal);
	
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

	public function save() {
		//Should throw error since you don't need to save this.
		echo "inside ContactTable";
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
	
