<?php
require_once "../../config/config.php";
$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
if(!$db->db_connect_id)  die( "Impossibile connettersi al database");


/*foreach($_REQUEST["dati"] as $key=>$val){
	if($val) 
		echo "<input type=\"hidden\" name=\"dati[$key]\" value=\"$val\">\t\t\n";

}*/

?>
<script>
	$(document).ready(function(){
		$('#frm_param').append('<input type="hidden" name="livello" value="layer" />');
		$('#method').bind('change',function(){
			var obj=$('#method');
			var tr=$('#tr_nbins');
			if(obj.val()==2)
				tr.css('display','none');
			else
				tr.css('display','');
		});
		$('#annulla').bind('click',function(){
			$('#frm_param').submit();
		});
	});
	function classify(param){
		var d = {'azione':'classify'};
		for(i=0;i<param.length;i++){
			v=$('#'+param[i]).val();
			if (!v){
				if (!confirm('Manca il campo '+param[i]+'.\nSei sicuro di voler procedere?'))
					return;
			}
			eval('d.'+param[i]+'= \''+ v +'\';');
		}
		$.getJSON('rpc.php',d,function(data){
				code='<form id="form_dati" method="POST">\n';
				code+='<table class="legend">\n\t';
				//eval("var data='"+res+"'");
				
				for(i=0;i<data.length;i++){
					
					code+='<tr>\n\t\t';
					code+='<td class="legend" style="background-color:#'+data[i].color+'">\n\t&nbsp;\n\t</td>\n\t\t';
					code+='<td><span class="legend">'+data[i].val+'</span>\n\t\t';
					code+='<input type="hidden" name="dati[class]['+i+'][class_name]" id="" value="'+data[i].name+'" />\n\t\t';
					code+='<input type="hidden" name="dati[class]['+i+'][class_title]" id="" value="'+data[i].title+'" />\n\t\t';
					code+='<input type="hidden" name="dati[class]['+i+'][expression]" id="" value="'+data[i].condition+'" />\n\t\t';
					code+='<input type="hidden" name="dati[class]['+i+'][legend_type]" id="" value="'+data[i].legend_type+'" />\n\t</td>\n\t';
					code+='<input type="hidden" name="dati[style]['+i+'][style_name]" id="" value="stile_1" />\n\t</td>\n\t';
					code+='<input type="hidden" name="dati[style]['+i+'][color]" id="" value="'+data[i].color+'" />\n\t</td>\n\t';
					code+='</tr>\n';
				}
				code+='</table>';
				code+='<input type="hidden" name="classify" value="1" id="classify" />\n';
				code+='<input type="hidden" name="azione" value="classify_save" id="azione" />\n';
				code+='</form>';
				$('#classification').attr('innerHTML',code);
				if (!$('#save').attr('id')) $('#button').append('<input type="button" class="hexfield" value="Salva" name="azione" id="save" style="margin-left:5px;" />');
				$('#save').bind('click',function(){
					var d=$('#form_dati').serialize();
					if (confirm('Attenzione confermando si elimineranno tutte le classificazioni esistenti per questa feature!\nSiete sicuri di voler proseguire?'))
					$.ajax({
						url:'rpc.php',
						data:d+'&layer='+$('#prm_layer').val(),
						success:function(data){
							$('#frm_param').submit();
						}
					})
				});
			}
		);
	}
</script>
<style>
	table.legend{
		width:90%;
	}
	td.legend{
		width:20px;
		height:7px;
		border:1px solid black;
		
	}
	span.legend{
		font-size:12px;
		font-weight:bold;
	}
	
</style>

<table cellPadding="2" border="0" class="stiletabella" width="90%">
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Campo per Classificazione</b></font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="16" value="" name="classField" id="classField">
			
			<input class="hexfield" style="width:100px;" type="button" value="Elenco" onclick="javascript:get_elenco('classField',['prm_layer'])" />

		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Metodo Classificazione</b></font></td>
		<td valign="middle">
			<select class="textbox" name="metodo" id="method">
				<option value="1">Intervalli</option>
				<option value="2">Singoli Valori</option>
			</select>
		</td>
	</tr>
	<tr id="tr_nbins">
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>N. Intervalli</font></td>
		<td valign="middle">
			<input type="text" class="textbox" size="6" value="" name="nbins" id="nbins">
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Colore Iniziale</font></td>
		<td valign="middle">
			<INPUT type="text" maxLength="11" size="11"  class="textbox" name="startCol" id="startCol" value="" /><a href="#" onclick="javascript:open_color('startCol')"><img src="images/paste.gif" border=0 style="width:18px;height:18px;" /></a>
		</td>
	</tr>
	<tr>
		<td width="200px" bgColor="#728bb8"><font color="#FFFFFF"><b>Colore Finale</font></td>
		<td valign="middle">
			<INPUT type="text" maxLength="11" size="11"  class="textbox" name="endCol" id="endCol" value="" /><a href="#" onclick="javascript:open_color('endCol')"><img src="images/paste.gif" border=0 style="width:18px;height:18px;" /></a>
		</td>
	</tr>
	<tr id="row-classification">
		<td colspan="2">
			<div id="classification">
				
			</div>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		<hr>
		<div id="button">
			<input type="button"  class="hexfield" style="width:120px;" value="Classifica" name="azione" onClick="javascript:classify(['prm_layer','classField','nbins','startCol','endCol'])">
			<input type="button" class="hexfield" value="Annulla" name="azione" id="annulla" style="margin-left:5px;">
		</div>
		</td>
	</tr>
</table>