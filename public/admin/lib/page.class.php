<?php

	include_once ADMIN_PATH."lib/tabella_h.class.php";
	include_once ADMIN_PATH."lib/tabella_v.class.php";
	include_once ADMIN_PATH."lib/savedata.class.php";
	include_once ADMIN_PATH."lib/export.php";
	
	
	class page{
		
		const MODE_VIEW=0;
		const MODE_LIST=3;
		const MODE_EDIT=1;
		const MODE_NEW=2;
		
		var $parametri;	// Elenco dei parametri
		var $tableList;	// Elenco delle tabelle da disegnare
		var $arr_mode=Array("view"=>0,"edit"=>1,"new"=>2,"list"=>3);
		var $mode;
		var $livello;
		var $array_levels=Array();
		var $db; 	// Connessione ad DB postegres
		var $tb;			// Oggetto Tabella
		var $save;			//Oggetto SaveData
		var $errors;
		var $notice;
		var $pageKeys;
		var $action;
		private $primary_keys;
		private $navTreeValues;
		
		// Costruttore della classe
		function page($param=Array()){
            $user = new GCUser();
			//Recupero Le Chiavi Primarie
			$pk=_getPKeys();
			$this->primary_keys=$pk["pkey"];
			//Inizializzo l'oggetto con i parametri (di REQUEST)
			$this->_get_parameter($param);
			//Setto Il tipo di Amministratore
			//$this->admintype=($_SESSION["USERNAME"]==SUPER_USER)?(1):(2);
            $this->admintype = $user->isAdmin() ? 1 : 2;
			//Inizializzo l'oggetto DB
			$this->db = GCApp::getDB();
			if(is_null($this->db))  die( "Impossibile connettersi al database ".DB_NAME);
			//Ricostruisco L'albero
                        $sql="select e_level.id,e_level.name,coalesce(e_level.parent_id,0) as parent,X.name as parent_name,e_level.leaf 
                            from ".DB_SCHEMA.".e_level left join ".DB_SCHEMA.".e_level X on (e_level.parent_id=X.id)  
                            order by e_level.depth asc;";
                        $stmt = $this->db->prepare($sql);
                        $success = $stmt->execute();

                        if (!$success) 
                            print_debug($sql,null,"page_obj");

                        print_debug($sql,null,"conf");
                        $ris=$stmt->fetchAll(); 
			foreach($ris as $v) $this->array_levels[$v["id"]]=Array("name"=>$v["name"],"parent"=>$v["parent"],"leaf"=>$v["leaf"]);
		}
		private function _get_frm_parameter(){
			$out = null;
			if (is_array($this->parametri) && count($this->parametri)){
				$i=0;
				foreach($this->parametri as $key=>$val){
					$out["parametri[$i][$key]"]=$val;
					$i++;
				}
			}
			return $out;
		}
		private function _get_pkey($lev){
			return $this->primary_keys[$lev];
		}
		private function _get_pkey_value($pk){
			foreach($this->parametri as $k=>$v){
				//if ($pk==$k."_id" || $pk==$k."_name")
					if(in_array($pk,$this->primary_keys[$k])){
						
						for($i=0;$i<count($this->primary_keys[$k]);$i++){
							if($this->primary_keys[$k][$i]==$pk){
								if(is_numeric($v) && (int)$v<=0) return 0;
								return stripslashes($v);
							}
						}
					}
			}
			return 0;
		}
		function _getKey($value){
			if (is_null($this->parametri)) {
				return;
			}
			$tmp=array();
			foreach($this->parametri as $key=>$val){
				$ris=$this->_get_pkey($key);
				foreach($ris as $val){
					$v=$this->_get_pkey_value($val);
					if($v)
						$tmp[$val]=$v;
					elseif ($value) $tmp[$val]=stripslashes($value);
				}
				$this->levKey[$key]=$tmp;
				$tmp=null;
			}
		}

		// Metodo che prende le configurazioni della pagina da Database
		public function get_conf(){
                        $sqlParam = array();
			if(!$this->livello) $lev="root";
			else
				$lev=$this->livello;
				
			if ($this->mode==self::MODE_VIEW or $this->mode==self::MODE_LIST)
				$filter_mode="(mode=0 or mode=3)";
                        else {
                                $sqlParam[':mode'] = $this->mode;
                                $filter_mode='(mode=:mode)';
                        }
                        $sql="select e_form.name as form_name,e_form.save_data,config_file,tab_type,form_destination,e_form.parent_level,foo.parent_name,e_level.name as level,e_form.js as javascript,order_fld,coalesce(foo.depth,-1) 
                            from ".DB_SCHEMA.".form_level left join ".DB_SCHEMA.".e_form on (form_level.form=e_form.id) 
                            left join ".DB_SCHEMA.".e_level on (e_form.level_destination=e_level.id) 
                            left join ".DB_SCHEMA.".e_level as foo on (form_level.level=foo.id) 
                            where ".$filter_mode." 
                            and foo.name=:lev
                            and visible=1 
                            and :admintype <= e_level.admintype_id 
                            order by e_level.depth,order_fld;";

                        $sqlParam[':lev'] = $lev;
                        $sqlParam[':admintype'] = $this->admintype;
			
                        print_debug($sql,null,"conf");
                        print_debug($sqlParam,null,"conf");
                        $stmt = $this->db->prepare($sql);
                        $success = $stmt->execute($sqlParam);
			
                        if (!$success) {
				print_debug($sql,null,'error');
				echo "<p>Errore nella configurazione del sistema</p>";
				exit;
			}
                        $res=$stmt->fetchAll();
			
                        // FIXME: column menu_field does not exist
                        // $sql="select id as val,name as key,menu_field as field from ".DB_SCHEMA.".e_level order by id";
                        $sql="select id as val,name as key from ".DB_SCHEMA.".e_level order by \"order\"";
                       
                        $stmt = $this->db->prepare($sql);
                        $success = $stmt->execute();

			$arr_livelli=$stmt->fetchAll();
			foreach($arr_livelli as $value){
				list($lvl_id,$lvl_name)=array_values($value);
				$this->navTreeValues[$lvl_name] = 'XXX';
				// list($lvl_id,$lvl_name,$lvl_header)=array_values($value);
				// see obive FIXME: $this->navTreeValues[$lvl_name]=$lvl_header;
				$livelli[$lvl_id]=Array("val"=>$lvl_id,"key"=>$lvl_name);
			}
			unset($this->tableList);			
			
			for($i=0;$i<count($res);$i++){
				$res[$i]["parent_level"]=isset($livelli[$res[$i]["parent_level"]])?$livelli[$res[$i]["parent_level"]]:null;
				$this->tableList[]=$res[$i];
			}
		}
		
		//Metodo che scrive il menu di navigazione
		function writeMenuChild(){	//Da Fare!!!!!!
			
		}
		
		function writeMenuNav(){
			$rel_dir = GCAuthor::getTabDir();
			
			$tmp=parse_ini_file(ROOT_PATH.$rel_dir.'menu.tab',true);
			$this->navTreeValues=$tmp;
			//print_array($this->navTreeValues);
			$lbl="<a class=\"link_label\" href=\"#\" onclick=\"javascript:navigate([],[])\">Admin</a>";
			$n_elem=count($this->parametri);
			if ($n_elem>0){
				$lvl=Array();
				$val=Array();
				foreach($this->parametri as $key=>$value){
					$sqlParam = array();
					array_push($lvl,$key);
					array_push($val,$value);
					$pk=$this->_get_pkey($key);
					//echo '<pre>'; var_export($this->navTreeValues);
					if(($this->mode==2 || !isset($this->navTreeValues[$key]["standard"])) && $key==$this->livello){
						$navTreeTitle=trim($this->navTreeValues[$key]["constant"], "'");
					}
					else{
						$filter=Array();
						$i=0;
						foreach($pk as $v){
							$value=$this->_get_pkey_value($v); 
							if($value) {
								$filter[]=sprintf("%s=:VALUE%d", $v, $i);
								$sqlParam[sprintf(":VALUE%d", $i)] = $value;
								$i++;
							}
						}
						$xml = new ParseXml();
						$xml->LoadFile(PK_FILE);
						$struct=$xml->ToArray();
						$table=$struct[$key]["table"];
						$schema=(in_array($key,Array("users","groups","user_group")))?(USER_SCHEMA):(DB_SCHEMA);						
						$sql = "SELECT coalesce(CAST(".$this->navTreeValues[$key]["standard"]." AS varchar),'') as val 
							FROM ".$schema.".".$table;
						if(!empty($filter)) {
							$sql .= " WHERE ".implode(' AND ',$filter);
						}

						$stmt = $this->db->prepare($sql);
						$success = $stmt->execute($sqlParam);
						if(!$success){
							print_debug($sql,null,"navtree");
						}
						$_row=$stmt->fetch(PDO::FETCH_ASSOC);
						$navTreeTitle = $_row['val'];
					}

					if ((is_numeric($value) && $value>0) || (!is_numeric($value) && strlen($value)>0))
						$lbl.="<a class=\"link_label next\" href=\"#\" onclick=\"javascript:navigate(['".@implode("','",$lvl)."'],['".@implode("','",$val)."'])\"> $navTreeTitle</a>";
					else
						$lbl.="<a class=\"link_label next\" href=\"#\"> $navTreeTitle</a>";
				}
			}
			echo "
			<form name=\"frm_label\" id=\"frm_label\" method=\"POST\">
				".$lbl."
			</form>";
		}
		
		// Metodo privato che setta i parametri della classe
		function _get_parameter(array $p){
			$m=(!empty($p["mode"]))?($p["mode"]):('view');
			$this->mode=$this->arr_mode[$m];
			if (!empty($p["parametri"])){
				
				for($i=0;$i<count($p["parametri"]);$i++){
					$arr=$p["parametri"][$i];
					$val=each($arr);
					if(preg_match("|^'(.+)'$|",stripslashes($val["value"]),$match)) $this->parametri[$val["key"]]=$match[1];
					else
						$this->parametri[$val["key"]]=$val["value"];
				}
			}

			if (!empty($p["parametri"]) > 0) {
				$lastParams = array_keys(array_pop($p["parametri"]));
				$this->last_livello=array_pop($lastParams);
			} else {
				$this->last_livello="project";
			}
			$this->livello=(!empty($p["livello"]))?($p["livello"]):("");
			if (!empty($p["azione"])){
				$this->action=strtolower($p["azione"]);
				if (in_array($this->action, array("esporta", "esporta test", "importa raster", "importa catalogo"))) {
					$this->mode=$this->arr_mode["edit"];
				} elseif (in_array($this->action, array("importa", "wizard wms", "classifica"))) {
					$this->mode=$this->arr_mode["new"];
				}
			}
		}
		
		function write_parameter(){
			
			if(is_array($this->parametri) && count($this->parametri)){
				$i=0;
				foreach ($this->parametri as $key=>$val){
					echo "\t<input type=\"hidden\" name=\"parametri[$i][$key]\" id=\"$key\" value=\"".stripslashes($val)."\">\n";
					$i++;
				}
			}
		}
		
		function write_page_param($param){
			
			if (count($param)>0){
			foreach($param as $key=>$value)
				if ($value) echo "\t<input type=\"hidden\" name=\"$key\" value=\"".stripslashes($value)."\" id=\"prm_$key\">\n";
			}
		}
		
		function get_livello(){
			if(count($this->parametri)){
				$lvl=array_keys($this->parametri);
				return $lvl[count($lvl)-1];
			}
			else
				return "";
		}
		function get_value(){
			if(count($this->parametri)){
				$tmp=array_keys($this->parametri);
				return $this->parametri[$tmp[count($this->parametri)-1]];
			}
			else
				return 0;
		}
		
		function get_parentValue(){
			if(count($this->parametri)>1){
				$tmp=array_keys($this->parametri);
				return $this->parametri[$tmp[count($this->parametri)-2]];
			}
			else
				return 0;
		}
		function get_idLivello($lev=""){
			if(!$lev){
                                $sql="SELECT id FROM ".DB_SCHEMA.".e_level WHERE name=:livello";
                                $stmt = $this->db->prepare($sql);
                                $success = $stmt->execute(array($this->livello));

                                if ($success) {
                                    $row=$stmt->fetch(PDO::FETCH_ASSOC);
                                    return $row['id'];
                                }
			}
			else{
				foreach($this->array_levels as $key=>$value){
					if ($value["name"]==$lev)
						return $key;
				}
				return null;
			}
			
		}
		function _getChild(){
			$out=Array();
			foreach($this->array_levels as $key=>$val) if($val["parent"]==$this->livello) $out[]=$val;
			return $out;
		}
		function writeAction($mode){
			$lev=$this->livello;
			require_once ADMIN_PATH."lib/filesystem.php";
			$dir=ADMIN_PATH."export/";
			$tmp=elenco_file($dir,"sql");
			for($i=0;$i<count($tmp);$i++){
				$list=file($dir.$tmp[$i]);
				if (strtolower(trim($list[1]))=="--type:$lev"){
					$rows[]="<tr><td><input type=\radio\"></td><td></td><td></td></tr>";
				}
			}
			include ADMIN_PATH."inc/import.php";
		}
		function setErrors($err){
			foreach($err as $key=>$val){
				$this->errors[$key]=$val;
			}
		}
		function setNotice($notice){
			foreach($notice as $val)
				if ($val) $this->notice[]=$val;
		}
		
		//Metodo che scrive il div dei Messagii e Errori Generici
		private function writeMessage($msg){
			if(!empty($this->errors["generic"]) || !empty($msg["generic"]) || $this->notice){
				$generic=Array();
				for($i=0;$i<count($this->notice);$i++)
					if ($this->notice[$i]) $generic[]=$this->notice[$i];
				for($i=0;$i<count($this->errors["generic"]);$i++)
					if ($this->errors["generic"][$i]) $generic[]=$this->errors["generic"][$i];
				for($i=0;$i<count($msg["generic"]);$i++)
					if ($msg["generic"][$i]) $generic[]=$msg["generic"][$i];
				echo "<div id=\"error\" class=\"errori\" style=\"width=100%;color:red;font-weight:bold;\"><ul><li>".@implode("</li><li>",$generic)."</li></ul></div>";
			}
		}
		
		//Metodo che scrive il Form in modalit� List  Elenco dei Child
		
		private function writeListForm(array $tab,$el,&$prm){
            $user = new GCUser();
			switch ($tab["tab_type"]){
				case 0:	//elenco con molteplici valori (TABELLA H)
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					
					if (is_array($el) && $el["value"] && $tab["parent_name"]) $filter=$tab["parent_name"]."_name = ".$this->db->quote($el["value"]);
					
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					
					foreach($this->pageKeys as $key) if ($el["value"]) $flt[]="$key = ".$this->db->quote($el["value"]);
					$filter=@implode(" AND ",$flt);
					if($tab["level"]=="project" && !$user->isAdmin() && defined('USER_SCHEMA'))
						$filter="project_name in (SELECT DISTINCT project_name FROM ".DB_SCHEMA.".project_admin WHERE username=".$this->db->quote($user->getUsername()).")";
					$butt="nuovo";
					if($tab["level"]=="project" && $this->admintype==2) $butt="";
					if($tab["level"]=='tb_logs') $butt="";
					//$tb->set_titolo($tab["title"],$butt,$prm,20);
					$tb->set_titolo($tb->FileTitle,$butt,$prm,20);
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,(isset($tab["order_by"]))?$tab["order_by"]:null);
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 2:	//elenco con molteplici valori (TABELLA H) che porta alla modifica tramite Aggiungi
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$this->db->quote($el["value"]);
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					foreach($this->pageKeys as $key) if ($el["value"]) $flt[]="$key = ".$this->db->quote($el["value"]);
					$filter=@implode(" AND ",$flt);
					switch($tab["level"]){
						case "project_groups":
							$filter.=" AND NOT group_name ilike 'gisclient_author'";
							break;
						case "mapset_link":
							$filter.=" AND presente>0";
							break;
						default:
							break;
					}
					$tb->set_titolo($tb->FileTitle,"modifica",$prm);
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,isset($tab["order_by"])?$tab["order_by"]:null);
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 3:	// Elenco con un solo valore(TABELLA H)
					
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_name = ".$this->db->quote($el["value"]);
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_dati($filter);
					if ($tb->num_record==0){
						$tb->set_titolo($tb->FileTitle,"nuovo",$prm);
					}	
					else{
						foreach($tb->pkeys as $key) $prm["parametri[][".$tab["level"]."]"]=$tb->array_dati[0][$key];	//Passo i valori delle Primary Key
						if($tab["level"]!='tb_import')
							$tb->set_titolo($tb->FileTitle,"modifica",$prm);
						else
							$tb->set_titolo($tb->FileTitle,"",$prm);
					}
					$tb->tag=$tab["level"];
					
					$tb->get_titolo();
					$tb->elenco();
					break;
					
				case 4:	//elenco con molteplici valori (TABELLA H) dove si include un file di configurazione
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="-1";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$this->db->quote($el["value"]);
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$data=Array();
					$enabled=1;
					if ($tab["save_data"])
						include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";

					$tb->set_titolo($tb->FileTitle,"modifica",$prm);
					
					$tb->tag=$tab["level"];
					$tb->set_multiple_data($data);
					$tb->get_titolo();
					$tb->elenco();
							
					break;
					
				case 5:
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					if (is_array($el) && $el["value"] && $tab["parent_name"]) $filter=$tab["parent_name"]."_name = ".$this->db->quote($el["value"]);
					$tb=new Tabella_h($tab["config_file"].".tab","list");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->tag=$tab["level"];
					$tb->set_dati($filter,$tab["order_by"]);
					$tb->get_titolo();
					$tb->elenco();
					break;
			}
		}
		
		//Metodo che scrive il Form in modalit� View
		
		private function writeViewForm($tab,$el,&$prm){
			$frm = '';
			switch ($tab["tab_type"]){
				case 1:	// MODALITA' VIEW STANDARD (TABELLA V)
					$tb=new Tabella_v($tab["config_file"].".tab","view");

					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];

					$e=array_pop($this->levKey);
					if (is_null($e))
					    $e = array();
					foreach($e as $k=>$v){
						//$flt[]="$k='".addslashes($v)."'";
						$flt[]="$k=".$this->db->quote($v);
					}
					$filter=@implode(" AND ",$flt);
					if(trim($tab["form_destination"])) $frm=trim($tab["form_destination"]);
					$tb->set_dati($filter);
					$prm["livello"]=$tab["level"];
					if ($tb->num_record>0){
						
						for($j=0;$j<count($tb->pkeys);$j++){
							$tb->pkeys_value[$j]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
						}
						$b="modifica";
						$tb->set_titolo($tb->FileTitle,$b,$prm);
						$tb->get_titolo($frm);
						$tb->tab();
					}
					else{
						$b="nuovo";
						$tb->set_titolo($tb->FileTitle,$b,$prm);
						$tb->get_titolo($frm);
							echo "<p><b>".GCAuthor::t('nodata')."</b></p>";
						}
					break;
					
				case 50: // MODALITA' VIEW Inclusione File(TABELLA V)
					$tb=new Tabella_v($tab["config_file"].".tab","view");
					$data=Array();
					include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					
					if(trim($tab["form_destination"])) $frm=trim($tab["form_destination"]);
					$tb->set_dati(isset($data[0])?$data[0]:null);
					$prm["livello"]=$tab["level"];
					for($j=0;$j<count($tb->pkeys);$j++){
						$tb->pkeys_value[$j]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
					}
					$tb->set_titolo($tb->FileTitle,$button,$prm);
					$tb->get_titolo($frm);
					$tb->tab();
					break;
			}
			echo "<hr>\n";
		}
		
		/**
		 * Metodo che scrive il formulario in modalita EDIT
		 * 
		 * @param array $tab
		 * @param type $el
		 * @param type $prm
		 * 
		 * @throws RuntimeException
		 */
		private function writeEditForm(array $tab,$el,&$prm){
			switch ($tab["tab_type"]){
				case 110:
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$prm["mode"]="new";

					$tb=new Tabella_h($tab["config_file"].".tab","list");
					foreach($tb->pkeys as $key=>$value) if ($el["value"]) $flt[]="$key = ".$this->db->quote($el["value"]);
					$filter=@implode(" AND ",$flt);
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$button=@implode("\n\t\t",$btn);
					
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->mode="edit";
					if($tab["level"]=="layer_link") {
						$filter=$tab["parent_name"]."_id = ".$this->db->quote($this->parametri[$tab["parent_name"]]);
						$tb->tag=Array("pkey"=>"link","pkey_value"=>0);
					}
					$tb->set_dati($filter);
					$tb->elenco();
					echo "<hr>$button";
					echo "</form>";
					break;
					
				case 100: //Tabella H per elencare tutti i valori possibili e quelli selezionati
					$prm["livello"]=$tab["level"];
					$prm["savedata"]=$tab["save_data"];
					$tmp=array_values($this->parametri);
					$parent_key=$tmp[count($tmp)-2];
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					switch($tab["level"]){
						case "mapset_layergroup":
							$param="layergroup";
							break;
						case "mapset_usergroup":
							$param="usergroup";
							break;
						case "mapset_qt":
							$param="qt";
							break;
						case "mapset_link":
							$param="link";
							break;
						case "qt_selgroup":
							
							$param="qt";
							break;
						case "project_groups":
							$filter.=" NOT group_name ilike 'gisclient_author'";
							break;
						case "layer_groups":
							$param="layer_groups";
							$filter="";
							break;
						case "":
							$param="";
							break;
						default:
							break;
					}
					
					$btn[]="\n\t<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"".GCAuthor::t('button_cancel')."\">";
					$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"".GCAuthor::t('button_save')."\">";
					//$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Tutti\" onclick=\"javascript:selectAll(this,'$param');\">\n";
					$tb->get_titolo();
					$tb->set_dati($filter);
					$tb->elenco();
					$button=@implode("\n\t\t",$btn);
					
					echo "<hr>$button";
					echo "\n<input type=\"hidden\" name=\"save_type\" value=\"multiple\">";
					echo "</form>";
					break;
					
				case 0:		//SERVE PER ELENCARE I VALORI IN FUNZIONE DEL PARENT (TABELLA H)
					
					$prm["livello"]=$tab["level"];
					$tmp=array_values($this->parametri);
					$parent_key=$tmp[count($tmp)-2];
					$filter=$tab["parent_level"]["key"]."_id = ".$this->db->quote($parent_key);
					$prm["parametri[][".$tab["level"]."]"]="";
					
					$tb=new Tabella_h($tab["config_file"].".tab",$mode);
					
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					
					$tb->get_titolo();
					$tb->set_dati($filter);
					$tb->elenco();
				
					break;
				case 1:	//MODALITA' STANDARD
				case 50:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
									
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];	
					$j=0;
					$e=array_pop($this->levKey);
					$j=0;
					foreach($e as $k=>$v){
						$flt[]="$k=".$this->db->quote($v);
						$prm["pkey[$j]"]=$k;
						$prm["pkey_value[$j]"]=$v;
						$j++;
					}
					$j=0;					
					$filter=@implode(" AND ",$flt);
					
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					else{						
						if($tab["tab_type"]==1)
							$tb->set_dati($filter);
						else{
							include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
							$tb->set_dati(isset($data[0])?$data[0]:null);
						}
					}
					$tb->set_titolo($tb->FileTitle,"",$prm);
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->get_titolo();
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 2:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=$tb->pkeys[$j];
						$prm["pkey_value[$j]"]=$this->_get_pkey_value($tb->pkeys[$j]);
					}
					$e=array_pop($this->levKey);
					foreach($e as $k=>$v){
						$flt[]="$k=".$this->db->quote($v);
					}
					$filter=@implode(" AND ",$flt);
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 4:
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_v($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					include_once ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=$tb->pkeys[$j];
						$prm["pkey_value[$j]"]=$this->_get_pkey_value($tb->pkeys[$j]);
					}
					$filter=$tab["parent_name"]."_id = ".$this->db->quote($el["value"]);
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					$tb->edita();
					$this->write_page_param($prm);
					echo "</form>";
					break;
				case 10:	// Caso di Form AGGIUNGI  DA DEFINIRE
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$prm["mode"]="new";
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					switch($tb->tabelladb){
						case "vista_mapset_layergroup":
							$filtro="mapset_id in (0,".$this->parametri["mapset"].") and project_id=".$this->parametri["project"]." ORDER BY layergroup_name;";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Layer\" onclick=\"javascript:selectAll(this,'layergroup');\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Status\" onclick=\"javascript:selectAll(this,'status');\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:130px;margin-right:5px;margin-left:5px;\" value=\"Seleziona RefMap\" onclick=\"javascript:selectAll(this,'refmap');\">";
							break;
						case "vista_qt_selgroup":
						case "vista_selgroup":
							$filtro="project_id=".$this->parametri["project"];
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Annulla\">";
							$btn[]="<input type=\"submit\" name=\"azione\" class=\"hexfield\" style=\"margin-right:5px;margin-left:5px;\" value=\"Salva\">";
							$btn[]="<input type=\"button\" name=\"azione\" class=\"hexfield\" style=\"width:180px;margin-right:5px;margin-left:5px;\" value=\"Seleziona Query Template\" onclick=\"javascript:selectAll(this,'qt');\">";
							break;
						case "user_project":
							$filtro="user_id=".$this->db->quote($this->parametri["user"]);
							break;
						default:
							$filtro=($tab["parent_level"]["val"])?(" ".$tab["parent_level"]["key"]." = ".$this->db->quote($tab["parent_level"]["val"])):("");
							break;
					}
					$button=@implode("\n\t\t",$btn);
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->set_dati($filtro);
					
					$tb->elenco();
					echo "<hr>$button";
					echo "</form>";
					break;
				case 5:		//CON FILE DI INCLUSIONE (TABELLA H)
					foreach($prm as $key=>$val){
						if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
							$prm[$ris[2]]=$val;
						}
					}
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					$tb=new Tabella_h($tab["config_file"].".tab","edit");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$msg="";
					
					// do some basic checks!
					if (!isset($tab["save_data"])) {
						throw new RuntimeException("save_data not set in tab");
					}
					
					$includeFile = ADMIN_PATH."include/".$tab["save_data"].".inc.php";
					if (!file_exists($includeFile)) {
						throw new RuntimeException("can not find include file for '{$tab["save_data"]}': $includeFile not found");
					}
					
					include_once $includeFile;
					
					for($j=0;$j<count($tb->pkeys);$j++){
						$prm["pkey[$j]"]=isset($tb->pkeys[$j])?$tb->pkeys[$j]:null;
						$prm["pkey_value[$j]"]=isset($tb->pkeys[$j])?$this->_get_pkey_value($tb->pkeys[$j]):null;
					}
					$filter=$tab["parent_name"]."_id = ".$this->db->quote($el["value"]);
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					//$tb->set_titolo($tab["title"],"",$prm);
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					if (is_array($data) && !$data && !$msg) $data=$filter;
					
					$tb->set_multiple_data($data);
					$tb->elenco($msg);
					if(count($btn)) $button=implode("\n\t",$btn);
					echo "<hr>$button";
					echo "<input type=\"hidden\" name=\"save_type\" value=\"multiple\">";
					echo "</form>";
					break;
					
			}	
		}
		//Metodo che scrive il Form in modalita NEW
		private function writeNewForm($tab,$el,&$prm){
			$j=0;
			foreach($this->pageKeys as $v){
				$prm["pkey[$j]"]=$v;
				$j++;
			}
			$j=0;
			switch ($tab["tab_type"]){
				
				case 0:
					$prm["livello"]=$tab["level"];
					$prm["parametri[][".$tab["level"]."]"]="";
					if (is_array($el) && $el["value"]) $filter=$tab["parent_name"]."_id = ".$this->db->quote($el["value"]);
					$tb=new Tabella_h($tab["config_file"].".tab","new");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->tag=$tab["level"];
					$tb->get_titolo();
					$tb->elenco();
					break;
				case 50:
				case 1:
				case 2:
					$prm["livello"]=$tab["level"];
					$prm["config_file"]=$tab["config_file"].".tab";
					$prm["savedata"]=$tab["save_data"];
					if($this->action=='wizard wms'){
						$prm["config_file"]="layergroup_wms.tab";
						$prm["savedata"]="layergroup_wms";
						$tab["title"]="Nuovo Layergroup da WMS";
					}
					$tb=new Tabella_v($prm["config_file"],"new");
					for($j=0;$j<count($tb->function_param);$j++) $tb->function_param[$j]=$this->parametri[$tb->function_param[$j]];
					if(count($this->errors)){
						$tb->set_errors($this->errors);
						$tb->set_dati($_POST["dati"]);
					}
					echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
					$tb->set_titolo($tb->FileTitle,"",$prm);
					$tb->get_titolo();
					$tb->edita($prm);
					$this->write_page_param($prm);
					echo "</form>";
					break;
			}		
		}
		
		// Metodo che costruisce la pagina
		public function writePage(array $err=Array()){
			
			//Stampa errori generici e messaggi se ci sono
			$this->writeMessage($err);
			if(!empty($this->tableList)){
				/*RECUPERO I DATI DELLA TABELLA PRIMARIA*/
				$table=new Tabella_v($this->tableList[0]["config_file"].".tab");
				$this->pageKeys=array_keys($table->pkeys);
				if (!is_null($this->parametri)) {
					foreach($table->pkeys as $k=>$v) 
						foreach($this->parametri as $k1=>$v1)
							if(preg_match("/(".$k1."_id|".$k1."_name)/Ui",$k))
								if(!empty($_POST["dati"][$k])) $this->parametri[$k1]=$_POST["dati"][$k];
				}
				unset($table);
				
				for($i=0;$i<count($this->tableList);$i++){
					
					$el=@each(@array_reverse($this->parametri,true));
					$this->_getKey($el["value"]);
					$prm=$this->_get_frm_parameter();
					//VALORIZZO SE PRESENTI I PARAMETRI DELLE FUNZIONI DI SELECT
					$tab=$this->tableList[$i];
					switch ($this->mode){		//IDENTIFICO LA MODALITA DI VISUALIZZAZIONE 0:VIEW --- 1:EDIT --- 2:NEW
						
						case self::MODE_VIEW:					// MODALITA VIEW
						case self::MODE_LIST:					// MODALITA LIST
							if($tab["tab_type"]==1 || $tab["tab_type"]==50) {
								$this->currentMode='view';
								$this->writeViewForm($tab,$el,$prm);
							}
							else{
								$this->currentMode='list';
								$this->writeListForm($tab,$el,$prm);
							}
							break;
						case self::MODE_EDIT:					//MODALITA EDIT
							$this->currentMode='edit';
							$prm["modo"]="edit";
							$prm["livello"]=$tab["level"];
							$prm["config_file"]=$tab["config_file"].".tab";
							switch ($this->action){
								
								case "importa raster":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/import_raster.php";
									$this->write_page_param($prm);
									echo "</form>";
									echo $resultForm;
									break;
								case "wizard wms":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/import_raster.php";
									$this->write_page_param($prm);
									echo "</form>";
									echo $resultForm;
									break;
									break;
								case "esporta test":
								case "esporta":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->parametri[$tab["level"]];
									include ADMIN_PATH."include/export.php";
									$this->write_page_param($prm);
									echo "</form>";
									if(isset($resultForm)) echo $resultForm;
									break;

                                case "importa catalogo":
                                    include ADMIN_PATH."include/catalog_import.php";
                                    break;

								default:
									$this->writeEditForm($tab,$el,$prm);
									break;
							}
							break;
						case self::MODE_NEW:					////MODALITA NEW
							$prm["modo"]="new";
							$this->currentMode='new';
							foreach($prm as $key=>$val){
								if(preg_match("|parametri[\[]([\d]+)[\]][\[]([A-z]+)[\]]|i",$key,$ris)){
									$prm[$ris[2]]=$val;
								}
							}
							
							switch ($this->action){
								case "classifica":
									//echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\" class=\"\">";
									
									$level=$this->get_idLivello();
									
									$livello=$this->livello;
									$prm['layer']=$this->parametri[$livello];
									//$prm["pkey[0]"]='layer_id';
									//$prm["pkey_value[0]"]=
									include ADMIN_PATH."include/classify.php";
									$this->write_page_param($prm);
									
									
									break;
								case "importa":
									echo "<form name=\"frm_data\" id=\"frm_data\" enctype=\"multipart/form-data\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"POST\">";
									$level=$this->get_idLivello();
									$project=$this->parametri["project"];
									$objId=$this->get_parentValue();
									$livello=$this->livello;
									include ADMIN_PATH."include/import.php";
									$this->write_page_param($this->parametri);
									$this->write_parameter(array_pop($this->parametri));
									echo "</form>";
									echo $resultForm;
									break;
								default:	
									$this->writeNewForm($tab,$el,$prm);
									break;
							}
							break;
						
					}
					
					if($tab["javascript"]){
						echo "<script>\n\t".$tab["javascript"]."('".$tab["form_name"]."');\n</script> \n";
					}
				}
				$arr_keys=(count($this->parametri))?(array_keys($this->parametri)):(Array());
				
				if(($this->mode==self::MODE_VIEW || $this->mode==self::MODE_LIST) && !empty($arr_keys[0])){
					$tmp=$this->parametri;
					array_pop($tmp);
					$arrkeys=array_keys($tmp);
					$arrvalues=array_values($tmp);
					$keys=(count($arrkeys))?("'".implode("','",$arrkeys)."'"):("");
					$values=(count($arrvalues))?("'".implode("','",$arrvalues)."'"):("");
					
					$btn  = "\n\t<div id=\"footerButton\">";
					$btn .= "<input type=\"button\" class=\"hexfield\" value=\"".GCAuthor::t('button_back')."\" onclick=\"javascript:navigate([$keys],[$values])\">";
					if($this->initI18n()) {
						$btn .= "<input type=\"button\" class=\"hexfield\" id=\"i18n\" value=\"".GCAuthor::t('translations')."\">";
					}
					$btn .= "</div>";
					echo $btn;
				}
			}
			else
				echo "<p>Nessun configurazione definita per la pagina</p>";
			//$this->showTime();
		}
		
		function getTime($str){
			$tmp=explode(" ",microtime());
			$t=$tmp[0]+$tmp[1];
			$this->time[$str]=$t;
		}
		
		function showTime(){
			print_debug($this->time,null,'TIME');
		}
		
		public function initI18n() {
			if(isset($this->parametri['project'])) {		
				$localization = new GCLocalization($this->parametri['project']);
				if($localization->hasAlternativeLanguages($this->livello) && $this->mode == 0) {
					return true;
				}
			}
			return false;
		}
	}
