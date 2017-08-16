<?php

namespace GisClient\Author\Services\R3GisGisclientMap\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GisClient\Author\Services\R3GisGisclientMap\Utils\GCMap;

class ClientController
{
    public function gcmapAction(Request $request)
    {
        /*
        //  TODO: add as requirement
        if(empty($_REQUEST['mapset'])) die(json_encode(array('error' => 200, 'message' => 'No mapset name')));
         
$getLegend = false;
if(isset($_REQUEST['legend']) && $_REQUEST['legend'] == 1) {
	$getLegend = true;
}
$languageId = null;
if(!empty($_REQUEST['lang'])) {
	$languageId = $_REQUEST['lang'];
}

$onlyPublicLayers = false;
if (!empty($_REQUEST['show_as_public'])) {
	$onlyPublicLayers = true;
}*/
        $objMapset = new GCMap($request->query->get('mapset'), $request->query->get('legend', 0) == 1, $request->query->get('lang'), $request->query->has('show_as_public'));



        /*
       header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header ("Content-Type: application/json; Charset=UTF-8");
         */

         

	// $output = $objMapset->mapOptions;


    //if(empty($_REQUEST["callback"]))
    //     die(json_encode($output));
    //else
    // die($_REQUEST["callback"]."(".json_encode($output).")");


        return new JsonResponse($objMapset->mapOptions);
    }
}
