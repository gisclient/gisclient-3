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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;

if (!empty(getenv('TRUSTED_PROXIES'))) {
    Request::setTrustedProxies(
        // trust *all* requests
        array_map(function ($proxy) {
            return trim($proxy);
        }, explode(',', getenv('TRUSTED_PROXIES'))),
        Request::HEADER_X_FORWARDED_ALL
    );
}

/************ Session Name ********/
define('GC_SESSION_NAME', 'gisclient3'); // se definito, viene chiamato session_name() prima di session_start();

ini_set('max_execution_time',90);
ini_set('memory_limit','512M');
//error_reporting (E_ERROR | E_PARSE);
error_reporting  (E_ALL & ~E_STRICT);

define('LONG_EXECUTION_TIME',300);
define('LONG_EXECUTION_MEMORY','512M');

//custom tab files
//define('TAB_DIR','it-custom');
define('FORCE_LANGUAGE', 'it'); // Questi valori devono corrispondere a (it, de, en, ..)
//define('PRIVATE_MAP_URL', 'http://localhost/map/index.php'); //URL CLIENT DI MAPPA PRIVATA
//define('EXTERNAL_LOGIN_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

define('LAYER_CLOSE_CONNECTION_DEFER', true);

// decorator for msMapObjFactory
// define('MS_MAP_OBJ_FACTORY_DECORATOR', 'GisClient\MapServer\MsMapObjFactoryDecorator');

// custom user provider, must implement GisClient\Author\Security\User\UserProviderInterface
// define('GC_CUSTOM_USER_PROVIDER', 'GisClient\Author\Security\User\UserProvider');

/*******************Installation path *************************/
define('ROOT_PATH', realpath(__DIR__.'/..').'/');
if (php_sapi_name() == "cli") {
    if (!getenv('AUTHOR_PUBLIC_URL')) {
        throw new \RuntimeException(
            'AUTHOR_PUBLIC_URL environment variable is not defined. ' .
            'You need to define environment variables for configuration.'
        );
    }
    define('PUBLIC_URL', trim(getenv('AUTHOR_PUBLIC_URL'), '/').'/'); // url for external requests (like map client)
} else {
    $requestContext = new RequestContext();
    $requestContext->fromRequest(Request::createFromGlobals());
    $scheme = $requestContext->getScheme();
    $httpPort = $requestContext->getHttpPort();
    $httpsPort = $requestContext->getHttpsPort();
    if ('http' == $scheme && 80 != $httpPort) {
        $host = $requestContext->getHost().':'.$httpPort;
    } elseif ('https' == $scheme && 443 != $httpsPort) {
        $host = $requestContext->getHost().':'.$httpsPort;
    } else {
        $host = $requestContext->getHost();
    }
    define('PUBLIC_URL', sprintf('%s://%s/', $scheme, $host)); // url for external requests (like map client)
}
define('INTERNAL_URL', 'http://127.0.0.1/'); // url for internal requests
define('MAP_URL', 'http://localhost/map/'); //URL CLIENT DI MAPPA
define('IMAGE_PATH','/tmp/');
define('IMAGE_URL','/tmp/');
define('PROJ_LIB',"/usr/share/proj");
define('TILES_CACHE','/tmp/');
define('OPENLAYERS','///cdnjs.cloudflare.com/ajax/libs/openlayers/2.13.1/OpenLayers.js'); // PREVIEW MAP PER LAYERGROUP E LAYER
//define('PROJ_LIB',"/msiis/proj/nad/");
/*******************                  *************************/

/*******************OWS service url *************************/
define('GISCLIENT_OWS_URL', PUBLIC_URL.'services/ows.php');     //NON E' OBBLIGATORIO
define('GISCLIENT_TMS_URL', PUBLIC_URL.'services/tms/');        //NON E' OBBLIGATORIO
define('ENABLE_OGC_SINGLE_LAYER_WMS', false);                   // true = ABILITA IL WMS PER SINGOLO LAYER NEI SERVIZI OGC

/********************* MAPPROXY ***************/
define('MAPSERVER_URL', 'http://localhost/cgi-bin/mapserv?');   //NON E' OBBLIGATORIO; ? finale è necessario (serve per le richieste WFS di OpenLayers, quando il loadparams non funziona, vedi ows.php commento #LOADPARAMS)
define('MAPSERVER_BINARY_PATH', '/usr/lib/cgi-bin/mapserv');
define('MAPPROXY_PATH', '/opt/mapproxy/');
define('MAPPROXY_URL', '/');
define('MAPPROXY_CACHE_PATH', '/data/tiles/');
define('MAPPROXY_CACHE_TYPE', 'mbtiles');                       //SUPPORTED:file/mbtiles/sqlite
define('MAPPROXY_DEMO', true);
define('MAPPROXY_GRIDS_NUMLEVELS', 20);

/**************** PRINT - EXPORT ***************/
define('GC_PRINT_TPL_DIR', ROOT_PATH.'public/services/print/');
define('GC_PRINT_TPL_URL', PUBLIC_URL.'services/print/');
define('GC_PRINT_IMAGE_SIZE_INI', ROOT_PATH.'config/print_image_size.ini');
define('GC_PRINT_LOGO_SX', 'http://localhost/images/logo_sx.png');  //LOGO SINISTRO DI STAMPA
define('GC_PRINT_LOGO_DX', 'http://localhost/images/logo_dx.png');  //LOGO DESTRO DI STAMPA
define('GC_FOP_CMD', '/usr/local/fop/fop');
define('GC_FOP_LIB', ROOT_PATH.'lib/fop.php');
define('GC_PRINT_SAVE_IMAGE', true);                                // baco mapscript: il saveImage a volte funziona solo specificando il nome del file, altre volte funziona solo se NON si specifica il nome del file
define('PRINT_RELATIVE_URL_PREFIX', 'http://localhost');            // se GISCLIENT_OWS_URL è relativo, questo prefisso viene aggiunto in fase di stampa
define('PRINT_FORCE_HTTP', false);                                   // Forza http per le stampe [Fast-Fix]

/****** print vectors ********/
define('PRINT_VECTORS_TABLE', 'print_vectors');     //TABELLA DB IN CUI VENGONO SALVATI I DATI VETTORIALI PER LA STAMPA
define('PRINT_VECTORS_SRID', 4326);                 //SRID DELLA TABELLA DB IN CUI VENGONO SALVATI I DATI VETTORIALI PER LA STAMPA

/******************* TINYOWS **************/
define('TINYOWS_PATH', '/var/www/cgi-bin');
define('TINYOWS_EXEC', 'tinyows');
define('TINYOWS_FILES', ROOT_PATH.'tinyows/');
define('TINYOWS_SCHEMA_DIR', '/usr/share/tinyows/schema/');
define('TINYOWS_ONLINE_RESOURCE', PUBLIC_URL.'services/tinyows/');

/*************  REDLINE ***************/
define('REDLINE_SCHEMA', 'public');             //SCHEMA DB IN CUI VIENE CREATA LA TABELLA ANNOTAZIONI
define('REDLINE_TABLE', 'annotazioni');         //NOME DELLA TABELLA DB DELLE ANNOTAZIONI
define('REDLINE_SRID', '4326');                 //SRID DELLA TABELLA DB DELLE ANNOTAZIONI
define('REDLINE_FONT', 'dejavu-sans-bold');     //FONT DELLE ANNOTAZIONI. DEVE ESISTERE TRA I FONT AUTHOR

require_once (ROOT_PATH."config/config.db.php");

//Author
define('ADMIN_PATH',ROOT_PATH.'public/admin/');

//debug
if(!defined('DEBUG')) define('DEBUG', 0); // Debugging 0 off 1 on

/****************** QUERY REPORTS ***************+*/
define('MAX_REPORT_ROWS',5000);
define('REPORT_PROJECT_NAME','REPORT');
define('REPORT_MAPSET_NAME','report');
define('FONT_LIST','fonts');
define('MS_VERSION','');

define('CATALOG_EXT','SHP,TIFF,TIF,ECW');   //elenco delle estensioni caricabili sul layer
define('DEFAULT_ZOOM_BUFFER',100);          //buffer di zoom in metri in caso non venga specificato layer.tolerance
define('MAX_HISTORY',6);                    //massimo numero di viste memorizzate
define('MAX_OBJ_SELECTED',2000);            //massimo numero di oggetti selezionabili
define('WIDTH_SELECTION', 4);               //larghezza della polilinea di selezione
define('TRASP_SELECTION', 50);              //trasparenza della polilinea di selezione
define('COLOR_SELECTION', '255 0 255');     //colore della polilinea di selezione
define('MAP_BG_COLOR', '255 255 255');      //colore dello sfondo per default
define('EDIT_BUTTON', 'edit');

define('DEFAULT_TOLERANCE',4);                          //Raggio di ricerca in caso non venga specificato layer.tolerance
define('LAYER_SELECTION','__sel_layer');                //Nome per i layer di selezione
define('LAYER_IMAGELABEL','__image_label');             //Nome per il layer testo sulla mappa
define('LAYER_READLINE','__readline_layer');
define('DATALAYER_ALIAS_TABLE','__data__');             //nome riservato ad alias per il nome della tabella del layer (usato dal sistema nelle query, non ci devono essere tabelle con questo nome)
define('WRAP_READLINE','\\');
define('COLOR_REDLINE','0 0 255');                      //Colore Line di contorno oggetti poligono o linea selezionati
define('OBJ_COLOR_SELECTION','255 255 0');              //Colore Line di contorno oggetti poligono o linea selezionati
define('MAP_DPI',72);                                   //Mapserver map resolution
define('TILE_SIZE',256);                                //Mapserver map resolution
// define('SERVICE_MAX_RESOLUTION',156543.03392812);    // WMTS: Calcolare in base al valore presente nel campo ScaleDenominator del GetCapabilities (nella TileMatrix 0)
// define('SERVICE_MIN_ZOOM_LEVEL',7);                  // WMTS: min zoom level (default: 0 for google maps)
// define('SERVICE_MAX_ZOOM_LEVEL',19);                 // WMTS: max zoom level (default: 21 for google maps)
define('PDF_K',2);//Mapserver map resolution

define('SCALE','8000000,7000000,6000000,5000000,4000000,3000000,2000000,1000000,900000,800000,700000,600000,500000,400000,300000,200000,100000,50000,25000,10000,7500,5000,2000,1000,500,200,100,50');

/****************** LEGEND ***************+*/
define('LEGEND_ICON_W',24);
define('LEGEND_ICON_H',16);
define('LEGEND_POINT_SIZE',15);
define('LEGEND_LINE_WIDTH',1);
define('LEGEND_POLYGON_WIDTH',2);
define('PRINT_PDF_FONT','times');

/****************** DATA MANAGER ***************+*/
define('USE_DATA_IMPORT', false);                                           // true = ABILITA IL DATAMANAGER
define('CURRENT_EDITING_USER_TABLE', 'gc_current_editing_user');            //TABELLA DB IN CUI VENGONO SCRITTI GLI UTENTI DI EDITING
define('TRANSFORM_EDIT_GEOMETRY', false);                                   // true = CONSENTE L'EDITING SU MAPPA CON SRID XXXXX DI UNA TABELLA DB CON SRID YYYYY
//define('USE_PHP_EXCEL', true);                                            // true = ABILITA IL TAB XLS DEL DATAMANAGER. DEVE ANCHE ESISTERE LA LIBRERIA PHPExcel 
//define('MEASURE_AREA_COL_NAME', 'gc_area');                               //NOME DEL CAMPO DB IN CUI VERRA' SCRITTO IL VALORE CALCOLATO DELL'AREA IN EDITING DI MAPPA
//define('MEASURE_LENGTH_COL_NAME', 'gc_length');                           //NOME DEL CAMPO DB IN CUI VERRA' SCRITTO IL VALORE CALCOLATO DELLA LUNGHEZZA IN EDITING DI MAPPA
//define('COORDINATE_X_COL_NAME', 'gc_coord_x');                            //NOME DEL CAMPO DB IN CUI VERRA' SCRITTO IL VALORE CALCOLATO DELLA COORDINATA X IN EDITING DI MAPPA
//define('COORDINATE_Y_COL_NAME', 'gc_coord_y');                            //NOME DEL CAMPO DB IN CUI VERRA' SCRITTO IL VALORE CALCOLATO DELLA COORDINATA Y IN EDITING DI MAPPA
//define('LAST_EDIT_USER_COL_NAME', 'gc_user');                             //NOME DEL CAMPO DB IN CUI VERRA' SCRITTO IL NOME DELL'UTENTE DI ULTIMA MODIFICA IN EDITING DI MAPPA
//define('LAST_EDIT_DATE_COL_NAME', 'gc_date');                             //NOME DEL CAMPO DB IN CUI VERRA' SCRITTA LA DATA DI ULTIMA MODIFICA IN EDITING DI MAPPA
//define('UPLOADED_FILES_PRIVATE_PATH', ROOT_PATH.'files/');                //DECOMMENTARE PER ABILITARE IL CARICAMENTO IMMAGINI/DOCUMENTI IN FASE DI EDITING DA MAPPA 
//define('UPLOADED_FILES_PUBLIC_PATH', ROOT_PATH.'public/services/files/'); //DECOMMENTARE PER ABILITARE IL CARICAMENTO IMMAGINI/DOCUMENTI IN FASE DI EDITING DA MAPPA 
//define('UPLOADED_FILES_PUBLIC_URL', PUBLIC_URL.'services/files/');        //DECOMMENTARE PER ABILITARE IL CARICAMENTO IMMAGINI/DOCUMENTI IN FASE DI EDITING DA MAPPA 

define('CLIENT_LOGO', null); //LOGO CLIENTE PERSONALIZZATO IN AUTHOR

define('MAPFILE_MAX_SIZE', '4096'); //MASSIMA DIMENSIONE IN PIXEL DEL MAPFILE. PER STAMPE A0 INSERIRE: 20480

// Cache in ows.php
define('OWS_CACHE_TTL', 60);            // CACHE PER EVITARE DOPPIE RICHIESTE DI OL
define('OWS_CACHE_TTL_OPEN', 4*60*60);  // CACHE ALLA PRIMA RICHIESTA DI MAPPA PER VELOCIZZARE
//define('DYNAMIC_LAYERS', '');         // comma separated list of dynamic layers (same url different result)

