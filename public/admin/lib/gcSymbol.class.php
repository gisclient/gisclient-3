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

class Symbol{
	
	function __construct($table){
		$this->table=$table;
		$this->db = new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
		if(!$this->db->db_connect_id) die( "Impossibile connettersi al database ". DB_NAME);
	}

	function createIcon(){
		$dbSchema=DB_SCHEMA;
		$this->mapfile=ROOT_PATH.'map/tmp.map';
		$this->simbolSize=array(LEGEND_POINT_SIZE,LEGEND_LINE_WIDTH,LEGEND_POLYGON_WIDTH);
		$aClass=array();
		
		if($this->table=='class'){
			$sql="select class.class_id,layertype_ms,style_id,color,outlinecolor,bgcolor,angle,size,width,symbol_name,symbol_def
			from $dbSchema.class inner join $dbSchema.layer using(layer_id) inner join $dbSchema.layergroup using (layergroup_id) 
			inner join $dbSchema.theme using (theme_id) inner join $dbSchema.project using (project_name) 
			inner join $dbSchema.e_layertype using (layertype_id)
			left join $dbSchema.style using(class_id) left join $dbSchema.symbol using(symbol_name)";
			
		
			if($this->filter) $sql.=" where ".$this->filter;
			$sql.=" order by style_order;";
			$this->db->sql_query($sql);
			$res=$this->db->sql_fetchrowset();	
			$aSymbol=array("SYMBOL\nNAME \"___LETTER___\"\nTYPE TRUETYPE\nFONT \"verdana\"\nCHARACTER \"a\"\nANTIALIAS TRUE\nEND");//lettera A per le icone dei testi
			for($i=0;$i<count($res);$i++){
				$aClass[$res[$i]["class_id"]]["icontype"]=$res[$i]["layertype_ms"];
				if($res[$i]["style_id"]){
					$aStyle["color"]=explode(" ",$res[$i]["color"]);
					$aStyle["outlinecolor"]=explode(" ",$res[$i]["outlinecolor"]);
					$aStyle["bgcolor"]=explode(" ",$res[$i]["bgcolor"]);
					$aStyle["angle"]=$res[$i]["angle"];	
					$aStyle["width"]=$res[$i]["width"];	
					$aStyle["size"]=$res[$i]["size"];			
					$aStyle["symbol"]=$res[$i]["symbol_name"];	
					$aClass[$res[$i]["class_id"]]["style"][]=$aStyle;				
				}
				if($res[$i]["symbol_def"]){
					$sSy="SYMBOL\nNAME \"".$res[$i]["symbol_name"]."\"\n".$res[$i]["symbol_def"]."\nEND";
					if(!in_array($sSy,$aSymbol)) $aSymbol[]=$sSy;
				}
			}
			
	
			
			$this->_createMapFile($aSymbol);
			
			
			foreach($aClass as $classId=>$class){
				$oIcon = $this->_iconFromClass($class);
				if($oIcon){
					ob_start();
					$oIcon->saveImage('');
					$image_data =pg_escape_bytea(ob_get_contents());
					ob_end_clean();
					$sql="update $dbSchema.class set class_image='{$image_data}' where class_id=$classId;";
					//echo ($sql."<br>");
					$this->db->sql_query($sql);
				}
			}
		}

		elseif($this->table=='symbol'){
			$sql="select symbol_name,icontype,symbol_def from $dbSchema.symbol inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
			if($this->filter) $sql.=" where ".$this->filter;
			$this->db->sql_query($sql);
			$res=$this->db->sql_fetchrowset();	
			for($i=0;$i<count($res);$i++){
				$class=array();$style=array();
				$class["icontype"]=$res[$i]["icontype"];
				$style["symbol"]=$res[$i]["symbol_name"];
				$style["color"]=array(0,0,0);
				$class["style"][]=$style;
				$aClass[]=$class;
				$aSymbol[]="SYMBOL\nNAME \"".$res[$i]["symbol_name"]."\"\n".$res[$i]["symbol_def"]."\nEND";
			
				$this->_createMapFile($aSymbol);
				$oIcon = $this->_iconFromClass($class);
				if($oIcon){
					ob_start();
					$oIcon->saveImage('');
					$image_data =pg_escape_bytea(ob_get_contents());
					ob_end_clean();
					$sql="update $dbSchema.symbol set symbol_image='{$image_data}' where symbol_name='".$style["symbol"]."';";
					//echo ($sql."<br>");
					$this->db->sql_query($sql);
				}
			}
		}

		if(!DEBUG) unlink($this->mapfile);	
	}
	

	function _iconFromClass($class){
		//creo la mappa 
		$oMap = $this->oMap;
		$error = ms_GetErrorObj();
		if($error->code != MS_NOERR){
			$this->mapError=150;
			while($error->code != MS_NOERR){
				print("MAPFILE ERROR ". $this->mapfile."<br>");
				printf("Error in %s: %s<br>\n", $error->routine, $error->message);
				$error = $error->next();
			}
			return;
		}
		$oMap->setFontSet(ROOT_PATH.'fonts/fonts.list');		
		$oMap->outputformat->set('name','PNG');
		$oMap->outputformat->set('driver','GD/PNG');
		$oMap->outputformat->set('extension','png');
		$oMap->outputformat->setOption("INTERLACE", "OFF");
		$oLay=ms_newLayerObj($oMap);
		$oLay->set('type', $class["icontype"]);	
		$oClass=ms_newClassObj($oLay);
		$smbSize=$this->simbolSize[$class["icontype"]];
		$style=isset($class["style"])?$class["style"]:array();
		//print_array($class);
		//Aggiungo gli stili
		for($i=0;$i<count($style);$i++){
			$oStyle=ms_newStyleObj($oClass);
			$oStyle->set("size",$smbSize);
			if(!empty($style[$i]['symbol'])) $oStyle->set('symbolname',$style[$i]['symbol']);
			if(!empty($style[$i]['angle']))	$oStyle->set('angle',$style[$i]['angle']);
			if(isset($style[$i]['color']) && count($style[$i]['color'])==3)	$oStyle->color->setRGB($style[$i]['color'][0],$style[$i]['color'][1],$style[$i]['color'][2]);
			if(isset($style[$i]['outlinecolor']) && count($style[$i]['outlinecolor'])==3) $oStyle->outlinecolor->setRGB($style[$i]['outlinecolor'][0],$style[$i]['outlinecolor'][1],$style[$i]['outlinecolor'][2]);	
			if(isset($style[$i]['bgcolor']) && count($style[$i]['bgcolor'])==3) $oStyle->backgroundcolor->setRGB($style[$i]['bgcolor'][0],$style[$i]['bgcolor'][1],$style[$i]['bgcolor'][2]);
			$oStyle->set('width',1);
			if(!empty($style[$i]['width'])) $oStyle->set('width',$style[$i]['width']);
			if(!empty($style[$i]['size'])) $oStyle->set('size',$style[$i]['size']);
			

		}
		//Aggiungo lo stile per il simbolo ttf
		if(!empty($class["symbol_ttf"])){
			$oStyle=ms_newStyleObj($oClass);
			$oStyle->set("size",$smbSize);	
			$oStyle->set('symbolname',$class['symbol_ttf']);
			$oLay->set('postlabelcache', 'true');
			if(count($class['label_color'])==3) $oStyle->color->setRGB($class['label_color'][0],$class['label_color'][1],$class['label_color'][2]);
			//if(count($class['label_bgcolor'])==3)$oStyle->backgroundcolor->setRGB($class['label_bgcolor'][0],$class['label_bgcolor'][1],$class['label_bgcolor'][2]);
		}
		//print_array($oClass);
		//print_array($oClass->getStyle(0));

		$icoImg = @$oClass->createLegendIcon(LEGEND_ICON_W,LEGEND_ICON_H);
		return $icoImg;
	}
	
	function _createMapFile($aSymbol){
		//creazione del file di simboli
		$mapText=array();
		$mapText[] = "MAP";
		$mapText[] = "EXTENT 0 0 180 180";
		$mapText[] = implode("\n",$aSymbol);
		$mapText[] = "END";
		$this->oMap = ms_newMapObjFromString(implode("\n",$mapText),ROOT_PATH.'mapset/map');
	}
	
	//RESTITUISCE UN ELENCO DI SIMBOLI FILTRATI
	function getList(){
		$dbSchema=DB_SCHEMA;
		$table=$this->table;
        $values=array();
		if($table=='class'){
			$sql="select project_name as project,theme_name as theme,layergroup_name as layergroup,layer_name as layer,class_name as class,class_id
			from $dbSchema.class inner join $dbSchema.layer using(layer_id) inner join $dbSchema.layergroup using (layergroup_id) 
			inner join $dbSchema.theme using (theme_id) inner join $dbSchema.project using (project_name)";	
			if($this->filter) $sql.=" where ".$this->filter;
			$sql.="  order by 1,2,3,4,5";
			$headers = array("Image","Class","Layer","Layergroup","Theme","Project");	
			$values=array();
			$this->db->sql_query($sql);
			while($row=$this->db->sql_fetchrow()){
				$values[]=array("table=class&id=".$row["class_id"],$row["class"],$row["layer"],$row["layergroup"],$row["theme"],$row["project"]);
			}
		}
		elseif($table=='symbol'){
			$sql="select symbol_name as symbol,symbolcategory_name as category from $dbSchema.symbol inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
			
			if($this->filter) $sql.=" where ".$this->filter;
			$sql.="  order by 1";
			$headers = array("Image","Symbol","Category");
			$this->db->sql_query($sql);
			while($row=$this->db->sql_fetchrow()){
				$values[]=array("table=symbol&id=".$row["symbol"],$row["symbol"],$row["category"]);
			}
		}

		return array("headers"=>$headers,"values"=>$values);
	}
	
	
	//METODI PER LA GESTIONE DELLE TABELLE DEI SIMBOLI DA RIVEDERE
		
	function updateFileSmb(){
		$smbfile = fopen ("smb.map","w");
		for($i=0;$i<count($style);$i++){
				fwrite($smbfile, "SYMBOL\n");
				fwrite($smbfile, "NAME \"".$style[$i]["symbol_name"]."\"\n");
				fwrite($smbfile, $style[$i]["def"]."\n");
				fwrite($smbfile, "END\n");				
			}
			fclose($smbfile);
	
	}
	
	function importFilesmb($filename){
		$handle=fopen($filename,'r');
		$content=trim(fread($handle,filesize($filename)));
		$smbList=preg_split("/[\n\r]{1,2}/",$content);
		$n_line=count($smbList);
		//print('<pre>');
		foreach ($smbList as $line_num => $line) {
			$line=strtoupper(str_replace("'",'"',$line));
			$sym_flag=preg_match('/SYMBOL(.*)/',$line,$res);
			if($res || ($n_line-1)==$line_num){//Inizio simbolo
				if($sSymbol){
					if(($n_line-1)>$line_num) array_pop($sSymbol);
					//savesmb($nome,$sSymbol);
					$aSymbol[$nome]=implode("\n",$sSymbol);
					$sSymbol=Array();
					$nome="";
				} 
			}
			preg_match('/NAME(.*)/',$line,$res);
			if(!$sym_flag && !$res && trim($line)){	// CONTROLLO NON SIA UN NAME
				$sSymbol[]=trim($line);
			}
			elseif($res){
				$nome=trim($res[1]);
				$nome=str_replace("'","",$nome);
				$nome=str_replace('"',"",$nome);
			}
		}
		//Salvo su database
		$dbSchema=DB_SCHEMA;
		$table=$this->table;
		$tableId=$table."_id";
		foreach($aSymbol as $smbName=>$smbDef){
			$sql="insert into $dbSchema.symbol(symbol_id,symbol_name,def) values ((select $dbSchema.new_pkey('symbol','symbol_id')),'$smbName','$smbDef');";
			$this->db->sql_query($sql);
			print($sql."\n");
		}
	}
	
	function updateFontList(){
		$dbSchema=DB_SCHEMA;
		$sql="select font_name,file from $dbSchema.font;";
		$this->db->sql_query($sql);
		$file = fopen (ROOT_PATH.'fonts/fonts.list',"w");
		while($row=$this->db->sql_fetchrow())
			$text[]=$row["font_name"]."\t".$row["file"];
		fwrite($file, implode("\n",$text));
		fclose($file);
	}

}//END CLASS
?>
