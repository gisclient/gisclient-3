<?php
  include_once "../../config/config.php";
  error_reporting (E_ERROR | E_PARSE);

  $db = GCApp::getDB();
  $sql="SELECT project_name FROM ".DB_SCHEMA.".project;";
  try {
    $ris = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  } catch(Exception $e) {
    echo "<p>Impossibile eseguire la query : $sql</p>";
  }
	
  $opt[]="<option value=\"-1\">Seleziona ===></option>";
  $opt[]="<option value=\"0\">Tutti</option>";
  for($i=0;$i<count($ris);$i++){
    $pr=$ris[$i];
	$opt[]="<option value=\"$pr[project_name]\">$pr[project_name]</option>";
  }
  $prm=$this->parametri;
  $pr=$this->parametri["project"];
  if($_POST["livello"]=="qt") {
    $sql="SELECT layer_id,layer_name FROM ".DB_SCHEMA.".layergroup INNER JOIN ".DB_SCHEMA.".layer USING(layergroup_id) WHERE theme_id=:theme_id order by layer_name";
    $stmt = $db->prepare($sql);
    try {
      $stmt->execute(array('theme_id'=>$this->parametri["theme"]));
    } catch(Exception $e) {
      echo "<p>Impossibile eseguire la query : $sql</p>";
    }
    $ris = $stmt->fetchAll(PDO::FETCH_ASSOC);
    for($i=0;$i<count($ris);$i++){
      $lay=$ris[$i];
	  $opt2[]="<option value=\"$lay[layer_id]\">$lay[layer_name]</option>";
    }
  }
?>
<div class="tableHeader ui-widget ui-widget-header ui-corner-top">
	
<b>Importa</b></div>
<table cellPadding="2" border="0" class="stiletabella" width="90%">
<?php if($_POST["livello"]=="qt"){?>
	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Layer</b></font></td>
		<td colspan="2">
			<select name="layer" id="layer" class="textbox">
				<?php echo @implode('\n',$opt2)?>
			</select>
		</td>
	</tr>
<?php }?>
	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Nome File</b></font></td>
		<td valign="middle" colspan="2">
			<input type="text" class="textbox" size="50" name="filename" id="filename" readonly>
			<input type="button" class="hexfield ui-button ui-widget ui-state-default ui-corner-all" style="width:100px" value="Seleziona" onclick="javascript:openListNew('filename',['project','livello'], false, true);">
		</td>
	</tr>
	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Elimina File</b></font></td>
		<td valign="middle" colspan="2">
			<SELECT class="textbox" id="overwrite" name="overwrite" >
				<OPTION value="0" selected>No</OPTION>
				<OPTION value="1">Si</OPTION>
			</SELECT>
		</td>
	</tr>

	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Nuovo Nome</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="50" value="" name="name" id="name">
		</td>
	</tr>

	<tr>
        <td class="label ui-widget ui-state-default">&nbsp;</td>
		<td colspan="2">
		<hr>
			<input type="hidden" name="importa" value="1">
			<input type="hidden" name="mode" value="view">
			<input type="hidden" name="project" id="project" value="<?php echo $project;?>">
			<input type="hidden" name="obj_id" id="obj_id" value="<?php echo $objId;?>">
			<input type="hidden" name="level" id="level" value="<?php echo $level;?>">
			<input type="hidden" name="livello" id="livello" value="<?php echo $livello?>">
			<input type="submit" class="hexfield" value="Annulla" name="azione" style="margin-left:5px;" onclick="javascript:annulla()">
			<input type="submit" class="hexfield" value="Importa" id="importBtn" name="azione">
            <div id="loadingImg" style="display: none;"><img src="/gisclient3/images/ajax_loading.gif" alt="<?php echo GCAuthor::t('progress') ?>"></div>
		</td>
	</tr>
</table>
<DIV><p id="result" style="color:red; weight: bold;"></p></DIV>
<script type="text/javascript">
$( document ).ready(function() {
  $('#importBtn').click(function(event) {
    event.preventDefault();
    $("#result").empty();
    $('#loadingImg').css('display', 'inline');
    $.ajax({
      url: 'ajax/import.php',
      dataType: 'text',  // what to expect back from the PHP script, if anything
      cache: false,
      contentType: false,
      processData: false,
      data: createFormData(),
      type: 'post',
      success: function(response){
        $('#loadingImg').css('display', 'none');
        $("#result").append(response);
      }
    });
  });
});

function createFormData() {
  var form_data = new FormData();
  form_data.append('obj_id', $('#obj_id').val());
  form_data.append('level', $('#level').val());
  form_data.append('project', $('#project').val());
  form_data.append('livello', $('#livello').val());
  form_data.append('filename', $('#filename').val());
  form_data.append('name', $('#name').val());
  form_data.append('layer', $('#layer').val());
  form_data.append('overwrite', $('#overwrite').val());
  return form_data;
}
</script>

