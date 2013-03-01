<?php
include_once(dirname(__FILE__)."/../../../interface/globals.php");
require_once(dirname(__FILE__) ."/../../sql.inc");
require_once(dirname(__FILE__) ."/TableDataDisplay.php");
require_once(dirname(__FILE__) ."/DataDisplayObjectFactory.php");


class ContactAddressTable extends TableDataDisplay {

	
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
		$this->_idColumn = 'contact_address_id';
		$this->_fres = null;
		if($fromTable === NULL) {
			$this->_fromTable = "person";
			$this->_fromTableIDField = "person_id";
		}
		else if(strcasecmp($fromTable, "person") ===0) {
			//Add join clause information here.
			$this->_fromTable = "person";
			$this->_fromTableIDField = "person_id";
		}
		//Insert other clauses here.
		else {
			//Throw exception or perhaps try a basic table match.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "person_id";
		}
		$this->tableSetupValues();
		parent::__construct("contact_address");
	}
	
	private function tableSetupValues() {
		$this->_tableIDField = "contact_address_id"; 
	}
	
	public function setIdValue($val) {
		//Throw exception.
	}
	
	public function setSourceIdValue($val) {
		//Throw exception.
		$this->_fromTableIDValue = $val;
		$this->_dataReady = true;
	}
	
	
	public function setFromTableIDField($val) {
		$this->_fromTableIDValue = $val;
	}

	protected function getSQLString() {
		$sql = "select contact_address.* ".
				" FROM `".$this->_fromTable."` \n".
				" inner join contact on person.contact_id = contact.contact_id ".
				" inner join contact_to_contact_address on contact_to_contact_address.contact_id = contact.contact_id ".
				" inner join contact_address on contact_address.contact_address_id = contact_to_contact_address.contact_address_id ".
				" WHERE `".$this->_fromTable."`.`".$this->_fromTableIDField."` = '$this->_fromTableIDValue'";
		return $sql;
	} 
	
	public function insertData($val) {
		//1) Get contact_id, if missing, insert, done.
		//2) Insert contact_address
		//3) Take returned id and insert it into contact_to_contact_address
		$contactTable = DataDisplayObjectFactory::build("ContactTable",$this->_fromTable);
		$contactTable->setSourceIdValue($this->_fromTableIDValue);
		$contactID = $contactTable->getId(true);
		$val = array( "priority"=> "1", "type"=> "legal","address_title"=> "home",
						"street_line_1"=> "132 E st.", "street_line_2"=> "", "city"=> "Tampa", "state"=> "FL",
						"postal_code"=> "33333", "country_code" => "USA");
		
		$this->insertAddressData($contactID, $val );
		
		
	}
	
	private function insertAddressData($val) {
		//Get contact address id.
		$contact_addressid = -1;
		//Don't need this part since we don't have a contact_address yet.
		$contactObject = DataDisplayObjectFactory::build("contact"."Table");
 		
		$contactObject->setSourceIdValue($this->_fromTableIDValue);
		
		$contactObject->getID(true);
 		
 		$contact_addressid = sqlInsert("insert into `contact_to_contact_address` ".
					" (`contact_id`) ".
					" VALUES (?)",array($contactID));
// 		
		
		$insertID = sqlInsert("insert into `contact_address` ".
		"(`contact_address`.`contact_address_id`,".
		"`contact_address`.`priority`,".
		"`contact_address`.`type`,".
		"`contact_address`.`address_title`,".
		"`contact_address`.`street_line_1`,".
		"`contact_address`.`street_line_2`,".
		"`contact_address`.`city`,".
		"`contact_address`.`state`,".
		"`contact_address`.`postal_code`,".
		"`contact_address`.`country_code`,".
		"`contact_address`.`created_date`,".
		"`contact_address`.`activated_date`)".
		" values (?, ?, ?,".
		"?,". 
		"?,".// street_line_1
		"?, ".// street_line_2
		"?,".
		"?,".
		"?,".
		"?,".
		"DATE(NOW()), DATE(NOW()))",
				array($contact_addressid, $val['priority'], $val['type'],$val['address_title'],
						$val['street_line_1'], $val['street_line_2'], $val['city'], $val['state'],
						$val['postal_code'], $val['country_code'])
				);

		return $insertID; 
	}
	
	private function updateIfDifferent($val) {
		
		
		//UPDATE table_name
		//SET column1=value, column2=value2,...
		//WHERE some_column=some_value

		//split by .
		
		$arrayParameters = array();
		$startLine = "update `contact_address` SET ";
		
		foreach($val as $key => $value) {
			//skip db_id, db_action
			if(strcmp($key, "db_id") == 0 || strcmp($key, "db_action") == 0) {continue;}
			$startLine .= "$key = ?, ";
			$arrayParameters[] = $value;
		}
		//add Id
		$startLine = rtrim($startLine, ', ');
		$startLine .=" where `contact_address`.`contact_address_id` = ?";
		$arrayParameters[] = $val['db_id'];
		sqlStatement($startLine, $arrayParameters);
// 		$insertID = sqlInsert("insert into `contact_address` ".
// 				"(`contact_address`.`contact_address_id`,".
// 				"`contact_address`.`priority`,".
// 				"`contact_address`.`type`,".
// 				"`contact_address`.`address_title`,".
// 				"`contact_address`.`street_line_1`,".
// 				"`contact_address`.`street_line_2`,".
// 				"`contact_address`.`city`,".
// 				"`contact_address`.`state`,".
// 				"`contact_address`.`postal_code`,".
// 				"`contact_address`.`country_code`,".
// 				"`contact_address`.`created_date`,".
// 				"`contact_address`.`activated_date`)".
// 				" values (?, ?, ?,".
// 				"?,".
// 				"?,".// street_line_1
// 				"?, ".// street_line_2
// 				"?,".
// 				"?,".
// 				"?,".
// 				"?,".
// 				"DATE(NOW()), DATE(NOW()))",
// 				array($contact_addressid, $val['priority'], $val['type'],$val['address_title'],
// 						$val['street_line_1'], $val['street_line_2'], $val['city'], $val['state'],
// 						$val['postal_code'], $val['country_code'])
// 		);
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
	
	//General notes, should add somehting about

	public function save() {
		//Should throw error since you don't need to save this.
		//Should add table lock.
		//Probably need to add section about deleting IDs.
		
		foreach($this->_data as $row) {
			if($row['db_action'] == "insert") {
				unset($row['db_action']);
				$this->insertAddressData($row);
			}
			elseif($row['db_action'] == "update") {
				unset($row['db_action']);
				$this->updateIfDifferent($row);
			}
			else {
				//Throw exeception unknown action.
			}
		}
	}

	
	//Used when saving 
	public function addItem($val) {
		if(!isset($this->_data)) {
			$this->_data = array();
		}
		if(!isset($val['db_id']) && $val['action'] != "insert") {
			throw new Exception("No ID Column set for data. Try using \"+\" for adding");
		}
		$this->_data[] = $val;
	}
	public function delete() {
		//throws error since you shouldn't delete this.
	}
	public function insert() {
		//just to be consistent with SQL terms.
		return $this->addNew();
	}

}