<?php

//SE IL DATABASE NON È QUELLO DOVE RISIEDE LO SCHEMA GISCLIENT RIDEFINISCO LE COSTANTI
define('DB_NAME','geoweb_dati');
//define('DB_USER','******'); 
//define('DB_PWD','*******');
//define('DB_HOST','******');
//define('DB_PORT','*****');


/***** CONFIGURAZIONE ELEMENTI DA INTERCETTARE : condotta, valvola, sollevamento, riduttore, serbatoio, vasca 
(altri elementi e/o campi delle tabelle possono essere aggiunti a piacere dall'utente) ****/
$SCHEMA = "acq";
$FID_FIELD = "gs_id";
$GEOM_FIELD = "geom";
$GEOM_SRID = "25832";
$TIME_OUT = "10000";
$ELEMENTS = array(
	"condotta" => array("featureType"=>array(
		"table" => "ra_traccia_acquedotto_pg_v",
		"title" => "Condotta",
		"properties" => array(
			array(
				"name"=>"gs_id_a",
				"fieldHeader"=>"Gs_id",
				"type"=>"string"
			),
			array(
				"name"=>"tipo_traccia",
				"fieldHeader"=>"Tipo condotta",
				"type"=>"string"
			),
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"materiale",
				"fieldHeader"=>"Materiale",
				"type"=>"string"
			),
			array(
				"name"=>"lunghezza",
				"fieldHeader"=>"Lunghezza",
				"type"=>"string"
			),
			array(
				"name"=>"profondita",
				"fieldHeader"=>"Profondità",
				"type"=>"string"
			),
			array(
				"name"=>"diametro",
				"fieldHeader"=>"Diametro",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			),
			array(
				"name"=>"sede_posa",
				"fieldHeader"=>"Sede posa",
				"type"=>"string"
			)
		)
	)),
	"valvola" => array("featureType"=>array(
		"table" => "ra_saracinesca_rete_pg_v",
		"title" => "Valvola",
		"properties" => array(
			array(
				"name"=>"gs_id_a",
				"fieldHeader"=>"Gs_id",
				"type"=>"string"
			),
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"tipologia",
				"fieldHeader"=>"Tipologia",
				"type"=>"string"
			),
			array(
				"name"=>"tipo_valvola",
				"fieldHeader"=>"Tipo valvola",
				"type"=>"string"
			),
			array(
				"name"=>"materiale",
				"fieldHeader"=>"Materiale",
				"type"=>"string"
			),
			array(
				"name"=>"diametro",
				"fieldHeader"=>"Diametro",
				"type"=>"string"
			),
			array(
				"name"=>"n_cameretta",
				"fieldHeader"=>"Nr. cameretta",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			),
			array(
				"name"=>"sede_posa",
				"fieldHeader"=>"Sede posa",
				"type"=>"string"
			)
		)
	)),
	"sollevamento" => array("featureType"=>array(
		"table" => "ra_stazioni_sollevamento_pg_v",
		"title" => "Stazione sollevamento",
		"properties" => array(
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"tipologia",
				"fieldHeader"=>"Tipologia",
				"type"=>"string"
			),
			array(
				"name"=>"n_pompe",
				"fieldHeader"=>"Nr.pompe",
				"type"=>"string"
			),
			array(
				"name"=>"denominazione",
				"fieldHeader"=>"Denominazione",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			)
		)
	)),
	"riduttore" => array("featureType"=>array(
		"table" => "ra_riduttore_pg_v",
		"title" => "Riduttore",
		"properties" => array(
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"diametro",
				"fieldHeader"=>"Diametro",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			)
		)
	)),
	"serbatoio" => array("featureType"=>array(
		"table" => "ra_serbatoi_pg_v",
		"title" => "Serbatoio",
		"properties" => array(
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"denominazione",
				"fieldHeader"=>"Denominazione",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			)
		)
	)),
	"vasca" => array("featureType"=>array(
		"table" => "ra_vasca_decantazione_pg_v",
		"title" => "Vasca decantazione",
		"properties" => array(
			array(
				"name"=>"comune",
				"fieldHeader"=>"Comune",
				"type"=>"string"
			),
			array(
				"name"=>"via",
				"fieldHeader"=>"Via",
				"type"=>"string"
			),
			array(
				"name"=>"denominazione",
				"fieldHeader"=>"Denominazione",
				"type"=>"string"
			),
			array(
				"name"=>"stato",
				"fieldHeader"=>"Stato",
				"type"=>"string"
			)
		)
	))
)


?>