<?php
/******************************************************************************
*
* Purpose: Inizializzazione dei parametri per la creazione della mappa ò
     
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
if(empty($_REQUEST['mapset'])) die("alert('Manca il parametro mapset');");

require_once('../../config/config.php');
require_once (ADMIN_PATH."lib/functions.php");
require_once (ROOT_PATH."lib/i18n.php");
require_once ('include/gcMap.class.php');

$getLegend = false;
if(isset($_REQUEST['legend']) && $_REQUEST['legend'] == 1) $getLegend = true;
$languageId = null;
if(!empty($_REQUEST['lang'])) $languageId = $_REQUEST['lang'];

$objMapset = new gcMap($_REQUEST["mapset"], $getLegend, $languageId);
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header ("Pragma: no-cache"); // HTTP/1.0
header("Content-type: text/javascript; Charset=UTF-8");
echo $objMapset->OLMap();

?>
