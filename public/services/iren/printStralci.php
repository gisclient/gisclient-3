<?php
require_once '../../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once ROOT_PATH.'public/services/include/printDocument.php';
require_once ROOT_PATH.'public/services/include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';
require_once ROOT_PATH . 'lib/fop.php';


//http://<server>/gisclient/services/iren/printStralci.php?mapset=reti_grg_tb&theme=gas_bassa,gas_media,base_cartografica&date=29/6/2015&direction=vertical&dpi=300&extent=1494612.95%2C4917000.89%2C1494702.55%2C4917038.9&format=PDF&printFormat=A3&scale=200&srid=EPSG%3A3003&template=print_grg&northArrow=north1.png&text=%20Eventuali%20commenti%20o%20osservazioni


$gcService = GCService::instance();
$gcService->startSession();

$ajax = new GCAjax();

//DAI TEMI RECUPERO I LAYERS

$mapset = $_REQUEST["mapset"];
$project='';
$themes = str_replace(",","','",$_REQUEST["theme"]);
$aLayers = array();

$db = GCApp::getDB();
$sql = "select layergroup_name,project_name from ".DB_SCHEMA.".theme innner join ".DB_SCHEMA.".layergroup using(theme_id) inner join ".DB_SCHEMA.".mapset_layergroup using(layergroup_id) where mapset_name='".$mapset."' and theme_name in('".$themes."') order by layergroup_order DESC,theme_order DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $aLayers[] = $row["layergroup_name"];
    $project = $row["project_name"];
}

if(count($aLayers)==0){
    $ajax->error("L'elenco dei livelli è vuoto");
}

$layers = implode(",",$aLayers);

try {

    $_REQUEST["tiles"] = array(0=>array(
                    "opacity" => 100,
                    "parameters" => Array
                        (
                            "FORMAT" => "image/png",
                            "LAYERS" => $layers,
                            "SERVICE" => "WMS",
                            "VERSION" => "1.1.1",
                            "SRS" => "EPSG:3003"
                        ),
                    "type" => "WMS",
                    "url" => MAPSERVER_URL."?map=".ROOT_PATH."map/".$project."/".$mapset.".map"
                   ));

    $printMap = new printDocument();
    
    if(!empty($_REQUEST['lang'])) {
        $printMap->setLang($_REQUEST['lang']);
    }
    if(!empty($_REQUEST['logoSx']))
        $printMap->setLogo($_REQUEST['logoSx']);
    else if(defined('GC_PRINT_LOGO_SX')) 
        $printMap->setLogo(GC_PRINT_LOGO_SX);
    if(!empty($_REQUEST['logoDx'])) 
        $printMap->setLogo($_REQUEST['logoDx'], 'dx');
    else if(defined('GC_PRINT_LOGO_DX')) 
        $printMap->setLogo(GC_PRINT_LOGO_DX, 'dx');

    $fileUrl = $printMap->printMapPDF();
    $filePath = str_replace(GC_WEB_TMP_URL, GC_WEB_TMP_DIR, $fileUrl);
    
    
} catch (Exception $e) {
    $ajax->error($e->getMessage());
}

if (file_exists($filePath) && filesize($filePath)>0){
    $f = fopen($filePath,'r');
    $content = fread($f,filesize($filePath));
    fclose($f);
    $fileContent = base64_encode($content);
    $ajax->success(array("content"=>base64_encode($content)));
}
else{
    $ajax->error("Errore file $filePath non trovato");
}


