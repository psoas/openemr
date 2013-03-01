<?php
require_once("../../interface/globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/classes/display/SubItem.php");
require_once("$srcdir/classes/display/SubItemRow.php");

//Currently used to get columns of a given table.



$method = $_SERVER['REQUEST_METHOD'];

if($method == "GET") {
	// check for required values
	if ($_GET['tableName'] == "" ) { exit;}
	$_db = $GLOBALS['adodb']['db'];
	
	$lclVal00 = $_db->MetaColumns($_GET['tableName']);
	$retVal = array();
	foreach($lclVal00 as $column) {
		$retVal[] = $column->name;
	}
	echo json_encode($retVal);
	exit;
}
elseif($method == "POST") { //POST

	//Not supported.
	exit;


}
else { //Not supported
	exit;
}




