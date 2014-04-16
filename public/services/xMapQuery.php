<?php

//scelta da menu radio
define('QUERY_EXTENT',0);
define('QUERY_WINDOW',1);
define('QUERY_CURRENT',2);
define('QUERY_RESULT',3);
//selezione sulla mappa
define('QUERY_POINT',5);
define('QUERY_POLYGON',6);
define('QUERY_CIRCLE',7);

define('OBJ_POINT',1);
define('OBJ_CIRCLE',2);
define('OBJ_POLYGON',3);

define('AND_CONST','&&');
define('OR_CONST','||');
define('LT_CONST','<');
define('LE_CONST','<=');
define('GT_CONST','>');
define('GE_CONST','>=');
define('NEQ_CONST','!=');
define('JOLLY_CHAR','*');

define('STANDARD_FIELD_TYPE',1);
define('LINK_FIELD_TYPE',2);
define('EMAIL_FIELD_TYPE',3);
define('HEADER_GROUP_TYPE',10);
define('IMAGE_FIELD_TYPE',8);
define('SECONDARY_FIELD_LINK',99);

/* 
define('SUM_FIELD_TYPE',6);
define('COUNT_FIELD_TYPE',7);
define('AVG_FIELD_TYPE',8);
*/

define('RESULT_TYPE_SINGLE',1);
define('RESULT_TYPE_TABLE',2);
define('RESULT_TYPE_ALL',3);
define('RESULT_TYPE_NONE',4);

define('ONE_TO_ONE_RELATION',1);
define('ONE_TO_MANY_RELATION',2);

define('AGGREGATE_NULL_VALUE',' ');

define('ORDER_FIELD_ASC',1);
define('ORDER_FIELD_DESC',2);


//Definizione dell'oggetto GCMap
//configurazione del sistema
require_once('../../config/config.php');
require_once(ROOT_PATH.'lib/gcMap.class.php');
require_once(ROOT_PATH.'lib/gcMapset.class.php');
require_once (ROOT_PATH.'lib/functions.php');


$jsObject = array();

//Carica e verifica MapScript
if (!extension_loaded('MapScript')) {
	dl("php_mapscript" . MS_VERSION. "." . PHP_SHLIB_SUFFIX);
}

if (!extension_loaded('MapScript')){
	$jsObject['errorString'] = "php_mapscript" . MS_VERSION. "." . PHP_SHLIB_SUFFIX . " non esiste";
	$jsObject['error'] = 100;
	jsonString($jsObject);
	exit;
}

//Verifica il parametro mapset
if(!isset($_REQUEST["mapset"])){
	$jsObject['errorString'] = "manca il mapset";
	$jsObject['error'] = 100;
	jsonString($jsObject);
	exit;
}

$myMap = "MAPSET_".$_REQUEST["mapset"];

if($_REQUEST["action"]=="writeMapset"){
	$myMapset = new GCMapset($_REQUEST["mapset"]);
	$jsObject = $myMapset->writeMap();
	jsonString($jsObject);
	exit;
}

//Axjax fill query form 
if($_REQUEST["action"]=="getQT"){
	$myMapset = new GCMapset($_REQUEST["mapset"]);
	if(!empty($_REQUEST["qTheme"])){
		$jsObject = $myMapset->getQTname($_REQUEST["qTheme"]);
		$jsObject["qtselected"]=(isset($_REQUEST["qtSelected"]))?$_REQUEST["qtSelected"]:null;
	}
	elseif(isset($_REQUEST["qTname"])){
	//echo('fterterterter') exit;

		$jsObject = $myMapset->getQTfield($_REQUEST["qTname"]);
	}
	jsonString($jsObject);
	exit;
}

$Errors = array();

if($_REQUEST["action"]=="reloadref"){
	$oMap = new GCMap();
	if(($gcErr = $oMap->mapError) > 0){
		print $Errors[$gcErr];exit;
	}
	$imgRef = $oMap->getReferenceMap();
	if($oMap->mapError > 0) 
		writejsObject(array('error'=>$oMap->mapError));//Esce	
	$jsObject['updatereference'] = 1;
	$jsObject['refmapurl'] = $imgRef;
	$jsObject['error'] = 0;
	$jsObject['refbox'] = $oMap->getReferenceBox();
	jsonString($jsObject);
	exit;
}



//TODO
//SE PASSO UN PARAMETRO EXTCALL SCRIVO LA SESSIONE DEL MAPSET IN MODO DA ESEGUIRE RICHIESTE ESTERNE
//*****************************************************************************************************************

//Inizializzo l'applicazione DA RIORDINARE
if($_REQUEST["action"]=="initapp"){	
	$myMapset = new GCMapset($_REQUEST["mapset"]);
	$myMapset->initMapset();
	
	if(($gcErr = $myMapset->mapError) > 0){
		if($gcErr == 110){
			$jsObject['errorString'] = "Accesso al mapset negato";
			$jsObject['error'] = $gcErr;
			jsonString($jsObject);
		} else {//ERRORI DA GESTIRE
			$jsObject['errorString'] = "Errore . $gcErr";
			$jsObject['error'] = $gcErr;
			jsonString($jsObject);
		}
		exit;
	}
	
	//Inizializzo la reference map
	if($myMapset->staticReference){
		$jsObject['initref'] = 2;
		$jsObject['refmapurl'] = $myMapset->staticReference;
	}
	elseif($_REQUEST["referenceH"]>0 && $_REQUEST["referenceW"]>0 ){
		$oMap = new GCMap();
		if(($gcErr = $oMap->mapError) > 0){
			print $Errors[$gcErr];exit;
		}
		$imgRef = $oMap->getReferenceMap();
		if($oMap->mapError > 0) 
			writejsObject(array('error'=>$oMap->mapError));//Esce	
		$jsObject['initref'] = 1;
		$jsObject['refmapurl'] = $imgRef;
		$jsObject['error'] = 0;
		if($_REQUEST["action"]=="initref"){
			$jsObject['refbox'] = $oMap->getReferenceBox();
			//writejsObject($jsObject);
		}
	}
	

	//Composizione prefix per icone legenda in caso di db o schemi diversi
	$prefixLegend = DB_NAME."_".DB_SCHEMA;	
	//Informazioni di inizializzazione
	$jsObject['initapp'] = 1;	
	//$jsObject['initmode'] = "'".$myMapset->initMode."'";
	$jsObject['mapset'] = $_REQUEST["mapset"];
	$jsObject['mapsettitle'] = $myMapset->mapsetTitle;
	$jsObject['layertree'] = $myMapset->tocLayers;
	$jsObject['qtheme'] = $myMapset->getqueryThemes();	
	$jsObject['selgroup'] = $myMapset->getselGroupList();	
	$jsObject['selwms'] = $myMapset->getselWMSList();	
	$jsObject['printsize'] = $myMapset->printSize;	
	$jsObject['imageres'] = $myMapset->imageRes;
	$jsObject['geocoord'] = $myMapset->geocoord;
	$jsObject['utmzone'] = $myMapset->utmZone;
	$jsObject['utmsouth'] = $myMapset->utmSouthemi;
	$jsObject['mapunits'] = $myMapset->mapUnits;
	$jsObject['projname'] = $myMapset->srsName;	
	$jsObject['epsg'] = intval($_SESSION[$myMap]["SRID"]);	

	$jsObject['selcolor'] = $myMapset->selColor;
	//Recupero dalla sessione l'impostazione di gruppi e temi
	$jsObject['groupon'] = $myMapset->getGroupsOn();
	$jsObject['themeopen'] = '';//$myMapset->getThemeOpen();
	if($myMapset->selectedqTheme) $jsObject['qthemeselected'] = $myMapset->selectedqTheme;//Tema passato in url
	if($myMapset->selectedQt) $jsObject['qtselected'] = $myMapset->selectedQt;//Qt passato in url
	if($myMapset->selectedObj) $jsObject['objselected'] = $myMapset->selectedObj;//Oggetti passati in url

	if(!isset($_REQUEST['callBackKey'])){
		$jsObject['redline'] = $myMapset->redline;
		$jsObject['edit'] = $myMapset->edit;
		$jsObject['error'] = 0;
		jsonString($jsObject);
		return;
	}else{
		$_REQUEST['action']='initmap';
	}
	
}

$oMap = new GCMap();
if(($gcErr = $oMap->mapError) > 0){
		print $Errors[$gcErr];exit;
}

$dlImage=0;
$printMap=0;
$updateMap=1;	
$updateInfo=0;

$printPdfTable = 0;
$printXLSTable = 0;
$printCSVTable = 0;

$oMap->initMap();


switch ($_REQUEST["action"]){
	case "initmap" :
	case "zoomall" : 
	case "redraw" :
	case "reload" :
		$oMap->redraw();
		break; 

	case "zoomwindow" :
		$oMap->zoomWindow();
		break;	
	  
	case "zoompoint" : 
		$oMap->zoomPoint();
		break;
	
	case "printmap" :
		$printMap=1;
		$updateMap=0;	
		require_once('../../lib/gcPrintMap.class.php');
		$size=strtolower($_REQUEST["pageformat"]);
		$pageLayout = trim($_REQUEST["pagelayout"]);
		$pageFormat = strtoupper($_REQUEST["pageformat"]);
		$pMap = new printMap($myMap,$pageLayout,$pageFormat);
		//Dopo aver inizializzato l'oggetto ho la dimensione dell'immagine della mappa da creare
		$resFactor=max(1,intval($pMap->imageWidth/$_REQUEST["imageWidth"]));
		$_REQUEST["imageWidth"]=$pMap->reqImageWidth;
		$_REQUEST["imageHeight"]=$pMap->reqImageHeight;	
		print_debug($pMap->reqImageWidth." ".$pMap->reqImageHeight,null,'printmap');
		
		$oMap->scale();	
		break;
		
	case "dlimage" :	
		$dlImage=1;
		$updateMap=0;	
		$oMap->redraw();	
		break;
		
	case "scale" : 
		$oMap->scale();	
		break;
				
	case "zoom_result" ://zoom su oggetti risultato di query
		$oMap->zoomResult();
		break;
		
	case "zoomref" : 
		$oMap->zoomReference();	
		break;
		
	case "redline" :
		$oMap->redraw();
		if(!empty($_REQUEST["remove"]))
			$oMap->removeRedline();
		else
			$oMap->addRedline();
		break;
		
	case "infowms" :
		$oMap->redraw();
		$dataQuery = $oMap->getWMSInfo();
		if(isset($_REQUEST["printTable"]) && ($_REQUEST["printTable"]==1)){
			if($_REQUEST["destination"]=='pdf') 
				$printPdfTable = 1;
			elseif($_REQUEST["destination"]=='xls') 
				$printXLSTable = 1;
			else{
				$jsObject["resultype"] = $_REQUEST["resultype"];
				$jsObject['queryresult'][0] = $dataQuery; 
			}
		}
		else{		
			$jsObject["resultype"] = $_REQUEST["resultype"];
			$jsObject['queryresult'][0] = $dataQuery; 
		}
		$updateMap = 0;
		break;
	
	case "info" :	
		require_once("../../lib/gcPgQuery.class.php");//Definizione dell'oggetto PgQuery
		$oQuery=new PgQuery();
		
		if(isset($_REQUEST["printTable"]) && $_REQUEST["printTable"]==1){
			$dataQuery = $oQuery->allQueryResults[0];
			$updateMap = 0;//da vedere se aggiungere al pdf la mappa con la selezione
			if(isset($_REQUEST["destination"]) && $_REQUEST["destination"]=='pdf') 
				$printPdfTable = 1;
			elseif(isset($_REQUEST["destination"]) && $_REQUEST["destination"]=='xls') 
				$printXLSTable = 1;//$printCSVTable = 1;
			else{
				$jsObject["resultype"] = 2;
				$jsObject['queryresult'] = $oQuery->allQueryResults;
			}
		}
		else{
			$jsObject["resultype"] = intval($_REQUEST["resultype"]);
			$jsObject['queryresult'] = $oQuery->allQueryResults;
			$updateMap = $oQuery->mapToUpdate;//valore che indica se devo o meno aggiornare la mappa
			if($oQuery->zoomToResult){
				$oMap->zoomResult($oQuery->allQueryExtent);//zoom e/o selezione sugli oggetti risultato della query
			} 
			elseif($updateMap)
				$oMap->redraw();
		}
	}//END CASE
	
	//Aggiornamento Mappa:
	//$oMap->setLayersStatus();
	
	//Se ho in sessione l' oggetto di selezione lo aggiungo	
	if ($dlImage==0 && $printMap==0 && isset($_SESSION[$myMap]["SELECTION_ACTIVE"])){
		$oMap->addSelectionObject();
		$jsObject["optselcurr"] = 1;//Attivo "Area di selezione corrente"  se ho un oggetto di selezione attivo
	}
	
	//Se ho in sessione un set di oggetti selezionati li aggiungo alla mappa corrente
	if(isset($_SESSION[$myMap]["RESULT"])){	
		$oMap->addObjectSelected();
		$jsObject["optselobj"] = 1;//Attivo "Oggetti selezionati" se ho un set di oggetti selezionati
	}
	
	if(isset($_SESSION[$myMap]["REDLINE"]))	$oMap->addObjects("REDLINE");
	
	if(isset($_SESSION[$myMap]["CUSTOM_OBJECT"])) $oMap->addCustomObject();//polilinee di selezione aggiunte da fuori
	
	
	print_debug($_SESSION,null,'session');
	
	
	//Mappa online
	if($updateMap){	
		$oMap->setLayersStatus();
		if(isset($_SESSION[$myMap]["IMAGELABEL"])) $oMap->setImageLabel();
		$oExtent = $oMap->updateExtent();	
		$jsObject['updatemap'] = 1;
		$jsObject['mapurl'] = $oMap->getMapUrl();
		$jsObject['geopixel'] = $oMap->getPixelSize();	
		$jsObject['ox'] = round($oExtent->minx,2);
		$jsObject['oy'] = round($oExtent->maxy,2);		
		$jsObject['maxscale'] = round($oMap->getMaxScale(),-2);
		$jsObject['scale'] = round($oMap->getScale(),-2);
		$jsObject['refbox'] = $oMap->getReferenceBox();
		$jsObject['groupon'] = $oMap->getGroupsOn();
		$jsObject['grpdisabled'] = $oMap->groupsDisabled;
		
	}
	
	//scarica immagine
	elseif($dlImage){
		$imgPath=$oMap->map->web->imagepath;
		$webPath=$oMap->map->web->imageurl;
		
		$imgDpi=intval($_REQUEST["imgDpi"]);
		$imgRatio=(float)($_REQUEST["imgRatio"]);
		$resFactor = $imgDpi / MAP_DPI;
		$w=intval($oMap->map->width * $resFactor);
		$h=intval($w/$imgRatio);
		$oMap->map->set("width", $w);
        $oMap->map->set("height", $h);
	
		$oMap->setLayersStatus();
		if(isset($_SESSION[$myMap]["IMAGELABEL"])) $oMap->setImageLabel($resFactor);

		if($_REQUEST["gTiff"]==1){
			$oMap->map->outputformat->set('name','GTiff');
			$oMap->map->outputformat->set('driver','GDAL/GTiff');
			$oMap->map->outputformat->set('extension','tif');
			$oMap->map->outputformat->set('mimetype','image/tiff');
			$oMap->map->outputformat->set('imagemode',MS_IMAGEMODE_RGB);
			$imgFile = time().'.tif';
			$oImage=$oMap->map->draw();
			$oImage->saveImage($imgPath.$imgFile, $oMap->map);
		}
		else{
			$imgExt=$oMap->map->outputformat->extension;
			$imgFile = time().'.'.$imgExt;
			$oImage=$oMap->map->draw();
			$oImage->saveImage($imgPath.$imgFile);
		}
		
		$jsObject['downloadimage'] = 1;	
		$jsObject['imagefile'] = $webPath.$imgFile;

	}
	
	//Stampa immagine su pdf
	elseif($printMap){
		$imgPath=$oMap->map->web->imagepath;
		$webPath=$oMap->map->web->imageurl;

		$oMap->map->set("width", $oMap->map->width * PDF_K);
        $oMap->map->set("height", $oMap->map->height * PDF_K);
		$oMap->map->set("resolution", intval($oMap->map->resolution * PDF_K));
		
		$oMap->setLayersStatus();
		if(isset($_SESSION[$myMap]["IMAGELABEL"])) $oMap->setImageLabel($resFactor);
		$oMap->increaseLabels(PDF_K);

		//tolgo interlacciata che crea problemi sul pdf e genero l'immagine
		$oMap->map->outputformat->setOption("INTERLACE", "OFF");
		$imgExt=$oMap->map->outputformat->extension;
		$imgFile = time().'.'.$imgExt;
		$oImage=$oMap->map->draw();
		$oImage->saveImage($imgPath.$imgFile);

		//setto le altre proprieta dell'oggetto pMap e genero il pdf
		$pMap->printTitle = $_REQUEST["printtitle"];
		$pMap->mapsetTitle = $_REQUEST["mapsettitle"];
		$pMap->legendOption = $_REQUEST["legend"];
		$pMap->scale = $_REQUEST["scale"];
		$pMap->mapImage = $imgPath.$imgFile;
		$pdfFile=$pMap->printPdf();

		if(isset($pMap->mapError) && $pMap->mapError > 0) 
			writejsObject(array('error'=>$pMap->mapError));//Esce	
		$jsObject['printmap'] = 1;	
		$jsObject['pdffile'] = $webPath.$pdfFile;
	}
	
	elseif($printPdfTable){
		require_once ROOT_PATH."lib/class.ezpdf.php";
		require_once ROOT_PATH."config/config.ezpdf.php";
		$imgPath=$oMap->map->web->imagepath;
		$webPath=$oMap->map->web->imageurl;

		

		$title=$dataQuery["title"];
		$r=setPdfData($dataQuery);
		if (!isset($dataQuery['papersize_orientation'])) $dataQuery['papersize_orientation'] = 'P';	
	//	$dataQuery['papersize_orientation']='portrait';
		if($dataQuery['papersize_orientation']=='P') $dataQuery['papersize_orientation']='portrait';
		if($dataQuery['papersize_orientation']=='L') $dataQuery['papersize_orientation']='landscape';
		if (!isset($dataQuery['papersize_size'])) $dataQuery['papersize_size'] = null;	
		$pdf=new Cezpdf($dataQuery['papersize_size'],$dataQuery['papersize_orientation']);
		//SELEZIONO IL FONT DEL REPORT

		$pdf->selectFont(ROOT_PATH."/font_ezpdf/$fontName.afm");
		if(empty($tableParam)) $tableParam=Array();
		$tableWidth=$pdf->ez["pageWidth"]-2*($pdf->ez["rightMargin"]+$pdf->ez["leftMargin"]);
		
		//TITOLO DEL REPORT
		if ($titleColor) $pdf->setColor($titleColor[0],$titleColor[1],$titleColor[2]);
		$pdf->ezText($title."\n",$titleSize,$titleParam);
		$res=getPdfParameters($dataQuery,$tableWidth);
		$colWidth=$res["colWidth"];
		$cols=$res["cols"];
		$tableParam=array_merge($tableParam,Array("width"=>$tableWidth,"xOrientation"=>"center","cols"=>$colWidth));
		for($i=0;$i<count($r);$i++){
			$level=$r[$i]["level"];
			$data=$r[$i]["data"];
			$type=$r[$i]["type"];
			if(is_array($data)){
				$pdf->ezTable($data,$cols,null,$tableParam);
				$pdf->ezText("\n");
			}
			else{
				$pdf->setColor(0,0,0);
				$pdf->ezText($data."\n",$dataSize,Array("left"=>(10*$level)));
			}
		}
		$pdfFile=time().'.pdf';
		$pdfcode = $pdf->output();
		$fp=fopen($imgPath.$pdfFile,'w');
		fwrite($fp,$pdfcode);
		fclose($fp);
		$jsObject['printTable'] = 1;	
		$jsObject['tablefile'] = $webPath.$pdfFile;
	}
	
	elseif($printCSVTable){
		//require_once ROOT_PATH."lib/class.ezpdf.php";
		//require_once ROOT_PATH."config/config.ezpdf.php";
		$imgPath=$oMap->map->web->imagepath;
		$webPath=$oMap->map->web->imageurl;
		//print_array($dataQuery);	exit;
		$csvTable=array();
		$level=0;
		$row=0;
		writeTableData($dataQuery["tableheaders"],$dataQuery["groupheaders"],$dataQuery["fieldtype"],$dataQuery["columnwidth"],$dataQuery,$level,$row,$csvTable);
		
		$csvFile=time().'.xls';
		$fp=fopen($imgPath.$csvFile,'w');

		foreach($csvTable as $row){
			fwrite($fp,implode(';',$row)."\n");
		}
		
		fclose($fp);
		$jsObject['printTable'] = 1;	
		$jsObject['tablefile'] = $webPath.$csvFile;

	}
	elseif($printXLSTable){
		/** PHPExcel */
		require_once ROOT_PATH.'lib/PHPExcel.php';
		/** PHPExcel_IOFactory */
		require_once ROOT_PATH.'lib/PHPExcel/IOFactory.php';
		$imgPath=$oMap->map->web->imagepath;
		$webPath=$oMap->map->web->imageurl;
		$r=setPdfData($dataQuery);
		$data=getXLSData($r);
		
		
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->getProperties()->setCreator("GisClient")
			->setTitle($dataQuery["title"])
			->setSubject($dataQuery["title"])
			->setDescription("");
			
		$objPHPExcel->setActiveSheetIndex(0);	
		for($i=0;$i<count($data);$i++){
			for($j=0;$j<count($data[$i]);$j++)
				$objPHPExcel->getActiveSheet(0)->setCellValueByColumnAndRow($j, $i+1,$data[$i][$j]);
		}
			
		
		$file=time().'.xls';
		$xlsFile=$imgPath.$file;
		$webFile=$webPath.$file;
		$objPHPExcel->setActiveSheetIndex(0);	
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		
		$objWriter->save($xlsFile);
		$jsObject['printTable'] = 1;	
		$jsObject['tablefile'] = $webFile;

	}

	
	//Se non ci sono errori setto il controllo a 0
	if(empty($jsObject['error'])) $jsObject['error']=0;	
	jsonString($jsObject);
?>
