<?php
include_once(dirname(__FILE__)."/../../../interface/globals.php");
require_once(dirname(__FILE__) ."/../../sql.inc");
require_once(dirname(__FILE__) ."/TableDataDisplay.php");
require_once(dirname(__FILE__) ."/DataDisplayObjectFactory.php");


class PersonTable extends TableDataDisplay {

	
	// 	protected $_fromTable;
	// 	protected $_fromTableIDField;
	// 	protected $_fromTableIDValue;
		
	// 	protected $_table;
	// 	protected $_tableIDField;
	// 	protected $_tableIDValue;
	
	
	
	private $_fres;
	private $_dataLoaded;
	private $_data;
	//private $_tableType;
	
	private $_hasColumns;
	private $_columnList;
	
	public function __construct($fromTable = NULL) {
		//If from table is business return something differnt.
		$this->_idColumn = 'person_id';
		$this->_fres = null;
		
		if($fromTable === NULL) {
			$this->_fromTable = "insurance_company";
			$this->_fromTableIDField = "business_id";
		}
		else if(strcasecmp($fromTable, "person_secondary_contact") ===0) {
			//Add join clause information here.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "person_secondary_contact_id";
		}
		//Insert other clauses here.
		else {
			//Throw exception or perhaps try a basic table match.
			$this->_fromTable = $fromTable;
			$this->_fromTableIDField = "business_id";
		}
		$this->tableSetupValues();
		parent::__construct("person_secondary_contact");
	}
	
	private function tableSetupValues() {
		$this->_tableIDField = "business_id"; 
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
	
	public function createAppenedColumns($table, $outputType = "SQLCommaList") {
		$_db = $GLOBALS['adodb']['db'];
		
		$lclVal00 = $_db->MetaColumns($table);
		
		if($outputType == "SQLCommaList") {
			$retVal = "";
			foreach($lclVal00 as $column) {
				$retVal .= "$table".".".$column->name." as `".$table.".".$column->name."`, ";
			}
			$retVal = substr_replace($retVal,"",-2);
		}
		else if($outputType == "TableNameList") {
			$retVal = array();
			foreach($lclVal00 as $column) {
				$retVal[] = $table.".".$column->name;
			}
		}
		else if($outputType == "TableListArray") {
			$retVal = array();
			foreach($lclVal00 as $column) {
				$retVal[$table] = $column->name;
			}
		}
		else if($outputType == "Array") {
			$retVal = array();
			foreach($lclVal00 as $column) {
				$retVal[] = $column->name;
			}
		}
		return $retVal;
	}

	protected function getSQLString() {
// 		select insurance_company.cms_id, insurance_company.freeb_type, insurance_company.x12_receiver_id,
// 		insurance_company.x12_default_partner_id, insurance_company.alt_cms_id,
// 		business.type, business.subtype,
// 		business_name.business_name
// 		from insurance_company
// 		inner join business on insurance_company.business_id = business.business_id
// 		inner join business_name on business.business_id = business_name.business_id
// 		where business_name.priority = 1;
		
		if(!isset($this->_fromTableIDValue)) {
			$this->_fromTableIDValue = 0;
		}
		
		$sql = "select ".$this->createAppenedColumns($this->_fromTable).", ".$this->createAppenedColumns("business") .", ".
				$this->createAppenedColumns("business_name")." ".
				" FROM `".$this->_fromTable."` \n".
				" inner join `business` on business.business_id = `".$this->_fromTable."`.business_id ".
				" inner join business_name on business.business_id = business_name.business_id and business_name.priority = 1 ".		
				" WHERE `".$this->_fromTable."`.`".$this->_fromTableIDField."` = $this->_fromTableIDValue";
		return $sql;
	} 
	
	public function getColumns() {
		$returnVal = array();
		$returnVal[$this->_fromTable] =  createAppenedColumns($this->_fromTable,"TableListArray");
		$returnVal["person"] =  createAppenedColumns("person","TableListArray");
		$returnVal["person_last_name"] =  createAppenedColumns("person_last_name","TableListArray");
		$returnVal["person_first_name"] =  createAppenedColumns("person_first_name","TableListArray");
		
		return $returnVal;
	}
	
	public function insertData($val) {
		//1) Insert Person
		//2) Insert into Secondary Contact Table, referencing orginal person

		$personTable = DataDisplayObjectFactory::build("PersonTable",$this->_fromTable);
		
		//Add logic that if it is a source table it doesn't need a source id.
		$personTable->setSourceIdValue($this->_fromTableIDValue);
		
		$personID = $personTable->getId(true);
		
		//Need to split between source table and business name.
		
		//Type is business_name type
		
		//person => SSN
		//person => Gender
		//person_last_name => Last_name
		//person_first_name => first_name
		//person_secondary_contact => person id
		// ??? Person source.
		
		$val = array(array($this->fromTable => array("x12_receiver_id" => "", "x12_default_partner_id" => "", "alt_cms_id" => "")),
					"business" => array("type" => "TypeTest", "subtype" => "SubTypeTest"),
					"business_name" => array("business_name" => "fff"));	
				
		//Currently no values to insert
		$this->updateBusinessData($businessID, $val["business"] );
		
		
		//$this->insertChildData("business_name",$businessID, $val['business_name'] );
		
		//$this->insertTableTypeData($businessID, $val );
		
	}
	
	private function insertChildData($tableName, $sourceID, $val) {
// 		$businessTable = DataDisplayObjectFactory::build($tableName);
		
// 		//Add logic that if it is a source table it doesn't need a source id.
// 		$businessTable->setSourceIdValue($sourceID);
		
// 		$business_name_ID = $businessTable->getId(false);
// 		if($business_name_ID === false) {
// 			//need to insert data.
// 			sqlStatement("insert into ".$tableName." (`business_name`) VALUES (?)",$val);
// 		}
// 		else {
// 			//Need to update data.
// 			$a = array_push(array($sourceID),$val);
// 			//sqlStatement("update `".$tableName."` SET `business_name`=? where `business_id`=?",$a );
// 		}
	}
	
	private function insertTableTypeData($sourceId, $val) {
		
	}
	
	//Should be moved to business object.
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
	
	private function updateBusinessData($sourceId, $val) {
		$val = $this->createUpdateQuery("business",$val,$sourceId);
		
		sqlStatement($val[0],$val[1]);
	}
	
	private function insertBusinessData($sourceId, $val) {
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
		if(!$this->_hasColumns) {
			//fetch columns.
			//Get Columns - Auto Increment?
		}
 		
 		
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

	public function save($val, $sourceType = NULL) {
		//insert person
		//insert secondary_person_contact of primary person
		
		//insert into person () values ();
		//Get id
		//Insert FirstName
		//Insert Last Name
		
		//insert into secondary contact
		
		
		
		$sourcePersonID = 23;
		if(isset($sourceType) && $sourceType == "secondary_contact") {
			//PersonID = 23
			
		}
		else {
			throw new Exception("Saving to tables other than secondary_contact not supported");
		}
		
		// 		insert into person (social_security_number)
		// 		values ('55');
		
		$newPersonID = sqlInsert("insert into `person` ".
				" (`social_security_number`) ".
				" values (?) ",
				array($val['person']['SSN']));
		
		// 		insert into person_name (person_id, person_last_name, person_first_name, priority, type)
		// 		values (24, 'Paulus','chris-friend',1,'legal');
		
		sqlInsert("insert into `person_name` (person_id, person_last_name, person_first_name, priority, type) ".
		 		" values (?,?,?,?,?)",array($newPersonID, $val["person_last_name"]["person_last_name"], 
					$val["person_first_name"]["person_first_name"],1,'legal'));
		// 		insert into person_secondary_contact (source_person_id, secondary_contact_person_id, priority, relationship)
		// 		values (24, 23,1, 'Wife')
		
		sqlInsert("insert into person_secondary_contact (source_person_id, secondary_contact_person_id, priority, relationship) ".
		 		" values (?,?,?,?)",array($newPersonID, $sourcePersonID, 1, $val["person_seconday_contact"]["relationship"]));
	}
	
	public function search($val, $sourceType = NULL) {
		if(isset($sourceType) && $sourceType == "secondary_contact") {
			$sourceID = 23;
				
		}
		else {
			throw new Exception("Searching tables other than secondary_contact not supported");
		}
		//May need to review for potionally listing the relationship.
		$fres = sqlStatement("select person.person_id, person_name.person_first_name, person_name.person_last_name, person.social_security_number ".
					" from person inner join person_name on person.person_id = person_name.person_id " . 
					" where person_name.type = 'legal' and person.person_id  <> ?", array($val));
		return $fres;
		
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