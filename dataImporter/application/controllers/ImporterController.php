<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

require_once (dirname(__FILE__) ."/../../../../library/classes/dataImportTable.php");
class ImporterController extends Zend_Controller_Action
{

	public $my_var;
	//public $previewTableData;
	//public $previewTableStatus;
	
	//public $previewTableStatusMessage;
	private $columnRules;
	private $matchRules;
	private $rowRules;
	
    public function init()
    {
        /* Initialize action controller here */

    }

    //Sets whether the columns are matchrules or column rules.
    private function processColumnRules() {
    	
    	$this->columnRules = array();
    	$this->matchRules = array();
    	$i =0;
    	$colName = 'name_'.$i;
    	$lclVal = $_POST[$colName];
    	while(isset($lclVal)) {
    		if($lclVal == "-skip") { //if skip, then skip.
    			array_push($this->columnRules, NULL);
    			
    		}
    		elseif(substr($lclVal,0,1) == '*') { //First charater '*' so it must be a pattern match
    			//Treat the column as a skip
    			array_push($this->columnRules, NULL);
    			//Add the match rule. "key" => "value" | "columnName"=>"column number"
    			$this->matchRules[substr($lclVal,1)] = $i;
    		}
    		elseif(strpos($lclVal," (") !== FALSE) { //Remove "(Date)" and other values, should not be in the value, but just in case.
    			array_push($this->columnRules, substr($lclVal,0,strpos($lclVal,"(")));
    		}
    		else { //match found
    			array_push($this->columnRules, $lclVal);
    		}
    		$i++;
    		$colName = 'name_'.$i;
    		$lclVal = $_POST[$colName];
    	}
    	//Unset matchRules if none found.
    	if(count($this->matchRules) == 0) {
    		$this->matchRules = NULL;
    	}    	
    }
    
    private function processActions() {
    	$actions = array();
    	
    	$i = 0;
    	$actionRow = "action_$i";
    	$actionIDRow = "hidActionID_$i";
    	$lclAction = $_POST[$actionRow];
    	$lclActionID = $_POST[$actionIDRow];
    	while(isset($lclAction)) {
			if($lclAction == "U") {
				if(!isset($lclActionID)) {
					//raise exception
				}
				else {
					array_push($actions, $lclActionID);
				}
			}
			elseif($lclAction == "I") {
				array_push($actions, -1);
			}
			else {
				array_push($actions, -2);
			}
    		$i++;
    		$actionRow = "action_$i";
    		$actionIDRow = "hidActionID_$i";
    		$lclAction = $_POST[$actionRow];
    		$lclActionID = $_POST[$actionIDRow];
    	}
    	return $actions;
    }
    
    public function formAction()
    {

    	
    	$request = $this->getRequest();
    	$form = new Application_Form_Importer();
    	
    	
    	if($this->getRequest()->isPost()) {
    		//Given that there are no validators, this will always pass.  
    		//Is included for consistency with the Zend framework.
    		if($form->isValid($request->getPost())) {
    			
    			//Steps used on all files done here.
    			//Get the values from the check boxes for pattern matching.
    			$patternMatchingOptions = $form->getPatternMatchingArray();

    			$databaseMapper = new DataImportTable();
    			$form->columnListSet = $databaseMapper->getDatabaseColumnListWithDateMarker( $form->getValue('tableList')," (".xl('Date').")");
    				    	
    			//Add file uploaded to list.
    			if(isset($_POST['uploadFile']) && isset($_FILES['dataFile']['type']) ) {

    				$csvProcessor = new Application_Model_CsvFileImportMapper();
    				$csvProcessor->moveTemp($_FILES['dataFile']['tmp_name'], $_FILES['dataFile']['name']);
    				
    				$form->populateFileList($form->getElement('fileList'));
    				$form->getElement('fileList')->setValue($_FILES['dataFile']['name']);
    				
    			}  
    			//Process/Import File
    			elseif($_POST['processFile']) { 
    				
    				$tableName = $form->getValue('tableList');
    				
    				
					//This section processes the file column heading numbers.    				
    				$this->processColumnRules();
    				
    				//The actual processing of the file.
    				$csvProcessor = new Application_Model_CsvFileImportMapper();
    				
    				//Needs to know which rows to process.
    				//Needs special processing for patient_data
    				//Needs to know what to do by rules.
    				
    				
    				$importSuccess = $csvProcessor->importFile($csvProcessor->uploadLocationFromFile($form->getValue('fileList')), //File Pointer
    						$form->getValue('tableList'), //Table to exceute against
    						$this->columnRules, //Column Rules
    						$form->getValue('txtEncoding'), //Encoding
    						chr($form->getValue('fieldDelimitBox')), 
    						chr($form->getValue('txtQualifierBox')),
    						intval($form->getValue('firstRowColumnHeading')), //Column Headings
    						$form->getValue('dateSelect'),
    						$this->processActions()
    				
    				);
    				
					if($importSuccess["success"]) {
    					$form->lblOutput = "Your file was successfully imported. ".$importSuccess["successfulTransactions"].
    					                       " transaction imported.";
    				}
    				else if($importSuccess["rolledback"]){
    					//display error message
    					$form->lblOutput = "Problem importing your file. All updates rolled back.";
    				}
    				else {
    					$form->lblOutput = "Problem importing your file. Unable to rollback transactions.";
    				}
    			}
    			elseif ($_POST['delete']) {
    				if($form->getValue('fileList') !== NULL) {
	    				$csvProcessor = new Application_Model_CsvFileImportMapper();
	    				unlink($csvProcessor->uploadLocationFromFile($form->getValue('fileList')));
						
	    				$this->lblOutput = "File successfully deleted. Please select a file to import.";
	    				$form->getElement("processFile")->setAttrib("disable", true);
    				}
    				else {
    					$form->lblOutput = "You must first select a file to delete.";
    				}
    				
    			}
    			//Run Preview.
    			elseif ($_POST['Preview']) {
					if($form->getValue('fileList') !== NULL) {
						
					
	    				//Run processing
	    				$csvProcessor = new Application_Model_CsvFileImportMapper();
	
	    				$form->previewTableStatus = "render";
	    				$form->previewTableStatusMessage = NULL;
	
	    				//If first preview and not postback.
	    				$this->processColumnRules();
	    				
	    				
	    				
	    				$form->previewTableData = $csvProcessor->generateTableArray($csvProcessor->uploadLocationFromFile($form->getValue('fileList')), 
	    						chr($form->getValue('fieldDelimitBox')), 
	    						chr($form->getValue('txtQualifierBox')),
	    						$form->getValue('firstRowColumnHeading'), 
	    						$form->getValue('rowLimitList'));
	    				
	    				//Check to see if any pattern matching is used.  If so use those columns to return a status column
	    				//$form->previewTableDataMatching => will be any matches.
	    				//If refreshRequired && $form->getValue('firstRowColumnHeading')  //Since if the first row is not column headings then we can't match anyways.
	    				//Since refresh required we are doing the columns for the first time, so we don't need to get the data.
	    				//foreach to search for columns to find the column in question
	    				
	    				//Will return empty if no values found or column is empty.
	    				
	    				//$form->previewTableData->setKnownColumnHeadings($form->getValue('firstRowColumnHeading'));
	    				if($this->matchRules !== NULL) {
	    					$whereClauseArray = $form->previewTableData->convertMatchingToSelectStatement($form->previewTableData->getCriteriaForMatch($patternMatchingOptions));
	    				
	    					$form->previewTableData->setActionIds($csvProcessor->getMatchingIDs($whereClauseArray));
	    				}
	    				$form->previewTableData->setTargetTable($form->getValue('tableList'));
	    				if(count($this->columnRules) == 0) {
	    					$form->previewTableColumnRules = $form->previewTableData->getColumns();
	    				}
	    				else {
	    					$form->previewTableColumnRules = $this->columnRules;
	    				}
	    				
	    				//Get Where clause data and then execute
	    				
	    				$form->lblOutput = "Check the preview of the file.  If it is correct please process the file.";
	
	    				//Allow the processing of the file.
	    				$form->getElement("processFile")->setAttrib("disable", false);
	    				$form->getElement("Preview")->setAttrib("disable", false);
	    				$form->getElement("Delete")->setAttrib("disable", false);
					}
					else {
						$form->lblOutput = "You must select a file to process.";
					}
    			}
    			//All buttons processed, must be other action.
    			else { //Perhaps table changed.
    				if($form->getElement('hidPostBackAction')->getValue() == "TableChange") {
    					//Find out if matching should be enabled.
    					$tableListMapper = new DataImportTable();
    					
    					if(array_search(strtolower("pid"),array_map('strtolower',$tableListMapper->getDatabaseColumnList($form->getValue('tableList')))) !== FALSE) {
    						//enable matching.
    						$form->getElement('matchLName')->setAttrib(disable, false);
    						$form->getElement('matchFName')->setAttrib(disable, false);
    						$form->getElement('matchMName')->setAttrib(disable, false);
    						$form->getElement('matchSS')->setAttrib(disable, false);
    						$form->getElement('matchDateOfBirth')->setAttrib(disable, false);
    						$form->getElement('matchSex')->setAttrib(disable, false);
    						$form->getElement('matchPatient_Id')->setAttrib(disable, false);
    						$form->getElement('matchAddNew')->setAttrib(disable, false);
    						
    						if(strtolower($form->getValue('tableList')) == "patient_data") {
    							$form->getElement('matchMessage')->setDescription("Add New patient for unmatched patients/Update Matched Patients.");
    						}
    						else {
    							$form->getElement('matchMessage')->setDescription("Use matching to determine pid?");
    						}
    						
    					}
    					if($form->getElement('fileList')->getValue() !== NULL) {
    						$form->getElement("Preview")->setAttrib("disable", true);
    						$form->getElement("Delete")->setAttrib("disable", true);
    					}
    					else {
    						$form->getElement("Preview")->setAttrib("disable", false);
    						$form->getElement("Delete")->setAttrib("disable", false);
    					}
    					
    				}
    				else {
    					$form->previewTableStatus = "Ready";
    					$form->previewTableStatusMessage = "Select Apply to see a preview.";
    				}
    			}
    		}
    		$form->populateFileList($form->getElement('fileList'));
    	}
    	else {
    		
    		$form->previewTableStatus = "nodata";
    		$form->previewTableStatusMessage = "No data submitted";
    	}
    	//Assign the form to the view.
    	$this->view->form = $form;
    }

}

