<?php
require_once ROOT_PATH."lib/debug.php";

class error{
	var $code;
	var $message;
	var $note;
	var $errList=Array(
		"-1"=>"Errore Generico",													//ERRORI GENERICI
		"1"=>"Errore nella query",
		"2"=>"Tentativo di accesso non autorizzato",
		"A001"=>"Nessun utente con questa password",								//ERRORI SULLA VALIDAZIONE UTENTE
		"A002"=>"Utente disattivato.Contattare l'amministratore di sistema.",
		"A003"=>"Utente non assegnato ad alcun gruppo",
		"A004"=>"Nessun nome utente inserito",
		"A005"=>"Nessuna password inserita",
		"B001"=>"Errore nella codifica dei dati",									//ERRORI SULL'AUTENTICAZIONE DELL'UTENTE (TENTATIVO DI HACKING)
		"B002"=>"Filtro validazione errato",
		"B003"=>"Sessione scaduta",
		"C001"=>"Nessun Gruppo definito",											//ERRORI SUI GRUPPI
		"D001"=>"Nessun Ruolo definito"												//ERRORI SUI RUOLI
	);
	
	
	function __construct($err=Array()){
		if($err && is_array($err)) $this->errList=$err;
	}
	function getError($code='-1'){
		$this->code=$code;
		$this->message=$this->errList[$code];
		return $this;
	}
	function setNote($note=''){
		$this->note=$note;
	}
}

abstract class user{
	protected $domain;
	var $username;
	var $groups;
	var $roles;
	var $userIp;
	var $schema;
	var $error;
	var $virtualGroups;
	var $action;
	var $authorizedLayers = array();
	var $mapLayers = array();
	var $status=false;
	//METODO PER SETTARE INFORMAZIONI AGGIUNTIVE DURANTE L'ISTANZIAZIONE DELLA CLASSE
	abstract function _init($obj);
	//METODO PER L'AUTENTICAZIONE DELL'UTENTE TRAMITE USERNAME/PASSWORD
	abstract function validateUser();
	//METODO PER L'AUTENTICAZIONE DEL'UTENTE TRAMITE AUTHENTICATION STRING (ACCESSO DA APPLICAZIONE ESTERNA)
	abstract function authenticateUser();
	//METODO PER RECUPERARE LE INFORMAZIONI SUI GRUPPI AI QUALI APPARTIENE L'UTENTE
	abstract function getGroups($username);
	//METODO PER RECUPERARE I RUOLI CHE L'UTENTE HA NEI CONTESTI/APPLICAZIONI
	abstract function getRoles();
	//METODO CHE INSERISCE IN SESSIONE I DATI DELL'UTENTE
	//abstract function setInfo($activate,$username);
	//METODO CHE SCRIVE I DATI DELL'ACCESSO DELL'UTENTE 
	abstract function writeAccessInfo();	
	//abstract function setError($err,$username);
	
	
	public function __construct($obj=Array()){
		if(!empty($obj['request_data'])) {
			$this->data = $obj['request_data'];
		} else {
			$this->data=$_REQUEST;
		}
		$this->session=$_SESSION;
		$this->error=new error();
		$this->_init($obj);
	}
	public function __destruct(){
	
	}
	
	//METODO CHE GESTISCE IL TIPO DI VALIDAZIONE DA EFFETTUARE
	public function checkUser(){
		$this->status=false;
		if(isset($this->session["USERNAME"]) && $this->session["USERNAME"]){
			if($this->setGroups($this->getGroups($this->session["USERNAME"]))===true){
				$this->status=true;
				return true;
			}
			else
				return false;

		}
		if($this->action=="valida"){
			$ris=$this->validateUser();
		}
		elseif($this->action=="autentica")
			$ris=$this->authenticateUser();	
		else{
			$ris=false;
		}
		if(!$ris){
			return false;
		}
		else{
			$this->status=true;
			return true;
		}
	}
	
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI SUI GRUPPI
	public function setGroups($obj){
		if($obj instanceof error) return $obj;
		else if (is_array($obj)){
			$_SESSION["GROUPS"]=array_unique($obj);
		} else {
			$_SESSION["GROUPS"]=Array();
		}
		return true;
	}
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI SUI RUOLI
	public function setRoles($roles){
		if($roles instanceof error) return $roles;
		else
			$_SESSION["ROLES"]=$roles;
		return true;
	}
	//METODO CHE METTE IN SESSIONE LE INFORMAZIONI NECESSARIE
	function setInfo($activate,$username){
        
		switch($activate){
			case -1:
			case 0:
				$this->error->getError("A002");
				return false;
				break;
			default:
				$ris=$this->getGroups($username);
                
				//if(!$ris) 
				//	return false;
				$ris=$this->getRoles($username);
                
				if($ris instanceof error) 
					return false;
				$_SESSION['USERNAME'] = $username;
				$this->setGroups($this->groups);
				$this->setRoles($this->roles);
				$this->setUserOptions();
				return true;
				break;
			
			
				break;
		}
	}
	//Metodo che restituisce info su uno o più utenti
	public function getUsers($user,$mode){
		
	}
	//Metodo che restituisce l'elenco degli Utenti appartenenti ad un gruppo
	public function getUsersList($group,$mode){
	
	}
	public function getUserMapset(){
	
	}
	//Metodo che restituisce l'elenco dei Gruppi ai quali appartiene un Utente
	public function getGroupsList($user,$mode){
	
	}
	
	//Metodo che restituisce l'elenco degli amministratori locali del gisclient del progetto 
	public function getGisclientAdmin($project,$mode){
		
	}
	public function logout(){
		session_destroy();
		unset($_SESSION);
        if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
		session_start();
	}
	
	public function setAuthorizedLayers($filter) {
		$db = GCApp::getDB();
		
		if(isset($filter['mapset_name'])) {
			$sqlFilter = 'mapset_name = :mapset_name';
			$sqlValues = array(':mapset_name'=>$filter['mapset_name']);
		} else if(isset($filter['theme_name'])) {
			$sqlFilter = 'theme_name = :theme_name';
			$sqlValues = array(':theme_name'=>$filter['theme_name']);
		} else if(isset($filter['project_name'])) {
			$sqlFilter = 'project_name = :project_name';
			$sqlValues = array(':project_name'=>$filter['project_name']);
		} else return false;
		
		if(!isset($_SESSION)) {
            if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
            session_start();
        }
		if(!empty($_SESSION['USERNAME']) && $_SESSION['USERNAME']==SUPER_USER){
			$sql = "SELECT project_name,theme_name,layergroup_name,layer.layer_id,layer.private,layer.layer_name,1::integer as wms,1::integer as wfs,1::integer as wfst, layer.layer_order
					FROM ".DB_SCHEMA.".theme 
					INNER JOIN ".DB_SCHEMA.".layergroup USING (theme_id) 
					INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
					LEFT JOIN ".DB_SCHEMA.".layer USING (layergroup_id)
					WHERE ".$sqlFilter." order by layer.layer_order";
			$stmt = $db->prepare($sql);
			$stmt->execute($sqlValues);
		} else {

			if (isset($_SESSION["GROUPS"])) {
				$groups = array();
				foreach ($_SESSION["GROUPS"] as $grp) array_push($groups, $db->quote($grp));  // gruppi dell'utente
				$userGroups = implode(',', $groups);
			}
			//$userGroup = implode(',', $this->authorizedGroups);
			

			$sql = "";
			if(!empty($userGroups)) {
				$sql .= "SELECT project_name,theme_name,layergroup_name,layer.layer_id,layer.private,layer.layer_name,wms,wfs,wfst,layer_order
					FROM ".DB_SCHEMA.".theme 
					INNER JOIN ".DB_SCHEMA.".layergroup USING (theme_id) 
					INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
					LEFT JOIN ".DB_SCHEMA.".layer USING (layergroup_id)
					INNER JOIN ".DB_SCHEMA.".layer_groups USING (layer_id)
					WHERE layer.private=1 AND groupname IN ($userGroups) AND $sqlFilter
					union ";
			}
			$sql .= "SELECT project_name,theme_name,layergroup_name,layer.layer_id,layer.private,layer.layer_name,1::integer as wms,1::integer as wfs,1::integer as wfst, layer_order 
				FROM ".DB_SCHEMA.".theme 
				INNER JOIN ".DB_SCHEMA.".layergroup USING (theme_id) 
				INNER JOIN ".DB_SCHEMA.".mapset_layergroup using (layergroup_id)
				LEFT JOIN ".DB_SCHEMA.".layer USING (layergroup_id)
				WHERE layer.private<>1 AND $sqlFilter
				order by layer_order;";
			$stmt = $db->prepare($sql);
			$stmt->execute($sqlValues);
		}

		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$featureType = $row['layergroup_name'].".".$row['layer_name'];
			$_SESSION['GISCLIENT_USER_LAYER'][$row['project_name']][$featureType] = array('WMS'=>$row['wms'],'WFS'=>$row['wfs'],'WFST'=>$row['wfst']);

			if(!empty($row['layer_id'])) {
				// se il filtro è richiesto e non è settato in sessione, escludi il layer
				$this->authorizedLayers[] = $row['layer_id'];
			}
			// create arrays if not exists
			if(!isset($this->mapLayers[$row['theme_name']])) $this->mapLayers[$row['theme_name']] = array();
			if(!isset($this->mapLayers[$row['theme_name']][$row['layergroup_name']])) $this->mapLayers[$row['theme_name']][$row['layergroup_name']] = array();
			
			array_push($this->mapLayers[$row['theme_name']][$row['layergroup_name']], $featureType);
		};
	}
	
	public function getAuthorizedLayers($filter) {
		if(empty($this->mapLayers)) $this->setAuthorizedLayers($filter);
		return $this->authorizedLayers;
	}
	
	public function getMapLayers($filter) {
		if(empty($this->mapLayers)) $this->setAuthorizedLayers($filter);
		return $this->mapLayers;
	}
	
	public function saveUserOption($key, $value) {
		if(!isset($_SESSION)) {
            if(defined('GC_SESSION_NAME')) session_name(GC_SESSION_NAME);
            session_start();
        }
		if(empty($_SESSION['USERNAME'])) return false;
		
		$db = GCApp::getDB();
		$sql = 'delete from '.DB_SCHEMA.'.users_options where option_key=:key and username=:username';
		$stmt = $db->prepare($sql);
		$stmt->execute(array('key'=>$key, 'username'=>$_SESSION['USERNAME']));
		
		$sql = 'insert into '.DB_SCHEMA.'.users_options (username, option_key, option_value) '.
			' values (:username, :key, :value)';
		$stmt = $db->prepare($sql);
		$stmt->execute(array('username'=>$_SESSION['USERNAME'], 'key'=>$key, 'value'=>$value));
	}
	
	public function setUserOptions() {
		$db = GCApp::getDB();
		$sql = 'select option_key, option_value from '.DB_SCHEMA.'.users_options where username=?';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_SESSION['USERNAME']));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$_SESSION[$row['option_key']] = $row['option_value'];
		}
	}
	
}