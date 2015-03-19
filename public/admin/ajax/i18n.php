<?php
include_once "../../../config/config.php";
include_once ADMIN_PATH."lib/ParseXml.class.php";
include_once ADMIN_PATH."lib/export.php";
include_once ROOT_PATH."lib/i18n.php";

$db = GCApp::getDB();

if(empty($_REQUEST['project'])) {
	errorJson('Undefined project');
}
$project = $_REQUEST['project'];

if(empty($_REQUEST['level'])) {
	errorJson('Undefined level');
}
$level = pg_escape_string($_REQUEST['level']);

if(empty($_REQUEST['p_key'])) {
	errorJson('Undefined pkey');
}

$xml = new ParseXml();
$xml->LoadFile(PK_FILE);
$struct=$xml->ToArray();

if(!isset($struct[$level])) errorJson('Invalid level');

$sql = 'delete from '.DB_SCHEMA.'.localization where project_name = :project and i18nf_id = :field_id and pkey_id = :pkey_id';
$emptyTranslations = $db->prepare($sql);

$sql = 'insert into '.DB_SCHEMA.'.localization (project_name, i18nf_id, pkey_id, language_id, "value") 
    values (:project, :field_id, :pkey_value, :lang_id, :translation)';
$insertTranslation = $db->prepare($sql);

if(isset($_POST['translations'])) {
	if(!is_array($_POST['translations'])) errorJson('Invalid data');
	foreach($_POST['translations'] as $fieldId => $translations) {
		
        $emptyTranslations->execute(array(
            'project'=>$project,
            'field_id'=>$fieldId,
            'pkey_id'=>$_REQUEST['p_key']
        ));
		
		foreach($translations as $languageId => $translation) {
            $insertTranslation->execute(array(
                'project'=>$project,
                'field_id'=>$fieldId,
                'pkey_value'=>$_REQUEST['p_key'],
                'lang_id'=>$languageId,
                'translation'=>$translation
            ));
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

	$sql = "select ".implode(',',$fieldNames)." from ".DB_SCHEMA.".$level where ".$struct[$level]['pkey']." = :pkey_value";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        'pkey_value'=>$_REQUEST['p_key']
    ));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	foreach($row as $key => $val) {
		$defaultLanguageData[$key] = $val;
	}
	$responseData['translations'][$defaultLanguageId] = $defaultLanguageData;
	
	
	foreach($languages as $languageId => $foo) {
		if($languageId == $defaultLanguageId) continue;
		$responseData['translations'][$languageId] = $localization->getTranslationsByFieldName($languageId, $level, $_REQUEST['p_key']);
	}
	successJson($responseData);
}



function errorJson($error = 'System error') {
	die(json_encode(array('result'=>'error','error'=>$error)));
}

function successJson($responseData = array()) {
	die(json_encode(array('result'=>'ok', 'data'=>$responseData)));
}