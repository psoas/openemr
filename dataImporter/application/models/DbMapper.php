<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.


require_once (dirname(__FILE__) ."/../../../../library/sql.inc");

class Application_Model_DbMapper
{
	protected $_db_tables ;
	protected $_db_table = "";
	protected $_db;

	/**
	 * Constructor
	 */
	public function __construct()
	{

		$this->_db = $GLOBALS['adodb']['db'];

	}
	/**
	 * Generic Getter method
	 * @param string $name of values trying to get
	 * @throws Exception
	 */
	public function __get($name)
	{
		$method = '__get' . $name;
		if (('mapper' == $name) || !method_exists($this, $method)) {
			throw new Exception('Invalid DBMapper property to get ('.$name . ')');
		}
		return $this->$method();

	}
	/**
	 * Generic Setter method
	 * @param string $name of values trying to set
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		$method = 'set' . $name;
		if (('mapper' == $name) || !method_exists($this, $method)) {
			throw new Exception('Invalid DBMapper property to set ('.$name . ')');
		}
		$this->$method($value);
	}
	
	/**
	 * returns an array of table names
	 * @returns array of table names
	 */
	public function __getTables()
	{
		//$resultSet =  $this->_db->Execute('SHOW TABLES');
		return($this->_db->MetaTables());
	}

	/**
	 * Gets a list of columns for a given table
	 * @param string $table
	 * @throws Exception if table not found.
	 * @return multitype: Array of column names, false if it fails.
	 */
	public function columnList($table)
	{
		$lclVal = $this->_db->MetaColumns($table);
		$retVal = array();
		foreach($lclVal as $column) {
			if($column->type == "date") {
				$retVal[] = $column->name." (".xl('Date').")";
			}
			else {
				$retVal[] = $column->name;
			}
		}
		return $retVal;
		
	}
}

