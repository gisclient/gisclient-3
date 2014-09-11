<?php

/*
Descrizione della classe e dei metodi
*/

class Tabella{
	// costanti che definiscono i file immagine
	var $testo_titolo="#FFFFFF";
	var $sfondo_titolo="#728bb8";
	var $sfondo_label="#728bb8";
	var $testo_label="#FFFFFF";
	var $stile="stiletabella";
	var $idpratica;
	var $titolo; //stringa del titolo puo essere il titolo esplicito o il nome del campo che contiene il titolo
	var $button_menu;//pulsante da inserire nella riga di intestazione della tabella "nuovo" o "modifica"
	var $array_hidden;//array con l'elenco dei campi nascosti
	
	var $array_dati;//array associativo campo=>dato con i dati da visualizzare
	var $num_record;//numero di record presenti in array_dati
	var $curr_record;//bookmark al record corrente di array_dati
	
	
	var $mode;
	var $config_file;//file di configurazione del form
	var $schemadb;
	var $tabelladb; //nome della tabella o vista sul db dalla quale estraggo i dati
	//var $campi_obb; // array con l'elenco dei campi obbligatori (non serve qui)
	var $tab_config; //vettore che definisce la configurazione della tabella. La dimensione corrisponde al numero di righe per le tabelle H o al numero di colonne per le tabelle V
					 //ogni elemento è un vettore con un elemento per la tabella V e un numero di elementi pari al numero di campi sulla stessa riga per le tabelle H 
	var $num_col; // numero di colonne di tab_config
	var $elenco_campi;//elenco dei campi per la select 
	var $pkeys;//elenco delle primary keys
	var $pkeys_value;//elenco dei valori delle chiavi primarie
	var $elenco_modelli;//elenco dei modelli di stampa da proporre nel form separati da virgola(posso non mettere nulla e lasciare all'utente ogni volta libera scelta)
	
	var $db;//puntatore a connessione a db da vedere se usare classe di interfaccia.....
	var $display_number=-1;
	function Tabella($config_file,$mode="standard"){
	// ******LETTURA FILE DI CONFIGURAZIONE e impostazione layout della tabella
		//	NUOVA MODALITA
		
		$mylang = GCAuthor::getLang();
		$rel_dir = GCAuthor::getTabDir();
		
		$tmp=parse_ini_file(ROOT_PATH.$rel_dir.$config_file,true);
		$data_mode=($mode=="list")?($mode):("standard");
		$data=$tmp[$data_mode];
		$this->mode=($mode=="list")?("view"):($mode);
		$this->FileTitle=(!empty($tmp["title"][$mode]))?$tmp["title"][$mode]:0;
		
		//ACQUISIZIONE DELLA TABELLA E DELLO SCHEMA 
		if(preg_match("|([\w]+)[.]{1}([\w()]+)|i",trim($data["table"]),$tmp)){
			$this->tabelladb=$tmp[2];
			$this->schemadb=$tmp[1];
		}
		else{
			$this->tabelladb=trim($data["table"]);
			$this->schemadb=DB_SCHEMA;		}
		
		$pkeys=(trim($data["pkey"]))?(explode(";",trim($data["pkey"]))):(Array("id"));
		
		$campi=$pkeys;
		for($i=0;$i<count($pkeys);$i++) $this->pkeys[$pkeys[$i]]="";
		
		$ncol=count($data["dato"]);
		for ($i=0;$i<$ncol;$i++){//comincio da 1 perchè sulla prima riga ho il nome della tabella e i campi obbligatori
			$d=$data["dato"][$i];
			//if (strtoupper(CHAR_SET) != 'UTF-8') $d = iconv($d, 'UTF-8', CHAR_SET.'//TRANSLIT');
			if (strtoupper(CHAR_SET) != 'UTF-8') $d=utf8_decode($d);
			$row[]=explode('|',$d);//array di configurazione delle tabelle
		}
		$tmp_ncol=$ncol;
		$tmp_nrow=0;
		for ($i=0;$i<$tmp_ncol;$i++){
			$tmp_nrow=max($tmp_nrow,count($row[$i]));
			for ($j=0;$j<count($row[$i]);$j++){ //ogni elemento può avere un numero di elementi arbitrario
				list(,$campo,,$tipo)=array_pad(explode(';',$row[$i][$j]), 4, null);
				$tipo=trim($tipo);
				if (($tipo!="submit") && ($tipo!="button"))
					if (!in_array($campo,$campi) && $campo) $campi[]=$campo;
			}
		}
		$this->function_param=(!empty($data["fun_prm"]))?explode("#",$data["fun_prm"]):array();
		$this->num_col=$ncol;
		$this->colspan=$tmp_nrow;
		$this->elenco_campi=implode(",",$campi);
		$this->tab_config=$row;
		$this->config_file=$config_file;
		$this->order_fld=(!empty($data["order_fld"]))?implode(",",explode("#",$data["order_fld"])):array();
		
	}
	
	function get_idpratica(){
		return $this->idpratica;
	}
	
	function set_titolo($titolo,$menu=0,$hidden=0,$display_first=0){
		$this->titolo=$titolo;
		if ($menu) $this->button_menu=$menu;
		if ($hidden) $this->array_hidden=$hidden;
		if($display_first>0) $this->display_number=$display_first;
	}
	
	function get_titolo($self = null){
		
		if(is_null($self)) $self=$_SERVER["PHP_SELF"];
		//testo titolo
		if (isset($this->array_dati[$this->curr_record][$this->titolo])) {
			$titolo=$this->array_dati[$this->curr_record][$this->titolo];//se il titolo è dato dal campo 
		} else {
			$titolo=$this->titolo;//altrimenti il titolo è la stringa passata
		}
		
		//pulsante di menù
		$mode = null;	
		if (!isset($_SESSION["PERMESSI"]) || $_SESSION["PERMESSI"]<4){
			if ($this->button_menu=="modifica"){
				if (!isset($_SESSION["PERMESSI"]) || $_SESSION["PERMESSI"]<=3 ){
					$mode = "edit";		
					$butt = GCAuthor::t('button_edit');
				}
			}
			elseif ($this->button_menu=="nuovo"){
				if (!isset($_SESSION["PERMESSI"]) || $_SESSION["PERMESSI"]<=3){
					$mode="new";
					$butt = GCAuthor::t('button_new');
				}
			}
			elseif($this->button_menu=="valida"){
				$mode="edit";
				$butt = GCAuthor::t('button_edit');
			}
		}
		
		$riga_titolo="<b>$titolo</b>";
		if (isset($butt))
			$riga_titolo.="<button>$butt</button>";
	
		//campi nascosti del form
		if (isset($this->array_hidden)){
			$hidden = '';	
			foreach ($this->array_hidden as $key=>$value){
				$nome=$key;
				if($value=='' && isset($this->array_dati[$this->curr_record][$nome]))	$value=$this->array_dati[$this->curr_record][$nome];//se non ho passato un valore vado a prenderlo nel record
				$hidden.="<input type=\"hidden\" name=\"$nome\" value=\"$value\">\n\t";
			}
		}
	
		if($this->idpratica) // se ho già l'id pratica lo passo
			$hidden.="<input type=\"hidden\" name=\"pratica\" value=\"".$this->idpratica."\">";
	
		$tabella_titolo="
		<div class=\"tableHeader ui-widget ui-widget-header ui-corner-top\">";
		if ($mode) $tabella_titolo.="<form method=\"post\" target=\"_parent\" action=\"".$_SERVER["PHP_SELF"]."\">";
		$tabella_titolo.="<input type=\"hidden\" name=\"mode\" value=\"$mode\">
				$hidden
				$riga_titolo";
		if ($mode) $tabella_titolo.="</form>";
		$tabella_titolo.="</div>\n";		
				
		print $tabella_titolo;
	}
	// >>>>>>>>>>>>>>>>>>>>>>>>>ATTENZIONE OGNI TABELLA DEVE AVERE I CAMPI ID PRATICA E CHK<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	function set_dati($data="",$order=""){
		
		//se passo un array questo è l'array di POST altrimenti è il filtro - per default filtra su idpratica se settato
		if (is_array($data)){
			$this->array_dati=array(0=>$data);
			$this->num_record=count($data);
			$this->curr_record=0;
		}
		else{
			if ($data) $data='WHERE '.$data;
			if (!isset($this->db)) $this->connettidb();
			if ($this->order_fld) $order='ORDER BY '.$this->order_fld;
			$tb=$this->tabelladb;
                        if (strpos($tb,"()") > 0) {
                            $tb=str_replace("()","",$tb);
                            $param=implode("','",$this->function_param);
                            $sql='SELECT '.$this->elenco_campi.
                                ' FROM '.$this->schemadb.'.'.$tb."('$param')".
                                ' '.$data.' '.$order;
                        }
                        else
                            $sql='SELECT '.$this->elenco_campi.
                            ' FROM '.$this->schemadb.'.'.$this->tabelladb.
                            ' '.$data.' '.$order.';';
                        print_debug($this->config_file."\n".$sql,null,"tabella");
                        try {
                            $stmt = $this->db->prepare($sql);
                            $success = $stmt->execute();
                            $this->array_dati=$stmt->fetchAll();
                            if (count($this->array_dati)==1){
                                foreach($this->pkeys as $key=>$val)
                                    if($this->array_dati[0][$key]) $this->pkeys_value[$key]=$this->array_dati[0][$key];
                            }
                            else{
                                for($i=0;$i<count($this->array_dati);$i++){
                                    foreach($this->pkeys as $key=>$val)
                                        if($this->array_dati[$i][$key]) $this->pkeys_value[$key]=$this->array_dati[$i][$key];
                                }
                            }
                            $this->num_record=$stmt->rowCount();
                        } catch(Exception $e) {
                            print_debug($sql,null,"errors");
                        }
			$this->curr_record=0;	
			return  $this->num_record;	
		}
	}
	function set_multiple_data($data){
		
		if (is_array($data)){
			for($i=0;$i<count($data);$i++){
				$this->array_dati[$i]=$data[$i];/*
				for($j=0;$j<count($this->pkeys);$j++) {
					if (isset($data[$i]) && isset($this->pkeys[$j]) && isset($data[$i][$this->pkeys[$j]])) {
						$this->pkeys_value[$i][$j]=$data[$i][$this->pkeys[$j]];
					}
				}
				*/
				 
				foreach($this->pkeys as $key=>$val){
					if (isset($data[$i]) && isset($key) && isset($data[$i][$key])) {
						$this->pkeys_value[$i][$key]=$data[$i][$key];
					}
				}
			}
		}
		
		$this->num_record=count($data);
		$this->curr_record=0;		
				
	}
	function date_format($stringa_data){
	//formatta la data in giorno-mese-anno
		if ($stringa_data){
			$ar= split('[/.-]', $stringa_data);
			$stringa_data=$ar[0]."-".$ar[1]."-".$ar[2];
		}
		return $stringa_data; 
	}
	
	function set_db($db){
		$this->db=$db;
	}
	
	function get_db(){
		if(!isset($this->db)) $this->connettidb();
		return $this->db;
	}
	
	function connettidb(){
		$this->db = GCApp::getDB();
		if(is_null($this->db))  die( "Impossibile connettersi al database");
	}
	
	function close_db(){
		if(isset($this->db)) unset($this->db);
	}
	
	function set_tag($mytag){
		$this->tag=$mytag;
	}
	
}