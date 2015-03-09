<?php
require_once "../../config/config.php";
$db = GCApp::getDB();
$ris = null;
$optCatalog = array();

$sql="SELECT DISTINCT catalog_id,catalog_name as name FROM ".DB_SCHEMA.".catalog WHERE project_name=:project order by catalog_name;";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('project'=>$project));
    if($stmt->rowCount() > 0) {
        $ris = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $optCatalog[]="<option value=\"-1\">Nessun Catalogo</option>";
    }
} catch(Exception $e) {
    $optCatalog[]="<option value=\"-1\">Nessun Catalogo</option>";
}

if($ris) {
	$optCatalog[]="<option value=\"0\">Seleziona ===></option>";
	foreach($ris as $val) $optCatalog[]="<option value=\"$val[catalog_id]\">$val[name]</option>";
}

$ext=explode(",",CATALOG_EXT);
foreach($ext as $e){
	if($e!="SHP") $extension[]=$e;
}

if($_POST["importa"]){
	include ADMIN_PATH."lib/export.php";
	extract($_POST);
	if(!$srid) $srid=-1;
	$error=import_raster($rasterdir,$extension,$objId,$catalog_id,$srid,$filtro,$delete);
	if (!$error) echo "<p>Procedura di importazione Terminata Correttamente.</p>";
	else{
		$mex="<ul><li>".implode("</li><li>",$error)."</li></ul>";
		echo $mex;
	}
}
?>
<script>
function annulla(){
	var frm=xGetElementById('frm_data');
	var inp=xCreateElement("input");
	inp.setAttribute("type","hidden");
	inp.setAttribute("id",'pass');
	inp.setAttribute("value",1);
	var inp=xCreateElement("input");
	inp.setAttribute("type","hidden");
	inp.setAttribute("name",'mode');
	inp.setAttribute("value",'edit');
	xAppendChild(frm,inp);
}
</script>
<table cellPadding="2" border="0" class="stiletabella" width="90%">
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Catalogo</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="catalog_id" id="catalog_id">
				<?php echo @implode('\n',$optCatalog);?>
			</SELECT>
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>EPSG</font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="6" value="" name="srid" id="srid">
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Filtro Nome File</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="15" value="" name="filtro" id="filtro">
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Directory</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="50" value="" name="rasterdir" id="rasterdir">
			<input type="button" class="hexfield" value="Elenco File" onclick="javascript:get_elenco('rasterdir',['catalog_id','filtro']);">
		</td>
	</tr>

	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Modalità</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="delete" >
				<option value="0" selected>Accoda</option>
				<option value="1">Sostituisci</option>
			</SELECT>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		<hr>
			<input type="hidden" name="importa" value="1">
			<input type="hidden" name="project" value="<?php echo $project;?>">
			<input type="hidden" name="level" id="level" value="<?php echo $level;?>">
			<input type="hidden" name="livello" value="<?php echo $livello?>">
			<input type="submit"  class="hexfield" style="width:120px;" value="Importa Raster" name="azione">
			<input type="submit" class="hexfield" value="Annulla" name="azione" style="margin-left:5px;" onclick="javascript:annulla()">
		</td>
	</tr>
</table>