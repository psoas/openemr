<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.


set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__). '/../../../library/zend_framework_1.11/library');

//Set OpenEMR includes
include_once("../../../interface/globals.php");
include_once("../../../library/api.inc");
require_once("../../../library/classes/ORDataObject.class.php");

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

    
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

//Zend_Controller_Front::run(realpath(dirname(__FILE__) . '/../application/controllers'));
//Question
if(!isset($_SESSION)) {
	session_start();
}


$application->bootstrap();
$application->run();