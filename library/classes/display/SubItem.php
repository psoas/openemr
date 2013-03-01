<?php
//Class for handling SubItems
require_once ("SubItemRow.php");
require_once(dirname(__FILE__) ."/../../options_test.inc.php");
class SubItem
{
	//Return JSON
	//Update JSON
	
	private $_name;
	private $_table;
	private $_rowCount;
	public $_subItemRows;
	private $_dataLoaded;
	
	
	
	public function __construct($name) {
		if($name !== NULL) {
			$this->_name = $name;
		}
		else {
			throw new Exception("Name must be used to struct object.");	
		}
	}
	
	public function getDatafromDatabase() {
		$subItemResults = sqlStatement("select `field_id`,`group_name`,`title`, `seq`, `data_type`, ". 
				"`list_id`, `description`, `fld_length`, `max_length`, `edit_options` ".
				" from `layout_options` ".
				//TODOCmp: Need to add other values.
				"where `form_id` = 'SUB' AND `group_name` = '$this->_name' " .
				"order by `seq`"); 
		$this->_rowCount = 0;
		$this->_subItemRows = array();
		
		while($subItemRowFromDB = sqlFetchArray($subItemResults)) {
			$subItemRow = new SubItemRow($subItemRowFromDB);
			if(!isset($this->_table) && $subItemRow->dataType != 36) {
				$this->_table = $subItemRow->tableName;
			}
			else if(isset($this->_table) && $subItemRow->tableName != $this->_table && $subItemRow->dataType != 36) {
				throw new Exception("All Subitems must be from the same table.");
			}
			
			$this->_subItemRows[] = $subItemRow;
			$this->_rowCount++;
		}
		
		$this->_dataLoaded = true;
	}
	
	public function getFromJSONObject($json) {
		$this->_name = $json->name;
		$this->_table = $json->table;
		$this->_subItemRows = array();
		$this->_rowCount = 0;
		foreach($json->rows as $rows) {
			$subItemRow = new SubItemRow();
			$subItemRow->LoadFromJSON($rows);
			$this->_subItemRows[] =$subItemRow; 
			$this->_rowCount++;
		}
		$this->_dataLoaded = true;
	}
	
	public function saveToDatabase() {
		//First check that it exists.
		//Update any rows that exist
		//Insert new ones.
	
		$subItemResults = sqlStatement("select `seq` from layout_options
			where form_id = 'SUB' and group_name = '$this->_name' order by `seq`");
		
		$foundList = array();
		while($subItemFound = sqlFetchArray($subItemResults)) {
			$foundList[] = $subItemFound['seq'];
		}
		
		foreach($this->_subItemRows as $row) {
			$gg = $row->getSequence();
			$gg= $gg+1;
			if(count($foundList) == 0 || in_array($row->getSequence(),$foundList) === FALSE) { //All Inserts
				$lclString = ("INSERT INTO layout_options (" .
						"form_id, field_id, group_name, title, seq".
								
						") VALUES (" .
						"'SUB', ".
						"'$this->_table.$row->columnName', ".
						"'$this->_name', ".
						"'$row->title', ".
						 $row->getSequence().
						")");
			}
			else { //M
				$lclString = "UPDATE layout_options SET " .
						"field_id = '" . "$this->_table.$row->columnName',  ".
						"title = '$row->title' " .
						" where form_id = 'SUB' and seq = ".$row->getSequence() ." AND group_name = '".$this->_name."'" ;
			}
			//Add Error logic if necessary.
			sqlStatement($lclString);
		}
	
	}
	
	public function getName() {
		return $this->_name;
	}
	
	public function getTableName() {
		return $this->_table;
	}
	
	public function display($data) {
		//Create table
		//Generate top based on type of subitem.
		$firstLine = true;
		echo "<table class=\"tableRepeat\"> <thead><tr>";
		foreach($this->_subItemRows as $row) {
			echo "<td>".$row->title ."</td>\n";
		}
		echo "</tr></thead>";
		
		while($rowValue = $data->getData()) { 
			echo "<tr>";
			foreach($this->_subItemRows as $row) {
			//This will need to have an extended name.
				generate_form_field(array("data_type" => 36, "field_id" => 15, "list_id" => 17), "44");
				
				// escaped variables to use in html
				$field_id_esc= htmlspecialchars( $field_id, ENT_QUOTES);
				$list_id_esc = htmlspecialchars( $list_id, ENT_QUOTES);
				
				// Added 5-09 by BM - Translate description if applicable
				$description = htmlspecialchars(xl_layout_label($frow['description']), ENT_QUOTES);
				echo "<td>".$rowValue[$row->columnName]."</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
	
	
	public function getJSONObject() {
		$jsonSubItemRows = array();
		foreach($this->_subItemRows as $subItemRow) {
			$jsonSubItemRows[] = $subItemRow->getObjectAsArray();
		}
		return json_encode(array("name" => $this->_name, "table" => $this->_table, "rows" => $jsonSubItemRows));
	}
	
	public function updateJSONObject($newJSONObject, $oldJSONObject = NULL) {
		
	}
}

