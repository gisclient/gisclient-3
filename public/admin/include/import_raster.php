<?php
require_once "../../config/config.php";
$db = GCApp::getDB();
$ris = null;
$defaultSrid = "";
$optCatalog = array();
$connectionType = 1;
$sql="SELECT DISTINCT catalog_id,catalog_name as name,project_srid as srid FROM "
  .DB_SCHEMA.".catalog natural join ".DB_SCHEMA.".project WHERE project_name=:project "
  ."and connection_type=:connection order by catalog_name;";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute(array('project'=>$project, 'connection'=>$connectionType));
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
	foreach($ris as $val) {
      $optCatalog[]="<option value=\"$val[catalog_id]\">$val[name]</option>";
      $defaultSrid = $val["srid"];
    }
    
}

$ext=explode(",",CATALOG_EXT);
foreach($ext as $e){
	if($e!="SHP") $extension[]=$e;
}

if(isset($_POST["importa"])){
    include_once ADMIN_PATH."lib/export.php";
	extract($_POST);
	if(!$srid) $srid=$defaultSrid;
	if(!empty($data)) {
	  if($delete)
        $error= deleteRasterLayer($objId);
      if (!$error) {
        $files = explode(" ", $data);
        foreach($files as $currentFile) {
          $error=import_raster($currentFile,$extension,$objId,$catalog_id,$srid, $layerDef)/*,$filtro)*/;
	      if (!$error)
            echo "<p>Procedura di importazione per ".$currentFile." terminata correttamente.</p>";
	      else{
		    $mex="<ul><li>".implode("</li><li>",$error)."</li></ul>";
		    echo $mex;
          }
	    }
      } else {
        $mex="<ul><li>".implode("</li><li>",$error)."</li></ul>";
		echo $mex;
      }
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
		<td width="200px" class="label ui-widget ui-state-default"><b>Catalogo</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="catalog_id" id="catalog_id">
				<?php echo @implode('\n',$optCatalog);?>
			</SELECT>
		</td>
	</tr>
	<tr>
		<td width="200px" class="label ui-widget ui-state-default"><b>EPSG</font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="6" value="" name="srid" id="srid">
            <label for="srid">(default: <?php echo $defaultSrid; ?>)</label>
		</td>
	</tr>
	<tr>
		<td width="200px" class="label ui-widget ui-state-default"><b>Lista File</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="50" value="" name="data" id="data" readonly>
			<input type="button" class="hexfield" value="Elenco File" onclick="javascript:openListRaster('data',['catalog_id','layertype_id','layergroup','project']);">
		</td>
	</tr>
    <tr>
		<td width="200px" class="label ui-widget ui-state-default"><b>Definizione</b></font></td>
        <td valign="middle">
            <textarea name="layerDef" id="layerDef" cols="50" rows="5"></textarea>
		</td>
	</tr>
	<tr>
		<td width="200px" class="label ui-widget ui-state-default"><b>Modalità</b></font></td>
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
