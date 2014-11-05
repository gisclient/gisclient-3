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

/**
 * TODO: swicth to PDO
 * change signature: pass da connection in directly
 * 
 */
class Symbol{
	public $filter;
	private $mapfile;
	
	function __construct($table){
		$this->table=$table;
		$this->db = $db = GCApp::getDB();
	}

	
	private function createClassIcon ($dbSchema) {
		$aClass=array();
		$image_data = null;
		
		$sql="select class.class_id,class.symbol_ttf_name,symbol_ttf.font_name,symbol_ttf.ascii_code,label_color,label_bgcolor,layertype_ms,style_id,color,outlinecolor,bgcolor,angle,size,width,symbol_name,symbol_def
		from $dbSchema.class inner join $dbSchema.layer using(layer_id) inner join $dbSchema.layergroup using (layergroup_id) 
		inner join $dbSchema.theme using (theme_id) inner join $dbSchema.project using (project_name) 
		inner join $dbSchema.e_layertype using (layertype_id) left join $dbSchema.symbol_ttf 
		on (symbol_ttf.symbol_ttf_name=class.symbol_ttf_name and symbol_ttf.font_name=class.label_font)
		left join $dbSchema.style using(class_id) left join $dbSchema.symbol using(symbol_name) where layertype_ms < 3";

		if($this->filter) {
			$sql.=" and ".$this->filter;
		}
		$sql.=" order by style_order;";
		$stmt = $this->db->query($sql);
		$aSymbol=array("SYMBOL\nNAME \"___LETTER___\"\nTYPE TRUETYPE\nFONT \"verdana\"\nCHARACTER \"a\"\nANTIALIAS TRUE\nEND");//lettera A per le icone dei testi
		while($row = $stmt->fetch()){
			$aClass[$row["class_id"]]["icontype"]=$row["layertype_ms"];
			$aClass[$row["class_id"]]["symbol_ttf"]=$row["symbol_ttf_name"];
			$aClass[$row["class_id"]]["label_color"]=explode(" ",$row["label_color"]);
			$aClass[$row["class_id"]]["label_bgcolor"]=explode(" ",$row["label_bgcolor"]);
			if($row["style_id"]){
				$aStyle["color"]=explode(" ",$row["color"]);
				$aStyle["outlinecolor"]=explode(" ",$row["outlinecolor"]);
				$aStyle["bgcolor"]=explode(" ",$row["bgcolor"]);
				$aStyle["angle"]=$row["angle"];	
				$aStyle["width"]=$row["width"];	
				$aStyle["size"]=$row["size"];			
				$aStyle["symbol"]=$row["symbol_name"];	
				$aClass[$row["class_id"]]["style"][]=$aStyle;				
			}
			if($row["symbol_ttf_name"]){
				$ch=($row["ascii_code"]==34)?"'".chr(34)."'":"\"".chr($row["ascii_code"])."\"";
				$sSy="SYMBOL\nNAME \"".$row["symbol_ttf_name"]."\"\nTYPE TRUETYPE\nFONT \"".$row["font_name"]."\"\nCHARACTER $ch\nANTIALIAS TRUE\nEND";
				if(!in_array($sSy,$aSymbol)){
					$aSymbol[]=$sSy;
				}
			}
			if($row["symbol_def"]){
				$sSy="SYMBOL\nNAME \"".$row["symbol_name"]."\"\n".$row["symbol_def"]."\nEND";
				if(!in_array($sSy,$aSymbol)) {
					$aSymbol[]=$sSy;
				}
			}
		}
		$this->createMapfile($aSymbol);
		foreach($aClass as $class){
			$oIcon = $this->_iconFromClass($class);
			if($oIcon){
				$image_data = $this->getIconImage($oIcon);
			}
		}
		return $image_data;
	}
	
	private function getIconImage($oIcon) {
		ob_start();
		$oIcon->saveImage('');
		$image_data = ob_get_contents();
		ob_end_clean();
		if (ms_GetVersionInt() < 60000) {
			$oIcon->free();
		}
		return $image_data;
	}
	
	private function createSymbolIcon($dbSchema) {
		$image_data = null;
		$aClass = array();
		
		$sql="select symbol_name,icontype,symbol_def from $dbSchema.symbol inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
		if($this->filter) {
			$sql.=" where ".$this->filter;
		}

		$stmt = $this->db->query($sql);
		while ($row = $stmt->fetch()){
			$class=array();$style=array();
			$class["icontype"]=$row["icontype"];
			$style["symbol"]=$row["symbol_name"];
			$style["color"]=array(0,0,0);
			$class["style"][]=$style;
			$aClass[]=$class;
			$aSymbol[]="SYMBOL\nNAME \"".$row["symbol_name"]."\"\n".$row["symbol_def"]."\nEND";

			$this->createMapfile($aSymbol);
			$oIcon = $this->_iconFromClass($class);
			if($oIcon){
				$image_data = $this->getIconImage($oIcon);
				$sql="update $dbSchema.symbol set symbol_image='{$image_data}' where symbol_name='".$style["symbol"]."';";
			}
		}
		return $image_data;
	}

	private function createSymbolTtfIcon($dbSchema) {
		$image_data = null;
		$aClass = array();
		
		$sql="select symbol_ttf_name,font_name,ascii_code from $dbSchema.symbol_ttf inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
		if($this->filter) {
			$sql.=" where ".$this->filter;
		}
		$stmt = $this->db->query($sql);
		while($row = $stmt->fetch()){
			$class=array();
			$class["icontype"]=MS_LAYER_POINT;		
			$class["symbol_ttf"]=$row["symbol_ttf_name"];
			$class["font_name"]=$row["font_name"];
			$class["label_color"]=array(0,0,0);
			$aClass[]=$class;
			$ch=(chr($row["ascii_code"])=='"')?"'".chr(34)."'":"\"".chr($row["ascii_code"])."\"";
			$aSymbol[]="SYMBOL\nNAME \"".$row["symbol_ttf_name"]."\"\nTYPE TRUETYPE\nFONT \"".$row["font_name"]."\"\nCHARACTER $ch\nANTIALIAS TRUE\nEND";

			$this->createMapfile($aSymbol);
			$oIcon = $this->_iconFromClass($class);
			if($oIcon){
				$image_data = $this->getIconImage($oIcon);
				$sql="update $dbSchema.symbol_ttf set symbol_ttf_image='{$image_data}' where symbol_ttf_name='".$class["symbol_ttf"]."' and font_name='".$class["font_name"]."';";
			}
		}
		return $image_data;
	}
	
	function createIcon(){
		$dbSchema=DB_SCHEMA;
		$mapDir=ROOT_PATH."map/tmp";
		if(!is_dir($mapDir)) {
			if (false === mkdir($mapDir)) {
				throw new RuntimeException("Could not create directory $mapDir");
			}
		}
		if (!is_writable($mapDir)) {
			throw new RuntimeException("Directory $mapDir is not writable");
		}
        GCUtils::deleteOldFiles($mapDir);
		$this->mapfile=ROOT_PATH.'map/tmp/tmp'.rand(0,99999999).'.map';
		$this->simbolSize=array(LEGEND_POINT_SIZE,LEGEND_LINE_WIDTH,LEGEND_POLYGON_WIDTH);

		if($this->table=='class'){
			$image_data = $this->createClassIcon($dbSchema);
		} elseif($this->table=='symbol'){
			$image_data = $this->createSymbolIcon($dbSchema);
		} elseif($this->table=='symbol_ttf'){
			$image_data = $this->createSymbolTtfIcon($dbSchema);
		} else {
			throw new Exception("Unknonwn icon class {$this->table}");
		}
		// if(!DEBUG) unlink($this->mapfile);	
        return $image_data;
	}
	

	private function _iconFromClass($class){

		//creo la mappa 
		ms_ResetErrorList();	

		$oMap = ms_newMapObj($this->mapfile);
		$error = ms_GetErrorObj();
		if($error->code != MS_NOERR){
			$this->mapError=150;
			while($error->code != MS_NOERR){
				print(__METHOD__.": MAPFILE ERROR ". $this->mapfile."<br>");
				printf("Error in %s: %s<br>\n", $error->routine, $error->message);
				$error = $error->next();
			}
			return false;
		}	
		$oMap->setFontSet('../../fonts/fonts.list');		
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
		}
		$icoImg = $oClass->createLegendIcon(LEGEND_ICON_W,LEGEND_ICON_H);
		return $icoImg;
	}
	
	private function createMapfile($aSymbol){
		
		// A dummy mapfile is created, from which the symbol can be extracted
		$mapfileTemplate = <<<EOT
MAP
    NAME "sample"
    STATUS ON
    SIZE 600 400
    {symbols}
    EXTENT -180 -90 180 90
    UNITS DD
    IMAGECOLOR 255 255 255
    FONTSET "../../fonts/fonts.list"

    #
    # Start of layer definitions
    #
    LAYER
        NAME dummy
		TYPE POINT
        STATUS DEFAULT
    END # LAYER
END # MAP
EOT;
		$mapfileString = str_replace('{symbols}', implode("\n", $aSymbol), $mapfileTemplate);
		if (false === file_put_contents($this->mapfile, $mapfileString)) {
			throw new RuntimeException("Could not write {$this->mapfile}");
		}
	}

	
	//RESTITUISCE UN ELENCO DI SIMBOLI FILTRATI
	function getList($assoc = false){
		$dbSchema=DB_SCHEMA;
		$table=$this->table;
        $values=array();
		if($table=='class'){
			$sql="select project_name as project,theme_name as theme,layergroup_name as layergroup,layer_name as layer,class_name as class,class_id
			from $dbSchema.class inner join $dbSchema.layer using(layer_id) inner join $dbSchema.layergroup using (layergroup_id) 
			inner join $dbSchema.theme using (theme_id) inner join $dbSchema.project using (project_name)";	
			if($this->filter) {
				$sql.=" where ".$this->filter;
			}
			$sql.="  order by 1,2,3,4,5";
			$headers = array("Image","Class","Layer","Layergroup","Theme","Project");	
			$values=array();
			$stmt = $this->db->query($sql);
			while($row=$stmt->fetchrow()){
				$values[]=array("table=class&id=".$row["class_id"],
					$row["class"], $row["layer"], $row["layergroup"],
					$row["theme"], $row["project"]);
			}
		}
		elseif($table=='symbol'){
			$sql="select symbol_name as symbol,symbolcategory_name as category from $dbSchema.symbol inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
			
			if($this->filter) {
				$sql.=" where ".$this->filter;
			}
			$sql.=" order by symbolcategory_name, symbol_name";
			$headers = array("Image","Symbol","Category");
			$stmt = $this->db->query($sql);
			while($row=$stmt->fetch()){
				if(!$assoc) {
					$values[]=array("table=symbol&id=".$row["symbol"],$row["symbol"],$row["category"]);
				} else {
					array_push($values, $row);
				}
			}
		}
		elseif($this->table=='symbol_ttf'){
			$sql="select symbol_ttf_name as symbol,font_name as font,position,symbolcategory_name as category  from $dbSchema.symbol_ttf inner join $dbSchema.e_symbolcategory using (symbolcategory_id)";
			if($this->filter) {
				$sql.=" where ".$this->filter;
			}
			$sql.=" order by 2,1";
			$headers = array("Image","Symbol","Font","Category","Position");
			$stmt = $this->db->query($sql);
			while($row=$stmt->fetch()){
				if(!$assoc) {
					$values[]=array("table=symbol_ttf&font=".$row["font"]."&id=".$row["symbol"],$row["symbol"],$row["font"],$row["category"],$row["position"]);
				} else {
					array_push($values, $row);
				}
			}
		}
		return array("headers"=>$headers,"values"=>$values);
	}
	
	
	//METODI PER LA GESTIONE DELLE TABELLE DEI SIMBOLI DA RIVEDERE
		
	function updateFileSmb(){
		$smbfile = fopen ("smb.map","w");
		if ($smbfile === false) {
			throw new RuntimeException("Could not open smb.map");
		}
		throw new Exception("Internal error, $style undefined!!");
		for($i=0; $i<count($style); $i++){
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
			$this->db->exec($sql);
			print($sql."\n");
		}
	}
	
	function updateFontList(){
		
		$fontlistFile = ROOT_PATH.'fonts/fonts.list';
		$file = fopen ($fontlistFile,"w");
		if ($file === false) {
			throw new RuntimeException("Could not open $fontlistFile");
		}

		$dbSchema=DB_SCHEMA;
		$sql="select font_name,file from $dbSchema.font;";
		$stmt = $this->db->query($sql);
		while($row=$stmt->fetch())
			$text[]=$row["font_name"]."\t".$row["file"];
		fwrite($file, implode("\n",$text));
		fclose($file);
	}

}