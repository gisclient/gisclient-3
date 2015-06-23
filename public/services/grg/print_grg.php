<?php
require_once '../../../config/config.php';
require_once ROOT_PATH.'lib/ajax.class.php';
require_once '../include/printDocument.php';
require_once '../include/mapImage.php';
require_once ROOT_PATH . 'lib/GCService.php';
require_once ROOT_PATH . 'lib/fop.php';

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




	//$layers = array("rfbi_traccia","rfbi_sollevamento","rfbi_vasca","rfbi_pozzetto_vertici","rfbi_caditoia","rfbi_percorrenza","rfbi_quote","rfmi_traccia","rfmi_sollevamento","rfmi_vasca","rfmi_pozzetto_vertici","rfmi_percorrenza","rfmi_quote","rfne_traccia","rfne_depuratore","rfne_sollevamento","rfne_imhoff","rfne_pozzetto_vertici","rfne_percorrenza","rfne_sfiato","rfne_quote","rf_intersezioni_argine","rggm_traccia","gm_traccia_vettoriamento","rggm_traccia_provvisoria","rggm_valvola_generica","rggm_cameretta_1_2_salto","rggm_cameretta_regolazione","rggm_derivazioni_utenza","rggm_derivazioni_utenza_provv","rggm_quote","gas_media_annotazioni","rggm_quote_du","gas_media_annotazioni_du","rggm_regolatore_utenza","rggm_montante","gas_media_pezzi_speciali","gas_media_particolari","gb_traccia_vettoriamento","rggb_traccia","rggb_traccia_provvisoria","rggb_valvola_generica","rggb_derivazioni_utenza","rggb_derivazioni_utenza_provv","rggb_quote","gas_bassa_annotazioni","rggb_quote_du","gas_bassa_annotazioni_du","rggb_regolatore_taglio","rggb_montante","rggb_fine_rete","rggb_gruppo_ricompressione","gas_bassa_pezzi_speciali","gas_bassa_particolari","bc_vie","bcest_viabilita","bc_viabilita","bc_nome_localita","bcest_localita","bc_localita","bcest_linea_costa","bc_linea_costa","bc_idrografia","bc_edifici","bc_confini_comunali","bc_civici");
	//$layers = array("rfbi_traccia");



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


	//$user = new GCUser();
    //$user->login('printservice', md5(PRINT_SERVICE_PWD));


//http://grg.gisclient.srv1/gisclient/services/grg/print_grg.php?mapset=stralci&theme=gas_bassa,gas_media,base_cartografica&date=29/6/2015&direction=vertical&dpi=300&extent=1494612.95%2C4917000.89%2C1494702.55%2C4917038.9&format=PDF&printFormat=A3&scale=200&srid=EPSG%3A3003&template=print_grg&northArrow=north1.png&text=%20Eventuali%20commenti%20o%20ossrvazioni



	//print_array($_REQUEST);



    $printMap = new printDocument();

	if(!empty($_REQUEST['lang'])) {
		$printMap->setLang($_REQUEST['lang']);
	}
    if(!empty($_REQUEST['logoSx'])) $printMap->setLogo($_REQUEST['logoSx']);
	else if(defined('GC_PRINT_LOGO_SX')) $printMap->setLogo(GC_PRINT_LOGO_SX);
    if(!empty($_REQUEST['logoDx'])) $printMap->setLogo($_REQUEST['logoDx'], 'dx');
	else if(defined('GC_PRINT_LOGO_DX')) $printMap->setLogo(GC_PRINT_LOGO_DX, 'dx');

    if ($_REQUEST['format'] == 'HTML') {
        $file = $printMap->printMapHTML();
    } else if ($_REQUEST['format'] == 'PDF') {
        $fileUrl = $printMap->printMapPDF();
        $filePath = str_replace(GC_WEB_TMP_URL, GC_WEB_TMP_DIR, $fileUrl);
    }
} catch (Exception $e) {
	$ajax->error($e->getMessage());
}

$ajax->success(array('fileUrl'=>$fileUrl, 'filePath'=>$filePath, 'format'=>$_REQUEST['format']));
