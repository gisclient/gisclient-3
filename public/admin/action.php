<?php
	require_once "../../config/config.php";

	$db=new sql_db(DB_HOST.":".DB_PORT,DB_USER,DB_PWD,DB_NAME, false);
	if(!$db->db_connect_id)  die( "Impossibile connettersi al database");
	extract($_REQUEST);
	$sql="SELECT id,name,struct_parent_id as parent_id,depth FROM ".DB_SCHEMA.".e_level";
	//echo "<p>$sql</p>";

	if($db->sql_query($sql))
		$ris=$db->sql_fetchrowset();
	for($i=0;$i<count($ris);$i++) $arrLevel[$ris[$i]["name"]]=ARRAY("id"=>$ris[$i]["id"],"parent_id"=>$ris[$i]["parent_id"],"depth"=>$ris[$i]["depth"]);
	for($i=0;$i<count($ris);$i++) $arrLevelId[$ris[$i]["id"]]=ARRAY("name"=>$ris[$i]["name"],"parent_id"=>$ris[$i]["parent_id"],"depth"=>$ris[$i]["depth"]);
	$name=$level;
	$i=0;
	while($name!="project" && $i<count($arrLevel)){
		$tmp1[]=$name;
		$name=$arrLevelId[$arrLevel[$name]["parent_id"]]["name"];
		$i++;
	}
	$tmp=array_reverse($tmp1);
	if($action=="sposta") array_pop($tmp);
	$sel[]="<caption style=\"padding:10px;\"><b>".ucwords($action)." $level </b></caption>";
	for($i=0;$i<count($tmp);$i++){
		
		switch($tmp[$i]){
			case "mapset":
				$js=($i==(count($tmp)-1))?("javascript:setVal(this,'newid')"):("javascript:requestVal(this,'layergroup')");
				$sql="(SELECT '' as id,'Seleziona ====>'as title) UNION ALL (SELECT DISTINCT mapset_name as id,mapset_title as title FROM ".DB_SCHEMA.".mapset WHERE project_name='$project' order by 2);";
				if($db->sql_query($sql))
					$ris=$db->sql_fetchrowset();
				for($j=0;$j<count($ris);$j++) $opt[]="<option value=\"".$ris[$j]["id"]."\">".$ris[$j]["title"]."</option>";
				$sel[]="\n\t\t<tr>
		<td><b>Mapset</b></td>
		<td>
			<select name=\"$tmp[$i]\" id=\"id$tmp[$i]\" onchange=\"$js\">
				".@implode("\n\t\t\t\t",$opt)."
			</select>
		</td>
	</tr>";
	echo "<p>$sql</p>";
				$opt=Array();
				break;
			case "theme":
				$js=($i==(count($tmp)-1))?("javascript:setVal(this,'newid')"):("javascript:requestVal(this,'layergroup')");
				$sql="(SELECT -1 as id,'Seleziona ====>'as title) UNION ALL (SELECT DISTINCT theme_id as id,theme_title as title FROM ".DB_SCHEMA.".theme WHERE project_name='$project' order by 2);";
				if($db->sql_query($sql))
					$ris=$db->sql_fetchrowset();
				for($j=0;$j<count($ris);$j++) $opt[]="<option value=\"".$ris[$j]["id"]."\">".$ris[$j]["title"]."</option>";
				$sel[]="\n\t\t<tr>
		<td><b>Tema</b></td>
		<td>
			<select name=\"$tmp[$i]\" id=\"id$tmp[$i]\" onchange=\"$js\">
				".@implode("\n\t\t\t\t",$opt)."
			</select>
		</td>
	</tr>";
				$opt=Array();
				break;
			case "layergroup":
				$js=($i==(count($tmp)-1))?("javascript:setVal(this,'newid')"):("javascript:requestVal(this,'layer')");
				$sel[]="\n\t\t<tr>
		<td><b>Gruppo di Layer</b></td>
		<td>
			<select name=\"$tmp[$i]\" id=\"id$tmp[$i]\" onchange=\"$js\">
				<option value=\"-1\">Seleziona ====></option>
			</select>
		</td>
	</tr>";
				break;
			case "layer":
				$js=($i==(count($tmp)-1))?("javascript:setVal(this,'newid')"):("javascript:requestVal(this,'class')");
				$sel[]="\n\t\t<tr>
		<td><b>Layer</b></td>
		<td>
			<select name=\"$tmp[$i]\" id=\"id$tmp[$i]\" onchange=\"$js\">
				<option value=\"-1\">Seleziona ====></option>
			</select>
		</td>	
	</tr>";
				break;
			case "class":
				$js="javascript:setVal(this,'newid')";
				$sel[]="\n\t\t<tr>
		<td><b>Classe</b></td>
		<td>
		<select name=\"$tmp[$i]\" id=\"id$tmp[$i]\" onchange=\"$js\">
			<option value=\"-1\">Seleziona ====></option>
		</select>
		</td>
	</tr>";
				break;
		}
	}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<SCRIPT language="javascript" src="./js/http_request.js" type="text/javascript"></SCRIPT>
	<script type="text/javascript" src="js/jquery/jquery.js"></script>
	<script  type="text/javascript" src="./js/Author.js"></script>
	<script>
		
		function requestVal(obj,name){
			var param='azione=request&id='+obj.options[obj.selectedIndex].value+'&parent_level='+obj.name+'&level='+name;
			xRequest('rpc.php',param,'setObj','POST');
		}
		function setObj(arr,obj){
			var sel=$('#id'+obj);
			switch(obj){
				case "theme":
					var _unset=new Array('theme','layergroup','layer','class');
					break;
				case "layergroup":
					var _unset=new Array('layergroup','layer','class');
					break;
				case "layer":
					var _unset=new Array('layer','class');
					break;
				default:
					var _unset=new Array('class');
					break;
			}
			for(i=0;i<_unset.length;i++){
				var o=$('#id'+_unset[i]);
				//alert(typeof(o)+': '+o.options.length);
				if (o){
					o.html('<option>Seleziona ====></option>');			//Aggiungo il Primo elemento
				}
			}
			var code='<option>Seleziona ====></option>\n';
			if (is_array(arr)){
				if (arr.length==0){
					sel.html('<option>Nessun dato definito</option>\n');
				}
				else{
					//sel.options[0]=new Option('Seleziona ====>','');			//Aggiungo il Primo elemento
					for(j=1;j<=arr.length;j++) {
						code+='<option value="'+arr[j-1].id+'">'+arr[j-1].name+'</option>\n';
						//sel.options[j]=new Option(arr[j-1].name,arr[j-1].id);	//Aggiungo le altre opzioni
					}
					sel.html(code);
				}
			}
			else if(typeof(arr)=='object'){
		
			}
		}
		function setVal(obj,id){
			var o =$('#'+id);
			var value=obj.options[obj.selectedIndex].value
			o.val(value);
		}
		function addElement(f,type,name,val){
			
			parent.$('#frm_data').appendChild(new Element('input',{'type':type,'name':name,'value':val}));
		}
		function chiudi(){
			parent.$("#dwindow").css('display',"none");
			parent.$("#cframe").attr('src',"");
		}
		function invia(lev_name){
			var o1 = $('#azione');
			var action =o1.val();
			
			var o=$('#newid');
			var newid=o.val();
			var oldid=$('#oldid').val();
			var f=parent.$('#frm_data');
			parent.$('#'+lev_name).val($('#'+lev_name).val());
			f.append('<input type="hidden" name="dataction[old]" value="'+oldid+'" />');
			f.append('<input type="hidden" name="dataction[new]" value="'+newid+'" />');
			f.append('<input type="hidden" name="azione" value="'+action+'" />');
			//addElement(f,'hidden','dataction[new]',newid);
			//addElement(f,'hidden','azione',action);
			parent.$("#dwindow").css('display',"none");
			parent.$('#azione').val(action);
			f.submit();
		}

	</script>
	<style type="text/css">
		body,td,th {
			font-family: Georgia, Times New Roman, Times, serif;
			font-size:11px;
			color: #000000;
		}
		a:link {
			color: #0000FF;
		}
	a:visited {
			color: #0000FF;
		}
		a:hover {
			color: #FF9966;
		}
		a:active {
			color: #0000FF;
		}
		body {
			background-color: #FFFFDF;
		}
	</style>
</head>
<body>
<table>
	<tr>
		<td><b>Nuovo Nome</b></td>
		<td><input type="text" name="newname" size="30" id="<?php echo $level?>_name"></td>
	</tr>
<?php echo @implode("",$sel);?>
</table>
<hr>
<table>
	<tr>
		<td><input type="button" value="Chiudi" class="hexfield" onclick="javascript:chiudi();"></td>
		<td><input type="button" value="Esegui" class="hexfield" onclick="javascript:invia('<?php echo $level?>_name')"></td>

	</tr>
</table>
<input type="hidden" id="oldid" value="<?php echo $id;?>">
<input type="hidden" id="newid" value="">
<input type="hidden" id="azione" value="<?php echo $action;?>">
</body>
</html>