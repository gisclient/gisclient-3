<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';

$ajax = new GCAjax();
$db = GCApp::getDB();
$dbSchema = DB_SCHEMA;

$font = isset($_REQUEST['font_name'])?$_REQUEST['font_name']:'r3-map-symbols.ttf';
$fontName = basename($font, '.ttf');

$result = array(
	'steps'=>1,
	'data'=>array(),
	'data_objects'=>array(),
	'step'=>1
);

$result['fields'] = array(
	'image'=>GCAuthor::t('image'),
	'symbol'=>GCAuthor::t('symbol'),
	'code'=>GCAuthor::t('code'),
	'name'=>GCAuthor::t('name')
);

for ($i=33; $i <= 126 ; $i++) {
	$sql = "SELECT symbol_name FROM $dbSchema.symbol WHERE symbol_def LIKE :like";
	$like = '%FONT "' . $fontName . '"%CHARACTER "&#'. $i .';"';
	$stmt = $db->prepare($sql);
	$stmt->execute(array(':like'=>$like));
	$name = $stmt->fetchColumn();

	$result['data'][] = array(
		'image'=>'',
		'symbol' => chr($i),
		'code' => $i,
		'name' => '<input type="text" value="' . $name . '" name="char' . $i . '" style="text-transform:uppercase;">'
	);
}

$ajax->success($result);
