<?php
class TableHelper
{
	
	private $_rows;
	private $_columns;
	private $_dataSet;
	private $_data;//The data for the table.
	private $_currentX;
	private $_currentY;
	
	public function __construct($width = null, $height = null) {
		$this->_dataSet = false;
		//We don't need to have a height since it just needs to make sure it is square. 
		if($width !== null && $width > 0 ) {
			$this->_columns = $width; //Since width covers the number of columns.
			$height = isset($height) ? $height : 1;
			$this->_dataSet = true;
			$this->initialDataArray();
		}
		else if (isset($width) && $width < 1) {
			throw new Exception("Html Table must have a positive width.");
		}
		else if (isset($height) && $height < 1) {
			throw new Exception("Html Table must have a positive height.");
		}
		$this->_currentX = 0;
		$this->_currentY = 0;
	}
	
	private function checkReady() {
		if(!$this->_dataSet) {
			throw new Exception("Html Table not set up yet.");
		}
	} 
	
	private function initialDataArray() {
		$this->_data = array();
		for($i =0; $i < $this->_rows; $i++) {
			$lclArray = array();
			for($j =0; $j < $this->_columns; $j++) {
				$lclArray[] = null;
			}
			$this->_data[] = $lclArray;
		}
	}
	
	public function addItem($width = null, $height = null) {
		$this->checkReady();
		if($width === null && $height > 0) {
			$width = 1;
		}
		else if ($height === null &&  $width > 0) {
			$height = 1;
		} 
		else if($height === null && $width === null) {
			$height = 1;
			$width = 1;
		}
		if($height > 1)  { //Make sure current table can support it.
			//Find Height remaining.
			 if(($this->_currentY+1)+($height - 1) >$this->_rows) {
			 	$this->_rows = $this->_rows + (($this->_currentY+1)+($height - 1) - $this->_rows);  
			 }  
		}
		$lclX = $this->_currentX;
		$lclY = $this->_currentY;
		for($i = 0; $i < $width; $i++) {
			for($j = 0; $j < $height; $j++) {
				$this->_data[$lclX+$i][$lclY+$j] = "Q";
			}
		}
	}
	//Text version of the table?
	public function drawTable() {
		$retVal = "";
		
		for($j = 0; $j < $this->_rows; $j++) { //y
			for($i = 0; $i < $this->_columns; $i++) { //x
				$retVal .= "[";
				if($this->_currentX == $i && $this->_currentY == $j) {
					$retVal .= "X->";
				} 
					
				if($this->_data[$i][$j] === NULL) {
					$retVal .= "_";	
				}
				else {
					$retVal .= $this->_data[$i][$j] ;
				}
				
				$retVal .= "]";
			}
			$retVal = $retVal."\n";
		}
		return $retVal;
	}
	
	public function nextRow() {
		$this->_currentX = 0;
		$this->_currentY++;
	}
	
	
	
	public function setPoint($x,$y) {
		$this->_currentX = $x;
		$this->_currentY = $y;
	}
	
	private function getPoint($x,$y) {
		return $this->_data[$x][$y];
	}
	
	public function hasDrawn($x,$y) {
		$this->setPoint($x,$y);
		if($this->getPoint($x,$y) == "D" ) {
			return true;
		}
		else {
			return false;
		}
	}
	public function draw($x,$y) {
		$this->setPoint($x,$y);
		for($i = $x; $i < $this->_columns; $i++) {
			for($j = $y; $j < $this->_rows; $j++) {
				if($this->getPoint($i,$j) == "Q") {
					$this->_data[$i][$j] = "D";
				}
			}
		}
	}
	
	public function drawBox($width, $height) {
		for($i = $this->_currentX; $i < $this->_columns; $i++ ) {
			for($j = $this->_currentY; $j < $this->_rows; $j++) {
				if($this->getPoint($i,$j) == "Q") {
					$this->_data[$i][$j] = "D";
				}
			}
		}
	
	}
}