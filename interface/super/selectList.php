<?php
function isSelectedValue($currentVal, $selectedValue) {
	if(isset($currentVal) && isset($selectedValue) && $currentVal == $selectedValue) {
		echo "SELECTED";
	}
}

function createSelectList($arrayOfValues, $selectedValue = NULL, $attributes = NULL, $selectOnly = FALSE) {
	//To do: Need to HTML encode.
	
	// 	<select>
	// 	<optgroup label="Swedish Cars">
	// 	<option value="volvo">Volvo</option>
	// 	<option value="saab">Saab</option>
	// 	</optgroup>
	// 	<optgroup label="German Cars">
	// 	<option value="mercedes">Mercedes</option>
	// 	<option value="audi">Audi</option>
	// 	</optgroup>
	// 	</select>
	
	//Assumes:
	//Option 1: array("value1"=>"label1","value2"=>"label2");  //<option value="value1">label1</option>
	//Option 2: array("text1","text2","text3");  //<option value="text1">text1</option>
	//Option 3: array("optgroup1" => array("value1" => "label1"), "optgroup2" => array("value1" => "label1"));
	//Option 4: array(array("title"=>"Good Car","value"=>"Volvo","label"="volvo"));
	//Option 5: array("optgroup1" => array("title"=>"Good Car","value"=>"Volvo","label"="volvo"), "optgroup2" => array("value1" => "label1"));	
	
	//add attributes here
	
	if($selectOnly) {
		
		//Check if assocative.
		if(is_assoc($arrayOfValues)) {
			echo "<option ";
			if(array_key_exists('title',$arrayOfValues)) {
				echo "title=\"".$arrayOfValues['title']."\" ";
				
			}
			echo "value=\"".$arrayOfValues['value']."\" ".isSelectedValue($arrayOfValues['value'], $selectedValue).">".$arrayOfValues['label']."</option>\n";
		}
		else { //Just dealing with 1-D array
			foreach($arrayOfValues as $singleValue) {
				echo "<option value=\"$singleValue\" ".isSelectedValue($singleValue, $selectedValue).">$singleValue</option>\n";
			}
		}
	}
	else {
		echo "<select>\n";
	
		if(is_assoc($arrayOfValues)) {
			foreach($arrayOfValues as $key => $value) {
				if(is_assoc($value))  { //Option 3 or 5: Option Groups
					//echo 
					echo "ERROR";
				}
				elseif(is_array($value)) {
					echo "<optgroup label=\"$key\">\n";
					//Check to see if it is single item.
						
					foreach($value as $value1) {
						createSelectList($value1,$selectedValue, NULL, TRUE);
					}
					echo "</optgroup>\n";
				}
				else { //array("value"=>"label","value2"="label2");
					echo "<option value=\"$key\" ".isSelectedValue($key, $selectedValue).">$value</option>\n";
				}
			}
		}
		else { //Option 2, just a regular array.
			//Just use the array value as the value and what is displayed.
			foreach($arrayOfValues as $value) {
				if(is_assoc($value)) {
					createSelectList($value,NULL,TRUE);
				}
				else {
					echo "<option value=\"$value\"  ".isSelectedValue($value, $selectedValue)." >$value</option>\n";
				}
			}
			
		}
		echo "</select>";
	}
}

function is_assoc($array) {
	if(is_array($array)) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
	else { 
		return false;
	}
}
echo 'testing array("name","car")'."\n\n";
$testArray = array("name","car");
createSelectList($testArray);
echo "\n\n".'testing array("value1"=>"label1","value2"=>"label2")'."\n\n";
$testArray = array("value1"=>"label1","value2"=>"label2");
createSelectList($testArray);
echo "\n\n".'testing array(array("title"="My Title", "value"= "value1", "label" = "label1"))'."\n\n";
$testArray = array(array("title"=>"My Title", "value"=> "value1", "label" => "label1"));
createSelectList($testArray);

echo "\n\n".'array("OptGruopVal" => array("title"=>"My Title", "value"=> "value1", "label" => "label1"))'."\n\n";
$testArray = array("OptGruopVal" => array(array("title"=>"My Title", "value"=> "value1", "label" => "label1"),array("value"=>"value2","label"=>"label2"),
		array(4,5,6)));
createSelectList($testArray);
