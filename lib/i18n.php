<?php

class GCi18n {
	private $db;
	private $languageId;
	private $translations = array();
	
	function __construct($projectName, $languageId) {
		$this->languageId = $languageId;
		$this->db = GCApp::getDB();
	
		$sql = "select table_name, field_name, pkey_id, value from ".
			DB_SCHEMA.".i18n_field inner join ".DB_SCHEMA.".localization using(i18nf_id) ".
			" where project_name=:project_name and language_id=:language_id ".
			" order by table_name, pkey_id, field_name ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':project_name'=>$projectName, ':language_id'=>$languageId));
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if(!isset($this->translations[$row['table_name']])) $this->translations[$row['table_name']] = array();
			if(!isset($this->translations[$row['table_name']][$row['pkey_id']])) $this->translations[$row['table_name']][$row['pkey_id']] = array();
			$this->translations[$row['table_name']][$row['pkey_id']][$row['field_name']] = $row['value'];
		}		
	}
	
	public function translate($value, $table, $pkey, $field) {
		if(!empty($this->translations[$table][$pkey][$field])) {
			return $this->translations[$table][$pkey][$field];
		} else {
			return $value;
		}
	}
	
	public function translateRow($row, $table, $pkey, $limitFields = array()) {
		$translatedRow = array();
		foreach($row as $key => $val) {
			if(!empty($limitFields) && !in_array($key, $limitFields)) {
				$translatedRow[$key] = $val;
				continue; // if limitFields is set and the current field is not in limitFields, don't translate!
			}
			$translatedRow[$key] = $this->translate($val, $table, $pkey, $key);
		}
		return $translatedRow;
	}
	
	public function getLanguageId() {
		return $this->languageId;
	}
}

class GCLocalization {
	private $languages;
	private $defaultLanguageId;
	private $project;
	private $i18nFields = array(
		/*'table'=>array(
			'fieldId'=>array()
		)*/
	);
	private $translations = array(
		/*'languageid'=>
			'table'=>
				'pkey_id' =>
					'fieldId'=>'value'*/
	);
	
	function __construct($project) {
		$this->db = GCApp::getDB();
		$this->project = $project;
	}
	
	public function hasAlternativeLanguages($level) {
		if(empty($this->project)) return false;
		$sql = "select i18nf_id from ".DB_SCHEMA.".i18n_field ".
			" where table_name=:level ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':level'=>$level));
		if($stmt->rowCount() < 1) return false;
		$sql = "select language_id from ".DB_SCHEMA.".project_languages ".
			" where project_name=:project_name ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':project_name'=>$this->project));
		return ($stmt->rowCount() > 0);
	}
	
	public function getDefaultLanguageId() {
		if(empty($this->defaultLanguageId)) $this->_getDefaultLanguageId();
		return $this->defaultLanguageId;
	}
	
	public function getLanguages() {
		if(empty($this->languages)) $this->_getLanguages();
		return $this->languages;
	}
	
	public function getAlternativeLanguages() {
		if(empty($this->languages)) $this->_getLanguages();
		$defaultLanguageId = $this->getDefaultLanguageId();
		unset($this->languages[$defaultLanguageId]);
		return $this->languages;
	}
	
	public function getI18nFields($table) {
		if(empty($this->i18nFields)) $this->_getI18nFields();
		if(!isset($this->i18nFields[$table])) return array();
		return $this->i18nFields[$table];
	}
	
	public function getTranslations($languageId, $table, $pkeyId) {
		if(empty($this->translations)) $this->_getTranslations();
		if(!isset($this->translations[$languageId][$table][$pkeyId])) return array();
		return $this->translations[$languageId][$table][$pkeyId];
	}
	
	public function getTranslationsByFieldName($languageId, $table, $pkeyId) {
		if(empty($this->translations)) $this->_getTranslations();
		if(!isset($this->translations[$languageId][$table][$pkeyId])) return array();
		$result = array();
		foreach($this->translations[$languageId][$table][$pkeyId] as $fieldId => $translation) {
			$result[$this->getFieldName($fieldId)] = $translation;
		}
		return $result;
	}
	
	public function getTranslation($languageId, $table, $pkeyId, $fieldId) {
		if(empty($this->translations)) $this->_getTranslations();
		if(!isset($this->translations[$languageId][$table][$pkeyId][$fieldId])) return null;
		return $this->translations[$languageId][$table][$pkeyId][$fieldId];
	}
	
	public function insertTranslations($table, $languageId, $translations) {
	}
	
	public function getFieldTable($i18nFieldId) {
		if(empty($this->i18nFields)) $this->_getI18nFields();
		foreach($this->i18nFields as $table => $fields) {
			foreach($fields as $fieldId => $field) {
				if($i18nFieldId == $fieldId) return $table;
			}
		}
		return null;
	}
	
	public function getFieldName($i18nFieldId) {
		if(empty($this->i18nFields)) $this->_getI18nFields();
		foreach($this->i18nFields as $table => $fields) {
			foreach($fields as $fieldId => $field) {
				if($i18nFieldId == $fieldId) return $field['field_name'];
			}
		}
		return null;
	}
	
	private function _getTranslations() {
		$sql = "select i18nf_id, pkey_id, language_id, value from ".DB_SCHEMA.".localization ".
			" where project_name=:project_name";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':project_name'=>$this->project));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if(!isset($this->translations[$row['language_id']])) $this->translations[$row['language_id']] = array();
			$table = $this->getFieldTable($row['i18nf_id']);
			if(!isset($this->translations[$row['language_id']][$table])) $this->translations[$row['language_id']][$table] = array();
			if(!isset($this->translations[$row['language_id']][$table][$row['pkey_id']])) $this->translations[$row['language_id']][$table][$row['pkey_id']] = array();
			$this->translations[$row['language_id']][$table][$row['pkey_id']][$row['i18nf_id']] = $row['value'];
		}
	}
	
	private function _getLanguages() {
		$sql = "select e.language_id, language_name from ".DB_SCHEMA.".e_language e ".
			" inner join ".DB_SCHEMA.".project_languages pl on e.language_id = pl.language_id ".
			" or e.language_id = :language_id ";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':language_id'=>$this->getDefaultLanguageId()));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$this->languages[$row['language_id']] = $row['language_name'];
		}
	}
	
	private function _getDefaultLanguageId() {
		$sql = "select default_language_id from ".DB_SCHEMA.".project where project_name=:project_name";
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':project_name'=>$this->project));
		$this->defaultLanguageId = $stmt->fetchColumn(0);
	}
	
	private function _getI18nFields() {
		$sql = "select i18nf_id, table_name, field_name from ".DB_SCHEMA.".i18n_field";
		$stmt = $this->db->query($sql);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if(!isset($this->i18nFields[$row['table_name']])) $this->i18nFields[$row['table_name']] = array();
			$this->i18nFields[$row['table_name']][$row['i18nf_id']] = $row;
		}
	}
}