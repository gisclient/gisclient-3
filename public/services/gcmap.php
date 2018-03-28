<?php
/******************************************************************************
*
* Purpose: Inizializzazione dei parametri per la creazione della mappa
     
* Author:  Roberto Starnini, Gis & Web Srl, roberto.starnini@gisweb.it
*
******************************************************************************
*
* Copyright (c) 2009-2010 Gis & Web Srl www.gisweb.it
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version. See the COPYING file.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with p.mapper; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
******************************************************************************/
require_once '../../config/config.php';
require_once ADMIN_PATH."lib/functions.php";
require_once ROOT_PATH."lib/i18n.php";
require_once ROOT_PATH . 'lib/GCService.php';

if(empty($_REQUEST["jsonformat"]))
	require_once 'include/gcMap.class.php';
else
	require_once 'include/gcMap.class.r3gis.php';

$gcService = GCService::instance();
$gcService->startSession();

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
}
$objMapset = new gcMap($_REQUEST["mapset"], $getLegend, $languageId, $onlyPublicLayers);
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header("Content-Type: application/json; Charset=UTF-8");

if(empty($_REQUEST['mapset'])) die(json_encode(array('error' => 200, 'message' => 'No mapset name')));

if(empty($_REQUEST["jsonformat"]))
	$output = $objMapset->mapConfig;
else
	$output = $objMapset->mapOptions;


if(empty($_REQUEST["callback"]))
	die(json_encode($output));
else
	die($_REQUEST["callback"]."(".json_encode($output).")");