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
        //  alert('NO IMAGE');
    }
function xHideImg(imgName){
    var img=$(imgName);
    if(img && typeof(img.style)!="undefined"){
        img.style.display='none';
    }
}

function selectAll(btn,name){
    var v, reg,typeobj;
    var reg1=new RegExp("Seleziona");
    var flag=0;
    if(reg1.test(btn.value)){
        flag=1;
        v=0;
        btn.value=btn.value.replace('Seleziona','Deseleziona');
    }
    else{
        flag=0;
        v=1;
        btn.value=btn.value.replace('Deseleziona','Seleziona');
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
            reg=new RegExp("project");
            typeobj='check';
            break;
        /*case "link":
            var reg=new RegExp('link_id');
            var typeobj='check';
            break;*/
        default:
            reg=new RegExp(name+'_id');
            typeobj='check';
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
    var code;
    if (typeof(obj.code)=='undefined'){
        code='<ul>';
        for(i=0;i<obj.mapset.length;i++){
            var map=obj.mapset[i];
            var tit=obj.title[i];
            var templ=obj.template[i];
            code+='<li><a href="javascript:openMap('+map+',\''+templ+'\')">'+tit+'</a></li>';
        }
        code+="</ul>";
    }
    else{
        code=obj.code;
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
                            $("#"+obj.id+" option[value='"+obj.value+"']").attr("selected",true);
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
        sel.remove(j);  //Rimuovo tutte le opzioni dal select
    }
    if (is_array(arr)){
        if (arr.length === 0){
            sel.options[0]=new Option('Nessun Ruolo Definito','');
        }
        else if (arr.length==1){
            sel.options[0]=new Option(arr[0].name,arr[0].id);   //Aggiungo le altre opzioni
        }
        else{
            sel.options[0]=new Option('Seleziona ====>','');            //Aggiungo il Primo elemento
            for(j=1;j<=arr.length;j++) sel.options[j]=new Option(arr[j-1].name,arr[j-1].id);    //Aggiungo le altre opzioni
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

function symbolsListLoaded() {
    $('#dialog_symbology #raster table button').click(function(e){
        var row = $(this).parent().parent(); // .attr('data-row_id')
        var td = row.find('.data-symbol');
        var symbolName = td.html();
        $.ajax({
            url: 'ajax/symbols.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action:'delete',
                symbol_name:symbolName
            },
            success: function(response) {
                if (response.result === 'ok') {
                    symbolsLoadList();
                } else {
                    alert('ERROR! \n' + response.error);
                    console.log(response);
                }
            }
        });
    });
}

function symbolsLoadList() {
    var params = {type:'PIXMAP'};
    var list = new GCList('symbol_user_pixmap');
    list.dialogId = 'raster';
    list.options = {
        handle_click:false,
        events: {
            list_loaded: symbolsListLoaded
        }
    };
    list.loadList(params);
}

function fontLoadList() {
    
    var fontName = $('#font #loadFont').val();
    if (fontName.length == 0) fontName = 'r3-map-symbols.ttf';
    fontName = fontName.substring(fontName.lastIndexOf('\\')+1);
    
    var params = {type:'TRUETYPE', font_name: fontName};
    var list = new GCList('symbol_user_font');
    list.dialogId = 'font';
    list.options = {
        handle_click:false,
        events: {
            list_loaded: loadFont
        }
    };
    list.loadList(params);
}

function importSymbols() {
    function readFile(file) {
        var FR = new FileReader();
        FR.onload = function(e) {
            $.ajax({
                url: 'ajax/symbols.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'import',
                    symbol_file: e.target.result,
                    file_name: file.name
                },
                success: function(response){
                    if (response.result === 'ok') {
                        symbolsLoadList();
                    } else {
                        alert('ERROR! \n' + response.error);
                        console.log(response);
                    }
                }
            });
        };
        FR.readAsDataURL(file);
    }

    var input = document.getElementById('importSymbols');
    if (input.files && input.files.length > 0) {
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            readFile(file);
        }
    }
}

function downloadFont() {
   var fontName = $('#font #loadFont').val();
   if (fontName.length == 0) fontName = 'r3-map-symbols.ttf';
   fontName = fontName.substring(fontName.lastIndexOf('\\')+1);
   window.location.assign("getFont.php?font="+fontName); 
}

function importFont() {
    function readFile(file) {
        var FR = new FileReader();
        FR.onload = function(e) {
            var fontName = $('#font #loadFont').val();
            if (fontName.length == 0) fontName = 'r3-map-symbols.ttf';
            fontName = fontName.substring(fontName.lastIndexOf('\\')+1);
            $.ajax({
                url: 'ajax/font.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'import',
                    font_file: e.target.result,
                    file_name: fontName
                },
                success: function(response){
                    if (response.result === 'ok') {
                        alert('Font importato correttamente');
                        $('div#dialog_symbology').dialog('close');
                    } else {
                        alert('ERROR! \n' + response.error);
                        console.log(response);
                    }
                }
            });
        };
        FR.readAsDataURL(file);
    }

    var input = document.getElementById('loadFont');
    if (input.files && input.files.length > 0) {
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            readFile(file);
        }
    }
}

function loadFont() {
    function readFile(file) {
        var FR = new FileReader();
        FR.onload = function(e) {
            var font = opentype.parse(e.target.result);

            for (var i = 33; i <= 126; i++) {
                var glyph = font.charToGlyph(String.fromCharCode(i));

                if(glyph.index > 0) {
                    var canvas = document.createElement('canvas');
                    canvas.height = 50;
                    canvas.width = 100;
                    var ctx = canvas.getContext('2d');
                    glyph.draw(ctx, 20, 30, 30);
                    
                    $('tr[data-row_id='+ (i-33) +'] .data-image').append(canvas);
                    if(!!$('tr[data-row_id='+ (i-33) +'] .data-name input').val()){
                        $('tr[data-row_id='+ (i-33) +']').addClass('font-old');
                    } else {
                        $('tr[data-row_id='+ (i-33) +']').addClass('font-new');
                    }
                } else {
                    if(!!$('tr[data-row_id='+ (i-33) +'] .data-name input').val()){
                        $('tr[data-row_id='+ (i-33) +']').addClass('font-missing');
                        $('tr[data-row_id='+ (i-33) +'] .data-image').html('CARATTERE VUOTO: SVUOTARE IL NOME');
                    } else {
                        $('tr[data-row_id='+ (i-33) +']').hide();
                    }
                }
            }

        };
        FR.readAsArrayBuffer(file);
    }

    var input = document.getElementById('loadFont');
    if (input.files && input.files.length > 0) {
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            readFile(file);
        }
    }
}

function saveFontSymbols() {
    var i, code;
    var namesNew = $('#font .font-new input').filter(function(){
        return !!this.value;
    });
    
    var namesUpd = $('#font .font-old input').filter(function(){
        return !!this.value;
    });
    
    var namesOld = $('#font .font-old input').filter(function(){
        return !this.value;
    });

    var missing = $('#font .font-missing input');
    var namesMissing = missing.filter(function() {
        return !!this.value;
    });
    if(namesMissing.length > 0) {
         alert('ERROR! \n' + 'manca il simbolo');
         return false;
    }

    var fontName = $('#font #loadFont').val();
    if (fontName.length == 0) fontName = 'r3-map-symbols.ttf';
    fontName = fontName.substring(fontName.lastIndexOf('\\')+1);
    
    var data = {
        action: 'saveFontSymbols',
        font_name: fontName,
        symbols: [],
    };
    for (i=0; i < namesNew.length; i++) {
        var newName = namesNew[i];
        code = newName.name.substring(4);
        data.symbols.push({
            symbol_name: newName.value,
            symbol_code: code,
            action: 'new'
        });
    }
    
    for (i=0; i < namesUpd.length; i++) {
        var newName = namesUpd[i];
        code = newName.name.substring(4);
        data.symbols.push({
            symbol_name: newName.value,
            symbol_code: code,
            action: 'new'
        });
    }

    for (i=0; i < missing.length; i++) {
        var m = missing[i];
        code = m.name.substring(4);
        data.symbols.push({
            symbol_code: code,
            action: 'del'
        });
    }
    
    for (i=0; i < namesOld.length; i++) {
        var old = namesOld[i];
        code = old.name.substring(4);
        data.symbols.push({
            symbol_code: code,
            action: 'del'
        });
    }

    if (data.symbols.length == 0) {
        alert ('Impostare almeno un font per il caricamento');
        return;
    }
    $.ajax({
        url: 'ajax/font.php',
        type: 'POST',
        dataType: 'json',
        data: data,
        success: function(response){
            if (response.result === 'ok') {
                importFont();
            } else {
                alert('ERROR! \n' + response.error);
                console.log(response);
            }
        }
    });
}

$(document).ready(function() {
    $('div#dialog_symbology').dialog({
        autoOpen: false,
        title: $('div#dialog_symbology').attr('data-title'),
        width: 800,
        height: 600,
        open: symbolsLoadList
    });
        
    $('a[data-action="symbology"]').show().click(function(event) {
        event.preventDefault();
        $('div#dialog_symbology').dialog('open');
    });

    $('div#dialog_symbology').tabs();
    
    if(typeof initOgcServices === 'undefined' || !initOgcServices) {
        return;
    }
    
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
