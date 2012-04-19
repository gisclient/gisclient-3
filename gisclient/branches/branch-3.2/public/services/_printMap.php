<?php
	include('../../config/config.php');
	$SRS = array(
		'3003'=>'+proj=tmerc +lat_0=0 +lon_0=9 +k=0.999600 +x_0=1500000 +y_0=0 +ellps=intl +units=m +no_defs +towgs84=-104.1,-49.1,-9.9,0.971,-2.917,0.714,-11.68',
		'900913'=>'+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +towgs84=0,0,0 +no_defs',
		'4326','+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs'
	);

	$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$db->db_connect_id){
			print('Errore di connessione');
			die();
		};
		
	$serviceURL = "http://".$_SERVER["SERVER_NAME"].GISCLIENT_OWS_URL;	
	$transform = defined('POSTGIS_TRANSFORM_GEOMETRY')?POSTGIS_TRANSFORM_GEOMETRY:'ST_Transform_Geometry';
	$project = 'servizicomunali';
	$map = 'stampa';
	$layers = 'base_ctr,toponomastica,civici,scavi';
	$buffer = 250;
	$inchesPerUnit = 39.3701;//facciamo sempr metri DA VEDERE
	$convFact = $inchesPerUnit * MAP_DPI;
	
	$width = $_REQUEST["WIDTH"];
	$height = $_REQUEST["HEIGHT"];
	$srs = $_REQUEST["srs"];
	$istanza = $_REQUEST["istanza"];
	
	//Verifica srid
	$sql = "SELECT f_geometry_column,srid FROM geometry_columns WHERE f_table_schema='test_istanze' AND f_table_name='elementi_scavi';";
	$db->sql_query ($sql);
	$geoCol = $db->sql_fetchfield('f_geometry_column');
	$dataSRID = $db->sql_fetchfield('srid');
	
	if($srs!="EPSG:$dataSRID"){
		$v = explode(':',$srs);
		$mapSRID = $v[1];
		if(!isset($SRS["$dataSRID"])) die("Mancano i parametri per EPSG:$dataSRID");
		if(!isset($SRS["$mapSRID"])) die("Mancano i parametri per EPSG:$mapSRID");
		$fromProj = $SRS["$dataSRID"];
		$toProj = $SRS["$mapSRID"];
		$geoCol = "$transform($geoCol,'$fromProj','$toProj',$mapSRID)";
	}

	$sql = "select extent($geoCol) as bbox from test_istanze.elementi_scavi where istanza = '$istanza';";
	$db->sql_query ($sql);
	if(!$db->sql_fetchfield('bbox')) die('Non esiste l\'istanza');

	
	$bbox = str_replace('BOX(','',$db->sql_fetchfield('bbox'));
	$bbox = str_replace(')','',$bbox);
	$bbox = str_replace(' ',',',$bbox);
	$bbox = explode(",",$bbox);
	$maxRes = max(($bbox[2]-$bbox[0]+$buffer)/$width,($bbox[3]-$bbox[1]+$buffer)/$height);

	$Xc = $bbox[0] + ($bbox[2]-$bbox[0])/2;
	$Yc = $bbox[1] + ($bbox[3]-$bbox[1])/2;
	$DX = $width * $maxRes;
	$DY = $height * $maxRes;

	$bbox[0] = $Xc - $DX/2;
	$bbox[2] = $Xc + $DX/2;
	$bbox[1] = $Yc - $DY/2;
	$bbox[3] = $Yc + $DY/2;
	$bbox = implode(",",$bbox);
	
	/*
	$sld = urlencode('<sld:StyledLayerDescriptor xmlns:sld="http://www.opengis.net/sld" version="1.0.0" xsi:schemaLocation="http://www.opengis.net/sld http://schemas.opengis.net/sld/1.0.0/StyledLayerDescriptor.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ogc="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml"><sld:NamedLayer><sld:Name>scavi.elementi_puntuali</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle><sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter><sld:PointSymbolizer><sld:Graphic><sld:Mark><sld:WellKnownName>circle</sld:WellKnownName><sld:Fill><sld:CssParameter name="fill">#ff0000</sld:CssParameter></sld:Fill><sld:Stroke><sld:CssParameter name="stroke">#FFFF00</sld:CssParameter><sld:CssParameter name="stroke-width">2</sld:CssParameter></sld:Stroke></sld:Mark><sld:Size>10</sld:Size></sld:Graphic></sld:PointSymbolizer></sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer><sld:NamedLayer><sld:Name>scavi.elementi_lineari</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle><sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter><sld:LineSymbolizer><sld:Stroke><sld:CssParameter name="stroke">#FFFF00</sld:CssParameter><sld:CssParameter name="stroke-width">2</sld:CssParameter></sld:Stroke></sld:LineSymbolizer></sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer><sld:NamedLayer><sld:Name>scavi.elementi_poligonali</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle><sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter><sld:PolygonSymbolizer><sld:Stroke><sld:CssParameter name="stroke">#FFFF00</sld:CssParameter><sld:CssParameter name="stroke-width">2</sld:CssParameter></sld:Stroke></sld:PolygonSymbolizer></sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer></sld:StyledLayerDescriptor>');
	$sldn = urlencode('<sld:StyledLayerDescriptor xmlns:sld="http://www.opengis.net/sld" version="1.0.0" xsi:schemaLocation="http://www.opengis.net/sld http://schemas.opengis.net/sld/1.0.0/StyledLayerDescriptor.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ogc="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml"><sld:NamedLayer><sld:Name>scavi.elementi_puntuali</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle>
	<sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter>
	</sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer><sld:NamedLayer><sld:Name>scavi.elementi_lineari</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle><sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter>
	</sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer><sld:NamedLayer><sld:Name>scavi.elementi_poligonali</sld:Name><sld:UserStyle><sld:Name>default</sld:Name><sld:FeatureTypeStyle><sld:Rule><ogc:Filter xmlns:ogc="http://www.opengis.net/ogc"><ogc:PropertyIsEqualTo><ogc:PropertyName>istanza</ogc:PropertyName><ogc:Literal>'.$istanza.'</ogc:Literal></ogc:PropertyIsEqualTo></ogc:Filter>
	</sld:Rule></sld:FeatureTypeStyle></sld:UserStyle></sld:NamedLayer></sld:StyledLayerDescriptor>');
	$url = $serviceURL."?PROJECT=$project&MAP=$map&FORMAT=image%2Fpng%3B%20mode%3D24bit&TRANSPARENT=TRUE&LAYERS=$layers&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&SRS=$srs&BBOX=$bbox&WIDTH=$width&HEIGHT=$height&SLD_BODY=$sld";
	*/
	
	$gcFilters = "scavi.elementi_puntuali@istanza='$istanza',scavi.elementi_lineari@istanza='$istanza',scavi.elementi_poligonali@istanza='$istanza'";
	$url = $serviceURL."?PROJECT=$project&MAP=$map&FORMAT=image%2Fpng%3B%20mode%3D24bit&TRANSPARENT=TRUE&LAYERS=$layers&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetMap&STYLES=&SRS=$srs&BBOX=$bbox&WIDTH=$width&HEIGHT=$height&GCFILTERS=$gcFilters";
	
	$ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    $image = curl_exec($ch);
	header('Content-type: image/png'); 
	echo $image;
	