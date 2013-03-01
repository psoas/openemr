<?php
require_once("../../interface/globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/classes/display/SubItem.php");
require_once("$srcdir/classes/display/SubItemRow.php");



$method = $_SERVER['REQUEST_METHOD'];

if($method == "GET") {
	// check for required values
	if ($_GET['subItemName'] == "" ) { exit;}
	
	
	$lclSubItem = new SubItem($_GET['subItemName']);
	$lclSubItem->getDatafromDatabase();
	echo $lclSubItem->getJSONObject();
	exit;
}
elseif($method == "POST") { //POST
	
	$i = 0;

	$varTemp = json_decode($_POST['objData']);
	$lclSubItem = new SubItem($varTemp->name); 
	$lclSubItem->getFromJSONObject($varTemp);
	$lclSubItem->saveToDatabase();
	
	
	$varTemp;
	$i =  0;
	$i++;
	
	
	
}
else { //Not supported
	exit;
}




