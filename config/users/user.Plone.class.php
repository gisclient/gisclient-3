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
	var $editUser=0;
	var $editGroup=0;
	var $userIp;
	var $schema;
	var $authInfo=Array('username','domain','role','context','time');
	var $virtualGroups=Array('Authenticated Users');
	
	

	function _init($obj){
		$this->flds=$obj;
		if(isset($this->data[$this->flds["user"]]) && isset($this->data[$this->flds["pwd"]]))
			$this->action="valida";
		elseif(isset($this->data[$this->flds["auth"]])){
			$this->action="autentica";
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
		if(count($this->virtualGroups)) foreach ($this->virtualGroups as $grp) $sqlVirtual.=" (SELECT '$grp' as groupname) UNION";
		//SELEZIONO DAL DATABASE L'ELENCO DEI GRUPPI AI QUALI L'UTENTE APPARTIENE
		$sql="$sqlVirtual (SELECT groupname FROM ".$this->schema.".groups WHERE username='$username')";

		if($this->db->sql_query($sql)){		//STRUTTURA GRUPPI PLONE
			$ris=$this->db->sql_fetchlist('groupname');
			if(!$ris) {
				$this->error->getError("C001");
				return false;
			}
			
			foreach($ris as $g){
				$this->groups[]=$g;
			}
			
			
			return $this->groups;
		}
		else{
			$this->error->setNote($sql);
			$this->error->getError("1");	
			return false;
		}
	}
	function getGisclientAdmin($project,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="select distinct '$project' as project_name,groups.username,groups.groupname,case when (coalesce(X.username,'')<>'') then 1 else 0 end as presente from ".USER_SCHEMA.".groups $JOIN (SELECT username FROM ".DB_SCHEMA.".project_admin WHERE project_name='$project') X on (groups.username=X.username) where coalesce(groups.username,'')<>'' order by groups.groupname,groups.username";
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
		
	}
	function getGroup($group,$mode=null){
		$filter=$group?" AND groupname ='$group'":'';
		$sql="select distinct groupname from ".$this->schema.".groups WHERE coalesce(groupname,'')<>'' $filter";
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}
	function getGroupsList($user,$mode){

		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		$sql="SELECT DISTINCT '$user' as username,coalesce(X.groupname,'') as groupname,CASE WHEN coalesce(Y.groupname,'')='' THEN 0 ELSE 1 END as presente FROM ".$this->schema.".groups X $JOIN (SELECT DISTINCT groupname FROM ".$this->schema.".groups WHERE username='$user') Y using(groupname) ";
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}
	function getUser($user,$mode){
		if($user) $filter=" WHERE username='$user'";
		else
			$filter=" WHERE username IN (SELECT DISTINCT username FROM ".$this->schema.".groups WHERE coalesce(username,'')<>'')";
		$sql="select username,fullname as cognome,'' as nome,email,password from ".$this->schema.".users $filter order by username";
		
		if(!$this->db->sql_query($sql)){
			return -1;
			
		}
		else{
			$ris=$this->db->sql_fetchrowset();
			return $ris;
		}
	}
	function getUsersList($group,$mode){
		$JOIN=($mode==0)?(" INNER JOIN "):(" LEFT JOIN ");
		//$sql="SELECT distinct username,1 as presente FROM ".$this->schema.".groups WHERE groupname='$group' and coalesce(username,'')<>''";
		$sql="SELECT DISTINCT '$group' as groupname,coalesce(X.username,'') as username,CASE WHEN coalesce(Y.username,'')='' THEN 0 ELSE 1 END as presente FROM ".$this->schema.".groups X $JOIN (SELECT * FROM ".$this->schema.".groups  WHERE groupname='$group' and coalesce(username,'')<>'') Y using(username)";
	
		if(!$this->db->sql_query($sql)){
			return -1;
		}
		else{
			$ris=$this->db->sql_fetchrowset();
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
			if(!$this->data[$this->flds["pwd"]]){
				$this->error->getError("A005");
				return false;
			}
			$sql = "SELECT count(*) as presente FROM ".$this->schema.".users  WHERE username='".$this->data[$this->flds["user"]]."' AND password = '".$this->data[$this->flds["pwd"]]."'";
			if(!$this->db->sql_query($sql)){
				$this->error->setNote($sql);
				$this->error->getError("1");
				return false;		
			}
			if($this->context=='author'){;
				$sql = "SELECT count(*) as presente FROM ".DB_SCHEMA.".project_admin  WHERE username='".$this->data[$this->flds["user"]]."';";
				if(!$this->db->sql_query($sql)){
					$this->error->setNote($sql);
					$this->error->getError("1");
					return false;
				}
			}
			$presente = $this->db->sql_fetchfield('presente');
			return $this->setInfo($presente,$this->data["username"]);
			
		}
	}
	//METODO PER L'AUTENTICAZIONE DELL'UTENTE DA PUNTO DI ACCESSO ESTERNO
	function authenticateUser(){

        require_once ROOT_PATH."config/users/rsa.class.php";
        extract($this->data);

        $sql="SELECT username,d,n FROM ".$this->schema.".users inner join admin.users_key using(username) WHERE md5(username)='".$this->data["usr"]."'";
        if($this->db->sql_query($sql)){
            $d=$this->db->sql_fetchfield('d');
            $n=$this->db->sql_fetchfield('n');
            $activate=$this->db->sql_fetchfield('attivato');
            $username=$this->db->sql_fetchfield('username');
            $rsa = New SecurityRSA;
            $decoded = $rsa->rsa_decrypt($authstring, $d, $n); 
            $tmp=explode("##",$decoded);
            foreach($tmp as $value) {
                list($key,$val)=explode("@@",$value);
                if(in_array($key,$this->authInfo)) $ris[$key]=$val;
            }

            if($ris["username"]!=$username){

                return $this->error->getError("B001");
            }
            //BYPASS SESSIONE SCADUTA
            $t=time();
            /*if(!($ris["time"] && $ris["time_exp"] && $ris["time_exp"]>=$t && $ris["time"]<=$t)){
                $this->error->getError("B003");
                return false;
            }*/

            $attivato = $this->db->sql_fetchfield('attivato');
            $attivato=1;
            return $this->setInfo($attivato,$ris["username"]);

        }
        else{
            $this->error->getError("1");
            return false;
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
		$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$this->db->db_connect_id)  die( "UTENTI Impossibile connettersi al database ".DB_NAME );
	}
	function close_db(){
		if(isset($this->db)) $this->db->sql_close;
	}
}
	
?>