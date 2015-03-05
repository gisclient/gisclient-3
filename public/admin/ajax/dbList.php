<?php
include_once "../../../config/config.php";
include_once ROOT_PATH.'lib/ajax.class.php';
include_once ADMIN_PATH.'lib/functions.php';
include_once ADMIN_PATH."lib/gcSymbol.class.php";

$ajax = new GCAjax();

$db = GCApp::getDB();

if(empty($_REQUEST['selectedField'])) $ajax->error('field');
$selectedField = $_REQUEST['selectedField'];

$result = array(
	'steps'=>1,
	'data'=>array(),
	'data_objects'=>array(),
	'step'=>1
		);

switch($selectedField) {
	case 'field_format':
		$result['fields'] = array(
			'format'=>GCAuthor::t('format'),
			'description'=>GCAuthor::t('description')
			);
		
		$sql="select fieldformat_name, fieldformat_format from ".DB_SCHEMA.".e_fieldformat order by fieldformat_order;";
		$stmt = $db->query($sql);
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$result['data'][] = array('format'=>$row['fieldformat_format'], 'description'=>$row['fieldformat_name']);
			$result['data_objects'][] = array('field_format'=>$row['fieldformat_format']);
		}
	break;
	
	case "class_symbol_ttf":	
	case "symbol_ttf_name":
		if($selectedField=="symbol_ttf_name" && empty($_REQUEST["label_font"])) {
			$ajax->error('missing parameter label_font');
		}
				
		$smb = new Symbol("symbol_ttf");
		if (!empty($_REQUEST["label_font"])) {
			$smb->filter = "font_name='".$_REQUEST["label_font"]."'";
		}
		$smbList = $smb->getList(true);
		
		$result['fields'] = array(
			'image'=>GCAuthor::t('image'),
			'symbol'=>GCAuthor::t('symbol'),
			'category'=>GCAuthor::t('category')
				);
		$result['fields']['font'] = 'Font';
		$result['fields']['position'] = GCAuthor::t('position');
		foreach($smbList['values'] as $symbol) {
			$result['data'][] = array_merge($symbol, array('image'=>'<img src="getImage.php?table=symbol_ttf&font='.urlencode($symbol["font"]).'&id='.urlencode($symbol["symbol"]).'">'));
			$result['data_objects'][] = array(
				'fk_symbol_ttf_name'=>$symbol['symbol'],
				'label_font'=>$symbol['font'],
				'label_position'=>$symbol['position']
					);
		}
	break;
	
	case "symbol_name":
	case "symbol_id":
		$smb = new Symbol("symbol");
		if ($selectedField == "symbol_name") {
			$filters = array();
			if (!empty($_REQUEST["label_font"])) {
				$filters[] = "font_name='".$_REQUEST["label_font"]."'";
			}
			if (!empty($_REQUEST["type"]) && strtoupper($_REQUEST["type"]) == 'PIXMAP') {
				$filters[] = 'symbol_def ~* E\'.*TYPE\\\\s+PIXMAP.*\'';
			}
			if (count($filters) > 0) {
				$smb->filter = '('.implode(') AND (', $filters).')';
			}
		}
		$smbList = $smb->getList(true);
		
		$result['fields'] = array(
			'image'=>GCAuthor::t('image'),
			'symbol'=>GCAuthor::t('symbol'),
			'category'=>GCAuthor::t('category')
				);
		foreach($smbList['values'] as $symbol) {
			$result['data'][] = array_merge($symbol, array('image'=>'<img src="getImage.php?table=symbol&id='.urlencode($symbol['symbol']).'">'));
			$result['data_objects'][] = array('symbol_name'=>$symbol['symbol']);
		}
	break;

	case "symbol_user_pixmap":
		$smb = new Symbol("symbol");
		$smb->filter = 'symbol_def ~* E\'.*TYPE\\\\s+PIXMAP.*\'';
		$smbList = $smb->getList(true);
		
		$result['fields'] = array(
			'image'=>GCAuthor::t('image'),
			'symbol'=>GCAuthor::t('symbol'),
			'category'=>GCAuthor::t('category'),
			'actions'=>'Cancella'
				);
		foreach($smbList['values'] as $symbol) {
			$result['data'][] = array_merge($symbol,
					array('image'=>'<img src="getImage.php?table=symbol&id='.urlencode($symbol['symbol']).'">',
					'actions' => '<button class="delete_symbol">Cancella</button>'));
			$result['data_objects'][] = array('symbol_name'=>$symbol['symbol']);
		}
	break;
	
	case 'table_name':
		$result['fields'] = array('table'=>GCAuthor::t('table'));
		
		if(empty($_REQUEST['catalog_id']) || !is_numeric($_REQUEST['catalog_id']) || $_REQUEST['catalog_id'] < 1) $ajax->error('catalog_id');		
		
		$sql = "select catalog_path, connection_type from ".DB_SCHEMA.".catalog where catalog_id=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_REQUEST['catalog_id']));
		$catalogData = $stmt->fetch(PDO::FETCH_ASSOC);
		if($catalogData['connection_type'] != 6) {
			$ajax->error('not implemented');
		}
		
		list($connStr, $schema) = connAdminInfofromPath($catalogData["catalog_path"]);
		$dataDb = GCApp::getDataDB($catalogData['catalog_path']);
		
		$sql = "SELECT table_name FROM information_schema.tables WHERE table_schema=? order by table_name";
		try {
			$stmt = $dataDb->prepare($sql);
			$stmt->execute(array($schema));
		} catch(Exception $e) {
			$ajax->error();
		}
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$result['data'][] = array('table'=>$row['table_name']);
			$result['data_objects'][] = array('table_name'=>$row['table_name']);
		}	
	break;
	default:
		$ajax->error();
	break;
}
$ajax->success($result);