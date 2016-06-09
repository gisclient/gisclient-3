<?php

include_once ADMIN_PATH."lib/export.php";
require_once ADMIN_PATH."lib/functions.php";

Class saveData{
	private $db=null;
	var $data=Array();			//Array dei dati da salvare
	var $fields=Array();		//Array dei campi e definizione dei loro tipi
	public $parent_flds=Array();
	var $oldId;
	var $newId;
	var $table;					//Nome della tabella
	var $schema;				//Nome della schema
	var $pkeys=Array();			//Array delle chiavi primarie e loro valori
	var $mode;					//Modalita di salvataggio
	var $action;				//Tipo di azione da eseguire
	var $array_action=Array("salva","aggiungi","elimina","cancella","copia","sposta");	//Elenco delle azioni possibili
	var $hasErrors;
	var $delete = 0;
	var $conf_dir;				//
	public $error;
	private $refreshMapfiles = false;

	function __construct($arr_dati){

		$rel_dir = GCAuthor::getTabDir();
		$this->db = GCApp::getDB();
		$this->hasErrors = false;

		$this->conf_dir=ROOT_PATH.$rel_dir;
		$pk=_getPKeys();
		$this->primary_keys=$pk["pkey"];
		//ESTRAZIONE DEI DATI DAL ARR_DATI
		//ACQUISIZIONE DEI DATI SULLA MODE E SULL?AZIONE DA ESEGUIRE
		if(empty($arr_dati["mode"]) && empty($arr_dati["modo"])){
			GCError::register("Manca la modalità, non è possibile continuare");
			$this->hasErrors=true;
			return;
		}
		else
			$this->mode=isset($arr_dati["modo"])?(strtolower($arr_dati["modo"])):(strtolower($arr_dati["mode"]));
		
		if(!$arr_dati["azione"]){
			GCError::register("Manca l'azione da eseguire, non è possibile continuare");
			$this->hasErrors=true;
			return;
		}
		else{
			if(isset($arr_dati["save_type"]) && $arr_dati["save_type"]=="multiple"){
				$this->mode="multiple-save";
			}
			$this->action=strtolower($arr_dati["azione"]);	
		}
		
		//Acquisizione dei dati dal file di configurazione
		$config_file=$arr_dati["config_file"];
		if((!$config_file || !is_file($this->conf_dir.$config_file)) && $this->action!="annulla"){
			GCError::register("Manca il file di definizione del form, non è possibile continuare");
			$this->hasErrors=true;
			return;
		}
		else{
			$this->_getConfig($this->conf_dir.$config_file,$arr_dati["pkey"],(isset($arr_dati["pkey_value"]))?$arr_dati["pkey_value"]:null);
			$this->newId=(isset($arr_dati["dataction"]))?$arr_dati["dataction"]["new"]:null;
			$this->oldId=(isset($arr_dati["dataction"]))?$arr_dati["dataction"]["old"]:null;
			$this->parent_flds=((count($arr_dati["parametri"])-2)<0)?(Array()):($arr_dati["parametri"][count($arr_dati["parametri"])-2]);
			if (!isset($arr_dati["dati"])) $arr_dati["dati"] = array();
			if($this->mode=="multiple-save"){
				foreach($this->fields as $arr){
					$fld=$arr["field"];
					if($arr_dati["dati"]){
						$cont=0;
						foreach($arr_dati["dati"] as $val){
							$this->data[$cont][$fld]=(isset($val[$fld]))?$val[$fld]:null;
							$cont++;
						}
					}
				}
			}
			else
				$this->data=$arr_dati["dati"];
		}
		
	}
	function getPKeys($level,$sk){
		return $this->primary_keys[$level];
	}
	function performAction($p=null){
		if ($this->hasErrors==true){
			$p->livello=$p->get_livello();	
			$p->get_conf();
			return $p;
		}
		
		switch($this->action){
			case "classifica":
				$p->mode=$p->arr_mode["list"];
				return $p;
				break;
			case "importa":
				return $p;
			case "elimina":
				array_pop($p->parametri);
				$p->mode=$p->arr_mode["list"];
				
			case "cancella":
				$flt = array();
				$sqlParams = array();
				foreach($this->pkeys as $key=>$value) {
				    $flt[]="$key = ?";
				    $sqlParams[] = $value;
				}
				$filter=implode(" AND ",$flt);
				$sql="delete from $this->schema.$this->table where ".$filter.";";
				print_debug($sql,null,"save.class");
				try {
				    $stmt = $this->db->prepare($sql);
				    $stmt->execute($sqlParams);
				} catch (Exception $e) {
				    GCError::registerException($e);
				    $this->hasErrors=true;
				}

				if($this->action=="cancella") $p->mode=$p->arr_mode["edit"];
				$this->delete=1;
				
				$this->refreshMapfiles = true;
				
				break;
			case "sposta":
				$flt = array();
				$sqlParams = array();
				$_cnt = 0;
				foreach($this->pkeys as $key=>$value) {
				    $flt[]="$key = :kv".$_cnt;
				    $sqlParams['kv'.$_cnt] = $value;
				    $_cnt++;
				}
				$filter=implode(" AND ",$flt);
				$parent=array_keys($this->parent_flds);
				$sqlParams['newId'] = $this->newId;
				$sql="UPDATE $this->schema.$this->table SET ".$parent[0]."_id = :newId WHERE ".$filter.";";
				try {
				    $stmt = $this->db->prepare($sql);
				    $stmt->execute($sqlParams);
				} catch (Exception $e) {
				    GCError::registerException($e);
				    $this->hasErrors=true;
				}

				$p->parametri[$parent[0]]=$this->newId;
				$p->get_conf();
				return $p;
				break;
			case "copia":
				//print_array($this);
				$idcopy=Array($this->newId);
				foreach($p->array_levels as $key=>$value){
					if($p->livello==$value["name"]) $idlevel=$key;
				}
				$parent=array_keys($this->parent_flds);
				$newName=$this->data[$this->table."_name"];
				if($this->newId && $this->oldId && $this->newId!=$this->oldId)
					$tree=$this->_copy_object($p->array_levels,$idlevel,$idcopy,Array("key"=>$parent[0],"value"=>$p->parametri[$parent[0]]),$idlevel,1,$newName);
				else{
					
					$tree=$this->_copy_object($p->array_levels,$idlevel,$idcopy,null,null,0,$newName);
				}
					
				array_pop($p->parametri);
				break;
			case "aggiungi":
				$this->mode="new";
				$Dati=$this->_validaDati();
				$tmp=array_keys($this->pkeys);
				$pkey=$tmp[0];
				$newid = GCApp::getNewPKey(DB_SCHEMA, $this->schema, $this->table, $pkey);
				$Dati[$pkey]=$newid;
				//INSERISCO I VALORI DEI GENITORI DELLA TABELLA
				if($this->parent_flds){
					foreach($this->parent_flds as $key=>$value){
						$Dati[$key."_id"]=$value;
					}
				}
				//INSERISCO I VALORI DELLA TABELLA
				$sqlinsertfield = array();
				$sqlinsertplaceholders = array();
				$sqlinsertvalues = array();
				$_cnt = 0;
				foreach ($Dati as $campo=>$valore){
					if (strlen($valore)>0) {
						$sqlinsertfield[]=$campo;
						$sqlinsertplaceholders[]=':ph'.$_cnt;
						$sqlinsertvalues['ph'.$_cnt]=$valore;
						$_cnt++;
					}
				}
				$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertplaceholders).");";
				try {
				    $stmt = $this->db->prepare($sql);
				    $result = $stmt->execute($sqlinsertvalues);					
				    print_debug($sql,null,"save.class");
				} catch (Exception $e) {
				    GCError::registerException($e);
				    $this->hasErrors=true;
				}
				$p->mode=$p->arr_mode["edit"];
				$this->refreshMapfiles = true;
				break;
				
			case "salva":
				
				$Dati=$this->_validaDati();
				if ($this->hasErrors==true){
					$p->mode=$p->arr_mode[$this->mode];
					$p->livello=$p->get_livello();	
					$p->get_conf();
					return $p;
				}
				if (isset($_SESSION["ADD_NEW"]) && $_SESSION["ADD_NEW"]){
					echo "Il record è già stato inserito ".$_SESSION["ADD_NEW"];
					$this->hasErrors=true;
					$p->livello=$p->get_livello();	
					$p->get_conf();
					return $p;
				}
				switch ($this->mode){
					case "multiple-save":
						$Dati=$this->_validaMultipleDati();
						if ($this->hasErrors){
							$p->mode=$p->arr_mode[$this->mode];
							$this->hasErrors=true;
							$p->livello=$p->get_livello();	
							$p->get_conf();
							return $p;
						}
						
						$tmp=array_keys($this->pkeys);
						
						$pkey=$tmp[0];
						$delete_filter = '';
						$arr_del_filter = array();
						$arr_del_values = array();
						$_cnt = 0;
						if($this->parent_flds){
							
							foreach($this->parent_flds as $key=>$value){
								$parentKeys=$this->getPKeys($key,DB_SCHEMA);
								
								foreach($parentKeys as $pk)
									for($i=0;$i<count($Dati);$i++) $Dati[$i][$pk]=$value;
								$arr_del_filter[]="$pk = :ph".$_cnt;
								$arr_del_values['ph'.$_cnt] = $value;
								$_cnt++;
							}
							if(count($arr_del_filter))
								$delete_filter="WHERE ".@implode(" AND ",$arr_del_filter);
						}
						$sql="DELETE FROM $this->schema.$this->table $delete_filter;";
						try {
						    $stmt = $this->db->prepare($sql);
						    $result = $stmt->execute($arr_del_values);
						} catch (Exception $e) {
						    GCError::registerException($e);
						    $this->hasErrors=true;
						}						    
						print_debug($sql,null,"save.class");
						for($i=0;$i<count($this->data);$i++){
							if ($pkey) {
								$newid = GCApp::getNewPKey(DB_SCHEMA, $this->schema, $this->table, $pkey, 1);
								if ($newid) 
								    $Dati[$i][$pkey]=$newid;
							}
							$sqlinsertfield = array();
							$sqlinsertplaceholders = array();
							$sqlinsertvalues = array();
							$_cnt=0;
							foreach ($Dati[$i] as $campo=>$valore){
								if ($campo && strlen($valore)>0 ) {
									$valore=str_replace("''","'",$valore);
									$sqlinsertfield[]=$campo;
									$sqlinsertplaceholders[]=':ph'.$_cnt;
									$sqlinsertvalues['ph'.$_cnt]=$valore;
									$_cnt++;
								}
							}
							$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertplaceholders).");";
							try {
							    $stmt = $this->db->prepare($sql);
							    $result = $stmt->execute($sqlinsertvalues);	
							} catch (Exception $e) {
							    GCError::registerException($e);
							    $this->hasErrors=true;
							}

							print_debug($sql,null,"save.class");
						}
						array_pop($p->parametri);
						$p->livello=$p->get_livello();	
						$p->get_conf();
						$this->hasErrors=false;
						return $p;
						break;
					case "new":
						//CERCO IL VALORE DELLA NUOVA CHIAVE PRIMARIA   ---- AL MOMENTO FUNZIONA SOLO CON UNA CHIAVE PRIMARIA 
						$tmp=array_keys($this->pkeys);
						
						$pkey=$tmp[0];
						switch($this->table){	// Starting point della tabella
							default:
								$start=1;
								break;
						}

						// ricerco le chiavi della tabella
						if(preg_match("|(.+)_id|Ui",$pkey) && $pkey != 'language_id'){ // strozzo Roberto (dice Marco)
							$newid = GCApp::getNewPKey(DB_SCHEMA, $this->schema, $this->table, $pkey, $start);
							if($newid) {
								$Dati[$pkey] = $newid;
								$this->data[$pkey] = $newid;
							}
						}
						else if (isset($this->data[$pkey])){
							
							$newid=$this->data[$pkey];
						}
						//INSERISCO I VALORI DEI GENITORI DELLA TABELLA
						
						if($this->parent_flds){
							foreach($this->parent_flds as $key=>$value){
								$pkeys=$this->getPKeys($key,$this->schema);
								for($i=0;$i<count($pkeys);$i++)
										$Dati[$pkeys[$i]]=$value;
							}
						}
						//INSERISCO I VALORI DELLA TABELLA						
						$sqlinsertfield = array();
						$sqlinsertplaceholders = array();
						$sqlparams = array();
						$_cnt=0;
						foreach ($Dati as $campo=>$valore){
							
							if ($campo && strlen($valore)>0) {
								$sqlinsertfield[]=$campo;
								$sqlinsertplaceholders[]=':ph'.$_cnt;
								$sqlparams['ph'.$_cnt]=$valore;	
								$_cnt++;
							}
						}
						$sql="insert into $this->schema.$this->table (".@implode(",",$sqlinsertfield).") values (".@implode(",",$sqlinsertplaceholders).");";
						break;
					case "edit":
						$flt = array();
						$sqlupdate = array();
						$sqlparams = array();
						$_cnt=0;						
						foreach($this->pkeys as $key=>$value) {
						    $flt[]="$key = ".':ph'.$_cnt;
						    $sqlparams['ph'.$_cnt] = $value?$value:$this->data[$key];
						    $_cnt++;
						}
						$filter=implode(" AND ",$flt);
						foreach ($Dati as $campo=>$valore){
							if($campo){
								if (strlen($valore)>0){
									$sqlupdate[]="$campo = ".':ph'.$_cnt;
									$sqlparams['ph'.$_cnt] = $valore;
									$_cnt++;
									if(in_array($campo,array_keys($this->pkeys))) $newKey=$valore;
								}
								else
									$sqlupdate[]="$campo = NULL";
							}
						}
						$sql="update $this->schema.$this->table set ".@implode(", ",$sqlupdate)." where $filter;";
						break;
				}
				if(!$this->hasErrors){
				    try {
						$stmt = $this->db->prepare($sql);
						$result = $stmt->execute($sqlparams);
						print_debug($sql,null,"save.class");
				    } catch (Exception $e) {
						GCError::registerException($e);
						$p->mode=$p->arr_mode[$this->mode];
						$this->hasErrors=true;
						$p->livello=$p->get_livello();	
						$p->get_conf();
						return $p;
				    }
				}
				if(isset($newid) && $this->mode=="new"){
					$_SESSION["ADD_NEW"]=$newid;
					$p->parametri[$p->get_livello()]=$newid;	
				}
				if ($p->array_levels[$p->get_idLivello()]["leaf"] && $this->delete){
					array_pop($p->parametri);
				}

				$this->refreshMapfiles = true;
				break;
			default:
				if(in_array($this->mode,Array("new","multiple-save")) || $this->action=="chiudi"){
					array_pop($p->parametri);
				}
				break;
		}
		$p->livello = $p->get_livello();
		$p->get_conf();
		$this->hasErrors = false;
		
		if($this->refreshMapfiles && !empty($_SESSION['auto_refresh_mapfiles'])) {
            $publish = !empty($_SESSION['save_to_tmp_map']) ? false : true;
			if(!empty($p->parametri['project']) && !empty($p->parametri['mapset'])){
				GCAuthor::refreshMapfile($p->parametri['project'],$p->parametri['mapset'], $publish);
			}
			else if(!empty($p->parametri['project'])) {
				GCAuthor::refreshMapfiles($p->parametri['project'], $publish);
			}
		}
		
		return $p;
	}
	
	private function _copy_object($arr,$lev,$arr_id=Array(),$parent_fld=Array(),$start_lev=0,$modal=0,$newname=""){
		$struct["name"]=$arr[$lev]["name"];
		$el=$arr[$lev];
		if(!$arr[$lev]["leaf"]){
			$sql="SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE export>0 AND parent_id=?;";
			print_debug($sql,null,"save.class");
			try {
			    $stmt = $this->db->prepare($sql);
			    $stmt->execute(array($lev));
			} catch (Exception $e) {
			    GCError::registerException($e);
			    $this->hasErrors=true;
			}
			    
			$child = $stmt->fetchAll();
			print_debug($sql);
		}
		else{
			$child=Array();
		}	
		if(count($arr_id)){
			$sqlparams = array('structName1' => $struct["name"], 'structName2' => $struct["name"]);
			$sql="SELECT column_name FROM information_schema.columns WHERE table_name='"
			    .$struct["name"]."' and table_schema='".DB_SCHEMA
				."' AND NOT column_name IN 
				    (SELECT Y.column_name FROM 
					(select constraint_name FROM information_schema.table_constraints 
					WHERE constraint_type='PRIMARY KEY' AND constraint_schema='".DB_SCHEMA
					."' and table_name=:structName1)  as X 
					left join 
					    (SELECT constraint_name,column_name FROM information_schema.constraint_column_usage 
					    WHERE constraint_schema='".DB_SCHEMA
					    ."' and table_name=:structName2) as Y using(constraint_name))";
			print_debug($sql,null,"save.copy");
			$tmp = array();
			try {
			    $stmt = $this->db->prepare($sql);
			    $stmt->execute($sqlparams);
			    $tmp = $stmt->fetchAll();
			} catch (Exception $e) {
			    GCError::registerException($e);
			    $this->hasErrors=true;
			}
			
			foreach($tmp as $v) {
				$flds[]=$v["column_name"];
				if($parent_fld["value"] && ($v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_id" || $v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_name")){
					$value[]="'{$parent_fld["value"]}'";
				}
				elseif(preg_match("|(.*)name$|i",$v["column_name"]) && $v['column_name'] != 'symbol_ttf_name' && $v['column_name'] != 'symbol_name'){
					$value[]=($newname!="" && $struct["name"]."_name"==$v["column_name"])?("'$newname'"):(($start_lev!=$lev)?($v["column_name"]):("copia"));
				}
				else
					$value[]=$v["column_name"];
			}
			
 			$list_flds=@implode(",",$flds);	
			$list_value=@implode(",",$value);	
		}
		
		// INSERISCO GLI ELEMENTI DI QUESTO LIVELLO
		if ($arr_id)
		foreach($arr_id as $id){
			$idx = GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, $struct["name"], $struct["name"].'_id');
			$parent[$lev][$id]=Array("key"=>$struct["name"],"value"=>$idx);
			// PDO: $list_values cannot be quoted/made into a bound parameter because it holds a list of column names
			$sql="INSERT INTO ".DB_SCHEMA.".".$struct["name"]."(".$struct["name"]."_id,$list_flds) SELECT $idx,$list_value FROM ".DB_SCHEMA.".".$struct["name"]." WHERE ".$struct["name"]."_id=:id;";
			print_debug($sql,null,"save.copy");
			try {
			    $stmt = $this->db->prepare($sql);
			    $result = $stmt->execute(array('id' => $id));
			} catch (Exception $e) {
			    GCError::registerException($e);
			    $this->hasErrors=true;
			}
		}
		foreach($child as $ch){
			$tb=$ch["name"];
			$fld=$tb."_id";
			foreach($arr_id as $id){
				$sql="SELECT DISTINCT $fld as id FROM ".DB_SCHEMA.".$tb WHERE ".$struct["name"]."_id=:id";
				print_debug($sql,null,"save.class");
				$rows = array();
				try {
				    $stmt = $this->db->prepare($sql);
				    $result = $stmt->execute(array('id' => $id));	
				    $rows = $stmt->fetchAll();
				} catch (Exception $e) {
				    GCError::registerException($e);
				    $this->hasErrors=true;
				}				    				    
				$newArrId = array();
				foreach ($rows as $r) {
				    $newArrId[] = $r['id'];
				}
				if (count($newArrId)){
					$struct["child"][$lev]=$this->_copy_object($arr,$ch["id"],$newArrId,$parent[$lev][$id],$start_lev,$modal);
				}
				else{
					$struct["child"][$lev]=Array();
				}
			}
		}
		return $struct;
	}
	
	private function _export_object($arr,$lev,$arr_id=Array()){
		$struct["name"]=$arr[$lev]["name"];
		$el=$arr[$lev];
		if(!$arr[$lev]["leaf"]){
			$sql="SELECT id,name,leaf FROM ".DB_SCHEMA.".e_level WHERE parent_id=:lev;";
			print_debug($sql,null,"save.class.debug");
			$child = array();
			try {
			    $stmt = $this->db->prepare($sql);
			    $result = $stmt->execute(array('lev' => $lev));	
			    print_debug($sql);
			    $child = $stmt->fetchAll();			
			} catch (Exception $e) {
			    GCError::registerException($e);
			    $this->hasErrors=true;
			}				    

		}
		else{
			$child=Array();
		}
		if(count($arr_id)){
			$sqlparams = array('structName1' => $struct["name"], 'structName2' => $struct["name"], 'structName3' => $struct["name"]);
			$sql="SELECT column_name FROM information_schema.columns WHERE table_name=:structName1 and table_schema='".DB_SCHEMA
				."' AND NOT column_name IN (SELECT Y.column_name FROM 
					(select constraint_name FROM information_schema.table_constraints 
					WHERE constraint_type='PRIMARY KEY' AND constraint_schema='".DB_SCHEMA
					."' and table_name=:structName2)  as X left join 
					    (SELECT constraint_name,column_name FROM information_schema.constraint_column_usage 
					    WHERE constraint_schema='".DB_SCHEMA."' and table_name=:structName3) as Y using(constraint_name))";
			print_debug($sql,null,"save.class.debug");
			$tmp = array();
			try {
			    $stmt = $this->db->prepare($sql);
			    $result = $stmt->execute($sqlparams);				
			    $tmp = $stmt->fetchAll();
			} catch (Exception $e) {
			    GCError::registerException($e);
			    $this->hasErrors=true;
			}				    			    
			foreach($tmp as $v) {
				$flds[]=$v["column_name"];
				
				if($parent_fld["value"] && $v["column_name"]==$arr[$arr[$lev]["parent"]]["name"]."_id"){
					$value[]="(select ".$arr[$arr[$lev]["parent"]]["name"]."_id from ".DB_SCHEMA.".".$arr[$arr[$lev]["parent"]]["name"]." where ".$arr[$arr[$lev]["parent"]]["name"]."_name='')";
				}
				else
					$value[]=$v["column_name"];
			}
			
				
			$list_flds=@implode(",",$flds);	
			$list_value=@implode(",",$value);	
		}
		
		// INSERISCO GLI ELEMENTI DI QUESTO LIVELLO
		foreach($arr_id as $id){
			$idx = GCApp::getNewPKey(DB_SCHEMA, DB_SCHEMA, $struct["name"], $struct["name"].'_id');
			$parent[$lev][$id]=Array("key"=>$struct["name"],"value"=>$idx);
			// PDO: $list_values cannot be quoted/made into a bound parameter because it holds a list of column names
			$sql="INSERT INTO ".DB_SCHEMA.".".$struct["name"]."(".$struct["name"]."_id,$list_flds) SELECT $idx,$list_value FROM ".DB_SCHEMA.".".$struct["name"]." WHERE ".$struct["name"]."_id=:id;";
			print_debug($sql,null,"save.class.debug");
			
			// FIXME: Migrazione PDO: le seguenti righe sono commentate perchè anche nel sorgente originale
			//        questo codice finiva "nel vuoto" (senza l'esecuzione effettiva della query INSERT)
			// 
			//$stmt = $this->db->prepare($sql);
			//$result = $stmt->execute(array('id' => $id));
			//if (!$result){
			//	print_debug($stmt->errorInfo(),null,"save.copy.debug");
			//}
		}
		foreach($child as $ch){
			$tb=$ch["name"];
			$fld=$tb."_id";
			foreach($arr_id as $id){
				$sql="SELECT DISTINCT $fld as id FROM ".DB_SCHEMA.".$tb WHERE ".$struct["name"]."_id=:id";
				print_debug($sql,null,"save.class.debug");
				$rows = array();
				try {
				    $stmt = $this->db->prepare($sql);
				    $result = $stmt->execute(array('id' => $id));	
				    $rows = $stmt->fetchAll();
				} catch (Exception $e) {
				    GCError::registerException($e);
				    $this->hasErrors=true;
				}				    				    
				$newArrId = array();
				foreach ($rows as $r) {
				    $newArrId[] = $r['id'];
				}
				if (count($newArrId)){
					$struct["child"][$lev]=$this->_copy_object($arr,$ch["id"],$newArrId,$parent[$lev][$id],$modal);
				}
				else{
					$struct["child"][$lev]=Array();
				}
			}
		}
		return $struct;
	}
	
	private function _validaMultipleDati(){
		$dati = array();
		for($i=0;$i<count($this->data);$i++){
			$OK_Save=1;
			
			$dati[$i]=$this->_validaDati($i);
			$error=$this->error;
			$this->error=Array();
			$this->error[$i]=$error;
		}
		return $dati;
	}
	private function _validaDati($curr_rec=null){
		$array_data = array();
		//dall'array tratto dal file di configurazione crea l'array campi=>valori validati per il db
		$OK_Save=1;
		$sql="SELECT DISTINCT column_name as fields FROM information_schema.columns WHERE table_name=:tableName AND table_schema=:tableSchema";
		try {
		    $stmt = $this->db->prepare($sql);
		    $result = $stmt->execute(array('tableName' => $this->table, 'tableSchema' => $this->schema));
    		} catch (Exception $e) {
		    GCError::registerException($e);
		    $this->hasErrors=true;
		    return;
		}				    

		$flds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		foreach($this->fields as $def){
			$campo=$def["field"];
			$tipo=$def["type"];
                        if ($curr_rec===Null) {
                            if (!array_key_exists($campo, $this->data)) continue;
                            else $val = trim($this->data[$campo]);
                        } else {
                            if (!array_key_exists($campo, $this->data[$curr_rec])) continue;
                            else $val = trim($this->data[$curr_rec][$campo]);
                        }
			$present=(!in_array($campo,$flds))?(0):(1);
			switch ($tipo) {
				case "pword":
				case "text":	
				case "textarea":
					if (get_magic_quotes_runtime() or get_magic_quotes_gpc())
						$val=stripslashes($val);
					break;
				case "select":
					break;
					
				case "selectdb":
				case "selectRPC":
				case "elenco":
					break;
				case "intero":
				case "numero":
					$val=str_replace(",",".",$val);
					if (strlen($val) and !is_numeric($val)){
						$OK_Save=0;
						GCError::register($campo.": Dato non numerico");
					}
					break;	
					
				case "bool":
					($val="SI")?($val="t"):($val="f");
					break;
					
				case "checkbox":
				case "radio":
					$arvalue=$_POST[$campo];
					break;
				case "color":
					if ($val && !(preg_match("|[0-9]{1,3} [0-9]{1,3} [0-9]{1,3}|",$val) || preg_match("|^([\[]{1})([A-z0-9]+)([\]]{1})$|",$val))){
						GCError::register($campo.": Valore non RGB");
						$OK_Save=0;
					}
					break;
				case "chiave_esterna":
					$val=($campo=="symbol_ttf_name")?("$val"):($val);
					break;
				case "check1":
					$val=(isset($this->data[$curr_rec][$campo]))?($this->data[$curr_rec][$campo]):(0);
					break;
					
			}
			if(($tipo!="button") && ($tipo!="submit") && $present)
				$array_data[$campo]=$val;
			
		}
		return $array_data;
	}

	private function _getConfig($file,$pk,$pk_val){
		
		$tmp=parse_ini_file($file,true);
		$array_config=$tmp["standard"];
		// ACQUISIZIONE DELLA TABELLA DEL DATABASE
		$dbtable=(isset($array_config["save_table"]) && $array_config["save_table"])?($array_config["save_table"]):($array_config["table"]);
		if(preg_match("|([\w]+)[.]{1}([\w]+)|i",trim($dbtable),$tmp)){
			$this->table=$tmp[2];
			$this->schema=$tmp[1];
		}
		else{
			$this->table=trim($dbtable);
			$this->schema=DB_SCHEMA;
		}
		// ACQUISIZIONE DELLE PRIMARY KEYS DELLA TABELLA (SI PUO' SOSTITUIRE PRENDENDO I DATI DALL?INFORMATION SCHEMA SU DB)
		
		if($array_config["pkey"]){
			$datipkeys=explode(';',$array_config["pkey"]);	
			//for($i=0;$i<count($datipkeys);$i++) $this->pkeys[trim($datipkeys[$i])]=$pk[str_replace("_name","",str_replace("_id","",trim($datipkeys[$i])))];
			for($i=0;$i<count($pk);$i++) $this->pkeys[$pk[$i]]=stripslashes($pk_val[$i]);
		}
		else{
			$this->pkeys=Array("id"=>"");
		}
		//ACQUISIZIONE DELLE DEFINIZIONI DEI CAMPI
		for ($i=0;$i<count($array_config["dato"]);$i++){
			$row_config=explode('|',$array_config["dato"][$i]);
			
			foreach($row_config as  $r){
				$def=array_pad(explode(';',$r), 4, '');
				$this->fields[]=array("field"=>trim($def[1]),"type"=>trim($def[3]));
			}
		}
	}
}
