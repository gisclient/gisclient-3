<?php
error_reporting (E_ERROR | E_PARSE);

	$db = GCApp::getDB();
    $fName='';
	if(isset($_POST["esporta"])){
		$array_levels = array();
		include_once ADMIN_PATH."lib/export.php";
		$level=$_POST["level"];
		$project=$_POST["project"];
		$objId=$_POST["obj_id"];
		$fName=$_POST["filename"];
		$overwrite=$_POST["overwrite"];
		//$pkey=parse_ini_file(ADMIN_PATH."include/primary_keys.ini");
		$structure=_getPKeys();
		if (!file_exists(ADMIN_PATH."export/$fName") || $overwrite){
			if (file_exists(ADMIN_PATH."export/$fName")) $overwrite_message="Il File $fName è stato sovrascritto.";
			if($_POST["azione"]="Esporta"){
				$l=$structure["pkey"][$_POST["livello"]][0];
				$sql="select e_level.id,e_level.name,coalesce(e_level.struct_parent_id,0) as parent,X.name as parent_name,e_level.leaf from ".DB_SCHEMA.".e_level left join ".DB_SCHEMA.".e_level X on (e_level.struct_parent_id=X.id) order by e_level.depth asc;";
                try {
                    $stmt = $db->query($sql);
                    //secondo me questo array_levels non serve a niente...
                    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $array_levels[$v["id"]]=Array("name"=>$v["name"],"parent"=>$v["parent"],"leaf"=>$v["leaf"]);
                    }
                } catch(Exception $e) {
                    die("<p>Impossibile eseguire la query : $sql</p>");
                }
				$r=_export($fName,$_POST["livello"],$project,$structure,1,'',Array("$l"=>$objId));
				
				$message="$overwrite_message <br> FILE <a href=\"#\" onclick=\"javascript:openFile('".ADMIN_PATH."export/$fName')\">$fName<a/> ESPORTATO CORRETTAMENTE";
			}
		}
	
			$resultForm="<DIV id=\"result\">
		<p style=\"color:red;\"><b>$message</b></p>
	<form name=\"file\" id=\"file\" target=\"_new\" method=\"POST\">
		
	</form>
</DIV>";
	}
	
?>
	<script>
		function openFile(f){
			var frm=$('file');
			frm.action='download.php'
			frm.appendChild(new Element('input',{'type':'hidden','name':'file','value':f})); 
			frm.appendChild(new Element('input',{'type':'hidden','name':'action','value':'view'})); 
			frm.appendChild(new Element('input',{'type':'hidden','name':'type','value':'text'}));
			frm.submit();
		}

	</script>
<div class="tableHeader ui-widget ui-widget-header ui-corner-top">
	
<b>Esporta</b></div>
<table class="stiletabella">
	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Nome File</b></font></td>
		<td valign="middle" colspan="2">
			<input type="text" class="textbox" value="<?php echo $fName?>" name="filename" id="filename">
		</td>
	</tr>

	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Sovrascrivi File</b></font></td>
		<td valign="middle" colspan="2">
			<SELECT class="textbox" name="overwrite" >
				<OPTION value="0" selected>No</OPTION>
				<OPTION value="1">Si</OPTION>
			</SELECT>
		</td>
	</tr>
	<tr>
        <td class="label ui-widget ui-state-default">&nbsp;</td>
		<td colspan="2">
		<hr>
			<input type="hidden" name="esporta" value="1">
			<input type="hidden" name="project" value="<?php echo $project;?>">
			<input type="hidden" name="obj_id" value="<?php echo $objId;?>">
			<input type="hidden" name="level" value="<?php echo $level;?>">
			
			<button name="azione" class="hexfield" type="submit" value="annulla" onclick="javascript:annulla();"><?php echo GCAuthor::t('button_cancel') ?></button>
			<button name="azione" class="hexfield" type="submit" value="esporta"><?php echo GCAuthor::t('button_export') ?></button>
		</td>
	</tr>
</table>
