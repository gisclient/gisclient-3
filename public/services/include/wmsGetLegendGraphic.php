<?php
/*
GisClient map browser

Copyright (C) 2008 - 2009  Roberto Starnini - Gis & Web S.r.l. -info@gisweb.it

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

/* POSSO PASSARE DEI PARAMETRI PERSONALIZZATI :
	LEGEND : 0/1 PER INDICARE CHE VOGLIO TUTTA L'IMMAGINE LEGENDA COMPLETA
	TITLES : SE NELL'IMMAGINE METTO ANCHE I TITOLI
	EXTENT : X VEDERE SE GLI OGGETTI SONO NELL'AREA VISUALIZZATA
	ICONW e ICONH
	RULE E' DATA DA LAYER_NAME:CLASS_NAME OPPURE LAYER_NAME

*/

if($objRequest->getvaluebyname('layer')){
	//PRENDO TUTTI I LIVELLI DEL GRUPPO E CREO UNA LEGENDA CON TUTTE LE CLASSI DI TUTTI I LIVELLI
	$ruleLayerName=false;
	$ruleClassName=false;

	$iconsArray = array();
	$iconW=isset($_REQUEST["ICONW"])?$_REQUEST["ICONW"]:24;
	$iconH=isset($_REQUEST["ICONH"])?$_REQUEST["ICONH"]:24;
	$totWidth = isset($_REQUEST['WIDTH'])?$_REQUEST['WIDTH']:250;


	$legend=false;
	if(isset($_REQUEST["RULE"])){
		//SE RULE E' FORMATA DA NOME_LIVELLO:NOME_CLASSE PRENDO LA SOLA CLASSE ALTRIMENTI CREO UNA LEGENDA CON TUTTE LE ICONE DELLE CLASSI DEL LIVELLO
		$rule=$_REQUEST["RULE"];
		if(strpos($rule,':')>0) {//USARE REGEXP!!!!
			$v=explode(":",$rule);
			$ruleLayerName=$v[0];
			$ruleClassName=$v[1];
			$legend=false;
		}
		else{
			$ruleLayerName=$rule;
			$legend = false;
		}
	}
    
    $gcLegendText = true;
    if (isset($_REQUEST['GCLEGENDTEXT']) && $_REQUEST['GCLEGENDTEXT'] == 0) {
        $gcLegendText = false;
    }

    $layers = array();
    if($aLayersIndexes=$oMap->getLayersIndexByGroup($objRequest->getvaluebyname('layer'))){
		for($j=0;$j<count($aLayersIndexes);$j++) array_unshift($layers, $oMap->getLayer($aLayersIndexes[$j]));
    } else {
        array_push($layers, $oMap->getLayerByName($objRequest->getvaluebyname('layer')));
    }

    $dy=0;
    foreach($layers as $oLayer) {
        $private = $oLayer->getMetaData('gc_private_layer');
        if(!empty($private)) {
			if(!OwsHandler::checkLayer($objRequest->getvaluebyname('project'), $objRequest->getvaluebyname('service'), $oLayer->name)) {
				continue;
			}
        }
		
        if($oLayer->connectiontype == MS_WMS) {
            $url = $oLayer->connection;
            if(strpos($url, '?') === false) $url .= '?';
            else if(substr($url, 0, -1) != '&' && substr($url, 0, -1) != '?') $url .= '&';
            $params = array(
                'request'=>'getlegendgraphic',
                'service'=>'wms',
                'format'=>'image/png',
                'width'=>$iconW,
                'height'=>$iconH,
                'layer'=>$oLayer->getMetaData('wms_name'),
                'version'=>$oLayer->getMetaData('wms_server_version')
            );
			
            if (defined('GC_SESSION_NAME') && isset($_REQUEST['GC_SESSION_ID']) && $_REQUEST['GC_SESSION_ID'] == session_id()) {

                $params['GC_SESSION_ID'] = session_id();
            }
            $urlWmsRequest = $url. http_build_query($params);
			
            $options = array(
                CURLOPT_URL => $urlWmsRequest, 
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_BINARYTRANSFER => true
            ); 
            $ch = curl_init(); 
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            if($result === false) {
				throw new RunTimeException("Could not call $urlWmsRequest: " . curl_error($ch));
            } else if($result) {
				array_push($iconsArray, $result);
            }
            curl_close($ch); 
            continue;
        }
        
        $oLayer->set('sizeunits',MS_PIXELS);
        if(!$ruleLayerName || $ruleLayerName == $oLayer->name){
            $numCls = $oLayer->numclasses;

            //!!!!!!!!!!!!!!!!! DEFINIRE QUI IL FILTRO PER SCALE O IL FILTRO SULL'ESISTENZA DEGLI OGGETTI TANTO CARO A PAOLO (OCCORRE PASSARE EXTENT!!) !!!!!!
            //!!!!!!!!!!!!! CICLARE SU TUTTI I LAYER E CREARE UNA LEGENDA UNICA  PER OGNI LEGENDA DI LAYER
            //if((($oLayer->maxscale == -1) || ($scale <= $oLayer->maxscale)) && (($oLayer->minscale == -1) || ($scale >= $oLayer->minscale))){

            //verifica sulle classi
            $classToRemove=array();
            for ($clno=0; $clno < $numCls; $clno++) {
                $oClass = $oLayer->getClass($clno);
                $className = $oClass->name;
                if($oClass->title) $oClass->set('name',$oClass->title);
                
                //VORREI TOGLIERE LA CLASSE DALLA LEGENDA MA NON TROVO UN MODO MIGLIORE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                //if($oClass->getMetaData("gc_no_image")) $oClass->set('maxscaledenom',1);
                if($oClass->getMetaData("gc_no_image") == 1)
                    $classToRemove[] = $oClass->title;
                elseif(!$ruleClassName || $ruleClassName == $className){
                    //RIMETTERE IN AUTHOR LA LEGENDA PRESA DA IMMAGINE ESTERNA ... 
                    //if(($oClass->getMetaData("gc_no_image")!='1') && (!$ruleClassName || $ruleClassName == $className)){
                    //if((($oClass->maxscale == -1) || ($scale <= $oClass->maxscale)) && (($oClass->minscale == -1) || ($scale >= $oClass->minscale))){
                    
                    $char=$oClass->getTextString();
                    //SE E' UNA CLASSE CON SIMBOLO TTF AGGIUNGO IL SIMBOLO
                    if(strlen($char)==3){//USARE REGEXP, non � detto che questa stringa sia lunga 3 !!!!
                        $lbl=$oClass->label;
                        $idSymbol = ms_newSymbolObj($oMap, "v");
                        $oSymbol = $oMap->getSymbolObjectById($idSymbol);
                        $oSymbol->set('type',MS_SYMBOL_TRUETYPE);
                        $oSymbol->set('font',$lbl->font);
                        $oSymbol->set('character',substr($char,1,1));
                        $oSymbol->set('antialias',1);

                        $oStyle=ms_newStyleObj($oClass);
                        $oStyle->set("size",$iconW/2);//DA VERERE !!!!!
                        //$oStyle->set("offsetx",-25);
                        //$oStyle->set("offsety",25);
                        $oStyle->set('symbolname','v');
                        $oStyle->color->setRGB($lbl->color->red,$lbl->color->green,$lbl->color->blue);
                    }

                    if($legend){
                        $icoImg = $oClass->createLegendIcon($iconW,$iconH);
                        header("Content-type: image/png");
                        $icoImg->saveImage('');
						if (ms_GetVersionInt() < 60000) {
							$icoImg->free();
						}
                        die();
                    }
                }
            }

            if ($gcLegendText && !($oLayer->type===MS_LAYER_ANNOTATION || $oLayer->type===MS_LAYER_RASTER)) {//ESCLUDO SEMPRE I LAYERS DI TIPO ANNOTATIONE I LAYER SENZA CLASSI VISIBILI
                //Elimino le classi non visibili: devo cercarle una ad una perchè il removeclass rinumera le classi ogni volta
                foreach($classToRemove as $className){
                    for ($clno=0; $clno < $oLayer->numclasses; $clno++) {
                        $oClass = $oLayer->getClass($clno);
                        if($oClass->name == $className) $oLayer->removeClass($clno);
                    }
                };
                //print('<pre>');print_r($classToRemove);echo $oLayer->numclasses;
                if($oLayer->numclasses>0){
                    ms_ioinstallstdouttobuffer(); 
                    $objRequest->setParameter('LAYER', $oLayer->name);
                    $objRequest->setParameter('WIDTH', $totWidth);
                    if(!empty($_REQUEST['SCALE'])) {
                        //$objRequest->setParameter('SCALE', intval($_REQUEST["SCALE"]-10));
                    }
                    
                    $oMap->owsdispatch($objRequest);
                    $contenttype = ms_iostripstdoutbuffercontenttype(); 

                    ob_start();
                    ms_iogetStdoutBufferBytes();
                    ms_ioresethandlers();
                    $imageContent = ob_get_contents();
                    ob_end_clean();
					// FIXME: ha senso aggiungere anche se il centent è vuoto?
					// oppure non è un'immagine?
                    //die($imageContent);
                    array_push($iconsArray, $imageContent);
                }
                
            } else {
                $numCls = $oLayer->numclasses;
                for ($clno=0; $clno < $numCls; $clno++) {
                    $oClass = $oLayer->getClass($clno);
                    $check = $oClass->getMetaData('gc_no_image');
                    if(!empty($check)) continue;
                    $icoImg = $oClass->createLegendIcon($iconW,$iconH);
                    ob_start();
                    $icoImg->saveImage('');
                    $imageContent = ob_get_contents();
                    ob_end_clean();
					if (ms_GetVersionInt() < 60000) {
                        $icoImg->free();
                    }
                    array_push($iconsArray, $imageContent);
                }
            }
        }
    }
}

if(!$legend) {
	$w = $totWidth;
	$h = 1;
	
	foreach($iconsArray as $icon) {
		$gdImage = @imagecreatefromstring($icon);
        if(!$gdImage) continue;
		$h += (imagesy($gdImage)+2);
	}
	$legendImage = imagecreatetruecolor($w, $h);
	$white = imagecolorallocate($legendImage, 255, 255, 255);
	imagefill($legendImage, 0, 0, $white);
	$offset = 1;
	
	foreach($iconsArray as $key => $icon) {
		$img = @imagecreatefromstring($icon);
        if(!$img) continue;
		$size = array(imagesx($img), imagesy($img));
		imagealphablending($img, true);
		imagesavealpha($img, true);
		$temp = imagecreatetruecolor($w, $h);
		$white = imagecolorallocate($temp, 255, 255, 255);
		imagefill($temp, 0, 0, $white);
		imagecopy($temp, $img, 0, 0, 0, 0, $size[0], $size[1]);
		imagecopymerge($legendImage, $temp, 0, $offset, 0, 0, $size[0], $size[1], 100);
		$addOffset = $size[1];
		if($gcLegendText) $addOffset += 2;
		$offset += $addOffset;
	}
	header("Content-type: image/png");
	imagepng($legendImage);
	exit(0);
}

$oLayer=$oMap->getLayer($aLayersIndexes[0]);
$objRequest->setParameter('LAYER', $oLayer->name);