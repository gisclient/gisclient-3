function normalize(arr, form, parentLevel){
  var array = new Array();
  for (var key in arr) {
    if(isNumeric(key)) {
      //qua sono all'interno del bivio numerico
      array.push(normalize(arr[key], form, parentLevel));
    } else {
      //qua sto gestendo il chiave valore
      var current = {
        label: key,
        parent: parentLevel
      }
      current.children = normalize(arr[key], form, key);
      return current;
    }
  }
  return array;
}

function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

function GCMassive(level, currentId) {
  this.selectedIndex;
  this.selectedTable;
  this.searchName;
  this.level = level;
  this.currentId = currentId;
  this.url = 'ajax/massive.php';
  this.filters = {};
  this.emptyOption = '<option value="-2">Select</option>';

  this.loadForm = function() {
    var form = this;
    $.ajax({
      type: 'GET',
      url: form.url,
      dataType: 'json',
      data: {action:'load', level:form.level, id: form.currentId},
      success: function(response) {
        var windowStruct = response[form.level];
        windowStruct = normalize(windowStruct, form, form.level);
        form.buildWindow(windowStruct);
        form.getData(windowStruct[0].label, form.currentId);
        form.addFormulaTextArea();
      },
      error: function() {
        window.alert("Errore nel popolamento della form");
      }
    });
  };
  this.addFormulaTextArea = function() {
    var self = this;
    var html = "<hr>"
        + "<div class=\"copyDivLeft\">Oggetto Selezionato:</div><div id=\"selEntityMassiveDiv\" class=\"copyDivRight\">nessun oggetto selezionato</div><div style=\"clear: both;\"/>"
        + "<div class=\"copyDivLeft\">Campo Selezionato:</div><div class=\"copyDivRight\"><select id=\"workingField\">"+this.emptyOption+"</select></div><div style=\"clear: both;\"/>"
        + "<div class=\"copyDivLeft\">Nuovo Valore:</div><div class=\"copyDivRight\"><input class=\"textInput\" disabled type=\"text\" id=\"newVal\"/></div><div style=\"clear: both;\"/>"
        + "<div class=\"copyDivLeft\">Condizioni aggiuntive:</div><div class=\"copyDivRight\"><textarea class=\"textInput\" disabled id=\"clause\" rows=\"4\" cols=\"50\"/></div><div style=\"clear: both;\"/>"
        + "<div id=\"mexDiv\" style=\"text-align: right;\"></div>"
        + "<div class=\"divButton\"><button class=\"massiveBtn\" style=\"display: none;\" id=\"massiveUpdate\">Modifica</button><button class=\"massiveBtn\" style=\"display: none;\" id=\"massivePreview\">Preview</button></div>"
        + "<div id=\"tableResult\" style=\"max-height: 120px; overflow-y: auto; margin-top: 10px;\"></div>"
        + "<div id=\"queryResult\"></div>";
        
    $('#massive_dialog').append(html);
    $('#massive_dialog button[id="massiveUpdate"]').click(function(event) {
      event.preventDefault();
      $('#frm_data').append('<input type="hidden" name="dati[searchName]" value="'+self.searchName+'" />');
      $('#frm_data').append('<input type="hidden" name="dati[searchIndex]" value="'+self.selectedIndex+'" />');
      $('#frm_data').append('<input type="hidden" name="dati[entityName]" value="'+self.selectedTable+'" />');
      $('#frm_data').append('<input type="hidden" name="dati[entityAttribute]" value="'+$("#workingField option:selected").text()+'" />');
      $('#frm_data').append('<input type="hidden" name="dati[attributeValue]" value="'+$("#newVal").val()+'" />');
      $('#frm_data').append('<input type="hidden" name="dati[externalClause]" value="'+$("#clause").val()+'" />');
      $('#frm_data').append('<input type="hidden" name="azione" value="massive" />');
      $('#frm_data').submit();
    });
    $('#massive_dialog button[id="massivePreview"]').click(function(event) {
      self.showPreview();
    });
    $('#workingField').change(function(){
      var condition = ($(this).val() == "-2");
      $(".textInput").attr("disabled", condition);
      if(condition)
        $(".textInput").val("");
      self.simulateQuery();
    });
    $("#clause").change(function(){
      self.simulateQuery();
    });
    $("#newVal").change(function(){
      if(!$("#clause").hasClass("wrongCondition"))
        $("#massiveUpdate").css("display", ($(this).val() == "") ? "none" : "");
    });
  };
  this.retrieveLastSelectedIndex = function(val, level) {
    if(val != undefined && val != "-1")
      return [val, level];
    else if(level == this.level)
      return [this.currentId, this.level];
    else {
      var selector = $("#massive_dialog select[name=\""+level+"\"]");
      var parent = selector.attr("parent");
      return this.retrieveLastSelectedIndex($("#massive_dialog select[name=\""+parent+"\"]").val(), parent);
    }
  };
  this.getFieldsForSelectedEntity = function(parent, level, val) {
    this.selectedTable = level;//cerco gli attributi per lei
    var arr = this.retrieveLastSelectedIndex(val, level);
    this.selectedIndex = arr[0];
    this.searchName = arr[1];
    var form = this;
    $.ajax({
      type: 'GET',
      url: form.url,
      dataType: 'json',
      data: {action:'load-fields', level:level},
      success: function(response) {
        if(checkResponse(response)) {
	      alert('Error');
          return;
        }
        $('#workingField').empty();
        $('#workingField').append(form.emptyOption);
        response.data.forEach(function(item,index){
          $('#workingField').append("<option value='"+item.column_name+"'>"+item.column_name+"</option>");
        });
        $(".textInput").attr("disabled", true);
        $(".textInput").val("");
        $("#mexDiv").empty();
        $(".massiveBtn").css("display", "none");
      },
      error: function() {
        window.alert("Errore nel popolamento della form");
      }
    });
  };
  this.disableFromLevel = function(level) {
    var self = this;
    var selector = $('#massive_dialog input:radio[value="'+level+'"]');
    if(selector.is(':checked')) {
      selector.prop('checked', false);
      changeContainerDivDisplay(selector.attr("key"), selector.value, false);
    }
    selector.attr('disabled', true);
    $('#massive_dialog div[id="'+level+'Div"]').addClass("radioDivDisabled");
    $('#massive_dialog select[name="'+level+'"]').empty();
    $('#massive_dialog select[name="'+level+'"]').append(this.emptyOption);
    $('#massive_dialog select[parent="'+level+'"]').each(function(index, element){
      self.disableFromLevel($(element).attr("name"));
    });
  };
  this.getData = function(level, parentId) {
    var form = this;
    if(parentId == null) parentId = '';
    $.ajax({
      type: 'GET',
      url: form.url,
      dataType: 'json',
      data: {action:'get-data', level: level, parent_id: parentId},
      success: function(response) {
        if(checkResponse(response)) {
	      alert('Error');
          return;
        }
        if(!jQuery.isEmptyObject(response.data) || parentId == '-1') {
          $('#massive_dialog input:radio[value="'+level+'"]').attr('disabled',false);
          $('#massive_dialog div[id="'+level+'Div"]').removeClass("radioDivDisabled");
          $('#massive_dialog select[name="'+level+'"]').empty();
          $('#massive_dialog select[name="'+level+'"]').append(form.emptyOption);
          $('#massive_dialog select[name="'+level+'"]').append('<option value="-1">-Tutti gli oggetti-</option>');
          
          $.each(response.data, function(pkey, title) {
            $('#massive_dialog select[name="'+level+'"]').append('<option value="'+pkey+'">'+title+'</option>');
	      });
          $('#massive_dialog select[parent="'+level+'"]').each(function(index, element){
            form.disableFromLevel($(element).attr("name"));
          });
        } else
          form.disableFromLevel(level);
        $("#selEntityMassiveDiv").empty();
        var label = recalculateString(form.level);
        $("#selEntityMassiveDiv").append(label ? label : "(nessun valore selezionato)");
      },
      error: function() {
        alert('Error ' + level);
      }
    });
  };
  this.buildWindow = function(windowStruct, hide = false, disclaimer = "") {
    var self = this;
    var multipleSolution = windowStruct.length > 1;
    var display = multipleSolution || hide ? "style=\"display: none;\"" : "";
    var displayClear = multipleSolution || hide ? "style=\"display: none; clear: both;\"" : "style=\"clear: both;\"";
    var html = "";
    var origDisclaimer = disclaimer;
    for(var i = 0; i < windowStruct.length; i++) {
      var levelData = windowStruct[i].label;
      if(multipleSolution) {
        html = "<div id=\""+levelData+"Div\" "+(hide ? display : "" )+" key=\""+origDisclaimer+"\"><input key=\""+origDisclaimer+"\" type=\"radio\" name=\"children_"+windowStruct[i].parent+"\" value=\""+levelData+"\"/>"+levelData+"</div>"
        $('#massive_dialog').append(html);
        disclaimer = origDisclaimer+"_"+levelData+"_";
      }
      html = '<div '+display +' class="copyDivLeft" key="'+disclaimer+'">' + levelData + ':</div>'
           + '<div '+display+' class="copyDivRight" key="'+disclaimer+'"><select parent="'+windowStruct[i].parent+'" name="'+levelData+'">'+self.emptyOption+'</select></div>'
           + '<div '+displayClear+' key="'+disclaimer+'"></div>';
      $('#massive_dialog').append(html);
      if(windowStruct[i].children.length > 0) {
        this.buildWindow(windowStruct[i].children, multipleSolution, disclaimer);
      }
      $('#massive_dialog select[name="'+windowStruct[i].label+'"]').change(function() {
        var val = this.value;
        $('#massive_dialog select[parent="'+this.name+'"]').each(function(index, element){
          self.getData($(element).attr("name"), val);
          //deve lavorare ricorsivamente
        });
        $("#selEntityMassiveDiv").empty();
        var label = recalculateString(self.level);
        $("#selEntityMassiveDiv").append(label ? label : "(nessun valore selezionato)");
        self.getFieldsForSelectedEntity($(this).attr("parent"), $(this).attr("name"), val);
      });
    }
    if(multipleSolution) {
      $('#massive_dialog input:radio[name="children_'+(windowStruct[0].parent)+'"]').change(function() {
        changeContainerDivDisplay($(this).attr("key"), this.value, true);
        $("#selEntityMassiveDiv").empty();
        var label = recalculateString(self.level);
        $("#selEntityMassiveDiv").append(label ? label : "(nessun valore selezionato)");
      });
    }
    html = "";
  };
  this.simulateQuery = function() {
    var form = this;
    $.ajax({
      type: 'GET',
      url: form.url,
      dataType: 'json',
      data: {action:'simulate', searchName: form.searchName, id: form.selectedIndex, level: form.selectedTable, clause: $("#clause").val()},
      success: function(response) {
        $("#mexDiv").empty();
        if(checkResponse(response)) {
          $("#mexDiv").css("color", "red");
	      $("#mexDiv").append("Condizioni di query errate: "+ response.error);
	      $("#clause").addClass("wrongCondition");
	      $(".massiveBtn").css("display", "none");
          return;
        }
        var result = response.data[0].count;
        $("#clause").removeClass("wrongCondition");
        $("#mexDiv").css("color", "black");
        $("#mexDiv").append("Numero elementi identificati: " + result);
        $("#massivePreview").css("display", "");
        $("#massiveUpdate").css("display", ($("#newVal").val() == "") ? "none" : "");
      },
      error: function(e) {
        alert('Error ');
      }
    });
  };
  this.showPreview = function() {
    var form = this;
    $.ajax({
      type: 'GET',
      url: form.url,
      dataType: 'json',
      data: {action: 'preview', searchName: form.searchName, id: form.selectedIndex, level: form.selectedTable, clause: $("#clause").val(), field: $("#workingField option:selected").text()},
      success: function(response) {
        if(checkResponse(response)) {
          window.alert("Condizioni di query errate. Impossibile procedere alla preview ");
	      return;
        }
        // create table header
        $("#tableResult").empty();
        $("#queryResult").empty();
        var html = '<table><tr>';
        $.each(response.fields, function (fieldName, fieldTitle) {
          html += '<th class="tableSelectorHeader">' + fieldTitle + '</th>';
        });
        html += '</tr>';
        // add rows with symbols to table
        $.each(response.data, function (rowId, rowData) {
          html += '<tr data-row_id=' + rowId + '>';
          $.each(response.fields, function (foo, fieldName) {
            var index = fieldName.indexOf(".");
            fieldName = fieldName.substring(index + 1);
            if (typeof rowData[fieldName] === 'undefined' || rowData[fieldName] === null) {
              html += '<td class="data-' + fieldName + ' tableSelectorRow"></td>';
              return;
            }
            html += '<td class="data-' + fieldName + ' tableSelectorRow">' + rowData[fieldName] + '</td>';
          });
          html += '</tr>';
        });
        html += '</table>';
        $("#tableResult").append(html);
        $("#queryResult").append(response.sql);
      },
      error: function(e) {
        alert('Error ');
      }
    });
  }
};

function recalculateString(level) {
  //si parte da quello con parent vuoto
  var result = "";
  var startingValue = $("#massive_dialog select[parent='"+level+"']:visible").val();
  var currentLevel = $("#massive_dialog select[parent='"+level+"']:visible").attr("name")
  switch(startingValue) {
    case "-1":
      result += "(tutti "+currentLevel+") > ";
      //return result;
      break;
    case undefined:
    case "-2":
      return "";
    default:
      result += $("#massive_dialog select[parent='"+level+"']:visible option:selected").text() + " > ";
      break;
  }
  result += recalculateString($("#massive_dialog select[parent='"+level+"']:visible").attr("name"));
  return result;
}

function changeContainerDivDisplay(key, value, visible) {
  $('#massive_dialog div[key^=\"'+key+'_\"]').css("display", "none");
  $('#massive_dialog div[key^=\"'+key+'_\"] > select').val("");
  if(visible)
    $('#massive_dialog div[key=\"'+key+"_"+value+'_\"]').css("display", "");
}

function openMassive(currentLevel) {
  if($('input[name="'+currentLevel+'"]').length == 0)
    return;
  var currentName = $('#'+currentLevel+'_name').val();
  var currentId = $('input[name="'+currentLevel+'"]').val();
  $('#massive_dialog').empty().dialog({
    width:800,
    height:760,
    title: 'Modifica massiva. ' + currentLevel + ': ' + currentName,
    modal: true,
    open: function() {
      var massive = new GCMassive(currentLevel, currentId);
      massive.loadForm();
    }
  });
}

function checkResponse(response) {
 return (typeof(response) == 'undefined' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok');
}
