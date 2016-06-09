<?php
/**
 * Handle user definable user font
 * 
 */
class Font {
	private $db;
	private $dbSchema;

	public function __construct() {
		$this->db = GCApp::getDB();
		$this->dbSchema = DB_SCHEMA;
	}

	public function upload($filename, $base64) {
		//extract file to base64 string
		$fileData = explode(',', $base64, 2);
		$file = base64_decode($fileData[1], true);
		if (false === $file) {
			throw new Exception("The argument is not a valid base64", 1);
		}

		// move to final destination
		$fontDir = ROOT_PATH . 'fonts/';
		$filePath = $fontDir . $filename;

		if (false === is_dir($fontDir)) {
			throw new Exception("Missing directory '$fontDir'", 1);
		} elseif (false === is_writable($fontDir)) {
			throw new Exception("Directory '$fontDir' is not writable", 1);
		}

		$result = file_put_contents($filePath, $file);
		if(false === $result) {
			throw new Exception("Impossible create file '$filePath'", 1);
		}
	}

	public function newSymbol($fontName, $symbolCode, $symbolName){
		// insert record into db
		$selectCategoryId = "SELECT symbolcategory_id FROM {$this->dbSchema}.e_symbolcategory WHERE symbolcategory_name = '" . DEFAULT_SYMBOLCATEGORY . "'";
		$q = $this->db->query($selectCategoryId);
		$categoryId = $q->fetchColumn();
		if(false === $categoryId) {
			$insertCategoryId = "INSERT INTO {$this->dbSchema}.e_symbolcategory VALUES (91, '" . DEFAULT_SYMBOLCATEGORY . "', NULL)";
			$count = $this->db->exec($insertCategoryId);
			if(false === $count) {
				throw new Exception("Error in query: $insertCategoryId", 1);
			} elseif ($count === 1) {
				$categoryId = 91;
			} else {
				throw new Exception("Unexpected error in query: $insertCategoryId", 1);
			}
		}

		$symbolName = strtoupper($symbolName);
		$fontName = basename($fontName, '.ttf');
		
		// check if symbol already exist
		$selectSymbolName = "SELECT symbol_name FROM {$this->dbSchema}.symbol WHERE symbol_def LIKE :like";
		$like = '%FONT "' . $fontName . '"%CHARACTER "&#'. $symbolCode .';"';
		$stmt = $this->db->prepare($selectSymbolName);
		$stmt->execute(array(':like'=>$like));
		$name = $stmt->fetchColumn();

		if ($name === $symbolName) {
			return;
		} elseif (false !== $name) {
			$deleteSymbol = "DELETE FROM {$this->dbSchema}.symbol WHERE symbol_name = '$name'";
			$count = $this->db->exec($deleteSymbol);
			if(1 !== $count) {
				throw new Exception("Error in query: $deleteSymbol", 1);
			}

			$updateSymbol = "UPDATE {$this->dbSchema}.style SET symbol_name = :symbolName WHERE symbol_name = '$name'";
			$stmt = $this->db->prepare($updateSymbol);
			$stmt->bindParam('symbolName', $symbolName);
			$result = $stmt->execute();

			if (false === $result) {
				throw new Exception("Error in query: $updateSymbol", 1);
			}
		}

		$symbolDef = "TYPE TRUETYPE FONT \"$fontName\" ANTIALIAS TRUE CHARACTER \"&#{$symbolCode};\"";
		$insertSymbol = "INSERT INTO {$this->dbSchema}.symbol (symbol_name, symbolcategory_id, symbol_def) VALUES (:symbol_name, :symbolcategory_id, :symbol_def)";
		$stmt = $this->db->prepare($insertSymbol);
		$stmt->bindParam('symbol_name', $symbolName);
		$stmt->bindParam('symbolcategory_id', $categoryId);
		$stmt->bindParam('symbol_def', $symbolDef);

		return $stmt->execute();
	}

	public function removeSymbol($fontName, $symbolCode){
		$fontName = basename($fontName, '.ttf');

		// check if symbol already exist
		$selectSymbolName = "SELECT symbol_name FROM {$this->dbSchema}.symbol WHERE symbol_def LIKE :like";
		$like = '%FONT "' . $fontName . '"%CHARACTER "&#'. $symbolCode .';"';
		$stmt = $this->db->prepare($selectSymbolName);
		$stmt->execute(array(':like'=>$like));
		$name = $stmt->fetchColumn();

		if (false !== $name) {
			$deleteSymbol = "DELETE FROM {$this->dbSchema}.symbol WHERE symbol_name = '$name'";
			$count = $this->db->exec($deleteSymbol);
			if(1 !== $count) {
				throw new Exception("Error in query: $deleteSymbol", 1);
			}

			$updateSymbol = "UPDATE {$this->dbSchema}.style SET symbol_name = NULL WHERE symbol_name = '$name'";
			$stmt = $this->db->prepare($updateSymbol);
			$result = $stmt->execute();

			if (false === $result) {
				throw new Exception("Error in query: $updateSymbol", 1);
			}
		}
		return true;
	}
}