<?php
//Include includes


class DataDisplayObjectFactory {
	
	
	
	public static function build($type, $foreignTable = null) {

		$_directoryPath = dirname(__FILE__) . "/";
		
		$retVal = null;
		//Should be updated to match file naming type.
		
		$convtype = str_replace('_','',$type);
		
		$path = $convtype;
		
		if($newPath = self::fileExists($_directoryPath . $path . '.php', false)) {
			require_once($newPath);
			if($foreignTable === NULL) {
				return new $convtype();
			}
			else {
				return new $convtype($foreignTable);
			}
		}
		else { //Try base table.
			//Name will have "Table" at end, so remove that.
			
			$type = substr_replace($type, "", -5);
			return new TableDataDisplay($type);
		}
		//Do Table look up
		
	}
	
	private static function fileExists($fileName, $caseSensitive = true) {
	
		if(file_exists($fileName)) {
			return $fileName;
		}
		if($caseSensitive) return false;
	
		// Handle case insensitive requests
		$directoryName = dirname($fileName);
		$fileArray = glob($directoryName . '/*', GLOB_NOSORT);
		$fileNameLowerCase = strtolower($fileName);
		foreach($fileArray as $file) {
			if(strtolower($file) == $fileNameLowerCase) {
				return $file;
			}
		}
		return false;
	}
	
	
}