<?php
include_once ADMIN_PATH."lib/tabella.class.php";

class Tabella_h extends Tabella{

var $def_col;//definizione delle colonne
var $colore_colonne="#E7EFFF";//"#CCCCCC";
var $color_title="#728bb8";
var $color_head_font="#415578";
var $color_title_font="#FFFFFF";
var $info_target;//pagina di destinazione per il link info
var $img_punto; //nome del gif da usare come punto elenco della tabella


function set_target($target){
	$this->info_target=$target;
}
function set_punto($image){
	$this->img_punto=$image;
}

function set_color($intestazione,$font_intestazione,$titolo,$font_titolo){
// ************ da fare********************usare class?????	
	//aggiungere stile
	$this->colore_colonne=$intestazione;
	$this->color_head_font=$font_intestazione;
}

function get_cella($row,$col){
	$nome=$this->def_col[$col][1]; //nome del campo
	//$valore=htmlspecialchars($this->array_dati[$row][$nome], ENT_QUOTES,"ISO-8859-1");//valore del campo
	$valore = $this->array_dati[$row][$nome];
	$w=$this->def_col[$col][2];//larghezza del campo
	$tipo=trim($this->def_col[$col][3]);//tipo del campo
	
	switch ($tipo){//tipo campo in configfile
		case "hidden":
			$retval="";
			break;
		case "idriga":
			$retval="<td><input type=\"hidden\" name=\"idriga\" value=\"$valore\" ></td>\n";
			break;
	
		case "text":
			//$valore=html_entity_decode($valore);
			$retval="<td>$valore</td>\n";
			break;
			
	//	Modificato Marco
		case "ora":
			$valore=number_format($dati[$campo],2, ':', '');
			if ($valore!=0)	
				$retval="<td>$valore</td>\n";
			else
				$retval="<td>---</td>\n";
			break;
		case "numero":
			$valore=number_format($valore,4, ',', '.');
			if ($valore!=0)	
				$retval="<td>$valore</td>\n";
			else
				$retval="<td>---</td>\n";
			break;
			
		case "valuta":
			if ($valore){
				$valore=number_format($valore,2, ',', '.');
				$retval="<td>€ $valore</td>\n";
			}
			else
				$retval="<td>---</td>\n";
			break;
		case "superficie":
			if ($valore!=0)	
				$retval="<td>$valore mq</td>\n";
			else
				$retval="<td>---</td>\n";
			break;
		case "volume":
			if ($valore!=0)	
				$retval="<td>$valore mc</td>\n";
			else
				$retval="<td>---</td>\n";
			break;
	// Fine Modifica
		case "data":
			$data=$this->date_format(stripslashes($valore));
			$retval="<td>$data</td>\n";
			break;	
			
		case "checkbox":
			(($valore=="t") or ($valore==1))?($selezionato="checked"):($selezionato="");
			
			if ($valore==-1)
				$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input type=\"checkbox\" name=\"$valore\" id=\"$valore\" value=\"$nome\" disabled checked></td>\n";
			else
				$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input type=\"checkbox\" name=\"$valore\" id=\"$valore\" value=\"$nome\" $selezionato></td>\n";
			break;
			
		case "checkbox_chk":
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input type=\"checkbox\" name=\"$valore\" id=\"$valore\" value=\"$nome\" checked=\"checked\"></td>\n";
			break;
		case "check":
			$size=explode("#",$this->def_col[$col][2]);
			$selected=($this->array_dati[$row][$size[1]]>0)?("checked"):("");
			$id=$this->array_dati[$row][$nome];
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input width=\"7\" type=\"checkbox\" name=\"dati[$row][$nome]\" value=\"$valore\" $selected></td>\n";
			break;
		case "check1":
			$size=explode("#",$this->def_col[$col][2]);
			$group=$this->array_dati[$row][$size[1]];
			$id=$this->array_dati[$row][$size[2]];
			
			$selected=($id>0)?("checked"):("");
			$strValore=($valore)?("value=\"$valore\""):("value=\"1\"");
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\" style=\"text-align:center;\"><input width=\"7\" type=\"checkbox\" name=\"dati[$group][$nome]\" $strValore $selected></td>\n";
			break;
		case "radio":
			$id=$this->array_dati[$row]["id"];
			(($valore=="t") or ($valore==1))?($selezionato="checked"):($selezionato="");
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input width=\"7\" type=\"radio\" name=\"$id\" value=\"$nome\" $selezionato></td>\n";
			break;
		case "radio_sino":
			$selected=($valore>0)?(Array("checked","")):(Array("","checked"));
			$val=($valore>0)?(1):(-1);
			$size=explode("#",$this->def_col[$col][2]);
			$id=$this->array_dati[$row][$size[1]];
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><table border=\"0\"><tr><td><input width=\"7\" type=\"radio\" name=\"dati[$size[1]][$id]\" value=\"1\" $selected[0]>Si&nbsp;</input></td><td><input width=\"7\" type=\"radio\" name=\"dati[$size[1]][$id]\" value=\"-1\" $selected[1]>No&nbsp;</input></td></tr></table></td>\n";
			break;
		case "info":
			if($this->tag){
				$args=$this->tag;
				$jslink="link('".addslashes(html_entity_decode($valore,ENT_QUOTES))."','$args')";
			}
			else
				$jslink="link('".addslashes(html_entity_decode($valore,ENT_QUOTES))."')";
			
			$retval ="<td align=\"center\" valign=\"middle\" width=\"$w\" class=\"printhide\">";
			$retval.="<a class=\"button info\" href=\"javascript:$jslink\">Info</a>";
			$retval.="</td>\n";
			break;
		case "edit":
			if($this->tag){
				$args=$this->tag;
				$jslink="edit('".addslashes(html_entity_decode($valore,ENT_QUOTES))."','$args')";
			}
			else
				$jslink="edit('".addslashes(html_entity_decode($valore,ENT_QUOTES))."')";
			
			$retval ="<td align=\"center\" valign=\"middle\" width=\"$w\" class=\"printhide\">";
			$retval.="<a class=\"button edit\" href=\"javascript:$jslink\">Edit</a>";
			$retval.="</td>\n";
			break;
		case "delete":
			#echo '<pre>'; var_export($this); die();
			$pkeys = array();
			$values = array();
			//print_array($this->pkeys);print $row;
			foreach($this->pkeys as $pkey => $value) {
				array_push($pkeys, $pkey);
				array_push($values, $this->array_dati[$row][$pkey]);
			}
			$jsLink = "deleteRow('".implode(',',$pkeys)."', '".implode(',',$values)."', '".$this->array_hidden['livello']."', '".$this->config_file."')";
			
			$retval ="<td align=\"center\" valign=\"middle\" width=\"$w\" class=\"printhide\">";
			$retval.="<a class=\"button delete\" href=\"javascript:$jsLink\">Delete</a>";
			$retval.="</td>\n";
			break;
			
		
			break;		
		case "zoom":
			$jslink=$this->zoomto(trim($this->tabelladb),$valore);
			$retval="<td align=\"center\" valign=\"middle\" width=\"$w\" class=\"printhide\"><a href=\"javascript:$jslink\"><img src=\"images/zoom.gif\" border=\"0\"></a></td>\n";
			break;
		case "noyes":
		case "yesno":
			($valore==0)?($yn=GCAuthor::t('no')):($yn=GCAuthor::t('yes'));
			$retval="<td align=\"left\" valign=\"middle\"  width=\"$w\">$yn</td>\n";
			break;		
		
		case "delete":
			$id=$this->array_dati[$row][$nome];
			$keys=array_keys($this->pkeys);
			foreach($keys as $key){
				$prm[]="{pkey:'$key',pkvalue:'".$this->array_dati[$row][$key]."'}";
			}
			$jslink="elimina([".implode(",",$prm)."])";
			$retval=($this->mode=="view")?('<td  align=\"center\" valign=\"middle\"  width=\"$w\"  class=\"printhide\" >&nbsp;</td>'):("<td  align=\"center\" valign=\"middle\"  width=\"$w\"  class=\"printhide\" ><a href=\"javascript:$jslink;\"><img src=\"images/delete16.gif\" border=\"0\"></a></td>\n");
			break;
			
		case "semaforo":
			($valore)?($img="frossa"):($img="fblu");
			$retval="<td  align=\"center\" valign=\"middle\"  width=\"$w\"  class=\"printhide\" ><img src=\"images/$img.gif\" border=\"0\"></td>\n";
			
			break;
		case "image":
			$size=explode("#",$w);
			$dim=explode("x",$w);
			$table=$size[2];
			$v=$this->array_dati[$row][$size[1]];
			if (true)
				$retval="<td><a href=\"#\" onclick=\"javascript:window.open('getImage.php?id=$v&table=$table')\"><img src=\"getImage.php?id=$v&table=$table\" style=\"width:".$dim[0]."px;height:".$dim[1]."px;\"></a></td>";
			else
				$retval="<td>Nessuna immagine salvata $v</td>";
			break;	
		case "image2":
			$img=(!$valore)?("frossa"):($valore);
			$retval="<td  align=\"center\" valign=\"middle\"  width=\"$w\"  class=\"printhide\" ><img src=\"images/$img\" border=\"0\"></td>\n";
			
			break;
		//Genera un array di text indicizzati su id
		case "text_box":
			$data=$this->date_format(stripslashes($valore));
			$nome.="[".$this->array_dati[$row]["id"]."]";
			$retval="<td><input $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"$nome\" id=\"data\" value=\"$data\">$help"; 
			break;
			
		case "radio1":
			
			$id=$this->array_dati[$row][$size[1]];
			(($valore=="t") or ($valore==1))?($selezionato="checked"):($selezionato="");
			$retval="<td align=\"center\" valign=\"middle\" width=\"7\"><input width=\"7\" type=\"radio\" name=\"$nome\" value=\"$id\" $selezionato></td>\n";
			break;	
			
	
		case "punto":
			$p_image=$this->img_punto;
			$retval="<td align=\"center\" valign=\"middle\" width=\"$w\"><img src=\"images/$p_image.gif\" border=\"0\"></td>\n";
			break;	
		//crea  un array di text area con associata un'immagine che permette di visualizzare le text area. Di default è nascosta
		case "nota":
			$nome.="[".$this->array_dati[$row]["id"]."]";
			$imm="imm_".$nome;
			$retval="<td>&nbsp;&nbsp;<img border=\"0\" id=\"$imm\" height=\"12\" src=\"images/left.gif\" onclick=\"show_note('$nome','$imm')\">&nbsp;<span id=\"$nome\" style=\"display:none\"><textarea name=\"$nome\" cols=\"$w\" rows=\"2\">$valore</textarea>$help</span>"; 
			break;
		case "selectdb":
			$size=explode("#",$w);
			$retval="<td>&nbsp;".$this->get_chiave_esterna($valore,"id",$size[1],"opzione")."</td>";
			break;
		case "chiave_esterna":
			$size=explode("#",$w);
			
			$retval="<td>&nbsp;".$this->get_chiave_esterna($valore,$nome,$size[1],$size[2])."</td>";
			break;
		case "color":
			$val=str_replace(" ",",",trim($valore));
			if ($val)
				$retval="<td>".$valore."&nbsp;<span style=\"width:10px;height:10px;border:1px solid black;background-color:rgb($val);\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></td>";
			else
				$retval = "<td>".GCAuthor::t('undefined')."</td>"; 
			break;
		case "button":
			$size=explode("#",$w);
			$width=$size[0];
			$jsfunction=$size[1];
			$size=array_slice($size,2);
			if (!count($size)) $param="[]";
			else
				$param="['".@implode("','",$size)."']";
			//if(!$size[2]) $size[2]="[]";
			if(!$mode || $mode=="all" || $this->mode==$mode)
				$retval="\n\t\t\t<input class=\"hexfield\" style=\"width:".$width."px\" type=\"button\" value=\"$label\" onclick=\"javascript:$jsfunction('$campo',$param)\" >";
			break;
		
		case "submit":
			if (in_array(strtolower($label),Array("cancella","elimina")))
				$js="onclick=\"javascript:return confirm('Sei sicuro di voler eliminare il record?');\"";
			elseif (in_array(strtolower($label),Array("copia")))
				$js="onclick=\"javascript:return confirm('Sei sicuro di voler copiare il record?');\"";
			if(!$mode || $mode=="all" || $this->mode==$mode)
				$retval="\n\t\t\t<input  name=\"$campo\"  id=\"$campo\" class=\"hexfield\" style=\"width:".$w."px;display:$display\" type=\"submit\" value=\"$label\" $js >";
			break;
		case "goto":
			list($size,$param)=explode("#",$w);
			$id=addslashes($this->array_dati[$row][$param]);
			if($this->tag){
				$args=$this->tag;
				$jslink="link('$id','$args')";
			}
			else
				$jslink="link('$id')";
			if($this->mode=="view")
				$retval="<td valign=\"middle\" width=\"$size\" class=\"printhide\"><a style=\"text-decoration:none\" href=\"javascript:$jslink\">$valore</a></td>\n";
			else
				$retval="<td>$valore</td>\n";
			break;
	}
	return $retval;
}	


function elenco ($message = null){
	if(empty($message)) $message = GCAuthor::t('nodata');
	$ncols=$this->num_col;
	$all="left";
	$tabella="";
	if ($this->display_number > 0 && $this->num_record>$this->display_number){
		//$hide="<a  href=\"#\" onclick=\"javascript:displayAllRows('".$this->tabelladb."');\"><img id=\"img_$this->tabelladb\" src=\"./images/vis_all_btn2.gif\" border=\"0\"></a>";
		$hide="<a style=\"margin-left:600px;\"href=\"#\" onclick=\"javascript:displayAllRows('".$this->tabelladb."');\"><span id=\"txt_$this->tabelladb\">Visualizza Tutti</span></a>";
	}
	//Intestazione delle colonne
	$tabella.="
		<div><table class=\"stiletabella\">
			
			<tr class=\"ui-widget ui-state-default\">\n";

	//riga intestazione colonne ecreazione di def_col
	for ($i=0;$i<$ncols;$i++){
		$this->def_col[]=explode(";",$this->tab_config[$i][0]);//qui trovo la definizione della i-esima colonna 
		//if($i==($ncols-1) && $hide)
		//	$tabella.="\t\t\t\t<td align=\"$all\" width=\"".$this->def_col[$i][2]."\"><font face=\"Verdana\" color=\"$this->color_head_font\" size=\"1\"><b>".$this->def_col[$i][0]."</b></font>$hide</td>\n";		
		//else
		//if($this->def_col[$i][0])
			$tabella.="\t\t\t\t<td align=\"$all\" width=\"".$this->def_col[$i][2]."\"><b>".$this->def_col[$i][0]."</b></td>\n";		
		$all="left";
	}
	$tabella.="\t\t\t</tr>\n";

	for ($i=0;$i<$this->num_record;$i++){
		
		for ($j=0; $j<$ncols; $j++)
			$tabella.="\t\t\t\t".$this->get_cella($i,$j);
		 $tabella.="\t\t\t</tr>\n";
		 //$tabella.="\t\t\t<tr>\n\t\t\t\t<td colspan=\"$ncols\"><img src=\"images/gray_light.gif\" height=\"1\" width=\"99%\"></td>\n\t\t\t</tr>\n";			
	}
	
	if($this->num_record==0){
		$tabella.="\t\t\t<tr>\n";//CICLO SULLE COLONNE	
		$tabella.="<td colspan=\"".$ncols."\"><p><b>$message</b></p></td>";
		 $tabella.="\t\t\t</tr>\n";
	}	
		//fine righe di dettaglio
		$tabella.="\t\t</table></div>\n";
		print $tabella;
}

function elenco_h($t){
$ncols=$this->num_record;
$all="center";

	//Intestazione delle colonne
	$tabella="
		<table class=\"stiletabella\">
			<tr bgcolor=\"$this->colore_colonne\">\n";

	//riga intestazione colonne ecreazione di def_col
	for ($i=0;$i<$ncols;$i++){
		$this->def_col[]=explode(";",$this->tab_config[$i][0]);//qui trovo la definizione della i-esima colonna 
		$tabella.="\t\t\t\t<td width=\"".$this->def_col[$i][2]."\"><font face=\"Verdana\" color=\"$this->color_head_font\" size=\"1\"><b>".$this->def_col[$i][0]."</b></font></td>\n";		
		$all="left";
	}
	$tabella.="\t\t\t</tr>\n";
	$tabella.="\t\t\t<tr><td valign=\"middle\" class=\"printhide\">\n<b>$t&nbsp;:&nbsp;</b>";//CICLO SULLE COLONNE
	for ($i=0;$i<$this->num_record;$i++){
		$tabella.="\t\t\t\t".$this->get_cella($i,0)."&nbsp;&nbsp;";
	}
	$tabella.="</td>\t\t\t</tr>\n";
	//$tabella.="\t\t\t<tr>\n\t\t\t\t<td colspan=\"$ncols\"><img src=\"images/gray_light.gif\" height=\"1\" width=\"99%\"></td>\n\t\t\t</tr>\n";			
	//fine righe di dettaglio
	$tabella.="\t\t</table>\n";
	print $tabella;
}

	function get_chiave_esterna($val,$fld,$tab,$campo){
		if ($val==-1)
			return GCAuthor::t('undefined');
		elseif(!$val){
			
			switch($tab){
				case "e_searchtype":
					$fkey="Nessuna Ricerca";
					break;
				case "seldb_qtrelation":
				case "qtrelation":
					$fkey="Layer Data";
					break;
				case "symbol":
					$fkey="";
					break;
				default:
					$fkey=GCAuthor::t('undefined');
					break;
			}
			return $fkey;
		}
		else{
			$sql="SELECT $campo FROM $this->schemadb.$tab WHERE $fld='$val';";
			if (!isset($this->db)) $this->connettidb();
			print_debug($sql,null,"fkey");
                        $stmt = $this->db->prepare($sql);
                        $success = $stmt->execute();

			if(!$success)
                            print_debug("Errore Chiave Esterna\n".$sql,null,"error");

                        $row=$stmt->fetch(PDO::FETCH_ASSOC);
                        return $row[$campo];
		
		}
	}
	/*
	function get_chiave_esterna($val,$fld,$tab,$campo){
		$sql="SELECT $campo FROM $this->schemadb.$tab WHERE $fld='$val';";
		
		if (!isset($this->db)) $this->connettidb();
		print_debug($sql,null,"fkey");
		if(!$this->db->sql_query($sql))
			print_debug($sql,null,"tabella");
		
		return $this->db->sql_fetchfield($campo);
	}*/
}//end class






