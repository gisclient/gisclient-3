<?php
require_once("nusoap.php");
require_once "../../../config/config.php";
error_reporting(E_ERROR && E_NOTICE);
define ('PROJECT', 'sit_alghero'); //admin_aster:admin_aster
define('NAME_SPACE', 'http://'.$_SERVER['HTTP_HOST']. $_SERVER['PHP_SELF'].'?wsdl');
define('RETURN_OK','OK');
define('SRS_3003','+proj=tmerc +lat_0=0 +lon_0=9 +k=0.9996 +x_0=1500000 +y_0=0 +ellps=intl +units=m +no_defs +towgs84=-168.6,-34.0,38.6,-0.374,-0.679,-1.379,-9.48');
define('SRS_4326','+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs');


$server = new soap_server;
$server->debug_flag=false;

$server->configureWSDL('Web_Service_Stradario', NAME_SPACE);
$server->wsdl->schemaTargetNamespace = NAME_SPACE; 


//Definizione dell'oggetto Via
$server->wsdl->addComplexType(
	'Via',
	'complexType',
	'struct',
	'all',
	'',
	array( // N.B. in NuSOAP il NAME_SPACE per i tipi base è xsd, in esempi precedenti noi avevamo usato xs
	'codice' =>array('name'=>'codice','type'=>'xsd:int'),
	'nome' =>array('name'=>'nome','type'=>'xsd:string'),	
	'specie' =>array('name'=>'specie','type'=>'xsd:string'),
	'da_via' =>array('name'=>'da_via','type'=>'xsd:string'),
	'a_via' =>array('name'=>'a_via','type'=>'xsd:string')
	)
);

//Definizione della collezione di Vie
$server->wsdl->addComplexType(
	'Vie',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType',
		'wsdl:arrayType'=>'tns:Via[]')
	),
	'tns:Via'
);

//metodo che restituisce le vie
$server->register( 
	'elencoVie',
	array('query'=>'xsd:string'),
	array('return'=>'tns:Vie'), 
	NAME_SPACE, 
	NAME_SPACE.'#elencoVie',
	'rpc',
	'encoded',
	'<br>Restitisce l\'elenco delle vie (codice, nome, specie, da_via , a_via).<br>
	Accetta il parametro query: parte del nome della via da usare come filtro (supporta il carattere %'
);


//Definizione dell'oggetto Civico
$server->wsdl->addComplexType(
	'Civico',
	'complexType',
	'struct',
	'all',
	'',
	array( // N.B. in NuSOAP il NAME_SPACE per i tipi base è xsd, in esempi precedenti noi avevamo usato xs
	'numero' =>array('name'=>'numero','type'=>'xsd:int'),
	'sub' =>array('name'=>'sub','type'=>'xsd:string'),	
	'etichetta' =>array('name'=>'etichetta','type'=>'xsd:string'),
	'latostrada' =>array('name'=>'latostrada','type'=>'xsd:string'),
	'tipoaccesso' =>array('name'=>'tipoaccesso','type'=>'xsd:string'),
	'presenzacivico' =>array('name'=>'presenzacivico','type'=>'xsd:string'),	
	'accessoprincipale' =>array('name'=>'accessoprincipale','type'=>'xsd:string'),
	'xcoord' =>array('name'=>'xcoord','type'=>'xsd:decimal'),
	'ycoord' =>array('name'=>'ycoord','type'=>'xsd:decimal'),
	'idaccesso' =>array('name'=>'idaccesso','type'=>'xsd:int'),
	'idcivico' =>array('name'=>'idcivico','type'=>'xsd:int')
	)
);


//Definizione della collezione di Civici
$server->wsdl->addComplexType(
	'Civici',
	'complexType',
	'array',
	'',
	'SOAP-ENC:Array',
	array(),
	array(
		array('ref'=>'SOAP-ENC:arrayType',
		'wsdl:arrayType'=>'tns:Civico[]')
	),
	'tns:Civico'
);


//metodo che restituisce i civici
$server->register( 
	'elencoCivici',
	array('codice'=>'xsd:int'),
	array('return'=>'tns:Civici'), 
	NAME_SPACE, 
	NAME_SPACE.'#elencoCivici',
	'rpc',
	'encoded',
	'<br>Restituisce i civici dato il codice della via'
);

		
function elencoVie($query=''){

	if($query=='?')
			$query='';

	$db = GCApp::getDB();
	$sql="SELECT tpstrid as codice,tpstrnom AS nome,specie,da_via,a_via FROM dbt_topociv.dbt_tpstr WHERE  stradario=1";
	if($query) $sql.= " AND tpstrnom ILIKE '%".pg_escape_string($query)."%'";
	$sql.="ORDER BY tpstrnom;";

	try {
		$stmt = $db->prepare($sql);
		$stmt->execute();
	} catch (PDOException $e) {
	    $message = $e->getMessage();
	    return new soapval('return', 'xsd:string', $message);
	}
	$result = $stmt->fetchAll();
	return $result;
	
}


function elencoCivici($query=''){

	if($query=='?')
			$query='';

	if ($query=='') {
		$message = "manca il Codice Via";
	    return new soapval('return', 'xsd:string', $message);
	}

	$db = GCApp::getDB();
	$sql = "SELECT
    civ.civiconum AS numero,
    civ.civicosub AS sub,
    btrim(COALESCE((lpad(civ.civiconum::text, 5) || '/'::text) || lower(civ.civicosub::text), lpad(civ.civiconum::text, 5))) AS etichetta,
    lst.descrizione AS latostrada,
    ty.descrizione AS tipoaccesso,
    cci.descrizione AS presenzacivico,
    rnc.descrizione AS accessoprincipale,
    round(st_X(acc.accpcpos)::numeric,2) AS xcoord,
    round(st_Y(acc.accpcpos)::numeric,2) AS ycoord,
    acc.accpcid AS idaccesso,
    acc.civicoid::integer AS idcivico
   FROM dbt_topociv.dbt_accpc acc
     LEFT JOIN dbt_topociv.dbt_civico civ ON acc.civicoid = civ.civicoid::numeric
     LEFT JOIN dbt_viab.dbt_enuvalore ty ON acc.enuclasse::text = ty.enuclasse::text AND acc.accpcty::text = ty.enucodice::text
     LEFT JOIN dbt_viab.dbt_enuvalore cci ON acc.enuclasse::text = cci.enuclasse::text AND acc.accpcciv::text = cci.enucodice::text
     LEFT JOIN dbt_viab.dbt_enuvalore rnc ON acc.enuclasse::text = rnc.enuclasse::text AND acc.accpcprnc::text = rnc.enucodice::text
     LEFT JOIN dbt_viab.dbt_enuvalore lst ON civ.enuclasse::text = lst.enuclasse::text AND civ.civicolatos::text = lst.enucodice::text
    WHERE tpstrid=:id_strada ORDER BY civiconum, civicosub;";

	try {
		$stmt = $db->prepare($sql);
		$stmt->execute(array($query));
	} catch (PDOException $e) {
	    $message = $e->getMessage();
	    return new soapval('return', 'xsd:string', $message);
	}
	$result = $stmt->fetchAll();
	//print_array($result);

	return $result;


}


$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);


