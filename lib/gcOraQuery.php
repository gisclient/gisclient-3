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

/*	Campo search_type per definizione della ricerca:
	1 - Testo secco;
	2 - Parte di testo senza suggerimenti
	3 - Testo con autocompletamento e lista suggerimenti (dati presi dal campo search_list);
	4 - Numerico
	5 - Data
	6 - SI/NO*/



//scelta da menu radio
define('QUERY_EXTENT',0);
define('QUERY_WINDOW',1);
define('QUERY_CURRENT',2);
define('QUERY_RESULT',3);
//selezione sulla mappa
define('QUERY_POINT',5);
define('QUERY_POLYGON',6);
define('QUERY_CIRCLE',7);

define('OBJ_POINT',1);
define('OBJ_CIRCLE',2);
define('OBJ_POLYGON',3);

define('AND_CONST','&&');
define('OR_CONST','||');
define('LT_CONST','<');
define('LE_CONST','<=');
define('GT_CONST','>');
define('GE_CONST','>=');
define('NEQ_CONST','!=');
define('JOLLY_CHAR','*');

define('STANDARD_FIELD_TYPE',1);
define('LINK_FIELD_TYPE',2);
define('EMAIL_FIELD_TYPE',3);
define('HEADER_GROUP_TYPE',10);
define('IMAGE_FIELD_TYPE',8);
define('SECONDARY_FIELD_LINK',99);

/* 
define('SUM_FIELD_TYPE',6);
define('COUNT_FIELD_TYPE',7);
define('AVG_FIELD_TYPE',8);
*/

define('RESULT_TYPE_SINGLE',1);
define('RESULT_TYPE_TABLE',2);
define('RESULT_TYPE_ALL',3);
define('RESULT_TYPE_NONE',4);

define('ONE_TO_ONE_RELATION',1);
define('ONE_TO_MANY_RELATION',2);




define('AGGREGATE_NULL_VALUE','----');


define('ORDER_FIELD_ASC',1);
define('ORDER_FIELD_DESC',2);

define('CONNECTION_TYPE_POSTGIS', 6);
define('CONNECTION_TYPE_ORACLE_SPATIAL', 8);

//SE SI MODIFICA RICORDARSI DI MODIFICARLA ANCHE NELLE FUNZIONI DI RICERCA SU DATABASE!!!!!!!!!!!
class OracleQuery{
	
	var $allQueryResults = array();
	var $allQueryExtent = array();
	var $mapToUpdate=0;
	var $aggregateFunction = array(101=>'sum',102=>'avg',103=>'min',104=>'max',105=>'count',106=>'variance',107=>'stddev');
	var $resultHeaders = array();
    var $dbAdapter;
    var $queryGeom;

	function __destruct (){
		//$this->db->sql_close();
		//unset($this->db);
		//unset($this->mapsetId);
		//unset($this->mapError);
	}

	function __construct(){
		$db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$db->db_connect_id) die( "Impossibile connettersi al database ");
		$this->db=$db;
		$dbschema=DB_SCHEMA;
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$this->queryGeom=false;
		
		//Se ho una chiamata esterna (zoomobject da querystring) riassegno le variabili di request
		//if($_REQUEST["action"]=="zoom_object") $this->_setRequest();
		
		//Risultato su tabella o singoli oggetti?
		$this->resultype = $_REQUEST["resultype"];
	
		//In funzione del tipo di richiesta creo la query che restituisce la struttura delle info
		$sqlQt = '';
		if($_REQUEST["mode"]=="select"){
			if($_REQUEST["item"]=="layers_all")//tutti i modelli di ricerca abilitati per i layer presenti nel mapset
				$sqlQt=" in (select qt_id from $dbschema.mapset_qt where mapset_name='".$_REQUEST["mapset"]."')";
			elseif($_REQUEST["item"]=="layers_on"){//tutti i modelli di ricerca abilitati per i layer attivi
				$layersOn = implode(',',$_SESSION[$myMap]["GROUPS_ON"]);
				$sqlQt=" in (select qt_id from $dbschema.mapset_qt inner join $dbschema.qt using(qt_id) inner join $dbschema.layer using(layer_id) inner join $dbschema.layergroup using(layergroup_id)  where layergroup_id in($layersOn) and  mapset_name='".$_REQUEST["mapset"]."')";
			}
			else//tutti i modelli di ricerca per il gruppo di selezione
				$sqlQt=" in (select qt_id from $dbschema.selgroup inner join $dbschema.qt_selgroup using(selgroup_id) where selgroup_id=".$_REQUEST["item"].")";
			
		}elseif($_REQUEST["mode"]=="search" || $_REQUEST["mode"]=="table"){//restituisco le info per questo modello di ricerca  oppure restituisco le info per la tabella secondaria
			$sqlQt .= " = " . $_REQUEST["item"];				
		}else{
			echo("Modalit� di selezione/ricerca non prevista");
			return;
		}
		
		//costruzione oggetto querytemplate
		$sqlField="select qtfield.*,qtrelation.qtrelation_name,qtrelation_id,qtrelationtype_id,data_field_1,data_field_2,data_field_3,table_field_1,table_field_2,table_field_3,table_name,catalog_path,catalog_url from $dbschema.qtfield left join $dbschema.qtrelation using (qtrelation_id) left join $dbschema.catalog using (catalog_id) where resultype_id in (".$this->resultype.",".RESULT_TYPE_ALL.",".RESULT_TYPE_NONE.") and qtfield.qt_id $sqlQt order by qtfield_order;";
		
		print_debug($sqlField,null,'template');
		$db->sql_query ($sqlField);
		$qRelation = array();
		$qField = array();
		while($row=$db->sql_fetchrow()){
			$qtId=$row["qt_id"];
			$qtfieldId=$row["qtfield_id"];
			$qField[$qtId][$qtfieldId]["field_name"]=trim($row["qtfield_name"]);
			$qField[$qtId][$qtfieldId]["field_alias"]=trim($row["field_header"]);
			$qField[$qtId][$qtfieldId]["field_type"]=$row["fieldtype_id"];
			$qField[$qtId][$qtfieldId]["data_type"]=$row["datatype_id"];			
			$qField[$qtId][$qtfieldId]["order_by"]=$row["orderby_id"];
			$qField[$qtId][$qtfieldId]["field_format"]=$row["field_format"];
			$qField[$qtId][$qtfieldId]["search_type"]=trim($row["searchtype_id"]);
			$qField[$qtId][$qtfieldId]["result_type"]=trim($row["resultype_id"]);
			$qField[$qtId][$qtfieldId]["field_filter"]=trim($row["field_filter"]);
			$qField[$qtId][$qtfieldId]["search_function"]=(isset($row["search_function"]))?trim($row["search_function"]):'';
			$qField[$qtId][$qtfieldId]["relation"]=$row["qtrelation_id"];
			$qField[$qtId][$qtfieldId]["column_width"]=$row["column_width"];
			$f=array();
			if($qtrelationId=$row["qtrelation_id"]){
				if(($row["data_field_1"])&&($row["table_field_1"])) $f[]=array(trim($row["data_field_1"]),trim($row["table_field_1"]));
				if(($row["data_field_2"])&&($row["table_field_2"])) $f[]=array(trim($row["data_field_2"]),trim($row["table_field_2"]));
				if(($row["data_field_3"])&&($row["table_field_3"])) $f[]=array(trim($row["data_field_3"]),trim($row["table_field_3"]));
				$qRelation[$qtId][$qtrelationId]["join_field"]=$f;
				$qRelation[$qtId][$qtrelationId]["name"]=trim($row["qtrelation_name"]);
				$qRelation[$qtId][$qtrelationId]["table_name"]=trim($row["table_name"]);
				$qRelation[$qtId][$qtrelationId]["path"]=trim($row["catalog_path"]);
				$qRelation[$qtId][$qtrelationId]["catalog_url"]=trim($row["catalog_url"]);
				$qRelation[$qtId][$qtrelationId]["relation_type"]=$row["qtrelationtype_id"];				
			}
		}
		
		//Assegno alle relazioni i valori  di schema e connessione
		foreach($qRelation as $qt=>$aRel){
			foreach($aRel as $qtrel=>$row){
				$aConnInfo = connInfofromPath($row["path"]);
				$qRelation[$qt][$qtrel]["table_connection"] = $aConnInfo[0];
				$qRelation[$qt][$qtrel]["table_schema"] = $aConnInfo[1];
			}
		}

		//Aggiungo eventuali hyperlink relativi ai query_template	
		$sqlLink="select qt_link.qt_id,link.link_id,link_def,link.link_name,winw,winh,link_order from $dbschema.link inner join $dbschema.mapset_link using (link_id) inner join $dbschema.qt_link using (link_id) where mapset_name = '". $_REQUEST["mapset"]."' and resultype_id in (".$this->resultype.",3) and qt_link.qt_id $sqlQt order by link_order;";
		$db->sql_query ($sqlLink);		
		while($row=$db->sql_fetchrow()){
			$qtId=intval($row["qt_id"]);
			$linkId=intval($row["link_id"]);
			$link=$row["link_def"];
			$linkTitle=$row["link_name_alt"]?$row["link_name_alt"]:$row["link_name"];
			$qLink[$qtId][$linkId]=array($link,$linkTitle,intval($row["winw"]),intval($row["winh"]));
		}		
		print_debug($sqlLink,null,'template');

		//query template *******************
		$sqlTemplate="select layer.layer_id,layer_name,layergroup_id,mapset_filter,qt_id,base_url,catalog_path,catalog_url,connection_type,data,data_geom,data_filter,data_unique,data_srid,template,tolerance,qt_name,max_rows,selection_color,zoom_buffer,edit_url,groupobject,layertype_ms,static,papersize_id,qt_filter from $dbschema.qt inner join $dbschema.layer using (layer_id) inner join $dbschema.e_layertype using (layertype_id) inner join $dbschema.catalog using (catalog_id) inner join $dbschema.project using (project_name) where qt.qt_id $sqlQt order by qt_order;";
		print_debug($sqlTemplate,null,'template');
		
		$db->sql_query ($sqlTemplate);
		//Tutti i query template dei modelli di ricerca interessati
		$allTemplates = array();
		while($row=$db->sql_fetchrow()){
			$qtId=$row["qt_id"];
			$allTemplates[$qtId]=$row;
			$allTemplates[$qtId]["field"]= (isset($qField[$qtId]))?$qField[$qtId]:null;
			$allTemplates[$qtId]["relation"]= (isset($qRelation[$qtId]))?$qRelation[$qtId]:null;
			$allTemplates[$qtId]["link"]=(isset($qLink[$qtId]))?array_values($qLink[$qtId]):array();
		}		
				
		//Memorizzo il valore per verificare se devo comunque ripulire la mappa dalla selezione corrente.
		$this->mapToUpdate = isset($_SESSION[$myMap]["RESULT"])?1:0;
		$this->zoomToResult = 0;
		//Svuoto la sessione con i risultati della query precedente
		unset($_SESSION[$myMap]["RESULT"]);
		
		//ritorno le informazioni per ogni querytemplate
		foreach ($allTemplates as $aTemplate)
			$this->_getInfoByTemplate($aTemplate);
		
		//Se resultAction non prevede l'aggiornameto della mappa devo verificare l'esistenza del poligono di selezione, nel caso lo tolgo e aggiorno la mappa
		if(isset($_SESSION[$myMap]["SELECTION_POLYGON"]) && $_REQUEST["resultAction"]==0){
			unset($_SESSION[$myMap]["SELECTION_POLYGON"]);
			$this->mapToUpdate=1;
		}
	}


	function _setQueryGeom($buffer=false){
	
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		extract($_REQUEST);
		$sPoly=false;
		switch ($spatialQuery) {

			case QUERY_POINT :
				if(!$buffer) $buffer = DEFAULT_TOLERANCE;
				$imgR = $buffer/(2*$geoPix);
				$sPoly = $this->dbAdpater->img2Circle($imgX,$imgY,$imgR,
                                $_REQUEST["geoPix"],$_REQUEST["oXgeo"],$_REQUEST["oYgeo"], $_SESSION[$myMap]["SRID"]);//raggio in relazione alle coordinate immagine
				break;
				
			case QUERY_CIRCLE :
				$sPoly = $this->dbAdpater->img2Circle($imgX,$imgY,$imgR,
                                $_REQUEST["geoPix"],$_REQUEST["oXgeo"],$_REQUEST["oYgeo"], $_SESSION[$myMap]["SRID"]);//raggio in relazione alle coordinate immagine
				break;		

			case QUERY_POLYGON :
				$sPoly = $this->dbAdpater->img2Polygon($imgX,$imgY,
                                $_REQUEST["geoPix"],$_REQUEST["oXgeo"],$_REQUEST["oYgeo"], $_SESSION[$myMap]["SRID"]);
				break;
				
			case QUERY_WINDOW ://finestra
				list($xMin,$yMin,$xMax,$yMax)=$_SESSION[$myMap]["MAP_EXTENT"];
                $sPoly = $this->dbAdapter->img2Extent($xMin,$yMin,$xMax,$yMax, $_SESSION[$myMap]["SRID"]);
				break;
	
			case QUERY_EXTENT ://Estensione della mappa
				list($xMin,$yMin,$xMax,$yMax)=$_SESSION[$myMap]["MAPSET_EXTENT"];
                $sPoly = $this->dbAdapter->img2Extent($xMin,$yMin,$xMax,$yMax, $_SESSION[$myMap]["SRID"]);
				break;	

			case QUERY_CURRENT ://selezione corrente
				$sPoly = $_SESSION[$myMap]["SELECTION_POLYGON"];
				break;
				
			case QUERY_RESULT ://risultato precedente
				$sPoly = $this->_idList2queryGeom($bufferSelected);//dall'idlist in sessione al poligono + eventuale buffer
				break;	
		}
		
		if($sPoly){
			if($resultAction > 0) $_SESSION[$myMap]["SELECTION_POLYGON"] = $this->dbAdapter->getWkt();//Metto in sessione il poligono di selezione
			$this->queryGeom = $sPoly;
		}
	}
			
	//Per ogni querytemplate ritorna un array di risultati
	function _getInfoByTemplate($aTemplate){
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$templateId = $aTemplate["qt_id"];
		$layerId=$aTemplate["layer_id"];
		$layerName=$aTemplate["layer_name"];
		$layergroupId=$aTemplate["layergroup_id"];
		$templateTitle=$aTemplate["qt_name"];
		$datalayerTable=$aTemplate["data"];	
		$datalayerGeom=$aTemplate["data_geom"];			
		$datalayerKey=$aTemplate["data_unique"];	
		$datalayerSRID=($aTemplate["data_srid"])?$aTemplate["data_srid"]:-1;			
		$dataOffset = 5; //Campi della query da saltare
		$this->resultHeaders = array();

        if ($aTemplate['connection_type'] == CONNECTION_TYPE_ORACLE_SPATIAL) {
            require_once 'oraAdapter.php';
            $this->dbAdapter = new OracleAdapter($aTemplate["catalog_path"]);
        } else if ($aTemplate['connection_type'] == CONNECTION_TYPE_POSTGIS) {
            require_once 'pgAdapter.php';
            $this->dbAdapter = new pgAdapter($aTemplate["catalog_path"]);
        } else {
            $aConnInfo = connInfofromPath($aTemplate["catalog_path"]);
            $connString = $aConnInfo[0];
            $datalayerSchema = $aConnInfo[1];
        }
		print_debug($aTemplate,null,'template');

		//nel caso di query point devo settare il valore della query per ogni qt che contiene la definizione del buffer
		if($_REQUEST["spatialQuery"] != QUERY_POINT){
            $this->_setQueryGeom();
        }

		//In caso di query puntuale devo chiamare la setQueryGeom per ogni qt in modo da usare il corrispondente valore di tolerance
		if($_REQUEST["spatialQuery"] == QUERY_POINT) {
			$this->_setQueryGeom(isset($aTemplate["tolerance"])?$aTemplate["tolerance"]:DEFAULT_TOLERANCE);
        }
		//Se ho un filtro sul mapset lo aggiungo a quello del layer
		//if (templateId==6) 
		//$aTemplate["qt_filter"]="matrice in('h2o_chimico','sedimenti')";
		$datalayerFilter = null;
		if($aTemplate["mapset_filter"]==1)
			$datalayerFilter=$_SESSION[$myMap]["FILTER"];
		if($aTemplate["qt_filter"]){
			if($datalayerFilter) 
				$datalayerFilter.=" AND " . $aTemplate["qt_filter"];
			else
				$datalayerFilter = $aTemplate["qt_filter"];
		}
		if($aTemplate["data_filter"]){
			if($datalayerFilter) 
				$datalayerFilter.=" AND " . $aTemplate["data_filter"];
			else
				$datalayerFilter = $aTemplate["data_filter"];
		}	

		//Definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
		if(preg_match("|select (.+) from (.+)|i",$datalayerTable))
			$datalayerTable="($datalayerTable) ".DATALAYER_ALIAS_TABLE;//DEVE essere allineato con quello in vista seldb_qtrelation
		else
			$datalayerTable=$this->dbAdapter->getSchema().".".$datalayerTable . " ".DATALAYER_ALIAS_TABLE;//DEVE essere allineato con quello in vista seldb_qtrelation

	    $resultHeaders = array();
	    $HresultHeaders = array();
		$fieldList = array();
		$HfieldList = array();
		$orderList = array();
		$fieldString = '';
		$orderbyString = '';
		//Elenco dei campi
		if($aTemplate["field"]){

			$HfieldList=array();
			$fieldList=array();
			$orderList=array();
			$whereFieldList=array();

			foreach($aTemplate["field"] as $idField=>$aField){
				$header = array();
				//Alias per la tabella
				if($idRelation = $aField["relation"]){//Il campo appartiene alla relazione e non alla tabella del layer 
					$aliasTable = "\"". $aTemplate["relation"][$idRelation]["name"]."\"";//uso come alias il nome della relazione che � unico
					$docUrl = $aTemplate["relation"][$idRelation]["catalog_url"];
				}else{
					$aliasTable = DATALAYER_ALIAS_TABLE;
					$docUrl = $aTemplate["catalog_url"];
				}
				
				//Campi calcolati non metto tabella.campo
				//if(strpos($aField["field_name"],'(')!==false)
				if(preg_match('|[(](.+)[)]|i',$aField["field_name"]))
					$fieldName = $aField["field_name"];
				else
					$fieldName = $aliasTable . "." . $aField["field_name"];
					
				$fieldString = $fieldName .  " as \"" . $aField["field_alias"]. "\"";	

				//elenco dei campi della tabella da restituire (escludo i campi funzione di aggregazione)
				if($aField["field_type"] < 100){
					/*condizioni:
					1 - il campo � previsto come campo risultato($aField["result_type"]!=RESULT_TYPE_NONE)
					2 - non sto restituendo la tabella secondaria e il campo non appartiene alla secondaria oppure sto restituendo la tabella secondaria e il campo appartiene alla secondaria
					*/
					if(($aField["result_type"]!=RESULT_TYPE_NONE) && (($_REQUEST["mode"]!='table' && $aTemplate["relation"][$idRelation]["relation_type"]!=ONE_TO_MANY_RELATION)||(($_REQUEST["mode"]=='table') && ($idRelation==$_REQUEST["relation"]) && ($aTemplate["relation"][$idRelation]["relation_type"]==ONE_TO_MANY_RELATION )))){
						$header["TITLE"]=$aField["field_alias"];
						$header["TYPE"]=$aField["field_type"];
						$header["FORMAT"]=$aField["field_format"];
						$header["WIDTH"]=$aField["column_width"];
						$header["FIELD"]=$fieldName;
						$header["URL"]=$docUrl;

						if($aField["field_type"] == HEADER_GROUP_TYPE){
							$HfieldList[] = $fieldString;
							$HresultHeaders[]=$header;
						}
						else{
							$fieldList[] = $fieldString;
							$resultHeaders[]=$header;
						}

						if($aField["order_by"]==ORDER_FIELD_DESC) 
							$orderList[]="\"".$aField["field_alias"]."\" desc";
						else
							$orderList[]="\"".$aField["field_alias"]."\"";
					}
					
					//Aggiungo le clausole where 
					if(isset($_REQUEST["qf"][$idField]) && $_REQUEST["qf"][$idField] != ''){
						$searchString = getSearchString($aField["search_type"],$fieldName,$_REQUEST["qf"][$idField],$_REQUEST["op_qf"][$idField],$aField["search_function"]);
						$whereFieldList[] = $searchString;	
					}
				}
				
				//campi con funzioni di aggregazione (tabella a parte solo in caso di risultato su tabella)
				if(($_REQUEST["mode"]=="table" || $this->resultype==RESULT_TYPE_TABLE) && $aField["field_type"] > 100 ){
					$header["TITLE"]=$aField["field_alias"];
					$header["TYPE"]=$aField["field_type"];
					$header["FORMAT"]=$aField["field_format"];
					$header["WIDTH"]=$aField["column_width"];
					$header["FIELD"]=$fieldName;
					$resultHeaders[]=$header;
					
					/*TODO Posso aggiungere le clausole having per le funzioni di agregazione 
					if($_REQUEST["qf"][$idField] != ''){
						todo
					}
					*/
					
				}
			}
		}


		//Elenco delle relazioni
		$joinString = $datalayerTable;
		if($aRelation=$aTemplate["relation"]){
			foreach($aRelation as $idrel => $rel){
				$relationAliasTable = "\"".$rel["name"]."\"";//Uso il nome della relazione come alias
				$joinList=array();
				for($i=0;$i<count($rel["join_field"]);$i++){
					$joinList[]=DATALAYER_ALIAS_TABLE.".".$rel["join_field"][$i][0]."=".$relationAliasTable.".".$rel["join_field"][$i][1];
					$flagField = $relationAliasTable.".".$rel["join_field"][$i][1]." " .$relationAliasTable;   //tengo un campo della tabella in relazione per sapere in caso di secondarie se il dato � presente
				}
				$joinFields=implode(" AND ",$joinList);
				$joinString = "$joinString left join ".$rel["table_schema"].".". $rel["table_name"] ." as ". $relationAliasTable ." on (".$joinFields.")";	
				//Se non sto visualizzando la secondaria e la relazione � 1 a molti genero il campo che dar� origine al link alla tabella
				if(($this->resultype==RESULT_TYPE_SINGLE) && ($_REQUEST["mode"]!='table') && ($rel["relation_type"] == ONE_TO_MANY_RELATION)){
					//aggiungo un campo che ha come nome il nome della relazione, come formato l'id della relazione  e valore il valore di un campo di join -> se la tabella secondaria non ha corrispondenze il valore � vuoto
					$fieldList[] = $flagField;
					//$groupbyList[] = $relationAliasTable;
					$header["TITLE"]=$rel["name"];
					$header["TYPE"]=SECONDARY_FIELD_LINK;
					$header["FORMAT"]=$idrel;//Id della relazione
					$header["WIDTH"]='';
					$header["URL"]='';
					$resultHeaders[]=$header;
				}
			}
		}
		

		$this->resultHeaders = $HresultHeaders?array_merge($HresultHeaders,$resultHeaders):$resultHeaders;
		$fieldList = $HfieldList?array_merge($HfieldList,$fieldList):$fieldList;
		if($fieldList)	$fieldString = implode(",",$fieldList);
		if($orderList)	$orderbyString = ' ORDER BY '.implode(",",$orderList);
		//################### WHERE #################
		if (isset($_REQUEST["queryOp"])) {
			$boolOp=$_REQUEST["queryOp"];
			if (count($whereFieldList)>0) $whereList[] = "(" . implode(" $boolOp ",$whereFieldList) . ")";//Filtro sui campi
		}
		if($_REQUEST["mode"]=='table')//se richiedo la tabella secondaria devo filtrare su objid
			$whereList[] = DATALAYER_ALIAS_TABLE.".".$datalayerKey." in (".$_REQUEST["objid"].")";	
		elseif ($datalayerFilter)
			$whereList[] = "(".DATALAYER_ALIAS_TABLE.".".$datalayerKey." in (select $datalayerKey from $datalayerTable where (" . $datalayerFilter . ")))";//SE C'E UN FILTRO APPLICATO AL LIVELLO LO APPLICO		
		//Connessione al db della tabella relativa al livello
        try {
        } catch(Exception $e){
			print ($templateTitle.":<br>Connessione al db fallita<br>".$e->getMessage());
			return;
		}

		//SE C'E UNA QUERY CARTOGRAFICA LA APPLICO
		$srid = $_SESSION[$myMap]["SRID"];
		if($srid>0 && $srid!=$datalayerSRID){
			$layerSRS = $_SESSION[$myMap]["SRS"][$datalayerSRID];
			$mapsetSRS = $_SESSION[$myMap]["SRS"][$srid];
			$geomColumn="transform_geometry(".DATALAYER_ALIAS_TABLE.".".$datalayerGeom.",'$layerSRS','$mapsetSRS',$srid)";
		}
		else
			$geomColumn=DATALAYER_ALIAS_TABLE.".".$datalayerGeom;	

		if(($_REQUEST["mode"]!='table') && ($this->queryGeom)){
			$mslayerType=$aTemplate["layertype_ms"];		
			$op=($_REQUEST["selectMode"]==1)?'contains':'intersects';
			$sqlGeom=$this->dbAdapter->$op($geomColumn, $this->queryGeom);
			$whereList[] = $sqlGeom;
		}
		
		//Se sono in modalit� chiamata esterna passo solo l'objectid
		if(isset($_REQUEST["zoomobj"]) && $_REQUEST["zoomobj"])
			$whereList[] = "$datalayerKey in (".$_REQUEST["zoomobj"].")";

		if(count($whereList)>0)	$whereString = " WHERE ".implode(" AND ",$whereList);
		
		//buffer per gli oggetti risultato(scarto il valore 0)
		$zoomBuffer = $aTemplate["zoom_buffer"]?$aTemplate["zoom_buffer"]:DEFAULT_ZOOM_BUFFER;
		if($fieldString) $fieldString = ','.$fieldString;
        $maxrows = null;
		//********************* DEFINIZIONE DELLA QUERY ******************************
		if($_REQUEST["mode"]=='table'){
			$queryString="select distinct ".DATALAYER_ALIAS_TABLE.".$datalayerKey as objid $fieldString from $joinString $whereString $orderbyString ";		
		}
		else{
			$queryString="SELECT DISTINCT ".DATALAYER_ALIAS_TABLE.".$datalayerKey as objid,".
                         "  CAST(".$this->dbAdapter->xmin('the_geom')." AS NUMERIC(12,2)) - $zoomBuffer as minx,\n".
                         "  CAST(".$this->dbAdapter->ymin('the_geom')." AS NUMERIC(12,2)) - $zoomBuffer as miny,\n".
                         "  CAST(".$this->dbAdapter->xmax('the_geom')." AS NUMERIC(12,2)) - $zoomBuffer as maxx,\n".
                         "  CAST(".$this->dbAdapter->ymax('the_geom')." AS NUMERIC(12,2)) - $zoomBuffer as maxy".
                         "  $fieldString\n".
                         "FROM $joinString\n $whereString\n $orderbyString ";
            $countString="SELECT COUNT(distinct ".DATALAYER_ALIAS_TABLE.".$datalayerKey) FROM $joinString $whereString";
			print_debug($countString,null,'template_query');
			$result = $this->dbAdapter->getDb()->query($countString);
			$totalrows = $result->fetchColumn(0);
			if(isset($aTemplate["max_rows"]) && !(isset($_REQUEST["allpage"]) && $_REQUEST["allpage"])){
				$maxrows=intval($aTemplate["max_rows"]);
				$pageIndex=(isset($_REQUEST["pageIndex"]) && $_REQUEST["pageIndex"])?intval($_REQUEST["pageIndex"]):1;
				
				if(isset($_REQUEST["totalRows"]) && $_REQUEST["totalRows"]){//Paging
					$totalrows=intval($_REQUEST["totalRows"]);
                    $queryString = $this->dbAdapter->limit($queryString, $maxrows, ($pageIndex-1)*$maxrows);
				}
				else{//Ho fissato un max numero di risultati sul query template: alla prima chiamata conto il totale risultati
                    $queryString = $this->dbAdapter->limit($queryString, $maxrows);
				}
                $numpages=ceil($totalrows/$maxrows);
			} else {
                $numpages = 1;
                $pageIndex = null;
            }
		}
		
		print_debug($queryString,null,'template_query');
		$result = $this->dbAdapter->getDb()->query($queryString);
		
		
		
		if($pageIndex>1){
			//$numpages=ceil(intval($totalrows/$maxrows));
			$maxrows=min(pg_num_rows($result),$maxrows);
		}
		
		if(!isset($totalrows)){
			$totalrows=pg_num_rows($result);
			$numpages=1;
		}
		
		

		//Propriet� dell'oggetto risposta
		if($result){
			//creo un array strutturato di risultati passando le righe di risultato e intestazione dei campi
			print_debug($result,null,'template_result');
			$dataResult["title"] = $templateTitle;
			$dataResult["template"] = $aTemplate["template"];
			$dataResult["qtid"] = intval($templateId);
			$dataResult["grpid"] = intval($layergroupId);
			$dataResult["layer"] = $aTemplate["layer_name"];
			$dataResult["staticlayer"] = intval($aTemplate["static"]);
			$dataResult["key"] = $aTemplate["data_unique"];
			$dataResult["papersize"] = intval($aTemplate["papersize_id"]);	
			$dataResult["numrows"] = $totalrows;
			$dataResult["maxrows"] = $maxrows;
			$dataResult["numpages"] = $numpages;
			$dataResult["pageindex"] = $pageIndex;
			if($_REQUEST["mode"]=='table') $dataResult["istable"]=1;
			$color = $aTemplate["selection_color"]?$aTemplate["selection_color"]:OBJ_COLOR_SELECTION;							
			$dataResult["color"] = str_replace(" ",",",$color);
			
			//Aggiungo i risultati strutturati (li aggiungo con array merge per poter avere gli attributi dell'oggetto risposta in ordine al fine di facilitare il debug)
			$dataResult = array_merge ($dataResult,$this->_getArrayData($result,$joinString,$datalayerKey,$aTemplate["base_url"],$aTemplate["groupobject"]));
			$dataResult["editurl"] = $aTemplate["edit_url"];	
			$dataResult["link"] = $aTemplate["link"];
			$resultId = $dataResult["resultid"];
			unset($dataResult["resultid"]);//Inutile passare alla stringa jason la lista degli id
			$this->allQueryResults[]=$dataResult;
			
			//Se ho attiva l'opzione seleziona e centra oggetto metto in sessione i valori;
			//NOTA: Ogni querytemplate definisce in sessione un oggetto RESULT. Se parto da un gruppo di selezione ottendo pi� oggetti result.
			//Quando clicco su "Zoom" ricostruisco l'oggetto con 1 risultato, quando clicco su "Zoom totale oggetti" ricostruisco l'oggetto con il set di risultati
			//Costruisco in sessione il set di risultati solo se li devo evidenziare ecc.. oppure se il layer � dinamico
			
			print_debug($dataResult,null,'dataresult');
			if($_REQUEST["mode"]!='table'){
				if($dataResult["numrows"]>0 && ($aTemplate["static"]==0 || $_REQUEST["resultAction"]>0)){
					$this->mapToUpdate=1;
					$this->zoomToResult=1;
					//if($_REQUEST["mode"]=='search') $this->polygonSelected=true;
					$_SESSION[$myMap]["RESULT"][$templateId]["LAYERGROUP"] = $dataResult["grpid"];
					$_SESSION[$myMap]["RESULT"][$templateId]["LAYER"] = $dataResult["layer"];
					$_SESSION[$myMap]["RESULT"][$templateId]["STATIC"] = $aTemplate["static"];				
					$_SESSION[$myMap]["RESULT"][$templateId]["COLOR"] = preg_split('/[\s,]+/',$color);
					$_SESSION[$myMap]["RESULT"][$templateId]["ID_FIELD"] = $aTemplate["data_unique"];
					$_SESSION[$myMap]["RESULT"][$templateId]["ID_LIST"] = $resultId;
					if($aTemplate["static"]==0){//accendo il livello se spento
						if(!in_array($layergroupId,$_SESSION[$myMap]["GROUPS_ON"])) $_SESSION[$myMap]["GROUPS_ON"][]=$layergroupId;
					}
					
					if($_REQUEST["resultAction"]>1){
						//Prendo l'estesione completa per tutti  gli oggetti selezionati in tutti i qt
						list($xMin,$yMin,$xMax,$yMax) = $dataResult["resultextent"];;
						list($selxMin,$selyMin,$selyMin,$selyMin) = array_pad($this->allQueryExtent, 4, null);
						$selxMin = isset($selxMin)?min($selxMin, $xMin):$xMin;
						$selyMin = isset($selyMin)?min($selyMin, $yMin):$yMin;
						$selxMax = isset($selxMax)?max($selxMax, $xMax):$xMax;
						$selyMax = isset($selyMax)?max($selyMax, $yMax):$yMax;
						$this->allQueryExtent = array($selxMin,$selyMin,$selxMax,$selyMax);
					}
				}
			}
		}
		else{
			header("Content-Type: application/json; Charset=ISO-8859-15");
			// print ("<b>$templateTitle</b>:<br>$queryString<br>".$err["message"]);
			print ("<b>$templateTitle</b>:<br>$queryString<br>");
		}
	}
	// END _getInfoByTemplate
	
	//Crea l'array con i risultati STRUTTURATI
	//da visualizzare in tabella o in info
	//L'array viene creato in modo che possa essere convertito subito in una stringa JSON da PHP
	
	function _getArrayData($result,$joinString,$keyField,$projectURL,$groupobject){
		
		$arrayData=array();	
		$aggList=array();			
		$idList=array();
		$printGroupHeaders = false;
		$numRows = 0;
		print_debug($this->resultHeaders,null,'result_headers');
		
		foreach($this->resultHeaders as $header){//Array con la definizione dei campi
			//if(!($this->resultype==RESULT_TYPE_TABLE && $header["TYPE"]==HEADER_GROUP_TYPE)){
			if($header["TYPE"]<100){
				$arrayData["tableheaders"][] = $header["TITLE"];//Tabella dei titolo
				$arrayData["columnwidth"][] = $header["WIDTH"]?$header["WIDTH"]:'';//Tabella delle larghezze
				$arrayData["fieldtype"][] = intval($header["TYPE"]);//Tabella dei tipi
			}
			else{
				$aggList[] = $this->aggregateFunction[$header["TYPE"]]."(case when ".$header["FIELD"]." ~ '^[0-9.]+$' then ".$header["FIELD"]."::float else 0 end) as \"". $header["TITLE"]. "\"";	;

			}
			//}
		}
		
		$resultData = $result->fetchAll(PDO::FETCH_ASSOC);
		print_debug($resultData,null,'template_result');
		$j=0;
        $numRows = count($resultData);
		if($numRows==0) return $arrayData;
        
		for($i=0;$i<$numRows;$i++){
			$resultRow = $resultData[$i];
			$myArray="\$arrayData";	
			$Row=array();
			
			$objId = intval($resultRow["objid"]);
			//costruisco un array con tutti gli id per le selezioni
			if(!in_array($objId,$idList)) $idList[] = $objId; 
			
		
			//calcolo l'estensione del set di risultati
			$extent=array(floatval($resultRow["minx"]),floatval($resultRow["miny"]),floatval($resultRow["maxx"]),floatval($resultRow["maxy"]));
			$selxMin = (isset($selxMin) && $selxMin)?min($selxMin, $resultRow["minx"]):$resultRow["minx"];
			$selyMin = (isset($selyMin) && $selyMin)?min($selyMin, $resultRow["miny"]):$resultRow["miny"];
			$selxMax = (isset($selxMax) && $selxMax)?max($selxMax, $resultRow["maxx"]):$resultRow["maxx"];
			$selyMax = (isset($selyMax) && $selyMax)?max($selyMax, $resultRow["maxy"]):$resultRow["maxy"];
			$allextent = array(floatval($selxMin),floatval($selyMin),floatval($selxMax),floatval($selyMax));

			$k=0;
			
			//Ciclo sui campi per generare la riga
			foreach($this->resultHeaders as $header){
				$fldName = $header["TITLE"];
				$fldFormat = $header["FORMAT"];
				$fldValue = $resultData[$i][strtolower($fldName)].'';
				$fldString = $fldFormat?sprintf("$fldFormat","$fldValue"):$fldValue;
				
				//raggruppamenti di campo im modalit� tabella
				if(($_REQUEST["mode"]=="table" || $this->resultype==RESULT_TYPE_TABLE) && $header["TYPE"]==HEADER_GROUP_TYPE){	
					if(strlen($fldString)==0) $fldString = AGGREGATE_NULL_VALUE;
					//eval($myArray."[\"DATA\"][\"$fldString\"][\"HEADER\"]='$fldName';");
					//eval($myArray."[\"DATA\"][\"$fldString\"][\"COUNT\"]++;");
					$myArray .= "[\"group\"][\"$fldString\"]";
					$printGroupHeaders = true;
					print_debug($myArray,null,'myArray');
					//$k++;
				}	
				
				
				elseif($header["TYPE"]==IMAGE_FIELD_TYPE || $header["TYPE"]==LINK_FIELD_TYPE){
					$Row[$k]=$this->_setLink($fldString,$header["URL"],$projectURL);	
					$k++;
				}
				
				//Link a tebella secondaria
				elseif($header["TYPE"]==SECONDARY_FIELD_LINK){
					//$Row[$k]=$header["FORMAT"].",".$objId;
					//$Row[]="{relation:".$header["FORMAT"].",objid:".$objId."}";
					$Row[$k]=intval($header["FORMAT"]);//Id della relazione
					$k++;
				}

				//Campo normale
				elseif($header["TYPE"] < 100){
					$Row[$k]=$fldString;	
					$k++;
				}
				
			}
			
			
		/*  						RAGGRUPPAMENTO DI OGGETTI  			
		 SE VIENE IMPOSTATO IL RAGGRUPPAMENTO DI OGGETTI IL SISTEMA CREA UNA LISTA DI RISULTATI NON DISTINTI
		SULLE LORO PROPRIETA' GEOMETRICHE SOSTITUENDO L'ID DELL'OGGETTO CON UN ARRAY DI ID DEGLI OGGETTI RAGGRUPPATI
		E SOSTITUENDO L'EXTENT CON IL BOX CHE CONTIENE TUTTI GLI OGGETTI. IN QUESTO MODO OTTENGO UN OGGETTO VIRTUALE 
		COSTITUITO DA DIVERSI RECORD 																	*/

			if($groupobject){
				eval("\$key = array_search(\$Row,".$myArray."[\"data\"]);");			
				if($key===false){	
					eval($myArray."[\"objid\"][] = array(\$objId);");
					eval($myArray."[\"data\"][]=\$Row;");
					eval($myArray."[\"extent\"][] = \$extent;");
					$j++;
				}else{
					eval("\$presente=in_array(\$objId,".$myArray."[\"objid\"][$key]);");
					//echo($myArray."[\"objid\"][$key][] = \$objId;");
					if(!$presente) eval($myArray."[\"objid\"][$key][] = \$objId;");//Aggiungo l'id all'array di objid e ricalcolo l'estensione per l'aggregazione di oggetti
					eval("list(\$objxMin,\$objyMin,\$objxMax,\$objyMax) = ".$myArray."[\"extent\"][$key];");
					$objxMin = $objxMin?min($objxMin, $resultRow["minx"]):$resultRow["minx"];
					$objyMin = $objyMin?min($objyMin, $resultRow["miny"]):$resultRow["miny"];
					$objxMax = $objxMax?max($objxMax, $resultRow["maxx"]):$resultRow["maxx"];
					$objyMax = $objyMax?max($objyMax, $resultRow["maxy"]):$resultRow["maxy"];
					$extent = array(floatval($objxMin),floatval($objyMin),floatval($objxMax),floatval($objyMax));
					eval($myArray."[\"extent\"][$key] = \$extent;");
				}
			}
			else{
				eval($myArray."[\"objid\"][] = array(\$objId);");
				eval($myArray."[\"data\"][]=\$Row;");
				eval($myArray."[\"extent\"][] = \$extent;");
				$j=$i+1;
			}
		}

		$arrayData["resultid"] = $idList;
		$arrayData["resultextent"] = $allextent;
		
		/********************** FUNZIONI DI AGGREGAZIONE *********************************/
		//Tabelle dei valori risultato delle funzioni di aggregazione (solo in modalit� tabella)
		if($printGroupHeaders){
			$aggFieldString = ','.implode(",",$aggList);
			$sIdlist=implode(',',$idList);
			print_debug($aggList,null,'agglist');
			//$level=0;
			foreach($this->resultHeaders as $header){
				//Per ogni intestazione di gruppo eseguo la query che restituisce i valori delle funzioni di aggregazione
				if($header["TYPE"]==HEADER_GROUP_TYPE){

					//$level++;
					$grpFlds[]=$header["FIELD"];
					$sql="select ".implode(',',$grpFlds)."$aggFieldString from $joinString where " . DATALAYER_ALIAS_TABLE . ".$keyField in ($sIdlist) group by " . implode(',',$grpFlds) . " order by " . implode(',',$grpFlds);
					//costruisco la stringa dell'array
					print_debug($sql,null,'aggregate_query');
					$result = pg_query($db,$sql);
					$aggResult = pg_fetch_all($result);	
					print_debug($aggResult,null,'aggregate_query');
					$v = explode('.',$header["FIELD"]);
					
					//$fldFormat = $header["FORMAT"];
					$grpField = $v[1];
					
					if($fldFormat = $header["FORMAT"])
						$tmp.="['group']['\".sprintf(\"$fldFormat\",\$aggResult[\$i]['$grpField']).\"']";
					else
						$tmp.="['group']['\".\$aggResult[\$i]['$grpField'].\"']";
						
					for($i=0;$i<count($aggResult);$i++){
						eval("\$idx=\"$tmp\";");
						//print_debug($tmp,null,'aggregate_query');
						$groupData = array();
						foreach($this->resultHeaders as $headerAgg){//Formatta il dato risultato delle funzioni di aggregazione
							if($headerAgg["TYPE"]>100){
								if($headerAgg["FORMAT"])
									$groupData[]=sprintf($headerAgg["FORMAT"],$aggResult[$i][$headerAgg["TITLE"]]);
								else
									$groupData[] = $aggResult[$i][$headerAgg["TITLE"]];
							}
						}
						print_debug($groupData,null,'aggregate_query');
						print_debug($fff,null,'aggregate_query');
						eval("\$arrayData".$idx."['groupdata']=\$groupData;");
					}
					
				}
				elseif($header["TYPE"]>100){
					$arrayData["groupheaders"][] = $header["TITLE"];//Tabella dei titolo campi con funzioni di aggregazione
					$arrayData["groupcolumnwidth"][] = $header["WIDTH"]?$header["WIDTH"]:'';//Tabella delle larghezze campi con funzioni di aggregazione
					
				}
			}
		}
		print_debug($arrayData,null,'arrayData');
		return $arrayData;
	}	
	
	function _idList2queryGeom($buffer){
		$dbschema = DB_SCHEMA;
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		$result = current($_SESSION[$myMap]["RESULT"]);
		$layerGroupId = $result["LAYERGROUP"];
		$layerName = $result["LAYER"];
		$idList = $result["ID_LIST"];
		$sql = "select data,data_geom,data_unique,layertype_id,data_srid,catalog_path from $dbschema.layer inner join $dbschema.catalog using (catalog_id) where layergroup_id=$layerGroupId and layer_name='$layerName';";

		print_debug($sql,null,'geomunion');
		$this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow();
		
		$aConnInfo = connInfofromPath($row["catalog_path"]);
		$connString = $aConnInfo[0];
		$datalayerSchema = $aConnInfo[1];

		$dbData = pg_connect($connString);
		$dbtable = $datalayerSchema.".".$row["data"];	
		$datalayerGeom = $row["data_geom"];	
		$dataKey = $row["data_unique"];	
		$datalayerSRID = ($row["data_srid"])?$row["data_srid"]:-1;	
		
		$mapSRID = $_SESSION[$myMap]["SRID"];
		$sList = implode(",",$idList);
		if($row["layertype_id"]<3 && $buffer<.1) $buffer=.1;
		
		if($mapSRID>0 && $mapSRID!=$datalayerSRID){
			$layerSRS = $_SESSION[$myMap]["SRS"][$datalayerSRID];
			$mapsetSRS = $_SESSION[$myMap]["SRS"][$mapSRID];
			$datalayerGeom="transform_geometry($datalayerGeom,'$layerSRS','$mapsetSRS',$mapSRID)";
		}
		
		if(count($idList)==1)
			$sql="select astext(buffer($datalayerGeom,$buffer)) as geom from $dbtable where $dataKey = $sList;";
		else		
			$sql="select astext(geomunion(buffer($datalayerGeom,$buffer))) as geom from $dbtable where $dataKey in ($sList);";
		
		$result = pg_query($dbData, $sql);
		$numRows = pg_num_rows($result);
		$geomString = pg_fetch_result($result, 0, 0);
		
		print_debug($sql,null,'geomunion');
		pg_close($dbData);
		return $geomString;
		
	}
	
	//Setta l'array di request per chiamate esterne
	function _setRequest(){
		$dbschema=DB_SCHEMA;
		$sql="select mapset_qt.qt_id,layer.layergroup_id,theme.theme_id from $dbschema.mapset_qt inner join $dbschema.qt using(qt_id) inner join $dbschema.theme using(theme_id) inner join $dbschema.layer using(layer_id) where lower(theme_name)=lower('".$_REQUEST["theme"]."') and lower(qt_name)=lower('".$_REQUEST["qt"]."');";
		print_debug($sql,null,'extcall');
		$this->db->sql_query($sql);
		$qtId = $this->db->sql_fetchfield('qt_id');
		$layergroupId = $this->db->sql_fetchfield('layergroup_id');
		$themeId = $this->db->sql_fetchfield('theme_id');
		$sql="select qtfield_id,field_header from $dbschema.qtfield where qt_id=$qtId";
		$this->db->sql_query($sql);
		$qfdata=array();
		while($row = $this->db->sql_fetchrow()){
			$field = $row["field_header"];
			$idField = $row["qtfield_id"];
			if(isset($_REQUEST[$field]))
				$qfdata[$idField]=$_REQUEST[$field];
		}
		$_REQUEST["item"]=$qtId;
		$_REQUEST["mode"]="search";
		$_REQUEST["optselobj"]=1;
		$_REQUEST["resultAction"]=2;
		$_REQUEST["qfdata"]=$qfdata;		
		print_debug($sql,null,'extcall');
		$myMap = "MAPSET_".$_REQUEST["mapset"];
		if(!in_array($layergroupId,$_SESSION[$myMap]["GROUPS_ON"])) $_SESSION[$myMap]["GROUPS_ON"][] = $layergroupId;
		if(!in_array($themeId,$_SESSION[$myMap]["THEME"])) $_SESSION[$myMap]["THEME"][] = $themeId;
	}
	
	function _composeURL($URL) {
		$prot="(((ht|f)tp(s?))\://)+";
	    $domain = "((([[:alpha:]][-[:alnum:]]*[[:alnum:]])(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+(\.[[:alpha:]][-[:alnum:]]*[[:alpha:]])+)|(([1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2}\.[1-9]{1}[0-9]{0,2})+))";
	    $dir = "(/[[:alnum:]][-[:alnum:]]*[[:alnum:]])*";
	    $page = "(/[[:alnum:]][-[:alnum:]]*\.[[:alpha:]]{3,5})?";
	    $getstring = "(\?([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+)(&([[:alnum:]][-_%[:alnum:]]*=[-_%[:alnum:]]+))*)?";
	    $pattern1 = "^".$domain.$dir.$page.$getstring."$";
		$pattern2 = "^".$prot.$domain.$dir.$page.$getstring."$";
	    if(eregi($pattern1, $URL))
			return "http://".$URL;
		elseif(eregi($pattern2, $URL))
			return $URL;
		else
			return null;
	}
	
	function _setLink($str,$docPath,$projPath){
		
		if(strlen($str)==0) return '';
		if(strpos(trim($str),'javascript:')===0) return $str;
		//print_debug("$str,$docPath,$projPath");
		if($url=$this->_composeURL($str))
			$urlString=$url;
		elseif($url=$this->_composeURL($docPath.$str))
			$urlString=$url;
		elseif($url=$this->_composeURL($projPath.$docPath.$str))
			$urlString=$url;
		else
			$urlString="http://".$projPath.$docPath.$str;
		
		return str_ireplace("http://http://","http://",$urlString);	
		
		/*if (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $str);	
		elseif (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$docPath.$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $docPath.$str);
		elseif (preg_match('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))',$projPath.$docPath.$str))
			$str = preg_replace ('(([:/~a-zA-Z0-9_\-\.]+)\.([:/~a-zA-Z0-9]+))', 'http://$1.$2', $projPath.$docPath.$str);		
		else
			$str = '';
		
		*/
	}
	
	

}//end class


function getSearchString($type,$name,$val,$op,$funct){
	$AND_args = explode(AND_CONST,$val);
	print_debug($AND_args,null,"pgquery");
	foreach($AND_args as $and){
		$and=trim($and);
		if(strpos($and,OR_CONST)>0){
			$OR_args=explode(OR_CONST,$and);
			print_debug($OR_args,null,"pgquery");
			foreach($OR_args as $or){
				$or=trim($or);
				$ORsql[]=setWhereCondition($type,$name,$or,$funct);
			}
			$ANDsql[]=($type==-1)?(implode(" UNION ",$ORsql)):(implode(" OR ",$ORsql));
		}
		else
			$ANDsql[]=setWhereCondition($type,$name,$op,$and,$funct);
	}
	$sql=($type==-1)?($name." in (".implode(" INTERSECT ",$ANDsql).") "):(implode(" AND ",$ANDsql));
	
	return "($sql)";
}


function setWhereCondition($type,$name,$op,$val,$f){
print_debug("Tipo : $type -- Campo: $name -- Valore : $val -- F : $f",null,'pgquery');
$regexp_jolly_end="/^([^*]+)[*]{1}$/";
$regexp_jolly_start="/^[*]{1}([^*]+)$/";
$regexp_jolly_both="/^[*]{1}(.*)[*]{1}$/";
	switch ($type) {
		case -1:
			$cond="(select * from $f('$val'))";
			break;
		case 1:
			if(preg_match($regexp_jolly_both,$val,$out)){				//Cerco la presenza del carattere JOLLY
					$cond="coalesce($name,'') ilike '%".$out[1]."%'";
			}
			elseif(preg_match($regexp_jolly_start,$val,$out)){
				$cond="coalesce($name,'') ilike '%".$out[1];
			}
			elseif(preg_match($regexp_jolly_end,$val,$out)){
				$cond="coalesce($name,'') ilike '".$out[1]."%'";
			}
			
			/*
			elseif($op=='!=' && $val='NULL'){
				$cond="$name is not null";
			}
			elseif($op=='=' && $val='NULL'){
				$cond="$name is null";
			}*/
			
			else
				$cond="$name $op '$val'";
			
			break; 
		case 3:
				$cond="$name $op '$val'";
			break;
		case 2:			
			if(preg_match($regexp_jolly_both,$val,$out)){				//Cerco la presenza del carattere JOLLY
					$cond="' '||coalesce($name,'')||' ' ilike '%".$out[1]."%'";
			}
			elseif(preg_match($regexp_jolly_start,$val,$out)){
				$cond="' '||coalesce($name,'')||' ' ilike '%".$out[1]." %'";
			}
			elseif(preg_match($regexp_jolly_end,$val,$out)){
				$cond="' '||coalesce($name,'')||' ' ilike '% ".$out[1]."%'";
			}
			else
				$cond="' '||$name||' ' ilike '% $val %'";
			break; 	
		case 4: 	//RICERCA NUMERICA	
			if(preg_match('|'.GE_CONST.'(.+)|',$val,$out))
				$cond=is_numeric($out[1])?$name. ">=". $out[1]:'false';
			elseif(preg_match('|'.LE_CONST.'(.+)|',$val,$out))
				$cond=is_numeric($out[1])?$name. "<=". $out[1]:'false';		
			elseif(preg_match('|'.GT_CONST.'(.+)|',$val,$out))
				$cond=is_numeric($out[1])?$name. ">". $out[1]:'false';
			elseif(preg_match('|'.LT_CONST.'(.+)|',$val,$out))
				$cond=is_numeric($out[1])?$name. "<". $out[1]:'false';
			else
				$cond=is_numeric($val)?$name. $op. $val:'false';
			break; 	
		case 5:
			$format = '%d/%m/%yyyy';
			$strf = strptime($val,$format);
			//if (!strtotime($val))
				//$cond="false";
			if(preg_match('|'.GE_CONST.'(.+)|',$val))
				$cond="$name >= '$val'::date";
				//$cond=strtotime($val)?"$name >= '$val'":'false';
			elseif(preg_match('|'.LE_CONST.'(.+)|',$val))
				$cond="$name <= '$val'::date";		
			elseif(preg_match('|'.GT_CONST.'(.+)|',$val))
				$cond="$name > '$val'::date";
			elseif(preg_match('|'.LT_CONST.'(.+)|',$val))
				$cond="$name < '$val'::date";
			else
				$cond="$name $op '$val'";
			break; 	
		case 6:
			if($val=='SI' || $val=='T' || $val=='t' || $val=='1')
				$cond="$name=1";
			elseif($val=='NO' || $val=='F' || $val=='f' || $val=='0')
				$cond="$name=0";
		break;
		
	}
	return $cond;
}
?>