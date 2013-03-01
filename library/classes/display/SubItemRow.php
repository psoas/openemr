<?php
class SubItemRow
{
	private $tableName;
	private $columnName;
	private $title;
	private $sequence;
	private $dataType;
	private $dataPopulated;
	private $list_id;
	private $description;
	private $fld_length;
	private $max_length;
	private $edit_options;
	
	
	//`field_id`,`group_name`,`title`, `seq`

	public function __construct($rowVal = NULL) {
		$this->dataPopulated = false;
		if($rowVal !== NULL) {
			$this->LoadFromDatabaseCall($rowVal);
			
		}
	}
	
	public static function getSplitTableAndColumnFromField_Id($val) {
		$results = explode(".", $val);
		if(count($results) == 2 && strlen($results[0]) > 0 && strlen($results[1]) > 0) {
			return $results;
		}
		else {
			throw new Exception("Incorrect number of table.column in SubItem");
		}
	}
	public function LoadFromDatabaseCall($rowVal) {
		//Sub-Items must use table.columnName, but a sub-item may have sub-items in it.
		if($rowVal['data_type'] != 36) {
			$lclVal = $this->getSplitTableAndColumnFromField_Id($rowVal['field_id']);
			$this->tableName = $lclVal[0];
			$this->columnName =  $lclVal[1];
		}
		else {
			$this->tableName = $rowVal['field_id'];
			$this->columnName =  "";
		}
		$this->title = $rowVal['title'];
		$this->dataType = $rowVal['data_type'];
		$this->sequence = $rowVal['seq'];
		$this->list_id = $rowVal[`list_id`];
		$this->description = $rowVal[`description`];
		$this->fld_length = $rowVal[`fld_length`];
		$this->max_length = $rowVal[`max_length`];
		$this->edit_options = $rowVal[`edit_options`];
		
		$this->dataPopulated = true;
	}
	
	public function LoadFromJSON($json) {
		
		$this->columnName =  $json->columnName;
		$this->title = $json->title;
		$this->sequence = $json->sequence;
	
		$this->dataPopulated = true;
	}
	
	//Provides mapping of simple names to database names. ("Seq" <==> "Sequence").
	public function getSequence() {
		return $this->sequence;
	}
	
	//Provides mapping of simple names to database names. ("Seq" <==> "Sequence").	
	public function setSequence($val) {
		$this->sequence = $val;
	}
	
	public function __get($property) {
		if (method_exists($this, 'get'.$property)) {
			return call_user_func(array($this, 'get'.$property));
		}
		else if(property_exists($this, $property)) {
			return $this->$property;
		}
		else {
			throw new Exception('Property "' . $property . '" does not exist.');
		}
		
	}
	
	public function __set($property, $value) {
		if (method_exists($this, 'get'.$property)) {
			call_user_func(array($this, 'set'.$property), $value);
		}
		else if(property_exists($this, $property)) {
			$this->$property = $value;
		}
		else {
			throw new Exception('Property "' . $property . '" does not exist.');
		}
	}
	
	public function getObjectAsArray() {
		return array("columnName" => $this->columnName, "dataType" => $this->dataType, "sequence" => $this->sequence, 
				"title" => $this->title);
	}
	

}