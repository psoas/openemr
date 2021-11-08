<?php
require_once (dirname(__FILE__) ."/../../../interface/globals.php");
require_once (dirname(__FILE__) ."/../../../library/sql.inc");

$_db = $GLOBALS['adodb']['db'];


$resultSet =  $_db->Execute('SHOW TABLES');
$showHeader = true;
if (is_object($resultSet)) {
	while($rowValue = $resultSet->FetchRow()) {

		
		foreach ($rowValue as $key => $value) {
			
			$resultSet2 =  $_db->Execute('DESCRIBE '.$value);
			$outputArray = array();
			
			if (is_object($resultSet2)) {
				
				while($rowValue = $resultSet2->FetchRow()) {
					//TODOCMP: Check fetch row type
					echo "$value, ";
					foreach ($rowValue as $key => $value2) {
						if($showHeader) {
							//echo "$key, ";
							
						}
						
						echo $value2 .", ";
						//break;
					}
					if($showHeader) {
							
						$showHeader = false;
					}
					echo "<br/>";
				}
			}
		}
	}
}


		

	?>
	