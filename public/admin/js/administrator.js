/*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

	function accedi(){
		var usr=$('#username');
		var pwd=$('#password');
		var enc_pwd=hex_md5(pwd.val());
		if (!usr.value){
			alert('Inserire il Nome Utente');
			return;
		}
		if (!pwd.value){
			alert('Inserire la PassWord');
			return;
		}
		pwd.val('');
		xRequest('./xserver/xClient.php','azione=entra&username='+usr.value+'&pwd='+enc_pwd,'setIndex','POST');
		return false;
	}
	
/*----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/	
function is_array(obj){
	if(typeof(obj)=='object' && typeof(obj.length)=='number')
		return true;
	else
		return false;
}
function isArray(obj) {
	var ret = false;
	if ((obj) && (!(isNaN(parseInt(obj.length))))) {
		var len = parseInt(obj.length, 10);
		if (len == obj.length) {
			obj[len] = 'probe';
			ret = (len < obj.length);
			delete obj[len];
		}
	}
	return ret;
}

function navigate(arr_key,arr_val){
	var f=$("#frm_label");
	for(i=0;i<arr_key.length;i++){
		var name="parametri["+i+"]["+arr_key[i]+"]";
		$('<input type="hidden" name="'+name+'" value="'+arr_val[i]+'">').appendTo(f);
	}
	if (arr_key.length>0){
		$('<input type="hidden" name="livello" value="'+arr_key[arr_key.length-1]+'">').appendTo(f);
	}
	f.submit();
}
function link(id,level){
	var f = $('#frm_param');
	
	f.append('<input type="hidden" name="parametri[]['+level+']" value="'+id+'" />'); // AGGIUNGO AI PARAMETRI IL VALORE DI DESTINAZIONE
	f.append('<input type="hidden" name="livello" value="'+level+'" />');  //LIVELLO DI DESTINAZIONE
	f.serialize();
	f.submit();
}
function edit(id, level) {
	var f = $('#frm_param');
	f.append('<input type="hidden" name="parametri[]['+level+']" value="'+id+'" />'); // AGGIUNGO AI PARAMETRI IL VALORE DI DESTINAZIONE
	f.append('<input type="hidden" name="livello" value="'+level+'" />');  //LIVELLO DI DESTINAZIONE
	f.append('<input type="hidden" name="mode" value="edit" />');
	f.serialize();
	f.submit();
}
function deleteRow(pkeys, values, level, config_file) {
	if(!confirm('Sei sicuro di voler eliminare il record ? ')) return;
	var f=$("#frm_param");
	var pkeysArray = pkeys.split(',');
	var valuesArray = values.split(',');
	$.each(pkeysArray, function(i, pkey) {
		f.append('<input type="hidden" name="pkey['+i+']" value="'+pkey+'"/>');
		f.append('<input type="hidden" name="pkey_value['+i+']" value="'+valuesArray[i]+'"/>');
	});
	var lastRow = pkeysArray.length-1;
	f.append('<input type="hidden" name="parametri[]['+level+']" value="'+valuesArray[lastRow]+'"/>');
	f.append('<input type="hidden" name="livello" value="'+level+'" />');
	f.append('<input type="hidden" name="config_file" value="'+config_file+'" />');
	f.append('<input type="hidden" name="modo" value="edit" />');
	f.append('<input type="hidden" name="azione" value="Elimina" />');
	f.submit();
}
function elimina(param){
	if (confirm('Sei sicuro di voler eliminare il record ?')){
		var f=$("#frm_data");
		if(is_array(param)){
			f.append('<input type="hidden" name="azione" value="cancella"/>');
			//f.appendChild(new Element('input',{'type':'hidden','name':'azione','value':'cancella'})); // AGGIUNGO AL POST L'AZIONE CANCELLA
			for(i=0;i<param.length;i++){
				var obj=param[i];
				f.append('<input type="hidden" name="pkey['+i+']" value="'+obj.pkey+'"/>');
				f.append('<input type="hidden" name="pkey_value['+i+']" value="'+obj.pkvalue+'"/>');
				//f.appendChild(new Element('input',{'type':'hidden','name':'pkey['+i+']','value':obj.pkey})); // AGGIUNGO AL POST LE CHIAVI PRIMARIE
				//f.appendChild(new Element('input',{'type':'hidden','name':'pkey_value['+i+']','value':obj.pkvalue})); // AGGIUNGO AL POST I VALORI DELLE CHIAVI PRIMARIE
			}
			f.submit();
		}
	}
}

function open_color(fld){
	window.open('coloreditor/color.php?fld='+fld+'','colorchoser','height=350,width=390,dependent=yes,directories=no,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');
}
function showElement(el,i,flag){
	switch (el){
		case "legendtype_id":
			if (flag==2)
				$('#upl_legend_icon').attr('disabled',0);
			else
				$('#upl_legend_icon').attr('disabled',1);
		case "filetype_id":
			if(flag==-1)
				$('#upl_file').attr('disabled',1);
			else
				$('#upl_file').attr('disabled',0);
			break;
		default:
			break;
	}
}
function encript_pwd(id,f){
	var pwd=$('#'+id);
	
	var enc_pwd=$('#enc_'+id);
	var frm=$('#'+f);

	if(pwd.val()){
		
		enc_pwd.val(hex_md5(pwd.val()));
		pwd.val('');
		return true;
	}
	else
		return false;
}
function validateSpezia(id,f){
	setAction(f,window.location.search.substring(1));
	var pwd=$('#'+id);
	var enc_pwd=$('#enc_'+id);
	var frm=$('#'+f);
	
	if(pwd.value){
		
		enc_pwd.value=hex_md5(pwd.value);
		pwd.value='';
		return true;
	}
	else
		return false;
}
function setAction(f,param){
	var frm=$(f);
	if(param) frm.attr('action',frm.attr('action')+'?'+param);
}
function xShowImg(imgName){
		var img=$('#'+imgName);
		
		if(img && typeof(img.style)!="undefined"){
			img.style.left= ((xClientWidth()/2)-50);
			img.style.top=((xClientHeight()/2)-20);
			img.style.display='';
			//alert(img.name);	
		}
		//else
		//	alert('NO IMAGE');
	}
function xHideImg(imgName){
	var img=$(imgName);
	if(img && typeof(img.style)!="undefined"){
		img.style.display='none';
	}
}

function selectAll(btn,name){
	var reg1=new RegExp("Seleziona");
	var flag=0;
	if(reg1.test(btn.value)){
		flag=1;
		var v=0;
		btn.value=btn.value.replace('Seleziona','Deseleziona')
	}
	else{
		flag=0;
		var v=1;
		btn.value=btn.value.replace('Deseleziona','Seleziona')
	}
	var frm=$('#frm_data');
	
	switch(name){
		/*case "qt":
			var reg=new RegExp("qt_id");
			var typeobj='check';
			break;
		case "layergroup":
			var reg=new RegExp("layergroup_id");
			var typeobj='check';
			break;
		case "table_name":
			var reg=new RegExp("table_name");
			var typeobj='check';
			break;*/
		case "project":
			var reg=new RegExp("project");
			var typeobj='check';
			break;
		/*case "link":
			var reg=new RegExp('link_id');
			var typeobj='check';
			break;*/
		default:
			var reg=new RegExp(name+'_id');
			var typeobj='check';
			break;
			
	}
	if (frm.elements.length>300){
		xShowImg('wait');
	}
	for (i=0;i<frm.elements.length;i++){
		var obj=frm.elements[i];
		if (reg.test(obj.name)){
			switch(typeobj){
				case "check":
					obj.checked=flag;
					break;
				case "radio":
					var obj1=document.getElementsByName(obj.name);
					obj1[v].checked=flag;
					break;
			}
		}
		
	}
	xHideImg('wait');
}
function enter(){
	var usr=$('#username');
	var pwd=$('#password');
	alert('');
	var enc_pwd='';
	if (!usr.value){
		alert('Inserire il Nome Utente');
		return false;
	}
	if (!pwd.value){
		alert('Inserire la PassWord');
		return false;
	}
	enc_pwd=hex_md5(pwd.value);
	var param='action=validate&username='+usr.value+'&enc_password='+enc_pwd;
	//alert(param);
	xRequest('login.php',param,'setIndex','POST');
	return false;
}
function setIndex(obj){
	if (typeof(obj.code)=='undefined'){
		var code='<ul>';
		for(i=0;i<obj.mapset.length;i++){
			var map=obj.mapset[i];
			var tit=obj.title[i];
			var templ=obj.template[i];
			code+='<li><a href="javascript:openMap('+map+',\''+templ+'\')">'+tit+'</a></li>'
		}
		code+="</ul>";
	}
	else{
		var code=obj.code;
	}
	
	$("main").innerHtml=code;
	//alert($("main").innerHtml);
}
function setMessage(obj){
	if (typeof(obj.div)!='undefined')
	$(obj.div).innerHTML=obj.message;
}
// Funzione che Inizializza i controlli di una pagina
function initForm(page){
	switch(page){
		case "":
			break;
		default:
			break;
	}
}

function requestCatalog(tb_import){
	var val=tb_import.options[tb_import.selectedIndex].value;
	if (val>0){
		var param='azione=reqCatalog&tb_import_id='+val; 
		xRequest('rpc.php',param,'setCatalog','POST');
	}
	else{
		$('dbtype_id').disabled=false;
		$('hostname').disabled=false;
		$('dbname').disabled=false;
		$('dbschema').disabled=false;
		$('dbport').disabled=false;
		$('username').disabled=false;
		$('pwd').disabled=false;
	}
}

function setCatalog(o){
	var obj=o.field;
	if (is_array(obj)){
		for(i=0;i<obj.length;i++){
			var fld=obj[i];
			setValue(fld.id,fld.value);
		}
	}
}
function setValue(id,val){
	var obj=$(id);
	if (obj){
		switch(typeof(obj)){
			case "select-one":
				for(i=0;i<obj.options.length;i++){
					if(obj.options[i].value==val){
						obj.options[i].selected=true;
						return;
					}
				}
				break;
			default:
				obj.value=val;
				break;
		}
	}
}

function setControls(arrObj){
	if (is_array(arrObj)){
		for (j=0;j<arrObj.length;j++){
			if(arrObj[j].id){
				var obj=arrObj[j];
				var ctr=($("#"+obj.id).attr('id'))?($("#"+obj.id)):(parent.$("#"+obj.id));
				switch(ctr.attr('type')){
						case "select-one":
							$("#"+obj.id+" option[value='"+obj.value+"']").attr("selected",true)
							break;
						case "checkbox":
							ctr.checked=true;
							break;
						default:
							parent.$("#"+obj.id).val(obj.value);
							break;
					}
				
			}
		}
	}
}

function requestRpcUsergroup(obj){
	param='azione=request_usergroup&project_id='+obj.options[obj.selectedIndex].value;
	xRequest('rpc.php',param,'setUsergroup','POST');
}

function setUsergroup(arr){
	var sel=$('usergroup_id');
	for(j=sel.length-1;j>=0;j--){
		sel.remove(j);	//Rimuovo tutte le opzioni dal select
	}
	if (is_array(arr)){
		if (arr.length==0){
			sel.options[0]=new Option('Nessun Ruolo Definito','');
		}
		else if (arr.length==1){
			sel.options[0]=new Option(arr[0].name,arr[0].id);	//Aggiungo le altre opzioni
		}
		else{
			sel.options[0]=new Option('Seleziona ====>','');			//Aggiungo il Primo elemento
			for(j=1;j<=arr.length;j++) sel.options[j]=new Option(arr[j-1].name,arr[j-1].id);	//Aggiungo le altre opzioni
		}
	}
	else if(is_object(arr)){
		
	}
	
}
function unsetFK(obj){
	$('#'+obj).val('');
	$('#fk_'+obj).val('');
}
function logout(){

window.location="index.php?logout=1";

}



$(document).ready(function() {	
	if(typeof(initOgcServices) == 'undefined' || !initOgcServices) return;
	
	$('div#ogc_services_getcapabilities').dialog({
		autoOpen: false,
		title: $('div#ogc_services_getcapabilities').attr('data-title'),
		width: 800,
		height: 600
	});
	
	$('a[data-action="ogc_services"]').show().click(function(event) {
		event.preventDefault();
		$('div#ogc_services_getcapabilities').dialog('open');
	});
	
	$('div#ogc_services_getcapabilities a[data-action="getcapabilities"]').button({icons:{primary:'ui-icon-extlink'}, text:false});
	

});