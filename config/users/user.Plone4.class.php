<?php
/*
CODICI DI ERRORE DELLA VALIDAZIONE
"1" ERRORE NELLA QUERY
-2 NESSUN UTENTE CON QUESTA PASSWORD
-3 UTENTE DISATTIVATO
-4 NESSUN GRUPPO ASSEGNATO A QUESTO UTENTE
"1"01 ERRORE NELLA CODIFICA DEI DATI (HACKING)
"1"02 FILTRO VALIDAZIONE ERRATO (HACKING)
"1"03 SESSIONE SCADUTA
*/

require_once ROOT_PATH."lib/user.abstract.php";
require_once ROOT_PATH."lib/postgres.php";

class userApps extends user{
	var $flds;
	var $editUser=-1;
	var $editGroup=-1;
	var $userIp;
	var $schema;
	var $authInfo=  Array('username','domain','role','context','time');
	var $virtualGroups=Array('Authenticated Users');
	var $sqlMembersGroups;
    
	

	function _init($obj){
        $this->sqlMembersGroups="select username,groupname from ".USER_SCHEMA.".group_members XXX inner join (select id,zope_id as groupname from ".USER_SCHEMA.".principals where type='group') YYY on (group_id=YYY.id) inner join (select id,zope_id as username from ".USER_SCHEMA.".principals where type='user') ZZZ on (ZZZ.id=principal_id)";
		$this->flds=$obj;
		if($obj && isset($this->data[$this->flds["user"]]) && isset($this->data[$this->flds["pwd"]]))
			$this->action="valida";
		elseif($obj && isset($this->flds["auth"]) && isset($this->data[$this->flds["auth"]])){
			$this->action="autentica";
        }
        elseif(isset($this->flds["cookie"]) && $_COOKIE[$this->flds["cookie"]]){
            $this->action="autentica";
            $this->method="cookie";
        }
        
		$_SESSION["VIRTUAL_GROUPS"]=$this->virtualGroups;
		$this->schema=USER_SCHEMA;
		$this->username=isset($_SESSION["USERNAME"])?$_SESSION["USERNAME"]:false;
		$this->groups=isset($_SESSION["GROUPS"])?$_SESSION["GROUPS"]:false;
		$this->role=isset($_SESSION["ROLE"])?$_SESSION["ROLE"]:false;
		$this->dbschema=DB_SCHEMA;
		$this->userIp=getenv("REMOTE_ADDR");
		$this->get_db();
		
	}
	
	function writeSessionInfo(){
		$_SESSION["USERNAME"];
		$_SESSION["GROUPS"];
		$_SESSION["ROLE"];
	}
	
	function writeAccessInfo(){	//ACCESSO ANDATO A BUON FINE SCRIVO LE INFORMAZIONI DI ACCESSO
		$sql="INSERT INTO ".$this->schema.".accessi_log(ipaddr,username,data_enter,application) VALUES('$this->user_ip','$this->username',CURRENT_TIMESTAMP(1),'$app')";
		$this->db->sql_query($sql);
	}
	
	function getRoles(){
		return Array();
	}
	function getGroups($username){
		// SE ESISTONO GRUPPI VIRTUALI (ESEMPIO Authenticated Users di PLONE) li aggiungo
        $sqlVirtual="";
        
		if(count($this->virtualGroups)) foreach ($this->virtualGroups as $grp) $sqlVirtual.=" (SELECT '$grp' as groupname) UNION";
		//SELEZIONO DAL DATABASE L'ELENCO DEI GRUPPI AI QUALI L'UTENTE APPARTIENE
		$sql="$sqlVirtual (select groupname from group_members X inner join (select id,zope_id as groupname from principals where type='group') Y on (group_id=Y.id) inner join (select id,zope_id as username from principals where type='user') Z on (z.id=principal_id) where Z.username=:username)";
        $stmt=$this->db->prepare($sql);
        $sth=$stmt->execute(Array("username"=>$username));
        
		if($sth){		//STRUTTURA GRUPPI PLONE
			$this->groups=$stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		}
		else{
			$this->error->setNote($sql);
			$this->error->getError("1");	
			return false;
		}
	}
	function getGisclientAdmin($project,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="select distinct :project1 as project_name,A.username,case when (coalesce(X.username,'')<>'') then 1 else 0 end as presente from (".$this->sqlMembersGroups.") A $JOIN (SELECT username FROM ".DB_SCHEMA.".project_admin WHERE project_name=:project2) X on (A.username=X.username)  order by A.username";
		$stmt = $this->db->prepare($sql);
		if(!$sth=$stmt->execute(Array("project1"=>$project,"project2"=>$project))){
			return -1;
		}
		else{
			$ris=$stmt->fetchAll();
			return $ris;
		}
		
	}
    
	function getGroup($group=null,$mode=null){
		$filter=$group?" AND groupname =:groupname":'';
        $vals=($group)?(Array("groupname"=>$group)):(Array());
		$sql="SELECT DISTINCT zope_id AS groupname FROM ".$this->schema.".principals WHERE type='group' $filter";
        $stmt = $this->db->prepare($sql);
		if(!$sth=$stmt->execute($vals)){
			return -1;
		}
		else{
			$ris=$stmt->fetchAll();
			return $ris;
		}
	}
    //Metodo che restituisce appartenenza dell'utente ai gruppi del progetto 
	function getGroupsList($user,$mode){

		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="SELECT DISTINCT :username1 as username,coalesce(X.groupname,'') as groupname,CASE WHEN coalesce(Y.groupname,'')='' THEN 0 ELSE 1 END as presente FROM (".$this->sqlMembersGroups.") X $JOIN (".$this->sqlMembersGroups." WHERE username=:username2) Y using(groupname)";
        $stmt = $this->db->prepare($sql);
		if(!$sth=$stmt->execute(Array("username1"=>$user,"username2"=>$user))){
			return -1;
		}
		else{
			$ris=$sth->fetchAll();
			return $ris;
		}
	}
    //Metodo che restituisce elenco deglli utenti
	function getUser($user,$mode){
		if($user){
            $filter=" WHERE username=:username";
            $vals=Array("username"=>$user);
        }
		else{
			$filter="";
            $vals=Array();
        }
		$sql="select login as username,fullname as cognome,'' as nome,email,password from ".$this->schema.".users $filter order by username";
		$stmt = $this->db->prepare($sql);
		if(!$sth=$stmt->execute($vals)){
			return -1;
		}
		else{
			$ris=$stmt->fetchAll();
			return $ris;
		}
	}
    //Metodo che restituisce appartenenza dell'utente ai gruppi del progetto 
	function getUsersList($group,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="SELECT DISTINCT :groupname1 as groupname,coalesce(X.username,'') as username,CASE WHEN coalesce(Y.username,'')='' THEN 0 ELSE 1 END as presente FROM (".$this->sqlMembersGroups.") X $JOIN (".$this->sqlMembersGroups." WHERE groupname=:groupname2) Y using(username)";
        $stmt = $this->db->prepare($sql);
		if(!$sth=$stmt->execute(Array("groupname1"=>group,"groupname2"=>group))){
			return -1;
		}
		else{
			$ris=$sth->fetchAll();
			return $ris;
		}
	}
	function getUserMapset(){
		
	}
	
	function getKey($username){
		$sql="SELECT d,e,n FROM ".$this->schema.".users_key WHERE username='$username'";
		if(!$this->db->sql_query($sql)){
			return false;
		}
		$ris=$this->db->sql_fetchrow();
		$this->key=Array("d"=>$ris[0],"e"=>$ris[1],"n"=>$ris[2]);
		return true;
	}
	function validateUser(){
		
		if(!$this->username){
			if(!$this->data[$this->flds["user"]]){
				$this->error->getError("A004");
				return false;
			}
			if(!$this->data[$this->flds["passw"]]){
				$this->error->getError("A005");
				return false;
			}
            $vals=Array("login"=>$this->data[$this->flds["user"]],"pwd"=>$this->data[$this->flds["passw"]]);
            $sql="SELECT :pwd||salt as passw FROM ".USER_SCHEMA.".users  WHERE enabled=true and login=:login";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($vals);
           
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $vals=Array("login"=>$this->data[$this->flds["user"]],"pwd"=>hash("sha1", $row["passw"]));
			$sql = "SELECT count(*) as presente FROM ".USER_SCHEMA.".users  WHERE enabled=true and login=:login AND (password = :pwd )";
			$stmt = $this->db->prepare($sql);
            $stmt->execute($vals);
           
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!count($row)){
				$this->error->setNote($sql);
				$this->error->getError("1");
				return false;		
			}
			if($this->context=='author'){;
				$sql = "SELECT count(*) as presente FROM ".DB_SCHEMA.".project_admin  WHERE username=:login;";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($vals);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
				if(!count($row)){
					$this->error->setNote($sql);
					$this->error->getError("1");
					return false;
				}
			}
            
			$presente = $row['presente'];
			return $this->setInfo($presente,$this->data["username"]);
			
		}
	}
	//METODO PER L'AUTENTICAZIONE DELL'UTENTE DA PUNTO DI ACCESSO ESTERNO
	function authenticateUser(){
        $md5user=Array('md5user'=>str_replace('"','',$_COOKIE[$this->flds["cookie"]]));
        
        $sql="SELECT * FROM ".$this->schema.".users WHERE md5(login)=:md5user and enabled=true;";
        $stmt = $this->db->prepare($sql);
        //print $stmt->execute($md5user);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->data['enc_password']=$row['password'];
        $this->data['username']=$row['login'];
        //return $this->validateUser();
        if(!$this->username){
			if(!$this->data[$this->flds["user"]]){
				$this->error->getError("A004");
				return false;
			}
			if(!$this->data[$this->flds["pwd"]]){
				$this->error->getError("A005");
				return false;
			}
            $vals=Array("login"=>$this->data[$this->flds["user"]],"pwd"=>$this->data[$this->flds["pwd"]]);
			$sql = "SELECT count(*) as presente FROM ".USER_SCHEMA.".users  WHERE enabled=true and login=:login AND (password = :pwd)";
			$stmt = $this->db->prepare($sql);
            $stmt->execute($vals);
           
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!count($row)){
				$this->error->setNote($sql);
				$this->error->getError("1");
				return false;		
			}
			if($this->context=='author'){;
				$sql = "SELECT count(*) as presente FROM ".DB_SCHEMA.".project_admin  WHERE username=:login;";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute($vals);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
				if(!count($row)){
					$this->error->setNote($sql);
					$this->error->getError("1");
					return false;
				}
			}
            
			$presente = $row['presente'];
            
			return $this->setInfo($presente,$this->data["username"]);
        }
	}
	function createAuthString($app){
		$arrStr=Array("time@@".time(),"username@@".$this->username,"role@@".$this->role,"context@@".$app);
		for($i=0;$i<5;$i++) $arrStr[]=rand(100000,999999)."@@".rand(100000,999999);
		shuffle($arrStr);
		$str=implode("##",$arrStr);
		return $str;
	}
	function encryptAuthString($str){
		$this->getKey($this->username);
		
		require_once ROOT_PATH."config/users/rsa.class.php";
		$rsa = New SecurityRSA;
		return $rsa->rsa_encrypt($str,$this->key["e"],$this->key["n"]);
	}
	
	//METODI DI CONNESSIONE AL DB
	function set_db(){
		$this->db=$this->db;
	}
	function get_db(){
		if(!isset($this->db)) $this->connettidb();
		return $this->db;
	}
	function connettidb(){
		$this->db =  GCApp::getDB();
		if(!$this->db)  die( "UTENTI Impossibile connettersi al database ".DB_NAME );
	}
	function close_db(){
		return;
	}
}
	
?>