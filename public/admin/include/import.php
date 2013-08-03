<?php
	
	include_once "../../config/config.php";
	error_reporting (E_ERROR | E_PARSE);


	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	$sql="SELECT project_name FROM ".DB_SCHEMA.".project;";
	if(!$db->sql_query($sql))
		echo "<p>Impossibile eseguire la query : $sql</p>";
	else
		$ris=$db->sql_fetchrowset();
	$opt[]="<option value=\"-1\">Seleziona ===></option>";
	$opt[]="<option value=\"0\">Tutti</option>";
	for($i=0;$i<count($ris);$i++){
		$pr=$ris[$i];
		$opt[]="<option value=\"$pr[project_name]\">$pr[project_name]</option>";
	}
	$prm=$this->parametri;
	$pr=$this->parametri["project"];
	if($_POST["livello"]=="qt" && !$_POST["importa"]){	
		$theme_id=$this->parametri["theme"];
		$sql="SELECT layer_id,layer_name FROM ".DB_SCHEMA.".layergroup INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id) WHERE theme_id=$theme_id order by layer_name";
		if(!$db->sql_query($sql))
			echo "<p>Impossibile eseguire la query : $sql</p>";
		else
			$ris=$db->sql_fetchrowset();
		for($i=0;$i<count($ris);$i++){
			$lay=$ris[$i];
			$opt2[]="<option value=\"$lay[layer_id]\">$lay[layer_name]</option>";
		}	
		
	}
	
	if($_POST["importa"]){
		
		include_once ADMIN_PATH."lib/export.php";
		$objId=$_POST["obj_id"];
		$level=$_POST["level"];
		$project=$_POST["project"];
		$livello=$this->livello;
		$fName=$_POST["filename"];
		$newName=$_POST["name"];
		$layer=$_POST["layer"];
		if($project!=''){
			$sql="SELECT project_name FROM ".DB_SCHEMA.".project WHERE project_name='$project';";
			if(!$db->sql_query($sql))
				echo "<p>Impossibile eseguire la query : $sql</p>";
			else
				$projectName=$db->sql_fetchfield("project_name");
		}
		else
			$projectName=$_POST["name"];
		if (!file_exists(ADMIN_PATH."export/$fName"))
			$message="File non Esiste.";
		else{
			$parentId=Array($objId);
			if($layer) {
				$layer=$_POST["layer"];
				$objId=$_POST["obj_id"];
			}
			$error=import(ADMIN_PATH."export/$fName",$objId,$projectName,$newName,$layer);
			
			
			if (!$error) echo "<p>Procedura di importazione Terminata Correttamente.</p>";
			else{
				$mex="<ul><li>".implode("</li><li>",$error)."</li></ul>";
				echo $mex;
			}
		}
	}
?>
<table cellPadding="2" border="0" class="stiletabella" width="90%">
	<tr>
		<!--<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>File dal Progetto</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="imp_project" id="imp_project">
				<?php //echo @implode('\n',$opt)?>
			</SELECT>
		</td>
	</tr>-->
<?php if($_POST["livello"]=="qt"){?>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Layer</b></font></td>
		<td>
			<select name="layer" class="textbox">
				<?php echo @implode('\n',$opt2)?>
			</select>
		</td>
	</tr>
<?php }?>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Nome File</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="50" value="<?php echo $fName?>" name="filename" id="filename">
			<input type="button" class="hexfield" value="Elenco File" onclick="javascript:get_elenco('filename',['project','livello']);">
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Elimina File</b></font></td>
		<td valign="middle">
			<SELECT class="textbox" name="overwrite" >
				<OPTION value="0" selected>No</OPTION>
				<OPTION value="1">Si</OPTION>
			</SELECT>
		</td>
	</tr>

	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Nuovo Nome</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="50" value="" name="name" id="name">
		</td>
	</tr>

	<tr>
		<td colspan="2">
		<hr>
			<input type="hidden" name="importa" value="1">
			<input type="hidden" name="mode" value="view">
			<input type="hidden" name="project" value="<?php echo $project;?>">
			<input type="hidden" name="obj_id" value="<?php echo $objId;?>">
			<input type="hidden" name="level" id="level" value="<?php echo $level;?>">
			<input type="hidden" name="livello" id="livello" value="<?php echo $livello?>">
			<input type="submit" class="hexfield" value="Importa" name="azione">
			<input type="submit" class="hexfield" value="Annulla" name="azione" style="margin-left:5px;" onclick="javascript:annulla()">
		</td>
	</tr>
</table>
