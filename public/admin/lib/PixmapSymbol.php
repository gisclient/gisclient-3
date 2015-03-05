<?php
/**
 * Handle user definable user symbols
 * 
 */
class PixmapSymbol {
	private $db;
	private $dbSchema;

	public function __construct() {
		$this->db = GCApp::getDB();
		$this->dbSchema = DB_SCHEMA;
	}
	
	public function getList($sqlFilter = '') {
		// filter by ILIKE '%PIXMAP%' and $sqlFilter
	}

	public function upload($filename, $base64) {
		//extract file to base64 string
		$fileData = explode(',', $base64, 2);
		$file = base64_decode($fileData[1], true);
		if (false === $file) {
			throw new Exception("The argument is not a valid base64", 1);
		}
		$fileInfo = getimagesize($base64);

		// verify extension
		$validMimeType = array('image/png','image/gif');
		$mimeType = $fileInfo['mime'];
		
		if (false === in_array($mimeType, $validMimeType)) {
			throw new Exception("Not valid type: $mimeType", 1);
		}

		// move to final destination
		$pixmapDir = ROOT_PATH . 'pixmap/';
		$filePath = $pixmapDir . $filename;

		if (false === is_dir($pixmapDir)) {
			throw new Exception("Missing directory '$pixmapDir'", 1);
		} elseif (false === is_writable($pixmapDir)) {
			throw new Exception("Directory '$pixmapDir' is not writable", 1);
		}

		$result = file_put_contents($filePath, $file);
		if(false === $result) {
			throw new Exception("Impossible create file '$filePath'", 1);
		}
		
		// insert record into db
		$selectCategoryId = "SELECT symbolcategory_id FROM {$this->dbSchema}.e_symbolcategory WHERE symbolcategory_name = 'PIXMAP'";
		$q = $this->db->query($selectCategoryId);
		$categoryId = $q->fetchColumn();
		if(false === $categoryId) {
			$insertCategoryId = "INSERT INTO {$this->dbSchema}.e_symbolcategory VALUES (90, 'PIXMAP', NULL)";
			$count = $this->db->exec($insertCategoryId);
			if(false === $count) {
				throw new Exception("Error in query: $insertCategoryId", 1);
			} elseif ($count === 1) {
				$categoryId = 90;
			} else {
				throw new Exception("Unexpected error in query: $insertCategoryId", 1);
			}
		}

		$pathInfo = pathinfo($filePath);
		$symbolName = strtoupper($pathInfo['filename']);
		$symbolDef = "TYPE PIXMAP IMAGE \"../../pixmap/$filename\"";
		$insertSymbol = "INSERT INTO {$this->dbSchema}.symbol (symbol_name, symbolcategory_id, symbol_def) VALUES (:symbol_name, :symbolcategory_id, :symbol_def)";
		$stmt = $this->db->prepare($insertSymbol);
		$stmt->bindParam('symbol_name', $symbolName);
		$stmt->bindParam('symbolcategory_id', $categoryId);
		$stmt->bindParam('symbol_def', $symbolDef);

		return $stmt->execute();
	}
}