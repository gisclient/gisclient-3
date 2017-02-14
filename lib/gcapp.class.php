<?php

class GCApp {
	static private $db;
	static private $dataDBs = array();

	public static function getDB() {
		if(empty(self::$db)) {
			$dsn = 'pgsql:dbname='.DB_NAME.';host='.DB_HOST;
			if(defined('DB_PORT')) $dsn .= ';port='.DB_PORT;
			try {
				self::$db = new PDO($dsn, DB_USER, DB_PWD);
				self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			} catch(Exception $e) {
				die('GCApp:Impossibile connettersi al database');
			}
		}
		return self::$db;
	}
	
	public static function getDataDB($path) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		return self::$dataDBs[$path]->db;
	}
	
	public static function getDataDBSchema($path) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		return self::$dataDBs[$path]->schema;
	}
	
	public static function getDataDBParams($path, $param = null) {
		if(empty(self::$dataDBs[$path])) {
			self::$dataDBs[$path] = new GCDataDB($path);
		}
		if(!empty($param)) {
			return self::$dataDBs[$path]->$param;
		} else {
			return array(
				'schema'=>self::$dataDBs[$path]->schema,
				'db_name'=>self::$dataDBs[$path]->dbName,
                'db_user'=>self::$dataDBs[$path]->dbUser,
                'db_pass'=>self::$dataDBs[$path]->dbPass,
                'db_host'=>self::$dataDBs[$path]->dbHost,
                'db_port'=>self::$dataDBs[$path]->dbPort
			);
		}
	}
    
    public static function getCatalogPath($catalogName) {
        $db = GCApp::getDB();
        
        $sql = 'select catalog_path from '.DB_SCHEMA.'.catalog where catalog_name=:catalog_name';
        $stmt = $db->prepare($sql);
        $stmt->execute(array('catalog_name'=>$catalogName));
        return $stmt->fetchColumn(0);
    }

	public static function prepareInStatement($values) {

		$i = 0;
		$params = array();
		$inArr = array();
		foreach ($values as $v) {
			$pkey = sprintf(":key%d", $i);
			$inArr[] = $pkey;
			$params[$pkey] = $v;
			$i++;
		}
		return array('inQuery' => implode(',', $inArr), 'parameters' => $params);

	}
	
	public static function getNewPKey($dbschema, $schema, $table, $pkey, $start=null) {

	    $db = GCApp::getDB();
	    try {
		if (is_null($start)) {
		    $sql="select $dbschema.new_pkey(:scm, :tbl, :pkey);";
		    $stmt = $db->prepare($sql);
		    $stmt->execute(array('scm' => $schema, 'tbl' => $table, 'pkey' => $pkey));									
		} else {
		    $sql="select $dbschema.new_pkey(:scm, :tbl, :pkey, :start);";
		    $stmt = $db->prepare($sql);
		    $stmt->execute(array('scm' => $schema, 'tbl' => $table, 'pkey' => $pkey, 'start' => $start));							
		}
	    } catch (Exception $e) {
		GCError::registerException($e);
	    }	
	    print_debug($sql,null,"gcapp.class");
	    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	    return isset($row['new_pkey'])?$row['new_pkey']:null;
	}
	    
	
    public static function getUniqueRandomTmpFilename($dir, $prefix = '', $extension = '') {
        $letters = '1234567890qwertyuiopasdfghjklzxcvbnm';
        $lettersLength = strlen($letters) - 1;
        $filename = !empty($prefix) ? $prefix.'_' : '';
        for ($n = 0; $n < 20; $n++) {
            $filename .= $letters[rand(0, $lettersLength)];
        }
		if(!empty($extension)) $filename .= '.'.$extension;
        if (file_exists($filename))
            return self::getUniqueRandomTmpFilename($dir, $prefix, $extension);
        else
            return $filename;
    }
    
    public static function schemaExists($dataDb, $schema) {
        $sql = 'select schema_name from information_schema.schemata '.
            ' where schema_name = :schema ';
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array('schema'=>$schema));
        return ($stmt->rowCount() > 0);
    }
    
    public static function tableExists($dataDb, $schema, $tableName) {
        $sql = "select table_name from information_schema.tables ".
            " where table_schema=:schema and table_name=:table ";
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$tableName));
        return ($stmt->rowCount() > 0);
    }
    
    public static function columnExists($dataDb, $schema, $tableName, $columnName) {
        $sql = "SELECT column_name from information_schema.columns " .
                "WHERE table_schema=:schema AND table_name=:table " .
                " AND column_name = :column ";
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$tableName, ':column'=>$columnName));
        $result = $stmt->fetchColumn(0);
        return !empty($result);
    }
    
    public static function getColumns($dataDb, $schema, $tableName) {
        $sql = "SELECT column_name from information_schema.columns " .
                "WHERE table_schema=:schema AND table_name=:table " .
                " ORDER BY ordinal_position";
        $stmt = $dataDb->prepare($sql);
        $stmt->execute(array(':schema'=>$schema, ':table'=>$tableName));
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public static function getTablePKey($db, $schema, $tableName) {
        $sql = "select column_name from 
            information_schema.table_constraints c
            inner join information_schema.key_column_usage k on c.constraint_schema = k.constraint_schema and c.constraint_name = k.constraint_name
            where c.table_schema = :schema and c.table_name = :table and c.constraint_type = 'PRIMARY KEY'";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('schema'=>$schema, 'table'=>$tableName));
        return $stmt->fetchColumn(0);
    }
    
    //questa stessa funzione è anche in admin/lib/functions.php
    //TODO: trovare dove viene usata e sostituirla con questa
	public static function nameReplace($name){

		$search = explode(","," ,ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u,.");
		$replace = explode(",","_,c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u,_");
		if(strtoupper(CHAR_SET)=='UTF-8'){
			for($i=0;$i<count($search);$i++){
				$name=str_replace($search[$i],$replace[$i],trim($name));
}
		}
		else
			$name = str_replace($search, $replace, trim($name));

		return $name;
		//return strtolower($name);
		
	}
}

class GCDataDB {
	public $schema;
	public $db;
	public $dbName;
    public $dbUser;
    public $dbPass;
    public $dbHost;
    public $dbPort;
    
    /**
     * Return an array with the parsed connection string
     */
    public static function parseConnectionPath($path) {
        $result = array(
            'db_user'=>null,
            'db_pass'=>null,
            'db_host'=>null,
            'db_name'=>null,
            'db_port'=>null,
            'schema'=>null
        );
        $pathInfo = explode("/", $path);
        
        $mapUser = defined('MAP_USER') ? MAP_USER : DB_USER;
		$mapPwd = defined('MAP_USER') ? MAP_PWD : DB_PWD;
        $result['db_user'] = $mapUser;
        $result['db_pass'] = $mapPwd;
        $result['db_host'] = DB_HOST;
        $result['db_name'] = DB_NAME;
        $result['db_port'] = DB_PORT;

        if(count($pathInfo) == 1) { // No database conection info. Use author database
            $result['schema'] = $pathInfo[0];
		} else {
            $result['schema'] = $pathInfo[1];
            $connInfo = explode(" ", $pathInfo[0]);
            if(count($connInfo) == 1) {
                // No full connection string given
				$result['db_name'] = $pathInfo[0];
			} else {
                // Full connection string
                foreach($connInfo as $confValue) {
                    foreach(array('user'=>'db_user', 'password'=>'db_pass', 'dbname'=>'db_name', 'host'=>'db_host', 'port'=>'db_port') as $cfgKey=>$resultKey) {
                        if (strpos("{$confValue}=", $cfgKey) === 0) {  // Found entry at position 0
                            $result[$resultKey] = trim(substr($confValue, strlen($cfgKey) + 1));
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }
	
	function __construct($path) {
		$connectionInfo = $this->parseConnectionPath($path);

		$dsn = "pgsql:dbname={$connectionInfo['db_name']};host={$connectionInfo['db_host']}";
		if (defined('DB_PORT')) {
            $dsn .= ";port={$connectionInfo['db_port']}";
        }
		try {
			$this->db = new PDO($dsn, $connectionInfo['db_user'], $connectionInfo['db_pass']);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(Exception $e) {
			throw $e;
		}
        $this->schema = $connectionInfo['schema'];
        $this->dbName = $connectionInfo['db_name'];
        $this->dbUser = $connectionInfo['db_user'];
        $this->dbPass = $connectionInfo['db_pass'];
        $this->dbHost = $connectionInfo['db_host'];
        $this->dbPort = $connectionInfo['db_port'];
	}
}

class GCAuthor {
	// Da OL 2.13 static public $aInchesPerUnit = array(1=>39.3701,2=>12,3=>1,4=>39370.1,5=>39.370,6=>63360,7=>4374754);
	static public $aInchesPerUnit_old = array(1=>39.3701,2=>12,3=>1,4=>39370.1,5=>39.3701,6=>63360,7=>4374754);
	static public $gMapResolutions = array(156543.0339,78271.51695,39135.758475,19567.8792375,9783.93961875,4891.969809375,2445.9849046875,1222.99245234375,611.496226171875,305.7481130859375,152.87405654296876,76.43702827148438,38.21851413574219,19.109257067871095,9.554628533935547,4.777314266967774,2.388657133483887,1.1943285667419434,0.5971642833709717,0.29858214168548586,0.14929107084274293,0.07464553542137146,0.03527776,0.01763888);
	static public $defaultScaleList = array(500000000,5000000,1000000,500000,250000,100000,50000,25000,10000,5000,2000,1000,900,800,700,600,500,400,300,200,100,50);
	static public $aInchesPerUnit = array("m"=>39.3701, "ft"=>12, "inches"=>1,"km"=>39370.1, "mi"=>63360, "dd"=>4374754);

	static private $lang;
	static private $errors = array();
	
	public static function registerError($msg) {
		array_push(self::$errors, $msg);
	}
	
	public static function getErrors() {
		return self::$errors;
	}	

	public static function refreshProjectMapfile($project, $publish = false) {
		require_once ADMIN_PATH."lib/functions.php";
		require_once ADMIN_PATH.'lib/spyc.php';
		require_once ADMIN_PATH.'lib/gcFeature.class.php';
		require_once ADMIN_PATH.'lib/gcMapfile.class.php';
		require_once ROOT_PATH."lib/i18n.php";
		
		$target = $publish ? 'public' : 'tmp';

		$mapfile = new gcMapfile();
		$mapfile->setTarget($target);
		$mapfile->writeProjectMapfile = true;
		$mapfile->writeMap("project",$project);
        
		$localization = new GCLocalization($project);
		$alternativeLanguages = $localization->getAlternativeLanguages();
		if($alternativeLanguages){
			foreach($alternativeLanguages as $languageId => $foo) {
				$mapfile = new gcMapfile($languageId);
				$mapfile->setTarget($target);
				$mapfile->writeMap('project', $project);
			}
		}
	}
	
    /**
     * Regenerate all the mapfiles for the given project
     *
     * @param string $project               The projet_name
     * @param bool $publish                 If false, generate a temporary mapfile (like tmp.)
     * @param bool $refreshLayerMapfile     If true, generate alse one mapfile for each layer (like layer.layer_group.layer)
     */
	public static function refreshMapfiles($project, $publish = false, $refreshLayerMapfile = false) {
        foreach(GCAuthor::getMapsets($project) as $mapset) {
            GCAuthor::refreshMapfile($project, $mapset['mapset_name'], $publish, $refreshLayerMapfile);
        }
	}
	
    /**
     * Regenerate the mapfiles for the given project and mapset
     *
     * @param string $project               The projet_name
     * @param string $mapset                The mapset_name
     * @param bool $publish                 If false, generate a temporary mapfile (like tmp.)
     * @param bool $refreshLayerMapfile     If true, generate alse one mapfile for each layer (like layer.layer_group.layer)
     */
	public static function refreshMapfile($project, $mapset, $publish = false, $refreshLayerMapfile = false) {
		require_once ADMIN_PATH."lib/functions.php";
		require_once ADMIN_PATH.'lib/spyc.php';		
		require_once ADMIN_PATH.'lib/gcFeature.class.php';
		require_once ADMIN_PATH.'lib/gcMapfile.class.php';
		require_once ROOT_PATH."lib/i18n.php";
		
		$target = $publish ? 'public' : 'tmp';

		$mapfile = new gcMapfile();
		$mapfile->setTarget($target);
		$mapfile->writeMap("mapset",$mapset);
        
        if ($refreshLayerMapfile) {
            foreach(GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                $mapfile = new gcMapfile();
                $mapfile->setTarget('layer');
                $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
            }
        }

		$localization = new GCLocalization($project);
		$alternativeLanguages = $localization->getAlternativeLanguages();
		if($alternativeLanguages){
			foreach($alternativeLanguages as $languageId => $foo) {
				$mapfile = new gcMapfile($languageId);
				$mapfile->setTarget($target);
				$mapfile->writeMap('mapset', $mapset);
                
                if ($refreshLayerMapfile) {
                    foreach(GCAuthor::getLayerList($project, $mapset) as $mapsetData) {
                        $mapfile = new gcMapfile();
                        $mapfile->setTarget('layer');
                        $mapfile->writeMap('layer', "{$mapset}.{$mapsetData['feature_type']}");
                    }
                }
			}
		}
	}
	
    public static function buildFeatureQuery($aFeature, array $options = array()) {
        $defaultOptions = array(
            'include_1n_relations'=>false, //se true, le relazioni 1-n vengono incluse nella query (se, per esempio, si vuole filtrare su un campo della secondaria)
            'group_1n'=>true, //se false, vengono inclusi i campi della secondaria, di conseguenza i records non sono più raggruppati per i campi della primaria (se, per esempio, si vogliono visualizzare i dati della secondaria in tabella),
            'show_relation'=>null, //se voglio visualizzare i dati di una sola secondaria, popolo questo con il nome della relazione da visualizzare
            'getGeomAs'=>null, // se text, viene usato st_astext, altrimenti nulla (astext serve per le interrogazioni, nulla serve per il mapfile)
            'srid'=>null //se non null, viene confrontato con lo srid della feature e, se necessario, viene utilizzato st_transform()
        );
        $options = array_merge($defaultOptions, $options);
        

		//$aFeature = $this->aFeature;
		$layerId=$aFeature["layer_id"];
		$datalayerTable=$aFeature["data"];	
		$datalayerGeom=$aFeature["data_geom"];			
		$datalayerKey=$aFeature["data_unique"];	
		$datalayerSRID=$aFeature["data_srid"];		
		$datalayerSchema = $aFeature["table_schema"];
		$datalayerFilter = $aFeature["data_filter"];

		if(!empty($aFeature["tileindex"])) { //X TILERASTER
			$location = "'".trim($aFeature["base_path"])."' || location as location";//value for location
			$table = $aFeature["table_schema"].".".$aFeature["data"];
			$datalayerTable="(SELECT $datalayerKey as gc_objid,$datalayerGeom as the_geom,$location FROM $table) AS ". DATALAYER_ALIAS_TABLE;
			return "the_geom from ".$datalayerTable;
		}
		elseif(preg_match("|select (.+) from (.+)|i",$datalayerTable))//Definizione alias della tabella o vista pricipale (nel caso l'utente abbia definito una vista)  (da valutare se ha senso)
			$datalayerTable="($datalayerTable) AS ".DATALAYER_ALIAS_TABLE; 
		else
			$datalayerTable=$datalayerSchema.".".$datalayerTable . " AS ".DATALAYER_ALIAS_TABLE; 
			
		$joinString = $datalayerTable;

		//Elenco dei campi definiti
		if($aFeature["fields"]){
			$fieldList = array();
            $groupByFieldList = array();
			
			foreach($aFeature["fields"] as $idField=>$aField){
            
                //se non vogliamo la relazione 1-n nella query (es. WMS) oppure se non vogliamo visualizzare i dati della secondaria ma solo usarli per il filtro (es. interrogazioni su mappa), non mettiamo i campi della secondaria
                if(!empty($aField['relation']) && ($aFeature["relation"][$aField["relation"]]["relation_type"] == 2)) {
                    if(!$options['include_1n_relations'] || $options['group_1n']) continue;
                    else if(!empty($options['show_relation'])) {
                        //se voglio vedere i dati della secondaria di una sola relazione, escludo i campi delle altre
                        if($options['show_relation'] != $aFeature['relation'][$aField['relation']]['name']) continue;
                    }
                }
            
                //field su layer oppure su relazione 1-1
                if(empty($aField['relation'])) {
                    $aliasTable = DATALAYER_ALIAS_TABLE;
                } else {
                    $aliasTable = GCApp::nameReplace($aFeature["relation"][$aField["relation"]]["name"]);
                }
                
                if(!empty($aField['formula'])) {
                    if (empty($aField['relation'])){
                        $fieldName = $aField["formula"] . " AS " . $aField["field_name"];
                    }else{
                        $fieldName = str_replace($aFeature["relation"][$aField["relation"]]["name"], $aliasTable, $aField["formula"]) . " AS " . $aField["field_name"];
                    }
                    $groupByFieldList[] = $aField['field_name'];
                } else {
                    $fieldName = $aliasTable . "." . $aField["field_name"];
                    $groupByFieldList[] = $aliasTable.'.'.$aField['field_name'];
                }
                
                $fieldList[] = $fieldName;
			}
			
			//Elenco delle relazioni
			if($aRelation=$aFeature["relation"]) {
				foreach($aRelation as $idrel => $rel){
					$relationAliasTable = GCApp::nameReplace($rel["name"]);
					
					//se relazione 1-n, salta se non vogliamo il join
                    //se vogliamo i dati della secondaria, elimina il groupBy
					if($rel["relation_type"] == 2) {
                        if(!$options['include_1n_relations']) continue;
                        if(!empty($options['show_relation']) && $rel['name'] != $options['show_relation']) continue;
                        
                        if(!$options['group_1n']) {
                            $groupByFieldList = null;
                        }
					}

						
                    $joinList = array();
                    foreach($rel['join_field'] as $joinField) {
                        $joinList[] = DATALAYER_ALIAS_TABLE . '.' . $joinField[0] . ' = ' . $relationAliasTable . '.' . $joinField[1];
                    }

                    $joinFields = implode(" AND ",$joinList);
                    $joinString = "$joinString left join ".$rel["table_schema"].".". $rel["table_name"] ." AS ". $relationAliasTable ." ON (".$joinFields.")";
				}
				
			}
			
			//$fieldString = implode(",",$fieldList);
		}
		
        $geomField = DATALAYER_ALIAS_TABLE.'.'.$datalayerGeom;
        if($options['srid'] && $options['srid'] != 'EPSG:'.$aFeature['data_srid']) {
            $srid = (int)str_replace('EPSG:', '', $options['srid']);
            $geomField = 'st_transform('.$geomField.', '.$srid.')';
        }
        if($options['getGeomAs']) {
            if($options['getGeomAs'] == 'text') {
                $geomField = 'st_astext('.$geomField.')';
            }
        }
		$datalayerTable = 'SELECT '.DATALAYER_ALIAS_TABLE.'.'.$datalayerKey.' as gc_objid, '.$geomField.' as gc_geom';
        if(!empty($fieldList)) $datalayerTable .= ', '.implode(',', $fieldList);
        $datalayerTable .= ' FROM '.$joinString;
        if(!empty($groupByFieldList)) $datalayerTable .= ' group by '.DATALAYER_ALIAS_TABLE.'.'.$datalayerKey.', '.DATALAYER_ALIAS_TABLE.'.'.$datalayerGeom.', '. implode(', ', $groupByFieldList);
		print_debug($datalayerTable,null,'datalayer');
		return $datalayerTable;
        
    }
	
	public static function GCTypeFromDbType($dbType) {
		$typesMap = array(
			1=>array('varchar','text','char','bool','bpchar'),
			2=>array('int','int2','int4','int8','float','float4','float8','serial4','serial8'),
			3=>array('date','timestamp','timestamptz')
		);
		foreach($typesMap as $typeId => $types) {
			if(in_array($dbType, $types)) return $typeId;
		}
		return false;
	}
	
	public static function getLang() {
		if(empty(self::$lang)) {
			if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$langs=explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			} else {
				$langs=array('it', 'en', 'de');
			}
			
			if(!empty($_SESSION['AUTHOR_LANGUAGE'])) {
				self::$lang = $_SESSION['AUTHOR_LANGUAGE'];
			} else if(defined('FORCE_LANGUAGE')) {
				self::$lang = FORCE_LANGUAGE;
			} else {
				self::$lang = (!empty($_REQUEST["language"])) ? $_REQUEST["language"] : substr($langs[0],0,2);
			}
			$_SESSION['AUHTOR_LANGUAGE'] = self::$lang;
		}
		return self::$lang;
	}
	
	public static function getTabDir() {
		$lang = self::getLang();
		$rel_dir="config/tab/$lang/";
		if(!is_dir(ROOT_PATH.$rel_dir)) $rel_dir="config/tab/it/";
		if(defined('TAB_DIR')) $rel_dir="config/tab/".TAB_DIR."/";
		return $rel_dir;
	}
	
	public static function getMapsets($project) {
		$db = GCApp::getDB();
		
		$sql = "select mapset_name, mapset_title, template, project_name from ".DB_SCHEMA.".mapset where project_name=?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($project));
		$mapsets = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($mapsets as &$mapset) {
			$url = defined('MAP_URL') ? MAP_URL : '';
			if(!empty($mapset['template'])) $url .= $mapset['template'];
			$url .= (strpos($url, '?') === false ? '?' : '&') . 'mapset='.$mapset['mapset_name'];
			$mapset['url'] = $url;
		}
		unset($mapset);
		
		return $mapsets;
	}
	
    /**
     * Return the list of layers fo the given project
     * @param string $project      The project name
     * @param string|null $mapset  The mapset name. If givet the result is filtered for the given mapset
     * @return array               The array layers data
     */
	public static function getLayerList($project, $mapset=null) {
		$db = GCApp::getDB();
		
        if (empty($mapset)) {
            $sql = "SELECT theme.project_name, theme_title, layergroup_title, layer_title, 
                           layergroup_name || '.' || layer_name AS feature_type,
                           TRUE AS is_wms,
                           (queryable=1 AND SUM(editable)>0) AS is_wfst
                    FROM ".DB_SCHEMA.".layer
                    INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id)
                    INNER JOIN ".DB_SCHEMA.".theme USING(theme_id)
                    INNER JOIN ".DB_SCHEMA.".field USING(layer_id)
                    WHERE theme.project_name=?
                    GROUP BY project_name, theme_title, layergroup_title, layer_title, layer_id, feature_type
                    ORDER BY theme_title, layergroup_title, layer_title";
            $stmt = $db->prepare($sql);
            $stmt->execute(array($project));
        } else {
            $sql = "SELECT theme.project_name, theme_title, layergroup_title, layer_title, 
                           layergroup_name || '.' || layer_name AS feature_type,
                           TRUE AS is_wms,
                           (queryable=1 AND SUM(editable)>0) AS is_wfst
                    FROM ".DB_SCHEMA.".layer
                    INNER JOIN ".DB_SCHEMA.".layergroup USING(layergroup_id)
                    INNER JOIN ".DB_SCHEMA.".theme USING(theme_id)
                    INNER JOIN ".DB_SCHEMA.".field USING(layer_id)
                    INNER JOIN ".DB_SCHEMA.".mapset_layergroup ON layergroup.layergroup_id=mapset_layergroup.layergroup_id
                    INNER JOIN ".DB_SCHEMA.".mapset USING(mapset_name)
                    WHERE theme.project_name=? AND mapset_name=?
                    GROUP BY theme.project_name, theme_title, layergroup_title, layer_title, layer_id, feature_type
                    ORDER BY theme_title, layergroup_title, layer_title";
            $stmt = $db->prepare($sql);
            $stmt->execute(array($project, $mapset));
        }
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public static function t($key) {
		return self::translate($key);
	}
	
	public static function translate($key) {
		$lang = self::getLang();
		if (isset(self::$translations[$key])) {
			if (isset(self::$translations[$key][$lang])) {
				// found localization
				return self::$translations[$key][$lang];
			} else {
				// requested localization not found,
				// take first localized string
				$availableLangs = array_keys(self::$translations[$key]);
				return self::$translations[$key][$availableLangs[0]];
			}
		} else {
			// worst case, return key itself
			return $key;
		}
	}
	
	static private $translations = array(
		'yes' => array('it'=>'Si','de'=>'Ja'),
		'no' => array('it'=>'No', 'de'=>'Nein'),
		'button_edit' => array('it'=>'Modifica', 'de'=>'Ändern'),
		'button_new' => array('it'=>'Nuovo', 'de'=>'Neu'),
		'button_back' => array('it'=>'Indietro', 'de'=>'Zurück'),
		'button_save' => array('it'=>'Salva', 'de'=>'Speichern'),
		'button_cancel' => array('it'=>'Annulla', 'de'=>'Abbrechen'),
		'button_publish' => array('it'=>'Pubblica', 'de'=>'Herausgeben'),
		'button_delete' => array('it'=>'Elimina', 'de'=>'Löschen'),
		'button_export' => array('it'=>'Esporta', 'de'=>'Exportieren'),
		'close' => array('it'=>'Chiudi', 'de'=>'Schließen'),
		'title'=>array('it'=>'Titolo', 'de'=>'Titel'),
		'name'=>array('it'=>'Nome', 'de'=>'Name'),
		'description'=>array('it'=>'Descrizione', 'de'=>'Beschreibung'),
		'nodata' => array('it'=>'Nessun Dato Presente', 'de'=>'Keine Daten'),
		'undefined'=>array('it'=>'Non definito', 'de'=>'Nicht definiert'),
		'table'=>array('it'=>'Tabella', 'de'=>'Tabelle'),
		'column'=>array('it'=>'Colonna', 'de'=>'Spalte'),
		'field'=>array('it'=>'Campo', 'de'=>'Feld'),
		'group'=>array('it'=>'Gruppo', 'de'=>'Gruppe'),
		'pkey'=>array('it'=>'Campo chiave', 'de'=>'Primärschlüssel'),
		'format'=>array('it'=>'Formato', 'de'=>'Format'),
		'image'=>array('it'=>'Immagine', 'de'=>'Bild'),
		'symbol'=>array('it'=>'Simbolo', 'de'=>'Symbol'),
		'category'=>array('it'=>'Categoria', 'de'=>'Art'),
		'position'=>array('it'=>'Posizione', 'de'=>'Position'),
		'save_to_temp'=>array('it'=>'Salva in un mapfile temporaneo', 'de'=>'In einem temporären Mapfile abspeichern'),
		'auto_refresh_mapfiles'=>array('it'=>'Rigenera automaticamente i mapfiles', 'de'=>'Mapset automatisch aktualisieren'),
		'save'=>array('it'=>'Salva', 'de'=>'Speichern'),
		'all'=>array('it'=>'Tutti', 'de'=>'Alles'),
		'online_maps'=>array('it'=>'Mappe online', 'de'=>'Online-Karten'),
		'ogc_services'=>array('it'=>'Servizi OGC', 'de'=>'OGC Dienste'),
		'update'=>array('it'=>'Aggiorna', 'de'=>'Aktualisieren'),
		'temporary'=>array('it'=>'temp.', 'de'=>'temporär'),
		'public'=>array('it'=>'pubblici', 'de'=>'öffentlich'),
		'theme'=>array('it'=>'Tema', 'de'=>'Thema'),
		'layergroup'=>array('it'=>'Layergroup', 'de'=>'Layergruppe'),
		'layer'=>array('it'=>'Layer', 'de'=>'Layer'),
		'lookup_id'=>array('it'=>'Campo chiave lookup', 'de'=>'Schlüsselfeld in der Nachschlagetabelle'),
		'lookup_name'=>array('it'=>'Campo descrizione lookup', 'de'=>'Beschreibungsfeld in der Nachschlagetabelle'),
		'confirm_delete'=>array('it'=>'Sei sicuro di voler eliminare il record?', 'de'=>'Sind Sie sicher, dass sie diesen Eintrag löschen wollen?'),
		'translations'=>array('it'=>'Traduzioni', 'de'=>'Übersetzungen'),
		'List of available Maps'=>array('it'=>'Elenco delle mappe disponibili', 'de'=>'Verfügbare Karten'),
		'Username'=>array('it'=>'Nome Utente', 'de'=>'Benutzername'),
		'Password'=>array('it'=>'Password', 'de'=>'Kennwort'),
		'project'=>array('it'=>'Progetto', 'de'=>'Projekt'),
		'symbology'=>array('it'=>'Simbologia', 'de'=>'Symbole'),
	);
}

class GCError {
	static private $errors = array();
	
	public static function register($msg) {
		array_push(self::$errors, $msg);
	}
	
	public static function get() {
		return self::$errors;
	}
	
	public static function registerException($e) {
	    array_push(self::$errors, $e->getMessage());
	}
}

class GCUtils {
	public static function parseBox($box) {
		$split = explode(',', str_replace(array('BOX(',')'), '', $box));
		list($l, $b) = explode(' ', $split[0]);
		list($r, $t) = explode(' ', $split[1]);
		return array($l, $b, $r, $t);
	}
    
    public static function deleteOldFiles($path) {
        $files = glob($path.'*');
		foreach($files as $file) {
			$isold = (time() - filectime($file)) > 5 * 60 * 60;
			if (is_file($file) && $isold) {
				if (false === unlink($file)) {
					echo __FILE__.":".__LINE__." Could not remove $file";
				}
			}
		}
    }
}
