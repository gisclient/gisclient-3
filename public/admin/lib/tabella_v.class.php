<?php
include_once ADMIN_PATH."lib/tabella.class.php";

class Tabella_v extends Tabella{

var $errors;
var $error_flag=0;
//var $rigagrigia="\t<tr>\n\t\t<td><img src=\"images/gray_light.gif\" height=\"1\" width=\"100%\"></td>\n\t</tr>\n";	
var $rigagrigia="";
var $tabella_elenco;//tabella dove prendo le opzioni per il tipo elenco

function set_tabella_elenco($nome_tabella){
	$this->tabella_elenco=$nome_tabella;
}

function set_errors($err){
	$this->errors=$err;
	$this->error_flag=1;
}
/*MODIFICA LOCK STATI AGGIUNTO PARAMETRO frozen*/
function get_controllo($label,$tipo,$w,$campo,$mode,$action='',$frozen=0){
//restituisce il controllo in funzione di tipo letto dal configfile e lo riempie con i dati il valore w può contenere più informazioni
    $retval = '';
	$class  = 'textbox';
	$help = '';
	$onChange = '';
	$display = '';
	$js = '';
	$yselected = '';
	$nselected = '';
    $wpx = '';
	$dati=(isset($this->array_dati[$this->curr_record]))?$this->array_dati[$this->curr_record]:array();
	$err=(isset($this->errors[$campo]))?$this->errors[$campo]:null;
	$dato=(isset($dati[$campo]))?$dati[$campo]:null;
	if(isset($err)){
		$class="class=\"errors\"";
		$help="<image src=\"images/small_help.gif\" onclick=\"alert('$err')\" />";
	}
	
	/*MODIFICA LOCK STATI SE IL CAMPO E' FROZEN AGGIUNGO disabled*/
	if ($frozen) $disabilitato="disabled";
	else
		$disabilitato="";
	/*FINE MODIFICA*/

	switch ($tipo) {
		
		case "idriga":
		case "idkey":
			$retval="<INPUT  type=\"hidden\" name=\"dati[$campo]\" id=\"$campo\"  value=\"$dato\" />";
			break;
		case "label":
			$retval="";
			break;
		case "string":
			$retval=stripslashes($dato);
			break;
		
		case "ora":	
			if ($dato) 	$dato=number_format($dato,2, ':', '');			
		
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dato\" $disabilitato />$help";
			break;
		case "numero":
			if ($dato) 
				$dato=number_format($dato,4, ',', '.');			
			else
				$dato="0";
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dato\" $disabilitato />$help";
			break;
		case "intero":
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dato\" $disabilitato />$help";
			break;
		case "valuta":
			if ($dato) 
				$dato="€ ".number_format($dato,2, ',','');
			else
				$dato="€ 0,00";
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"valuta\" value=\"$dato\" $disabilitato />$help";
			break;
		
		case "superficie":
			if ($dato)
				$dato=number_format($dato,2, ',','.')." mq";
			else
				$dato="0,00 mq";
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"valuta\" value=\"$dato\" $disabilitato />$help";
			break;
		case "volume":
			if ($dato)
				$dato=number_format($dato,2, ',','.')." mc";
			else
				$dato="0,00 mc";
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"valuta\" value=\"$dato\" $disabilitato />$help";
			break;
		case "locked":
			$size=intval($w+($w/5));
			$testo=stripslashes($dato);
			$retval="<INPUT $class maxLength=\"$w\" size=\"$size\"  class=\"textbox\"  id=\"locked_$campo\" value=\"$testo\" disabled /><input type=\"hidden\" name=\"dati[$campo]\" id=\"$campo\" value=\"$testo\" />$help";
			break;
		case "text":			
		case "textkey":
			$size=intval($w+($w/5));
			$testo=stripslashes(str_replace("\\","\\\\",$dato));
			$retval="<INPUT $class maxLength=\"$w\" size=\"$size\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$testo\" $disabilitato />$help";
			break;
			
		case "data":
			$data=$this->date_format(stripslashes($dato));
			$retval="<INPUT $class maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$data\" $disabilitato />$help";
			break;	
			
		case "textarea":
			$size=explode("x",$w);
			$dato=str_replace('\"','"',str_replace("\'","'",$dato));
			$retval="<textarea cols=\"$size[0]\" rows=\"$size[1]\" name=\"dati[$campo]\" id=\"$campo\" $disabilitato >$dato</textarea>";
			break;
		
		case "select"://elenco preso da file testo
			$size=explode("#",$w);
			$opzioni=$this->elenco_select($size[1],$size[2],$dati[$campo]);
			$retval="<select style=\"width:$size[0]px\" class=\"textbox\"  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $disabilitato>$opzioni</select>$help";
			break;
		
		case "selectdb"://elenco preso da query su db
			$size=explode("#",$w);
			$filter=array_slice($size,2);

			if(empty($filter)) $filter="";
			$opzioni=$this->elenco_selectdb($size[1],(isset($dati[$campo]))?$dati[$campo]:null,$filter);
			$class=($err)?($class):("class=\"textbox\"");
			if (isset($size[3]))
				$onChange=(preg_match("|([\w]+)[(](.+)[)]|i",$size[3]))?("onChange=\"javascript:".$size[3]."\""):("onChange=javascript:\"".$size[3]."()\"");
			$retval="<select style=\"width:$size[0]px\" $class  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $onChange $disabilitato>$opzioni</select>$help";
			break;
		case "selectRPC":
			$size=explode("#",$w);
			$opzioni=$this->elenco_selectdb($size[1],$dati[$campo],$size[2]);
			list($schema,$tb)=explode(".",$size[1]);
			if (isset($size[3])) $onChange="onChange=\"javascript:".$size[3]."(this)\"";
			$retval="<select style=\"width:$size[0]px\" class=\"textbox\"  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $onChange $disabilitato>$opzioni</select>$help";
			break;	
			
		case "elenco"://elenco di opzioni da un campo di db valori separati da virgola
			$size=explode("#",$w);	
			if (isset($size[2])) $onChange="onChange=\"".$size[2]."()\"";			
			$opzioni=$this->elenco_selectfield($campo,$dati[$campo],$size[1]);
			$retval="<select style=\"width:$size[0]px\" class=\"textbox\"  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $onChange $disabilitato>$opzioni</select>";	
			break;
		
		case "chiave_esterna":
			$size=explode("#",$w);
			$testo = '';
			if (isset($dati[$campo])) {
				$testo=stripslashes($this->get_chiave_esterna($dati[$campo],$campo,$size[1],$size[2]));
			}
			$val=isset($dati[$campo])?$dati[$campo]:"";
			//$retval="<INPUT $class maxLength=\"$size[0]\" size=\"$size[0]\"  class=\"textbox\" name=\"fk_$campo\" id=\"fk_".$campo."\" value=\"$dati[$campo]\" disabled><input type=\"hidden\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dati[$campo]\">$help";
			$retval="<INPUT $class maxLength=\"$size[0]\" size=\"$size[0]\"  class=\"textbox\" name=\"fk_$campo\" id=\"fk_".$campo."\" value=\"$testo\" disabled>";
			$retval.="<input type=\"hidden\" name=\"dati[$campo]\" id=\"$campo\" value=\"$val\">$help";
			
			break;
		case "checkbox":
			(($dati[$campo]=="t") or ($dati[$campo]=="on") or (abs($dati[$campo])==1))?($selezionato="checked"):($selezionato="");
			$ch=strtoupper($campo);
			
			if($dati[$campo]==-1) $ch="<font color=\"FF0000\">EX $ch</font>";
			$retval="<b>$ch</b><input type=\"checkbox\"  name=\"dati[$campo]\"  id=\"$campo\" $selezionato $disabilitato />&nbsp;&nbsp;";
			break;
		
		case "radio":
			(($dati[$campo]=="t") or ($dati[$campo]=="on") or ($dati[$campo]==1))?($selezionato="checked"):($selezionato="");
			$retval="<input type=\"radio\" name=\"dati[opzioni]\"  id=\"$campo\" $selezionato $disabilitato />";
			break;
		
		case "button":
			$size=explode("#",$w);
			$width=$size[0];
			$jsfunction=$size[1];
			$size=array_slice($size,2);
			if (!count($size)) $param="[]";
			else
				$param="['".@implode("','",$size)."']";
			if(!$mode || $mode=="all" || $this->mode==$mode)
				$retval="\n\t\t\t<input class=\"hexfield\" style=\"width:".$width."px\" type=\"button\" value=\"$label\" onclick=\"javascript:$jsfunction('$campo',$param)\" />";
			break;
		
		case "submit":
			if (in_array(strtolower($label),Array("cancella","elimina")))
				$js="onclick=\"javascript:return confirm('Sei sicuro di voler eliminare il record?');\"";
			elseif (in_array(strtolower($label),Array("copia")))
				$js="onclick=\"javascript:return confirm('Sei sicuro di voler copiare il record?');\"";
			if(!$mode || $mode=="all" || $this->mode==$mode)
				//$retval="\n\t\t\t<input  name=\"$campo\"  id=\"$campo\" class=\"hexfield\" style=\"width:".$w."px;display:$display\" type=\"submit\" value=\"$label\" $js />";
				$retval="\n\t\t\t<button  name=\"$campo\"  id=\"$campo\" class=\"hexfield\" style=\"width:".$w."px;display:$display\" type=\"submit\" value=\"$action\" $js>$label</button>";
			break;
			
		case "yesno":
			((!isset($dati[$campo])) or ($dati[$campo]==="t") or ($dati[$campo]==="on") or ($dati[$campo]==1))?($yselected="selected"):($nselected="selected");
			$opzioni="<option value=1 $yselected>".GCAuthor::t('yes')."</option><option value=0 $nselected>".GCAuthor::t('no')."</option>";
			$retval="<select style=\"width:$wpx\" class=\"textbox\"  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $disabilitato>$opzioni</select>";		  
			break;
			
		case "noyes":
			((!isset($dati[$campo]) or ($dati[$campo]==="f") or ($dati[$campo]==="off") or ($dati[$campo]==0)))?($nselected="selected"):($yselected="selected");
			$opzioni="<option value=1 $yselected>".GCAuthor::t('yes')."</option><option value=0 $nselected>".GCAuthor::t('no')."</option>";
			$retval="<select style=\"width:$wpx\" class=\"textbox\"  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $disabilitato>$opzioni</select>";		  
			break;	
			
		case "pword":
            $size=intval($w+($w/5));
			$testo=stripslashes($dato);
			$retval="<INPUT $class type=\"password\" maxLength=\"$w\" size=\"$size\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dato\" $disabilitato />$help";
			break;
		case "color":
			$retval="<INPUT $class type=\"text\" maxLength=\"$w\" size=\"$w\"  class=\"textbox\" name=\"dati[$campo]\" id=\"$campo\" value=\"$dato\" />$help<a href=\"#\" onclick=\"javascript:open_color('$campo')\"><img src=\"images/paste.gif\" border=0 style=\"width:18px;height:18px;\" /></a>";
			break;
		case "elenco_file":
			$size=explode("#",$w);
			if(!$size[2]) $size[2]="";
			$opzioni=$this->elenco_file($size[1],$size[2]);
			$class=($err)?($class):("class=\"textbox\"");
			$retval="<select style=\"width:$size[0]px\" $class  name=\"dati[$campo]\"  id=\"$campo\" onmousewheel=\"return false\" $onChange $disabilitato>$opzioni</select>$help";
			break;
		case "hidden":
			$retval="<input type=\"hidden\" name=\"hid[$campo]\" id=\"hid_$campo\" value=\"$dato\" />";
			break;
		case "upload":
			$size=explode("#",$w);
			$width=($size[0])?("size=\"$size[0]\""):("");
			$maxsize=($size[1])?($size[1]):("500");
			$retval="<input $class type=\"file\" $width name=\"$campo\" id=\"upl_$campo\" value=\"\" disabled>$help<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"$maxsize\" />";
			break;
		case "checklist":
			$retval="<div style=\"overflown:auto;scroll:auto;height:400px;\">";
			for($i=0;$i<count($dato);$i++){
				
			}
			$retval.="</div>";
			break;
		
	}
		
	return $retval;
}

function get_dato($tipo,$w,$campo){ 
//restituisce il dato come stringa
        $retval = '';
	$dati=isset($this->array_dati[$this->curr_record])?$this->array_dati[$this->curr_record]:array();
	switch ($tipo) {
		
		case "idriga":
			$retval="";
			break;
		case "locked":
		case "text":
		case "string":
			if(isset($dati[$campo]))
				$retval=$dati[$campo];
			else
				$retval='';//'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			break;
			
		case "data":
			$retval=$data=$this->date_format($dati[$campo]);
			break;
		case "ora":
			$retval=number_format($dati[$campo],2, ':', '');
			break;
		case "numero":
			$retval=number_format($dati[$campo],4, ',', '.');
			break;
		case "intero":
			if(isset($dati[$campo]))
				$retval=$dati[$campo];
			else
				$retval='0';//'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			break;
		case "valuta":
		//setto la valuta aggiungendo il metodo setvaluta alla classe tabella e poi la uso qui
		//echo("<br>Formatto valuta : $dati[$campo]<br>");
			$retval="€ ".number_format($dati[$campo],2, ',', '.');
			break;		
		case "superficie":	
			$retval=number_format($dati[$campo],2, ',', '.')." mq";
			break;
		case "volume":	
			$retval=number_format($dati[$campo],2, ',', '.')." mc";
			break;
		case "noyes":
		case "yesno":
			if ($dati[$campo]==0) $retval=GCAuthor::t('no');
			if ($dati[$campo]==1) $retval=GCAuthor::t('yes');
			break;			
			
		case "textarea":
			$retval="<div style=\"width:100%\"><pre>".$dati[$campo]."</pre></div>";
			break;
		case "selectdb":		//Restituisce il campo descrittivo di un elenco 
			$size=explode("#",$w);
			$retval=$this->get_chiave_esterna($dati[$campo],"id",$size[1],"opzione")	;
			//$retval=$this->elenco_selectdb($dati[$campo],"id",$flt,"opzione");
			break;
		//case "selectdb":
		case "chiave_esterna":		//Restituisce il campo descrittivo di un elenco 
			$size=explode("#",$w);
			//$retval=$this->get_chiave_esterna($size[1],$dati[$campo],$size[2]);
			
			$retval=stripslashes($this->get_chiave_esterna($dati[$campo],$campo,$size[1],$size[2]));
			
			break;	
		case "elenco":
			$retval=$this->get_dato_elenco($campo);
		case "color":
			$val=str_replace(" ",",",trim($dati[$campo]));
			if ($val)
				$retval=$dati[$campo]."&nbsp;<span style=\"width:10px;height:10px;border:1px solid black;background-color:rgb($val);\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>";
			else
				$retval = GCAuthor::t('undefined');
			break;
		case "image":
			$size=explode("#",$w);
			$dim=explode("x",$size[0]);
			$v=$dati[$size[1]];
			if ($dati[$campo])
				$retval="<img src=\"getImage.php?id=$v&tb=$campo\" style=\"width:".$dim[0]."px;height:".$dim[1]."px;\">";
			else
				$retval="Nessuna immagine salvata";
			break;
	}
	return $retval;
}

function get_campo($campo){
	return $this->array_dati[$this->curr_record][$campo];
}
function get_data($campo){
	$data=$this->array_dati[$this->curr_record][$campo];
	return $this->date_format($data);
}

//MODIFICA LOCK STATI AGGIUNTO PARAMETRO $frozen_cols ARRAY DI CAMPI CONGELATI
//function get_riga_edit($nriga){
function get_riga_edit($nriga,$frozen_cols=Array()){
//prendo una riga che può essere fatta da uno,  due o più colonne
// restituisce la riga in modalità edit con label controllo associato
	$riga=$this->tab_config[$nriga];
	$lbl="";
	$ctr='';
	
	for ($i=0;$i<count($riga);$i++){
		list($label,$campo,$w,$tipo,$mode,$action)=array_pad(explode(';',$riga[$i]), 6, null);
		$tipo=trim($tipo);
		if(($tipo!="button") and ($tipo!="submit") and ($tipo!="hidden"))
			($lbl)?(($label)?($lbl.=" -  ".$label):($lbl)):($lbl=$label);
		$ctr.=$this->get_controllo($label,$tipo,$w,$campo,$mode,$action);
	}
	return array($lbl,$ctr);
}

function get_riga_view($nriga){
// restituisce la riga in modalità view 
	$riga=$this->tab_config[$nriga];
	$testo_riga = '';
	for ($i=0;$i<count($riga);$i++){
		if(trim($riga[$i])){
			list($label,$campo,$w,$tipo) = array_pad(explode(';',trim($riga[$i])), 4, null);
			if ($label)  $label="<b>".str_replace("<br>","&nbsp;&nbsp;",$label).":&nbsp;</b>";
			$dato=$this->get_dato(trim($tipo),$w,$campo);
			if ($label.$dato && !(in_array($tipo,Array("submit","button"))))  
				$testo_riga.=$label.$dato."&nbsp;&nbsp;&nbsp;&nbsp;";
		}
	}
	return $testo_riga;
}
 
function edita($param=Array()){
//if($this->error_flag==1)
	//echo ("I campi evidenziati in rosso non sono validi");
	//crea la tabella di editing
	$nrighe=$this->num_col;
	$tabella="<table class=\"stiletabella\">\n";
	
	
	for ($i=0;$i<$nrighe;$i++){
		
		$riga=$this->get_riga_edit($i);
		$tabella.="\t<tr>\n";
		if ($riga[0]=="")
			$tabella.="\t\t<td class=\"label ui-widget ui-state-default\">&nbsp;</td><td colspan=\"2\"><hr></td>\n\t<tr>\n<td class=\"label ui-widget ui-state-default\">&nbsp;</td>";
		else
			//colonna labelelseif($riga[1]=="")
			$tabella.="\t\t<td class=\"label ui-widget ui-state-default\"><font color=\"".$this->testo_label."\"><b>$riga[0]</b></font></td>\n";
		//colonna controlli campi
		if($riga[1]=="")
			$tabella.="\t\t<td colspan=\"2\"><hr></td>\n\t<tr>\n";
		else
			$tabella.="\t\t<td valign=\"middle\" colspan=\"2\">$riga[1]</td>\n";
		$tabella.="\t</tr>";
	}
	$tabella.="</table>\n";
	//aggiungo i campi nascosti che possono servire
	//MODIFICA PER LOCK STATI HO SPOSTATO LA RIGA PIU' SU

	print $tabella;
}

function tab($curr=0){
//crea la tabella per l'elenco in consultazione
	$nrighe=$this->num_col;
	$span=2*$nrighe;
	$tabella="<table class=\"stiletabella\">\n";
	for ($i=0;$i<$nrighe;$i++){
		$riga=$this->get_riga_view($i);
		$tabella.="\t<tr>\n";
		if (!$i){
			$tabella.="\t\t<td width=\"95%\">$riga</td>\n";
			//$tabella.="<td  rowspan=\"".$span."\" align=\"center\" valign=\"middle\">".$this->doc."</td>\n";
		}else{
			$tabella.="\t\t<td>$riga</td>\n";
		}
		$tabella.="\t</tr>\n";	
		if ($i<$nrighe-1 && $riga) $tabella.=$this->rigagrigia;				
	}
	$tabella.="</table>\n";
	print $tabella;
}	

function elenco($form){
	if(!$form) $form=$_SERVER["PHP_SELF"];
	for ($i=0;$i<$this->num_record;$i++){
		$this->curr_record=$i;
		$this->get_titolo($form);
		$this->tab();
	}
}


//########################## ELENCHI ########################

function elenco_select($tabella,$sep,$selezionato){
// dal file tab crea la lista di opzioni per il controllo SELECT
	
	
	if (!file_exists($tabella)){
		$retval="\n<option value=\"\">File dei Fonts non Esistente</option>";
		return $retval;
	}
	$elenco=file($tabella);
	for ($i=0;$i<count($elenco);$i++){
		$tmp=preg_split("/[$sep]+/",trim($elenco[$i]));
		$key=trim($tmp[0]);
		$val=trim($tmp[1]);
		if(!$val)$val=$key;
		(trim($val)==trim($selezionato))?($selected="selected"):($selected="");
		$retval.="\n<option value=\"$key\" $selected>".$key."</option>";
  	}
	return $retval;
}

function elenco_selectdb($tabella,$selezionato,$filtro){
// dalla tabella crea la lista di opzioni per il controllo SELECT

	if (!isset($this->db)) $this->connettidb();
	$sql='SELECT DISTINCT id,opzione FROM '.$this->schemadb.'.'.$tabella;
	if (is_array($filtro)){
		
		for($i=0;$i<count($filtro);$i++){
			if (isset($this->array_dati[$this->curr_record][$filtro[$i]]) && $this->array_dati[$this->curr_record][$filtro[$i]]){
				$value=$this->array_dati[$this->curr_record][$filtro[$i]];
				$arrfiltro[]="$filtro[$i] IN ('0', '$value')";
			}
			else{
				$key=implode("_",array_slice(explode("_",$filtro[$i]),0,-1));
                                if (!array_key_exists($key, $this->array_hidden)) continue;
				$value=$this->array_hidden[$key];
				if ($value)
					$arrfiltro[]=$filtro[$i]." IN ('0','$value')";
			}
		}
		$filtro=@implode(' AND ',$arrfiltro);
	}
	elseif (trim($filtro)){
		if (!ereg("=",$filtro)){
			
			if ($this->array_dati[$this->curr_record][$filtro]){
				$value=$this->array_dati[$this->curr_record][$filtro];
				$filtro="$filtro = '$value'";
			}
			else{
				$value=$this->array_hidden[$filtro];
				$filtro=$filtro."_name IN ('0','$value')";
			}
		}
	}
	if($filtro) $sql.=" where $filtro";
	print_debug($sql,NULL,"SELECTDB");

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute();
	if (!$success){
		print_debug("Errore SelectDB\n".$sql,NULL,"error");
		return;
	}
	$retval="";
	$elenco = $stmt->fetchAll();
	$nrighe = $stmt->rowCount();
	//echo $nrighe;
	for  ($i=0;$i<$nrighe;$i++){
		($elenco[$i]["id"]==$selezionato)?($selected="selected"):($selected="");
		$retval.="\n<option value=\"".$elenco[$i]["id"]."\" $selected>".$elenco[$i]["opzione"]."</option>";
  	}
	return $retval;
}

function elenco_selectfield($campo,$selezionato,$filtro){
// dalla tabella crea la lista di opzioni per il controllo SELECT
//Utilizzata x ora solo sulla tabella per il calcolo degli oneri
//Temporanea fino alla costruzione dell'interfaccia di gestione configurazione tabella oneri

	$tabella=$this->tabella_elenco;
	if (!isset($this->db)) $this->connettidb();
	$sql="select $campo from $this->schemadb.$tabella";
	if (trim($filtro)){
		$filtro="id=".$this->array_dati[$this->curr_record][$filtro];
		$sql.=" where $filtro";
	}
	if ($this->debug)	echo("sql=$sql");
	print_debug($sql,NULL,"tabella");
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
	//$elenco = $this->db->sql_fetchrowset();
	$elenco=$row[$campo];
	if (!$elenco){
		return;
	}
	$ar_elenco=explode(";",$elenco);
	
	$nopt=count($ar_elenco)/2;
	$i=0;
	while  ($i<count($ar_elenco)){
		$desc=$ar_elenco[$i];
		$i++;
		$val=$ar_elenco[$i];
		$i++;
		($val==$selezionato)?($selected="selected"):($selected="");
		$retval.="\n<option value=\"".$val."\" $selected>".$desc."</option>";
  	}
	return $retval;
}
function elenco_file($dir,$ext=""){
	if (is_dir($dir)) {
		$elenco[]="<option value=\"\">Seleziona ====></option>";
	    if ($dh = opendir($dir)) {
	        while (($file = readdir($dh)) !== false) {
				if($ext){
					if(ereg("\.$ext",strtolower($file))){
						$elenco[]="<option value=\"$file\">".$file."</option>";
					}
				}
				else{
					if (!is_dir($file))
						$elenco[]="<option value=\"$file\">".$file."</option>";
				}
	        }
	        closedir($dh);
		}
	}
	return @implode("\n\t\t\t",$elenco);
}

function get_chiave_esterna($val,$fld,$tab,$campo){
	//echo "<p>$val -- $fld -- $tab -- $campo</p>";

	if ($val==-1)
		return GCAuthor::t('undefined');
	elseif(strlen($val)==0){
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
}//end class


?>	
