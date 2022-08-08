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
define('RESULT_TYPE_SINGLE', 1);
define('RESULT_TYPE_TABLE', 2);
define('RESULT_TYPE_ALL', 3);
define('RESULT_TYPE_NONE', 4);

define('ONE_TO_ONE_RELATION', 1);
define('ONE_TO_MANY_RELATION', 2);

define('AGGREGATE_NULL_VALUE', '----');

define('ORDER_FIELD_ASC', 1);
define('ORDER_FIELD_DESC', 2);

define('STANDARD_FIELD_TYPE', 1);
define('LINK_FIELD_TYPE', 2);
define('EMAIL_FIELD_TYPE', 3);
define('HEADER_GROUP_TYPE', 10);
define('IMAGE_FIELD_TYPE', 8);
define('SECONDARY_FIELD_LINK', 99);

//$aUnitDef = array(1=>"m",2=>"ft",3=>"inches",4=>"km",5=>"m",6=>"mi",7=>"dd");//units tables (force pixel ->m)
//$aInchesPerUnit = array(1=>39.3701,2=>12,3=>1,4=>39370.1,5=>39.3701,6=>63360,7=>4374754);



$gMapMaxZoomLevels = array('G_HYBRID_MAP'=>19,'G_NORMAL_MAP'=>21,'G_PHYSICAL_MAP'=>15,'G_SATELLITE_MAP'=>19,'VEMapStyle.Road'=>21,'VEMapStyle.Aerial'=>21,'VEMapStyle.Shaded'=>21,'VEMapStyle.Hybrid'=>21,'YAHOO_MAP_HYB'=>21,'YAHOO_MAP_REG'=>21,'YAHOO_MAP_SAT'=>21,'Mapnik'=>21,'Osmarender'=>21,'CycleMap'=>17);


function array_limit($aList, $maxVal = false, $minVal = false)
{
    $ar=array();
    foreach ($aList as $val) {
        if ($maxVal && $val>=$maxVal) {
            $ar[]=$val;
        }
        if ($minVal && $val<$minVal) {
            $ar[]=$val;
        }
    }
    return array_values(array_diff($aList, $ar));
}

function array_index($aList, $value)
{
    $retval=false;
    for ($i=0; $i<count($aList); $i++) {
        if ($value<=$aList[$i]) {
            $retval=$i;
        }
    }
    return $retval;
}


function getResolutions($srid, $convFact, $maxRes = false, $minRes = false)
{
    //se mercatore sferico setto le risoluzioni di google altrimenti uso quelle predefinite dall'elenco scale

    $aRes=array();
    if (($srid==900913)|($srid==3857)) {
        $aRes = array_limit(array_slice(GCAuthor::$gMapResolutions, GMAP_MIN_ZOOM_LEVEL), $maxRes, $minRes);
    } else {
        foreach (GCAuthor::$defaultScaleList as $scaleValue) {
            $aRes[]=$scaleValue/$convFact;
        }
        $aRes=array_limit($aRes, $maxRes, $minRes);
    }
    return $aRes;
}

function getExtent($xCenter, $yCenter, $Resolution)
{
    //4tiles
    $aExtent=array();
    $aExtent[0] = $xCenter - $Resolution * TILE_SIZE ;
    $aExtent[1] = $yCenter - $Resolution * TILE_SIZE ;
    $aExtent[2] = $xCenter + $Resolution * TILE_SIZE ;
    $aExtent[3] = $yCenter + $Resolution * TILE_SIZE ;
    return $aExtent;
}


function crop_border($image, $border)
{
    if (2*$border > imagesx($image)) {
        $width = 0;
        $src_x = 0;
    } else {
        $width = imagesx($image) - 2*$border;
        $src_x = $border;
    }
    if (2*$border > imagesy($image)) {
        $height = 0;
        $src_y = 0;
    } else {
        $height = imagesy($image) - 2*$border;
        $src_y = $border;
    }
    $croppped_img = imagecreatetruecolor($width, $height);
    imagesavealpha($croppped_img, true);
    $transparent = imagecolorallocatealpha($croppped_img, 0, 0, 0, 127);
    imagefill($croppped_img, 0, 0, $transparent);
    imagecopy($croppped_img, $image, 0, 0, $src_x, $src_y, $width, $height);
    return $croppped_img;
}



function connInfofromPath($sPath)
{
    $pathInfo = explode("/", $sPath);
    if (defined('MAP_USER')) {
        $mapUser = MAP_USER;
        $mapPwd = MAP_PWD;
    } else {
        $mapUser = DB_USER;
        $mapPwd = DB_PWD;
    }

    if (count($pathInfo)==1) {//Mancano le informazioni di connessione, ho solo lo schema e il db ï¿½ quello del gisclient
        $connString = "user=".$mapUser." password=".$mapPwd." dbname=".DB_NAME." host=".DB_HOST." port=".DB_PORT;
        $datalayerSchema = $pathInfo[0];
    } else {//Abbiamo db e schema
        $datalayerSchema = $pathInfo[1];
        $connInfo=explode(" ", $pathInfo[0]);
        if (count($connInfo)==1) { //abbiamo il nome del db
            $connString = "user=".$mapUser." password=".$mapPwd." dbname=".$connInfo[0]." host=".DB_HOST." port=".DB_PORT;
        } else { //abbiamo la stringa di connessione
            $connString = $pathInfo[0];
        }
    }
    return array($connString,$datalayerSchema);
}

function connAdminInfofromPath($sPath)
{
    if (!isset($sPath)) {
        return;
    }
    $pathInfo = explode("/", $sPath);
    $datalayerSchema = $pathInfo[1];
    $connInfo=explode(" ", $pathInfo[0]);

    if (count($connInfo)==1) { //abbiamo il nome del db
        $connString = "user=".DB_USER." password=".DB_PWD." dbname=".$connInfo[0]." host=".DB_HOST." port=".DB_PORT;
    } else { //abbiamo la stringa di connessione
        $connString = $pathInfo[0];
    }

    return array($connString,$datalayerSchema);
}

function setDBPermission($db, $sk, $usr, $type, $mode, $table = '')
{
    if ($type=='EXECUTE') {
        $sql="select specific_name,routine_name from information_schema.routines where routine_schema='$sk'";
        $result=pg_query($db, $sql);
        if (!$result) {
            echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
        }

        $ris=pg_fetch_all($result);
        for ($i=0; $i<count($ris); $i++) {
            $sql="select udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and specific_schema='$sk' order by ordinal_position";

            $sql="select udt_schema,udt_name from information_schema.parameters where specific_name='".$ris[$i]["specific_name"]."' and parameter_mode='IN' and specific_schema='$sk' order by ordinal_position";

            $fld=array();
            $result=pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
            $flds=pg_fetch_all($result);
            for ($j=0; $j<count($flds); $j++) {
                $fld[]=$flds[$j]["udt_name"];
            }
            $prm=implode(',', $fld);

            if ($ris[$i]["routine_name"]) {
                $fName=$sk.'.'.$ris[$i]["routine_name"]."($prm)";
                $sql=($mode=='GRANT')?("GRANT EXECUTE ON FUNCTION $fName TO $usr"):("REVOKE EXECUTE ON FUNCTION $fName FROM $usr");
                $result=pg_query($db, $sql);
                if (!$result) {
                    echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
                }
            }
        }
    } else {
        $sql=($mode=='GRANT')?("GRANT USAGE ON SCHEMA $sk TO $usr;"):("REVOKE USAGE ON SCHEMA $sk FROM $usr;");
        if (!$table) {
            $result=pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
        } else {
            $result=1;
        }
        if ($result) {
            $filter=($sk=='public')?("and table_name IN ('geometry_columns','spatial_ref_sys')"):(($table)?("and table_name ='$table'"):(""));
            $sql="select '$sk.'||table_name as tb from information_schema.tables where table_schema='$sk' $filter order by table_name";
            $result=pg_query($db, $sql);
            if (!$result) {
                echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
            }
            $ris=pg_fetch_all($result);
            for ($i=0; $i<count($ris); $i++) {
                $sql=($mode=='GRANT')?("GRANT SELECT ON TABLE ".$ris[$i]["tb"]." TO $usr;"):("REVOKE SELECT ON TABLE ".$ris[$i]["tb"]." FROM $usr;");
                $result=pg_query($db, $sql);
                if (!$result) {
                    echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
                }
            }
        }
    }
}

function setTriggerTransformGeometry($db, $schema, $table, $geometry_column)
{
    $sql = "DROP TRIGGER IF EXISTS transform_geometry ON {$schema}.\"{$table}\";";
    $result = $dataDb->exec($sql);
    if (!$result) {
        echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
    }

    $sql = "CREATE OR REPLACE FUNCTION {$schema}.gc_transform_geometry_{$table}()"
        . ' RETURNS trigger AS'
        . ' $BODY$ '
        . ' DECLARE '
        . '     table_srid integer;'
        . ' BEGIN'
        . "     table_srid = Find_SRID(TG_TABLE_SCHEMA::text, TG_TABLE_NAME::text, '{$geometry_column}'::text);"
        . "     if(table_srid <> ST_SRID(new.{$geometry_column})) then"
        . "         new.{$geometry_column} = ST_Transform(new.{$geometry_column}, table_srid);"
        . '     end if;'
        . '     return new;'
        . ' END'
        . ' $BODY$'
        . ' LANGUAGE plpgsql VOLATILE COST 100;'
        . " ALTER FUNCTION {$schema}.gc_transform_geometry_{$table}() OWNER TO " . MAP_USER . ';'
        . " GRANT EXECUTE ON FUNCTION {$schema}.gc_transform_geometry_{$table}() TO public;"
        . " GRANT EXECUTE ON FUNCTION {$schema}.gc_transform_geometry_{$table}() TO " . MAP_USER . ';';
    $result = $dataDb->exec($sql);
    if (!$result) {
        echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
    }

    $sql = "CREATE TRIGGER transform_geometry BEFORE INSERT OR UPDATE ON {$schema}.{$table} FOR EACH ROW EXECUTE PROCEDURE {$schema}.gc_transform_geometry_{$table}();";
    $result = $dataDb->exec($sql);
    if (!$result) {
        echo "<p><b style=\"color:red\">Errore nella query:<br>$sql</b></p>";
    }
}


function rgb2html($r, $g = -1, $b = -1)
{
    if (is_array($r) && sizeof($r) == 3) {
        list($r, $g, $b) = $r;
    }

    $r = intval($r);
    $g = intval($g);
    $b = intval($b);

    $r = dechex($r<0?0:($r>255?255:$r));
    $g = dechex($g<0?0:($g>255?255:$g));
    $b = dechex($b<0?0:($b>255?255:$b));

    $color = (strlen($r) < 2?'0':'').$r;
    $color .= (strlen($g) < 2?'0':'').$g;
    $color .= (strlen($b) < 2?'0':'').$b;
    return '#'.$color;
}

function html2rgb($color)
{
    if ($color[0] == '#') {
        $color = substr($color, 1);
    }

    if (strlen($color) == 6) {
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    } elseif (strlen($color) == 3) {
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    } else {
        return false;
    }

    $r = hexdec($r);
    $g = hexdec($g);
    $b = hexdec($b);

    return array($r, $g, $b);
}









/*-----------------------------------------------------------------------------------------------  Funzioni per ristrutturare ARRAY DATA in funzione di output pdf -----------------------------------------------------------------------------*/


/*-----------------------------------------------------------------------------------------------  Funzioni di prova per estrarre i dati per i grafici dall'ARRAY DATA  -----------------------------------------------------------------------------*/
    //TODO ||||||||||||||||||||||||||!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
function NameReplace($name)
{

    $search = explode(",", " ,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,.");
    $replace = explode(",", "_,c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,_");
    if (strtoupper(CHAR_SET)=='UTF-8') {
        for ($i=0; $i<count($search); $i++) {
            $name=str_replace($search[$i], $replace[$i], trim($name));
        }
    } else {
        $name = str_replace($search, $replace, trim($name));
    }

    return $name;
    //return strtolower($name);
}

function niceName($name)
{
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/_{2,}/', '_', $name);
        $name = preg_replace('/^_+/', '', $name);
        $name = preg_replace('/_+$/', '', $name);
    $name = NameReplace($name);
    $name = preg_replace('/[^a-z0-9_]+/i', '', $name);
    return $name;
}

/*---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

/* funzione http_build_url , se manca */

if (!function_exists('http_build_url')) {
    define('HTTP_URL_REPLACE', 1);                // Replace every part of the first URL when there's one of the second URL
    define('HTTP_URL_JOIN_PATH', 2);            // Join relative paths
    define('HTTP_URL_JOIN_QUERY', 4);            // Join query strings
    define('HTTP_URL_STRIP_USER', 8);            // Strip any user authentication information
    define('HTTP_URL_STRIP_PASS', 16);            // Strip any password authentication information
    define('HTTP_URL_STRIP_AUTH', 32);            // Strip any authentication information
    define('HTTP_URL_STRIP_PORT', 64);            // Strip explicit port numbers
    define('HTTP_URL_STRIP_PATH', 128);            // Strip complete path
    define('HTTP_URL_STRIP_QUERY', 256);        // Strip query string
    define('HTTP_URL_STRIP_FRAGMENT', 512);        // Strip any fragments (#identifier)
    define('HTTP_URL_STRIP_ALL', 1024);            // Strip anything but scheme and host

    // Build an URL
    // The parts of the second URL will be merged into the first according to the flags argument.
    //
    // @param    mixed            (Part(s) of) an URL in form of a string or associative array like parse_url() returns
    // @param    mixed            Same as the first argument
    // @param    int                A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
    // @param    array            If set, it will be filled with the parts of the composed url like parse_url() would return
    function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false)
    {
        $keys = array('user','pass','port','path','query','fragment');

        // HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
            $flags |= HTTP_URL_STRIP_PORT;
            $flags |= HTTP_URL_STRIP_PATH;
            $flags |= HTTP_URL_STRIP_QUERY;
            $flags |= HTTP_URL_STRIP_FRAGMENT;
        }
        // HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
        elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER;
            $flags |= HTTP_URL_STRIP_PASS;
        }

        // Parse the original URL
        $parse_url = parse_url($url);

        // Scheme and Host are always replaced
        if (isset($parts['scheme'])) {
            $parse_url['scheme'] = $parts['scheme'];
        }
        if (isset($parts['host'])) {
            $parse_url['host'] = $parts['host'];
        }

        // (If applicable) Replace the original URL with it's new parts
        if ($flags & HTTP_URL_REPLACE) {
            foreach ($keys as $key) {
                if (isset($parts[$key])) {
                    $parse_url[$key] = $parts[$key];
                }
            }
        } else {
            // Join the original URL path with the new path
            if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH)) {
                if (isset($parse_url['path'])) {
                    $parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
                } else {
                    $parse_url['path'] = $parts['path'];
                }
            }

            // Join the original query string with the new query string
            if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY)) {
                if (isset($parse_url['query'])) {
                    $parse_url['query'] .= '&' . $parts['query'];
                } else {
                    $parse_url['query'] = $parts['query'];
                }
            }
        }

        // Strips all the applicable sections of the URL
        // Note: Scheme and Host are never stripped
        foreach ($keys as $key) {
            if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key))) {
                unset($parse_url[$key]);
            }
        }


        $new_url = $parse_url;

        return
             ((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
            .((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
            .((isset($parse_url['host'])) ? $parse_url['host'] : '')
            .((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
            .((isset($parse_url['path'])) ? $parse_url['path'] : '')
            .((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
            .((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
        ;
    }
}

function addFinalSlash($dir)
{
    if (substr($dir, -1) != '/') {
        return $dir.'/';
    }
    return $dir;
}
