<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.


require_once (dirname(__FILE__) ."/../../../../library/sql.inc");
require_once (dirname(__FILE__) ."/../../../../library/classes/dataImportTable.php");
class Application_Form_Importer extends Zend_Form
{

	public $columnListSet;
	public $previewTableStatus;
	public $previewTableData;
	public $previewTableDataMatching;
	public $previewTableStatusMessage;
	public $lblOutput;
	public $previewTableColumnRules;
	
		
	/**
	 * Creates Field Delimit Select List
	 * @param string $name
	 * @return Zend_Form_Element_Select
	 */
    public function createFieldDelimitSelectList($name)
    {
    	$fielddDelimit = new Zend_Form_Element_Select($name);
    	$this->clearDecorators($fielddDelimit);
    	$fielddDelimit->addMultiOption(-1,"None");
    	$fielddDelimit->addMultiOption(0,"Other");
    	$fielddDelimit->addMultiOption(44,", (Comma)");
    	$fielddDelimit->addMultiOption(124,"| (Pipe");
    	$fielddDelimit->addMultiOption(9,"(Tab)");
    	$fielddDelimit->setValue(44);
    	$fielddDelimit->setAttribs(array(
    				"onchange" => "SetElement(this.value,this.attributes[\"id\"].value+\"Box\");configurationChanged();"
    			)
    			);

    	
    	return $fielddDelimit;
    	
    } 
    
    
    public function getPatternMatchingArray() {
    	return array(
    
    					"lname" => $this->getElement('matchLName')->isChecked(),
    					"fname" => $this->getElement('matchFName')->isChecked(),
    					"mname" => $this->getElement('matchMName')->isChecked(),
    					"SS" => $this->getElement('matchSS')->isChecked(),
    					"DOB" => $this->getElement('matchDateOfBirth')->isChecked(),
    					"sex" => $this->getElement('matchSex')->isChecked(),
    					"patient_id" => $this->getElement('matchPatient_Id')->isChecked()
    			);
    }
    public function createDateFormatSelectList($name)
    {
    	//From http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_get-format
    	//0 => dd
    	//1 => mm
    	//2 => yy
    	$dateSelect = new Zend_Form_Element_Select($name);
    	$this->clearDecorators($dateSelect);
    	$dateSelect->addMultiOption("none","None");
    	$dateSelect->addMultiOption("0,1,2","dd-mm-YYYY");
    	$dateSelect->addMultiOption("1,0,2","mm-dd-YYYY");
    	$dateSelect->addMultiOption("2,0,1","YYYY-dd-mm");
    	$dateSelect->addMultiOption("2,1,0","YYYY-mm-dd");
    	
    	return $dateSelect;
    	 
    }
    /**
     * Creates Text Qualifer Select List
     * @param string $name
     * @return Zend_Form_Element_Select
     */
    public function createTextQualifierSelectList($name)
    {
    	$localSelect = new Zend_Form_Element_Select($name);
    	$this->clearDecorators($localSelect);
    	 
    	$localSelect->addMultiOption(0,"Other");
    	$localSelect->addMultiOption(34,"\"");
    	$localSelect->addMultiOption(39,"'");
    	$localSelect->addMultiOption(96,"`");  
    	$localSelect->addMultiOption(124,"| (Pipe");
    	$localSelect->setValue(34);
    	$localSelect->setAttribs(array(
    			"onchange" => "SetElement(this.value,this.attributes[\"id\"].value+\"Box\")"
    	)
    	);
    	return $localSelect;    	 
    }
    /**
     * Creates the Row Limit Drop Down
     * @param string $name name of select list to create
     * @return Zend_Form_Element_Select
     */
    public function createRowLimitList($name) {
    	$localSelect = new Zend_Form_Element_Select($name);
    	$this->clearDecorators($localSelect);
    	$localSelect->addMultiOption("10","10");
    	$localSelect->addMultiOption("40","40");
    	$localSelect->addMultiOption("100","100");
    	$localSelect->addMultiOption("All","All");
    	return $localSelect;
    }
    
    /**
     * Creates Text Encoding drop down list.
     * @param string $name
     * @return Zend_Form_Element_Select
     */
    public function createTextEncoding($name)
    {
    	
    	//See here for more information: http://php.net/manual/en/mbstring.supported-encodings.php
    	$localSelect = new Zend_Form_Element_Select($name);
    	$this->clearDecorators($localSelect);
    
    	$localSelect->addMultiOption("UTF-7","ASCII");
    	$localSelect->addMultiOption("utf8","UTF-8");
    	$localSelect->addMultiOption("EUC-JP","EUC-JP");
    	$localSelect->addMultiOption("eucJP-win","eucJP-win");
    	$localSelect->addMultiOption("JIS","JIS");
    	
    	$localSelect->addMultiOption("ISO-8859-1","ISO-8859-1");
    	$localSelect->addMultiOption("ISO-8859-2","ISO-8859-2");
    	$localSelect->addMultiOption("ISO-8859-3","ISO-8859-3");
    	$localSelect->addMultiOption("ISO-8859-4","ISO-8859-4");
    	$localSelect->addMultiOption("ISO-8859-5","ISO-8859-5");
    	$localSelect->addMultiOption("ISO-8859-6","ISO-8859-6");
    	$localSelect->addMultiOption("ISO-8859-7","ISO-8859-7");
    	$localSelect->addMultiOption("ISO-8859-8","ISO-8859-8");
    	$localSelect->addMultiOption("ISO-8859-9","ISO-8859-9");
    	$localSelect->addMultiOption("ISO-8859-10","ISO-8859-10");
    	$localSelect->addMultiOption("ISO-8859-13","ISO-8859-13");
    	$localSelect->addMultiOption("ISO-8859-14","ISO-8859-14");
    	$localSelect->addMultiOption("ISO-8859-15","ISO-8859-15");
    	
    	$localSelect->addMultiOption('UTF-16BE','UTF-16BE');
    	$localSelect->addMultiOption('UTF-16LE','UTF-16LE');
    	
    	
    	return $localSelect;
    
    }
    
    /**
     * Remove Zend Errors, HtmlTag, and Label from Zend_Element
     * @param Zend_Element $item
     */
    
    public function clearDecorators($item){
    	$item->removeDecorator('Errors');
    	$item->removeDecorator('HtmlTag');
    	$item->removeDecorator('Label');
    }
    
    /**
     * Creates File List select list with given name
     * @param string $name
     * @return Zend_Form_Element_Select
     */
    public function createFileList($name) {
    	$localSelect = new Zend_Form_Element_Select($name);
    	//TODOCMP: Change to configured value
		$this->populateFileList($localSelect);
    	
    	$localSelect->setAttribs(array(
    			"size" => 4,
    			"style" => "min-width: 500px;",
    			"onChange" => "fileSelected()"
    			)
    	);
    	$localSelect->removeDecorator('Label');
    	return $localSelect;
    }
    
    public function populateFileList(&$name) {
    	$csvProcessor = new Application_Model_CsvFileImportMapper();
    	$list = $this->getFileList($csvProcessor->getUploadLocation());
    	$name->clearMultiOptions();
    	foreach($list as $value){
    		$name->addMultiOption($value,$value);
    	}
    }
    /**
     * Get a list of files, but not directories for a given directory name.
     * @param string $dirName
     * @return array of file names:
     */
    public function getFileList($dirName) {
    	
    	$returnList = array();
    	if ($handle = opendir($dirName)) {
    		while (false !== ($entry = readdir($handle))) {
    			if ($entry != "." && $entry != "..") {
    				if (is_dir($entry) !== true){
							array_push($returnList, $entry);
    				}
    			}
    		}
    		closedir($handle);
    	}
    	return $returnList;
    }
    /**
     * Initialization of the Importer.
     */
    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
    	
    	$this->setMethod('post');
    	$element = new Zend_Form_Element_File('dataFile');

    	$element->addValidator('Count', false, 1);
    	$element->addValidator('Size',false,29*1024*1024);
    	$element->setAttrib("class", "file");
    	
    	    	
    	$element->removeDecorator('DtDdWrapper');
    	$this->clearDecorators($element);
    	
    	$this->addElement($element, 'dataFile');
    	
    	$fieldDelimit = $this->createFieldDelimitSelectList('fieldDelimit');
    	
    	
    	$this->addElement($fieldDelimit,'fieldDelimit');
    	
    	$txtQualifier = $this->createTextQualifierSelectList('txtQualifier');
    	
    	$txtQualifier->removeDecorator('DtDdWrapper');
    	
    	$this->clearDecorators($txtQualifier);
    	$this->addElement($txtQualifier,'txtQualifier');
    	
    	$txtEncoding = $this->createTextEncoding('txtEncoding');
    	$txtEncoding->setAttribs(array(
    			"onclick"=>"configurationChanged()",
    			"style" => "width:160px"
    	));
    	$this->addElement($txtEncoding,'txtEncoding');
    	
    	$this->setAttrib('enctype','multipart/form-data');

    	$fileUpload = new Zend_Form_Element_Submit('uploadFile');
    	
    	$fileUpload->setLabel("Upload");
    	$fileUpload->setAttribs(array(
    			"style" => "width:90px;"
    			)
    	);
    	
    	$this->clearDecorators($fileUpload);
    	$fileUpload->removeDecorator('DtDdWrapper');
    	 
    	$this->addElement($fileUpload,'uploadFile');
    	
    	$fileApply = new Zend_Form_Element_Submit('Preview');
    	$fileApply->setLabel("Apply");
    	$this->clearDecorators($fileApply);
    	$fileApply->removeDecorator('DtDdWrapper');
    	$fileApply->setAttribs(array(
    			"disable" => true,
    			"style" => "width:90px;"
    	)
    	);
    	
    	$this->addElement($fileApply,'Preview');

    	
    	$fileDelete = new Zend_Form_Element_Submit('Delete');
    	$fileDelete->setAttribs(array(
    			"onclick" => "return ConfirmFileDelete(this)",
    			"disable" => true,
    			"style" => "width:90px;"
    			
    			)
    	);
    	$this->clearDecorators($fileDelete);
    	$fileDelete->removeDecorator('DtDdWrapper');
    	$fileDelete->setLabel("Delete");
    	
    	$this->addElement($fileDelete,'Delete');
    	
    	$fileProcess = new Zend_Form_Element_Submit('processFile');
    	$fileProcess->setLabel("Process File");
    	$this->clearDecorators($fileProcess);
    	
    	$fileProcess->setAttribs(array(
    			"onclick" => "return ConfirmFileProcess(this)",
    			"disable" => true,
    			"style" => "width:90px;"
    	)
    	);
    	$fileProcess->removeDecorator('DtDdWrapper');
    	$this->addElement($fileProcess,'processFile');
    	
    	    	
    	$view = $this->getView();
    	$tableListMapper = new DataImportTable();
		
    	$tableList = $GLOBALS['adodb']['db']->MetaTables();
    	
    	$tableListElement = $view->ArrayView()->makeSelectList('tableList',$tableList);
    	$tableListElement->setAttribs(array(
    			"onChange" => "tableListChanged()"
    	)
    	);
    	$tableListElement->setValue("patient_data");
    	$this->addElement($tableListElement,'tableList');
    	
    	$this->columnListSet = $tableListMapper->getDatabaseColumnListWithDateMarker("patient_data"," (".xl("Date").")");
    	
    	$txtQualifierBox = new Zend_Form_Element_Text("txtQualifierBox");
    	
    	
    	$txtQualifierBox->setAttribs(array(
    			"class" => "textbox",
    			"size" => 4,
    			"onchange" => "SelectElement(this.value,this.attributes[\"id\"].value.replace(\"Box\",\"\"));configurationChanged();"
    			)
    	);
    	$this->clearDecorators($txtQualifierBox);
    	$txtQualifierBox->setValue($txtQualifier->getValue());
    	$txtQualifierBox->removeDecorator('DtDdWrapper');
    	$this->addElement($txtQualifierBox,'txtQualifierBox');
    	
    	
    	$fieldDelimitBox = new Zend_Form_Element_Text("fieldDelimitBox");
    	$fieldDelimitBox->setAttribs(array(
    			"class" => "textbox",
    			"size" => 4,
    			"onchange" => "SelectElement(this.value,this.attributes[\"id\"].value.replace(\"Box\",\"\"));configurationChanged();"
    	)
    	);
    	$this->clearDecorators($fieldDelimitBox);
    	$fieldDelimitBox->setValue($fieldDelimit->getValue());
    	$this->addElement($fieldDelimitBox,'fieldDelimitBox');
    	
    	$firstRowColumnHeading = new Zend_Form_Element_CheckBox("firstRowColumnHeading");
    	$this->clearDecorators($firstRowColumnHeading);
    	$firstRowColumnHeading->setAttribs(array(
    			"onchange"=>"configurationChanged()"
    			));
    	$this->addElement($firstRowColumnHeading ,'firstRowColumnHeading');
    	
    	
    	//Hidden Table Data.
    	$hidTableData = new  Zend_Form_Element_Hidden('hidTableData');
    	$this->clearDecorators($hidTableData);
    	$hidTableData->removeDecorator('DtDdWrapper');
    	$this->addElement($hidTableData,'hidTableData');
    	
    	//Hidden Postback Data (like actions).
    	$hidPostBackAction = new  Zend_Form_Element_Hidden('hidPostBackAction');
    	$this->clearDecorators($hidPostBackAction);
    	$hidPostBackAction->removeDecorator('DtDdWrapper');
    	$this->addElement($hidPostBackAction,'hidPostBackAction');
    	
    	 
    	$this->addElement($this->createFileList("fileList"), "fileList");
    	
    	$this->addElement($this->createRowLimitList('rowLimitList'),'rowLimitList');
    	
    	$this->lblOutput = "Please select a file to process.";
    	
    	//Pattern Matching Checkboxes
    	
	    	$matchLName = new Zend_Form_Element_CheckBox("matchLName");
	    	$this->clearDecorators($matchLName);
	    	$this->addElement($matchLName ,'matchLName');
    	 
	    	$matchFName = new Zend_Form_Element_CheckBox("matchFName");
	    	$this->clearDecorators($matchFName);
	    	$this->addElement($matchFName ,'matchFName');
    	
    	
	    	$matchMName = new Zend_Form_Element_CheckBox("matchMName");
	    	$this->clearDecorators($matchMName );
	    	$this->addElement($matchMName  ,'matchMName');
    	
    	
	    	$matchSS = new Zend_Form_Element_CheckBox("matchSS");
	    	$this->clearDecorators($matchSS);
	    	$this->addElement($matchSS ,'matchSS');
	    	
    	
	    	$matchDateOfBirth = new Zend_Form_Element_CheckBox("matchDateOfBirth");
	    	$this->clearDecorators($matchDateOfBirth);
	    	$this->addElement($matchDateOfBirth ,'matchDateOfBirth');
	    	
    	
	    	$matchSex = new Zend_Form_Element_CheckBox("matchSex");
	    	$this->clearDecorators($matchSex);
	    	$this->addElement($matchSex ,'matchSex');
	    	
    	
	    	$matchPatient_Id = new Zend_Form_Element_CheckBox("matchPatient_Id");
	    	$this->clearDecorators($matchPatient_Id);
	    	$this->addElement($matchPatient_Id ,'matchPatient_Id');
	    	
	    	//These might need to be changed to radio buttons.
	    	$matchAddNew = new Zend_Form_Element_CheckBox("matchAddNew");
	    	$this->clearDecorators($matchAddNew);
	    	$this->addElement($matchAddNew ,'matchAddNew');
	    	
	    	
	    	$this->addElement('hidden', 'matchMessage', array(
	    			'description' => 'Add New patient for unmatched patients/Update Matched Patients.',
	    			'ignore' => true,
	    			'decorators' => array(
	    					array('Description', array('escape'=>false, 'tag'=>'')),
	    			),
	    	));
	    	
	    	
	    	
    	$dataSelect = $this->createDateFormatSelectList('dateSelect');
    	$dataSelect->removeDecorator('DtDdWrapper');
    	$dataSelect->setAttribs(array(
    			"onchange"=>"configurationChanged()"
    	));
    	$this->addElement($dataSelect, 'dateSelect');
    	
    	
    }

}

