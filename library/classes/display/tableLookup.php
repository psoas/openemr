<?php
class TableLookup {
	
	private $_mapping;
	private $_baseTable;
	
	public function __construct($baseTable) {
		$this->_baseTable = $baseTable;
		$this->_mapping = array();
	}
	
	//This returns the join lines.
	public function getJoinLines() {
		$retLine = "";
		foreach($this->_mapping as $lines) {
			$retLine .= "\ninner join `".$lines['table']."` on `".$lines['foreignTable']."`.`".$lines['foreignId']."` = ".
					"`".$lines['table']."`.`".$lines['localid']."`";
		}		
		return $retLine;
	}
	
	public function checkJoinLines() { 
		//Must check that localTable is not person or patient
		//It is possible to run this query against the database.
		//But really want we want check is that there is a string from 
	}
	
	public function addJoinLine($localTable, $localId, $foreignTable, $foreignId) {
		
		//$val->tableName, $val->fromID , $val->toTable, $val->toTableID
		$this->_mapping[] = array("table" => $localTable, "localid" => $localId, "foreignTable" => $foreignTable,
				"foreignId" => $foreignId);
	}
		
}