<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

/*	protected function _initView()
	{
		$view = new Zend_View();
		$view->setEncoding('UTF-8');
		$view->doctype('XHTML1_STRICT');
		$view->headMeta()->appendHttpEquiv(
				'Content-Type', 'text/html;charset=utf-8'
		);
		$viewRenderer =
		Zend_Controller_Action_HelperBroker::getStaticHelper(
				'ViewRenderer'
		);
		$viewRenderer->setView($view);
		
		return $view;
	}*/
	protected function _initView()
	{
		// Initialize view
		$view = new Zend_View();
		$view->doctype('XHTML1_STRICT');
		$view->headTitle('My Project');
		$view->env = APPLICATION_ENV;
	
		// Add it to the ViewRenderer
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper(
				'ViewRenderer'
		);
		$viewRenderer->setView($view);
		$view->addHelperPath(APPLICATION_PATH . "/views/helpers", "Zend_Helper");
		
		// Return it, so that it can be stored by the bootstrap
		return $view;
	}
	
/*	protected function _initFrontController()
	{
		//$this->bootstrap('FrontController');
		//$front = $this->getResource('FrontController');
		$front = Zend_Controller_Front::getInstance();
		$response = new Zend_Controller_Response_Http;
		$response->setHeader('Content-Type',
				'text/html; charset=UTF-8', true);
		$front->setResponse($response);
	}*/
	
	//protected function _initSetupBaseUrl() {
		//$controller = Zend_Controller_Front::getInstance();
		//$router = $controller->getRouter();
		//$controller->setControllerDirectory('./application/controllers')
		//->setRouter($router)
		//->setBaseUrl('/public'); // set the base url!
		//$response   = $controller->dispatch();
		//$this->bootstrap('frontcontroller');
		//$controller = Zend_Controller_Front::getInstance();
		//$controller->setBaseUrl('/public');
		//$controller->setBaseUrl('');
	//}
	
	protected function _initFrontControllerOutput() {
	
		$this->bootstrap('FrontController');
		$frontController = $this->getResource('FrontController');
		
		
		$response = new Zend_Controller_Response_Http;
		$response->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
		$frontController->setResponse($response);
	
		$frontController->setParam('useDefaultControllerAlways', false);
	
		return $frontController;
	
	}

	
}

