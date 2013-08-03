<?php
include_once "../../../config/config.php";
include_once ADMIN_PATH."lib/ParseXml.class.php";
include_once ADMIN_PATH."lib/export.php";
include_once ROOT_PATH."lib/i18n.php";

$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);

if(empty($_REQUEST['project'])) {
	errorJson('Undefined project');
}
$project = pg_escape_string($_REQUEST['project']);

if(empty($_REQUEST['level'])) {
	errorJson('Undefined level');
}
$level = pg_escape_string($_REQUEST['level']);

if(empty($_REQUEST['p_key'])) {
	errorJson('Undefined pkey');
}
$pKey = pg_escape_string($_REQUEST['p_key']);

$xml = new ParseXml();
$xml->LoadFile(PK_FILE);
$struct=$xml->ToArray();

if(!isset($struct[$level])) errorJson('Invalid level');

if(is_numeric($pKey)) $pkeyValue = (int)$pKey; else $pkeyValue = "'".$pKey."'";
$characterPkeyValue = "'".$pKey."'";

if(isset($_POST['translations'])) {
	if(!is_array($_POST['translations'])) errorJson('Invalid data');
	foreach($_POST['translations'] as $fieldId => $translations) {
		
		$sql = "delete from ".DB_SCHEMA.".localization where project_name='$project' and i18nf_id=$fieldId and pkey_id = $characterPkeyValue";
		$db->sql_query($sql);
		
		foreach($translations as $languageId => $translation) {
			$sql = "insert into ".DB_SCHEMA.".localization (project_name, i18nf_id, pkey_id, language_id, \"value\") ".
				" values ('$project', $fieldId, $characterPkeyValue, '$languageId', '".pg_escape_string($translation)."')";
			$db->sql_query($sql);
		}
	}
	successJson();
	
} else {

	$localization = new GCLocalization($project);
	$languages = $localization->getLanguages();
	$defaultLanguageId = $localization->getDefaultLanguageId();
	$fields = $localization->getI18nFields($level);
	
	$responseData = array(
		'defaultLanguage' => $defaultLanguageId,
		'languages' => $languages,
		'fields' => $fields,
		'translations' => array()
	);
	if(empty($fields)) successJson($responseData);

	$defaultLanguageData = array();
	
	$fieldNames = array();
	foreach($fields as $fieldId => $field) array_push($fieldNames, $field['field_name']);

	$sql = "select ".implode(',',$fieldNames)." from ".DB_SCHEMA.".$level where ".$struct[$level]['pkey']." = $pkeyValue";
	$db->sql_query($sql);
	$result = $db->sql_fetchrowset();
	if(empty($result)) echo $sql;
	$row = current($db->sql_fetchrowset());
	foreach($row as $key => $val) {
		$defaultLanguageData[$key] = $val;
	}
	$responseData['translations'][$defaultLanguageId] = $defaultLanguageData;
	
	
	foreach($languages as $languageId => $foo) {
		if($languageId == $defaultLanguageId) continue;
		$responseData['translations'][$languageId] = $localization->getTranslationsByFieldName($languageId, $level, $pKey);
	}
	successJson($responseData);
}



function errorJson($error = 'System error') {
	die(json_encode(array('result'=>'error','error'=>$error)));
}

function successJson($responseData = array()) {
	die(json_encode(array('result'=>'ok', 'data'=>$responseData)));
}