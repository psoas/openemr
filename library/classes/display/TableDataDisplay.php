<?php
//This is the top object.
class TableDataDisplay {
	
	protected $_fromTable;
	protected $_fromTableIDField;
	protected $_fromTableIDValue;
	
	protected $_table;
	protected $_tableIDField;
	protected $_tableIDValue;
	
	protected $_dataReady;
	
	protected $_numberOfRows;
	
	private $_fres;
	private $_dataLoaded;
	private $_data;
	private $_rowCount; //Used to generate IDs if none present.
	protected $_primary_key;
	
	protected $_idColumn = 'id'; //Used to generate IDs if none present.
	
	
	public function __construct($tableName, $fromTable =null) {
		//checks that table exists.
		$this->_table = $tableName;
		$this->_dataReady = false;
		//Add something to get all columns
		// select `insurance_company`.*, business.*, business_name.* FROM
		// (select -1 as `business_id`) as ff 
		// left join `insurance_company` on ff.business_id = `insurance_company`.`business_id`
		// left join `business` on business.business_id = `insurance_company`.business_id
		// left join business_name on business.business_id = business_name.business_id and business_name.priority = 1
		//  WHERE `insurance_company`.`business_id` = 3 or ff.business_id =-1
		
	}
	
	public function isGenericTable() {
		if(strcmp(get_class($this), "TableDataDisplay") === 0) {
			return true;
		}
		else {
			return false;
		}
		
	}
	public function setIdValue($val) {
		$this->_tableIDField = $val;
		$this->_dataReady = true;
	}
	
	protected function readyToRetrieve() {
		//Will add other logic.
		return $this->_dataReady;
	}
	
	protected function getPrimaryKey() {
		$_db = $GLOBALS['adodb']['db'];
		
		$lclVal00 = $_db->MetaColumns($this->_table);
		
		$retVal = "";
		foreach($lclVal00 as $column) {
			if($column->primary_key) {
				return $column->name;
			}
		}
	}
	
	protected function getSQLString() {
		//Note: for use with getData you should make sure the values are returned in the same order.
		if(!isset($this->_tableIDField)) {
			$this->_tableIDField = $this->getPrimaryKey();
		}
		if($this->_table == "patient_insurance") {
			$this->_tableIDValue = 1;
		}
		
		$sql = "select * ".
				" FROM `".$this->_table."` \n".
				" WHERE `".$this->_table."`.`".$this->_tableIDField."` = '$this->_tableIDValue'";
		return $sql;
	}
	
	//Probably need a function for doing the innner join.
	public function getDataAllArray($id) {
		//excute lookup and return results.
		$retrunVal = array();
		$sql = $this->getSQLString();
		
		$fres = sqlStatement($sql);
		while($frow = sqlFetchArray($fres)) {
			$retrunVal[] = $frow;
		}
		return $retrunVal;
	
	}

	public function setSourceIdValue($val,$table = "person") {
		//Throw exception.
		$this->_fromTableIDValue = $val;
		if(!isset($this->_fromTable)) {
			$this->_fromTable = $table;
		}
		$this->_dataReady = true;
	}
	
	public function getData($id) {
		//excute lookup and return results.
		if($this->_fres === null) {
			$this->_rowCount = 0;
			$sql = $this->getSQLString();
			$this->_fres = sqlStatement($sql);
		}
		$lclVal = sqlFetchArray($this->_fres);
		if($lclVal && count($lclVal) > 0 && (!isset($lclVal['id']))) {
			//Id Column is available.
			if(isset($lclVal[$this->_idColumn])) {
				//Use the ID column.
				$lclVal['id'] = $lclVal[$this->_idColumn];
			}
			else {
				$lclVal['id'] = $this->_rowCount;
			}
		}
		$this->_rowCount++;
		return $lclVal; 
	
	}
	
	public function getJSONObject() {
	
		$retRowVales = array();
		while($rowValue = $this->getData($this->_fromTableIDValue)) {
			//foreach($result as $value) {
			$retRowVales[] = $rowValue;
			$rowsFound++;
		}
		return json_encode(array("table" => $this->_table,
								"tableId" => $this->_tableIDField,
								"tableIdValue" => $this->_tableIDValue,
								"fromTable" => $this->_fromTable,
								"fromTableColumn" => $this->_fromTableIDField,
								"fromTableIdValue" => $this->_fromTableIDValue,
								"numberOfRows" => $rowsFound,
								"rows" => $retRowVales));
	}
	
	
	//General notes, should add somehting about
	
	public function save() {
		//Should throw error since you don't need to save this.
		
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
	
	public function checkForeignRelationship() {
		
	}

}