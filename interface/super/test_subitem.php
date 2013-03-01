<?php
require_once("../globals.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/classes/display/SubItem.php");
require_once("$srcdir/classes/display/SubItemRow.php");


$lclSubItem = new SubItem("teste group");
//header("Content-Type: text/plain");

echo var_dump($lclSubItem);

echo "<br/>Name :".$lclSubItem->getName() ."<br/>";

$lclSubItem->getDatafromDatabase();

echo var_dump($lclSubItem);

echo $lclSubItem->getJSONObject();