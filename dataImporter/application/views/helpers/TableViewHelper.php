<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

require_once dirname(__FILE__) ."/../../../../../library/classes/DataImportTable.php";

class Zend_Helper_TableViewHelper extends Zend_View_Helper_Abstract
{
	public function tableViewHelper() {
		return $this;
	}
	/**
	 * Generate HTML table from data specified.
	 * @param array $tableData
	 * @param array $listOfColumns
	 * @param array $columnChoice values from postback, if none specified will use list of $listOfColumns.
	 * @param array $matchingValues values used in matching.
	 * @return string HTML table
	 */
	public function generateTable($tableData, $listOfColumns, $columnChoice = NULL, $matchingValues = NULL) {
	
		$output =  "<table border=\"1\"><tr>";
		$i = 0;
		$lclTableData = $tableData->getData();
		$optionArray = array("actions"  => array(),
				             "columns"  => array(),
				             "matching" => array()); 
		//Header Row
			//Needs to be updated to include matching columns.
			
			$optionArray["actions"]["-skip"] = "skip";
			foreach($matchingValues as $key => $matchRuleValue) {
				if($matchRuleValue == true) {
					$optionArray["matching"]["*$key"] = $key;
				}
			}
			if(count($optionArray["matching"]) == 0) {
				unset($optionArray["matching"]); //If no matching values then remove choice.
			}
			foreach($listOfColumns as $col) {
				//Remove (DATE), etc options.
				if(strpos($col," (") === FALSE) { //" (" not found
					$optionArray["columns"][strtolower($col)]= $col;
				}
				else {
					$optionArray["columns"][strtolower(substr($col,0,strpos($col," (")))]= $col;
				}
				
			}
			if(isset($columnChoice) && count($columnChoice) >0) {
				$output = $output."<td>
						<select onChange=\"updateRowOptions(this)\">
						<option value=\"\"> </option>
						<option value=\"S\">S</option>
						<option value=\"U\">U</option>
						<option value=\"A\">A</option>
						</select>
								</td>"; //Provide blank space for column matching.
				$i =0;
				foreach($columnChoice as $value) { //TODOCmp: problem here
					$output = $output."<td>".$this->generateSelectList($optionArray, $i, $value)."</td>";
					$i++;
				}
				if($i < count($lclTableData[0])) { //Then we need to add columns.
					for($j=$i;$j< count($lclTableData[0]); $j++)
					$output = $output."<td>".$this->generateSelectList($optionArray, $j, null)."</td>";
				}
				$output .= "</tr>\n";
			}
			else {
				$output = $output."<td>
						<select onChange=\"updateRowOptions(this)\">
						<option value=\"\"> </option>
						<option value=\"S\">S</option>
						<option value=\"U\">U</option>
						<option value=\"A\">A</option>
						</select>
								</td>"; //Provide blank space for column matching.
				//Generate a select list for each.
				for($i=0;$i<count($lclTableData[0]); $i++)
				{	//changed from $optionArray $listOfColumns
					$output = $output."<td>".$this->generateSelectList($optionArray, $i)."</td>";
				}
				$output .= "</tr>\n";
			}
		$i = 0;
		$actionIds = $tableData->getActionIds();
		foreach($lclTableData as $row) {
			$output .= "<tr>";
			//Set the add or update values.  Will need to set a hidden value for the PID in question.
			//Need to add if statement incase not used.
			$actionID = $actionIds === NULL ? -1 : $actionIds[$i];
			$output .= "<td>".$this->createActionColumn($actionID, $i)."</td>";
						
			foreach($row as $value) {
				$output .= "<td>".htmlspecialchars($value,ENT_QUOTES)."</td>";
			}
			$output .= "</tr>";
			$i++;
		}
		$output .= "</table>";
		return $output;
	}
	
	public function createActionColumn($id, $rowNum) {
		//0 or more - ID 
		//-1 - does not exist.
		//-2 - more than one result returned.
		$action = new Zend_Form_Element_Select("action_$rowNum");
		$hidActionID = new  Zend_Form_Element_Hidden("hidActionID_$rowNum");
		if(isset($id) && $id !== NULL && $id > -1) { //we have a valid value
			//Update
			$action->addMultiOption("S","S");
			$action->addMultiOption("U","U");
			$action->addMultiOption("A","A");
			$action->setValue("U");
			
			$hidActionID->setValue($id);
			
		}
		elseif(isset($id) && $id !== NULL && $id < -1) { //Number is -2 or less, so you can only skip.
			//Probably too many results.  Make it so you can only skip.
			$action->addMultiOption("S","S");
			$action->setValue("S");
			$hidActionID->setValue($id);
			 
		}
		elseif(isset($id) && $id !== NULL && $id == -1) {
			//Add Value, it does not exist.
			$action->addMultiOption("A","A");
			$action->addMultiOption("S","S");
			$action->setValue("A");
			$hidActionID->setValue($id);
			
		}
		else {
			//Error, same as //Number is negative, so you can only skip.
			$i = 6;
		}
		
		//May need to remove decorators.
		$action->removeDecorator('DtDdWrapper');
		$action->removeDecorator('Errors');
		$action->removeDecorator('HtmlTag');
		$action->removeDecorator('Label');
		$hidActionID->removeDecorator('DtDdWrapper');
		$hidActionID->removeDecorator('Errors');
		$hidActionID->removeDecorator('HtmlTag');
		$hidActionID->removeDecorator('Label');
		
		return $action->render()." ".$hidActionID->render();
		
	}
	/**
	 * Generates the select list for display at top of columns
	 * @param array $fieldArray
	 * @param int $nameID number to be attended to "name_"
	 * @param string $headerValue deafult value
	 * @return string Zend Element Select rendered
	 */
	public function generateSelectList($fieldArray, $nameID, $headerValue = NULL) {
	
		$category = new Zend_Form_Element_Select('column');
		
		
		$category->setMultiOptions($fieldArray);

		$category->removeDecorator('Errors');
		$category->removeDecorator('HtmlTag');
		$category->removeDecorator('Label');
		//$columnArry = array();
		$category->name = "name_".$nameID;
		//array_push($columnArry, $category);
		if(isset($headerValue)) {
			
			$valueFound = false;
			foreach($fieldArray["columns"] as $key => $value) {
				if($key == strtolower($headerValue) || $headerValue == $key) {
					$category->setValue($key);
					$valueFound = true;
					break;
				}
				
			}
			//If no value found, set to false.
			if(!$valueFound) {
				$category->setValue("-skip");
			}
		}
		return $category->render();
	}
	
}