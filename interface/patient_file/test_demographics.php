<?php
require_once("../../library/classes/display/CommonDatabaseQueries.php");


//$businessFlatTable = DataDisplayObjectFactory::build("Business_Flat_Table","insurance_company");
//$businessFlatTable->setSourceIdValue(0);

// while($rowValue = $businessFlatTable->getData()) {
	
// 	echo "<br>line".var_dump($rowValue)."<br>";
// 	$data = $rowValue;
// }

// $data['cms_id'] .= "_new_";
// $businessFlatTable->save($data);
// echo "<br>Done<br>";


$values = getInsuranceDataForDemographics(23);

echo json_encode($values);

$values[0]['dirty'] = true;

$actions = setInsuranceDataForDemographics($values);

echo $actions;