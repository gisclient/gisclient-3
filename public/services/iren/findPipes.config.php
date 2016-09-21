<?php
/***** CONFIGURAZIONE ELEMENTI DA INTERCETTARE : condotta, valvola, sollevamento, riduttore, serbatoio, vasca ****/
define('POSTGIS_TRANSFORM_GEOMETRY','postgis_transform_geometry');
$SCHEMA = "acqua";
$FID_FIELD = "gs_id";
$GEOM_FIELD = "geom";
$GEOM_SRID = "3003";
$TIME_OUT = "50000";
$ELEMENTS = array(
	"condotta" => array("featureType"=>array(
		"table" => "ratraccia_v",
		"title" => "Condotta",
		"properties" => array(
			array(
				"name"=>"ubicaz_con",
				"fieldHeader"=>"Indirizzo",
				"type"=>"string"
			),
			array(
				"name"=>"diametro",
				"fieldHeader"=>"Diametro",
				"type"=>"string"
			),
			array(
				"name"=>"materiale",
				"fieldHeader"=>"Materiale",
				"type"=>"string"
			),		
			array(
				"name"=>"profondita",
				"fieldHeader"=>"Profondit�",
				"type"=>"string"
			),
			array(
				"name"=>"note",
				"fieldHeader"=>"Note",
				"type"=>"string"
			)
		)
	)),
	"valvola" => array("featureType"=>array(
		"table" => "ravalvola_generica_v",
		"title" => "Valvola",
		"properties" => array(
			array(
				"name"=>"indirizzo",
				"fieldHeader"=>"Indirizzo",
				"type"=>"string"
			),
			array(
				"name"=>"codice",
				"fieldHeader"=>"Codice",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			),		
			array(
				"name"=>"note",
				"fieldHeader"=>"Note",
				"type"=>"string"
			)
		)
	)),
	"sollevamento" => array("featureType"=>array(
		"table" => "rastaz_sollevamento_v",
		"title" => "Stazione sollevamento",
		"properties" => array(
			array(
				"name"=>"indirizzo",
				"fieldHeader"=>"Indirizzo",
				"type"=>"string"
			),
			array(
				"name"=>"codice",
				"fieldHeader"=>"Codice",
				"type"=>"string"
			),
			array(
				"name"=>"stato_funz",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			),		
			array(
				"name"=>"note",
				"fieldHeader"=>"Note",
				"type"=>"string"
			)
		)
	)),
	"riduttore" => array("featureType"=>array(
		"table" => "rariduttore_v",
		"title" => "Riduttore",
		"properties" => array(

			array(
				"name"=>"codice",
				"fieldHeader"=>"Codice",
				"type"=>"string"
			),	
			array(
				"name"=>"note",
				"fieldHeader"=>"Note",
				"type"=>"string"
			)
		)
	)),
	"serbatoio" => array("featureType"=>array(
		"table" => "raserbatoio_v",
		"title" => "Serbatoio",
		"properties" => array(
			array(
				"name"=>"indirizzo",
				"fieldHeader"=>"Indirizzo",
				"type"=>"string"
			),
			array(
				"name"=>"codice",
				"fieldHeader"=>"Codice",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			),		
			array(
				"name"=>"note",
				"fieldHeader"=>"Note",
				"type"=>"string"
			)
		)
	)),
	"vasca" => array("featureType"=>array(
		"table" => "ravasca_rompitratta_v",
		"title" => "Vasca rompitratta",
		"properties" => array(
			array(
				"name"=>"funzione",
				"fieldHeader"=>"Funzione",
				"type"=>"string"
			)
		)
	))
)


?>