
<div class="tableHeader ui-widget ui-widget-header ui-corner-top">
	
<b>Esporta</b></div>
<table class="stiletabella">
	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Nome File</b></font></td>
		<td valign="middle" colspan="2">
			<input type="text" class="textbox" name="filename" id="filename">
		</td>
	</tr>

	<tr>
		<td class="label ui-widget ui-state-default"><font color="#FFFFFF"><b>Sovrascrivi File</b></font></td>
		<td valign="middle" colspan="2">
			<SELECT class="textbox" id="overwrite" name="overwrite" >
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
			<input type="hidden" id="project" name="project" value="<?php echo $project;?>">
			<input type="hidden" id="obj_id" name="obj_id" value="<?php echo $objId;?>">
			<input type="hidden" id="level" name="level" value="<?php echo $level;?>">
			
			<button name="azione" class="hexfield" type="submit" value="annulla" onclick="javascript:annulla();"><?php echo GCAuthor::t('button_cancel') ?></button>
			<button id="exportBtn" name="azione" class="hexfield" type="submit" value="esporta"><?php echo GCAuthor::t('button_export') ?></button>
            <div id="loadingImg" style="display: none;"><img src="/gisclient3/images/ajax_loading.gif" alt="<?php echo GCAuthor::t('progress') ?>"></div>
		</td>
	</tr>
</table>
<DIV><p id="result" style="color:red; weight: bold;"></p></DIV>
<script type="text/javascript">
$( document ).ready(function() {
  $('#exportBtn').click(function(event) {
    event.preventDefault();
    $("#result").empty();
    $('#loadingImg').css('display', 'inline');
    $.ajax({
      url: 'ajax/export.php',
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
  form_data.append('level', $('#level').val());
  form_data.append('project', $('#project').val());
  form_data.append('obj_id', $('#obj_id').val());
  form_data.append('filename', $('#filename').val());
  form_data.append('overwrite', $('#overwrite').val());
  form_data.append('livello', $('#prm_livello').val());
  return form_data;
}
</script>

