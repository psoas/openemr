<?php
// Copyright (C) 2012 Chris Paulus <coding@cipher-naught.com>
// Sponsored by David Eschelbacher, MD
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

class Zend_Helper_ArrayView extends Zend_View_Helper_Abstract 
{
	/**
	 * Helper items related to Arrays.
	 * @return Zend_Helper_ArrayView
	 */
	public function arrayView()
	{
		return $this;
	}
	/**
	 * Use to create a list of items surrounded by $tag
	 * @param array $items an array of items.
	 * @param type of element $tag
	 * @return string of items incased in tags.
	 */
	public function makeRepeatingList($items, $tag) {
		$lcl = "";
		foreach($items as $data)
		{
			$lcl .= "<".$tag.">".$data."</".$tag.">\n";
		}
		return $lcl;
	}
	/**
	 * Create a list of list items.  Note: Does not generate ul tags.
	 * @param array $items an arry of items to be returned as a List Item.
	 * @return string
	 */
	public function makeLiList($items)
	{
		return $this->makeRepeatingList($items, "li");
	}
	
	/**
	 * Create a select itme
	 * @param string $name name of select list to generate.
	 * @param array $items array of items to be included.
	 * @param array $values Optional, uses $items as select list items
	 * @return Zend_Form_Element_Select 
	 */
	public function makeSelectList($name, $items,$values = NULL)
	{
			
		
		$lcl = new Zend_Form_Element_Select($name);
		if(!isset($values)) {
			foreach($items  as $data) {
				$lcl->addMultiOption($data,$data);
			}
		}
		elseif(count($items)== count($values)) { 
			
			$i = 0;
			while($i < count($items))
			{
				$lcl->addMultiOption($values[$i],$items[$i]);
				$i++;
			}
		}
		return $lcl;
	}
	/**
	 * Generates an div with values in alternating colors.
	 * @param array $arrayOfItems array of items to list
	 * @param string $altColor color to alternate to
	 * @return string
	 */
	public function makeAlternatingDiv($arrayOfItems, $altColor) {
		$lcl = "";
		for($i = 0; $i < count($arrayOfItems); $i++) {
			if($i % 2 != 0) {
				$lcl .= "<div style=\"background-color:".$altColor.";\">".$arrayOfItems[$i]. "</div>\n";
			}
			else {
				$lcl .= "<div style=\"background-color:white;\">".$arrayOfItems[$i]. "</div>\n";
			}
		}
		return $lcl;			
	}
}