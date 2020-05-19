function GCList(field, multipleSelection, uploadFile, refreshParent = false) {
    this.field = field;
    this.multipleSelection = multipleSelection;
    this.uploadFile = uploadFile;
    this.refreshParent = refreshParent;
    this.dialogId = 'list_dialog';
    this.options = {};
    this.urls = {
        'ajax/dataList.php': ['data'],
        'ajax/fileList.php': ['filename'],
        'ajax/lookupList.php': ['lookup_table'],
        'ajax/fieldList.php': ['class_text', 'label_angle', 'label_color', 'label_outlinecolor', 'label_size', 'label_font', 'label_priority', 'angle', 'color', 'outlinecolor', 'size', 'labelitem', 'labelsizeitem', 'classitem', 'classtitle', 'field_name', 'qt_field_name', 'data_field_1', 'data_field_2', 'data_field_3', 'table_field_1', 'table_field_2', 'table_field_3', 'filter_field_name'],
        'ajax/dbList.php': ['field_format', 'table_name', 'symbol_ttf_name', 'symbol_name', 'symbol_user_pixmap'],
        'ajax/fontList.php': ['symbol_user_font']
    };
    this.requireSquareBrackets = ['class_text', 'label_angle', 'label_color', 'label_outlinecolor', 'label_size', 'label_font', 'label_priority', 'angle', 'color', 'outlinecolor', 'size', 'classtitle'];
    this.listData = {};
    this.selectedData = {};
    this.currentStep = null;
    this.totSteps = null;
    this.getUrl = function() {
        var self = this;
        var requestUrl = null;
        $.each(self.urls, function (url, fields) {
            if ($.inArray(self.field, fields) > -1) {
                requestUrl = url;
                return false;
            }
        });
        if (requestUrl === null) {
            alert('Not implemented');
            return;
        }
        return requestUrl;
    };

    this.getParams = function(data) {
        var params = {};

        if (!$.isArray(data)) {
            if (data.length > 0) {
                data = data.split('@');
            } else {
                data = new Array();
            }
        }

        $.each(data, function (e, field) {
            if ($('#' + field).length > 0 && $('#' + field).val()) {
                params[field] = $('#' + field).val();
            }
        });

        return params;
    };

    this.checkResponse = function(response) {
        var errorMsg = null;
        if (typeof response !== 'object') {
            errorMsg = 'response is not in JSON format';
        } else if (response === null) {
            errorMsg = 'response is null';
        } else if (typeof response.result === 'undefined' ||
                response.result !== 'ok') {
            errorMsg = 'invalid result field';
        } else if (
                typeof response.fields !== 'object' ||
                typeof response.data !== 'object' ||
                typeof response.step === 'undefined' ||
                typeof response.steps === 'undefined') {
            errorMsg = 'invalid server response format';
        } else if (typeof response.error !== 'undefined') {
            if ($.inArray(response.error, ['catalog_id', 'layertype_id', 'data']) > -1) {
                errorMsg = 'invalid '.response.error;
            } else {
                errorMsg = response.error;
            }
        }

        return errorMsg;
    };

    this.loadStructuredList = function (params) {
      $.extend(this.selectedData, params);
      params.selectedField = this.field;
      var component = $('#' + this.dialogId).find('div');
      $('#' + this.dialogId).find('table').css("display", "none");
      component.css("display", "");
      component.empty();
      component.append(ajaxBuildSelector(this, params, "main"));
      component.addClass("treeMenuDiv");
      if(!this.uploadFile) {
        component.css("max-height", "95%");
        $(".uploadFile_listDialog").css("display","none");
      }
      $('#main').treeview();
      createFileListBehaviour(params['selectedField'], this.multipleSelection);
    }
    
    this.loadList = function (params) {
        var self = this;
        var dialogId = this.dialogId;
        var options = this.options;
        var dialogElement = $('#' + dialogId);
        dialogElement.find('div').css("display", "none");
        var resultTable = dialogElement.find('table');
        resultTable.css("display", "");
        var requestUrl = self.getUrl();

        self.listData = {};

        $.extend(self.selectedData, params);
        params.selectedField = self.field;
        if(!this.uploadFile) {
          dialogElement.css("max-height", "100%");
          $(".uploadFile_listDialog").css("display","none");
        }
        $.ajax({
            url: requestUrl,
            type: 'POST',
            dataType: 'json',
            data: params,
            success: function (response) {
                var errorMsg = self.checkResponse(response);
                if (errorMsg !== null) {
                    alert('Error: ' + errorMsg);
                    dialogElement.dialog('close');
                    return;
                }
                resultTable.empty();

                self.currentStep = response.step;
                self.totSteps = response.steps;

                // create table header
                var html = '<tr>';
                $.each(response.fields, function (fieldName, fieldTitle) {
                    html += '<th class="tableSelectorHeader">' + fieldTitle + '</th>';
                });
                html += '</tr>';

                // add rows with symbols to table
                $.each(response.data, function (rowId, rowData) {
                    html += '<tr data-row_id=' + rowId + '>';
                    $.each(response.fields, function (fieldName, foo) {
                        if (typeof rowData[fieldName] === 'undefined' || rowData[fieldName] === null) {
                            html += '<td class="data-' + fieldName + ' tableSelectorRow"></td>';
                            return;
                        }
                        html += '<td class="data-' + fieldName + ' tableSelectorRow">' + rowData[fieldName] + '</td>';
                    });
                    html += '</tr>';
                });
                resultTable.append(html);

                $.each(response.data_objects, function (rowId, rowData) {
                    self.listData[rowId] = rowData;
                });

                resultTable.find('td').hover(function () {
                    $(this).css('cursor', 'pointer');
                }, function () {
                    $(this).css('cursor', 'default');
                });

                if (typeof options.handle_click === 'undefined' || options.handle_click) {
                    resultTable.find('td').click(function (event) {
                        var rowId = $(this).parent().attr('data-row_id');
                        $.extend(self.selectedData, self.listData[rowId]);

                        if (self.currentStep == self.totSteps || typeof (self.listData[rowId].is_final_step) != 'undefined' && self.listData[rowId].is_final_step == 1) {
                            $.each(self.selectedData, function (key, val) {
                                if ($.inArray(key, self.requireSquareBrackets) > -1)
                                    val = '[' + val + ']';
                                $('#' + key).val(val);
                            });
                            dialogElement.dialog('close');
                            //questa istruzione funziona solo nel caso openListAndRefreshEntity venga invocato
                            //a livello di configurazione layer. Negli altri casi non ci entra... - MZ
                            if(self.refreshParent) {
                              var input = $("<input>").attr("type", "hidden").attr("name", "reloadFields").val("true");
                              $('#frm_data').append($(input));
                            }
                        } else {
                            self.currentStep += 1;
                            if(self.selectedData.directory != undefined && self.selectedData.directory.endsWith("../")) {
                              var navDir = self.selectedData.directory.replace("../","");
                              var index = (navDir.substr(0, navDir.length -1 )).lastIndexOf("/");
                              var back = navDir.substr(0, index + 1);
                              self.selectedData.directory = (back != "") ? back : undefined;
                            }
                            self.selectedData.step = self.currentStep;
                            self.loadList(self.selectedData);
                        }
                    });
                }

                if (typeof options.events !== 'undefined' && typeof options.events.list_loaded !== 'undefined') {
                    options.events.list_loaded();
                }
            },
            error: function () {
                alert('AJAX request returned with error');
            }
        });
    };

    this.loadData = function(params, callback) {
        var self = this;
        var requestUrl = self.getUrl();

        params.selectedField = self.field;

        $.ajax({
            url: requestUrl,
            type: 'POST',
            dataType: 'json',
            data: params,
            success: function (response) {
                var errorMsg = self.checkResponse(response);
                if (errorMsg !== null) {
                    alert('Error');
                    return;
                }
                callback(response);
            },
            error: function () {
                alert('AJAX request returned with error');
            }
        });
    };
}

function getSelectedField(txt_field) {
    var selectedField;
    if (txt_field.indexOf('.') > 0) {
        var tmp = txt_field.split('.');
        selectedField = tmp[0];
    } else {
        selectedField = txt_field;
    }
    return selectedField;
}

function openListAndRefreshEntity(txt_field, data) {
  genericOpenList(txt_field, data, true);
}

function openList(txt_field, data) {
  genericOpenList(txt_field, data);
}

function genericOpenList(txt_field, data, refresh = false) {
    var selectedField = getSelectedField(txt_field);
    $('#list_dialog').dialog({
        width: 500,
        height: 350,
        title: '',
        modal: true,
        open: function () {
            var list = new GCList(selectedField, false, false, refresh);
            list.loadList(list.getParams(data));
        }
    });

}

function openFileTree(txt_field, data, multipleSelection = false, uploadFile = false) {
  var selectedField = getSelectedField(txt_field);
  var list = new GCList(selectedField, multipleSelection, uploadFile);
  $('#list_dialog').dialog({
    width: 500,
    height: 350,
    title: '',
    modal: true,
    open: function () {
      list.loadStructuredList(list.getParams(data));
    }
  });
  $('#submitFile').click(function(event) {
    event.preventDefault();
    var file_data = $('#fileToUpload').prop('files')[0];
    var form_data = new FormData();
    form_data.append('file', file_data);
    $.ajax({
      url: 'ajax/upload.php',
      dataType: 'text',  // what to expect back from the PHP script, if anything
      cache: false,
      contentType: false,
      processData: false,
      data: form_data,
      type: 'post',
      success: function(response){
        if(response!="")
          alert(response); // display response from the PHP script, if any
        else {
          list.loadFileList(list.getParams(data));
          $('#fileToUpload').val("");
        }
      }
    });
  });
}

function buildSelector(response, obj, directory, id) {
  if(response.fields['file'] != undefined && response.fields['file'] != null)
    $('#' + obj.dialogId ).dialog("option", "title", response.fields['file']);
  var html = "";
  if(response.data_objects.length > 0) {
    if(id != undefined) {
      html += "<ul id=\""+id+"\" class=\"filetree treeview-famfamfam\">";
      html += "<li><span class='folder'>"
      html += (obj.multipleSelection ? "<input type=\"checkbox\" id=\"p_ckb_\">" : "");
      html += "</span>";
    }
    html += "<ul>";
    $.each(response.data_objects, function(rowId, rowData) {
      obj.listData[rowId] = rowData;
      if(rowData['directory'] != undefined && rowData['directory']!= null && !rowData['directory'].endsWith("../")){
        html += "<li><span class='folder'>";
        html += (obj.multipleSelection ? "<input type=\"checkbox\" id=\"p_ckb_"+directoryForCheckbox(rowData['directory'])+"\">" : "");
        html += directoryForTreeOutput(rowData['directory'])+"</span>";
        html += ajaxBuildSelector(obj, $.extend({}, obj.selectedData, obj.listData[rowId]));
        html += "</li>";
      } else if(rowData[obj.field] != undefined && rowData[obj.field]!= null) {
        var check = fieldContainsString($("#"+obj.field).val(), directory+rowData[obj.field]);
        html += "<li><span class='file'><input type=\"checkbox\" "+(check ? "checked" : "")+" id=\"ckb_"
             + directoryForCheckbox(directory)+rowData[obj.field]+"\" name=\"checkList\" value=\""+directory+rowData[obj.field]
             + "\"/>"+rowData[obj.field]+"</span></li>";
        if(!directory)
          checkTreeConsistency("ckb_"+directoryForCheckbox(directory), check);
      }

    });
    html += '</ul>';
    if(id != undefined) {
      html += "</li></ul>";
    }
  } else {
    html += "<ul id=\""+id+"\" class=\"filetree treeview-famfamfam\">";
    html += "<li><span class='folder'>- no files -</span>";
    html += "</li></ul>";
  }
  return html;
}

function fieldContainsString(fieldVal, searchKey) {
  var arr = fieldVal.split(' ');
  var result = false
  $.each(arr, function(index, val) {
    if(val == searchKey) {
      result = true;
      return false;
    }
  });
  return result;
}

function directoryForTreeOutput(inputDir) {
  var output = inputDir.substring(0, inputDir.length-1);
  return output.lastIndexOf("/")!=-1 ? output.substring(output.lastIndexOf("/")+1) : output;
}

function directoryForCheckbox(inputDir) {
  return inputDir.replace(/\//g,"_");
}

function ajaxBuildSelector(obj, params, id){
  var result = "";
  $.ajax({
    url: obj.getUrl(),
    type: 'POST',
    async: false,
    dataType: 'json',
    data: params,
    success: function (response) {
      var errorMsg = obj.checkResponse(response);
      if (errorMsg !== null) {
        alert('Error: ' + errorMsg);
        $('#' + obj.dialogId ).dialog('close');
        return;
      }
      var directory = params['directory']!=undefined ? params['directory'] : "";
      // create table header
      result = buildSelector(response, obj, directory, id);
    },
    error: function () {
      alert('AJAX request returned with error');
    }
  });
  return result;
}

function populateTextField(field, checked, value) {
  //circondo stringa con spazi in modo da poter essere sicuro di beccare esattamente la stringa che mi interessa in caso di "eliminazione"
  var fieldText = checked ? $('#' + field).val() : " "+$('#' + field).val()+" ";
  fieldText = $.trim(checked ? fieldText.concat(" " + value) : fieldText.replace(new RegExp("[ ]{1}"+value+"[ ]{1}"), " "));
  $('#' + field).val(fieldText.replace(/\s+/g, " "));
}

function updateExtent(txt_field) {
    var selectedField = getSelectedField(txt_field);
    var data = ["catalog_id", "layertype_id", "layergroup", "project", "data", "data_geom", "data_type", "data_srid"];
    var list = new GCList(selectedField);
    var params = list.getParams(data);

    // skip step
    params.step = 1;

    // force request for data_extent
    params.data_extent = null;

    list.loadData(params, function(response) {
        $.each(response.data_objects, function (rowId, rowData) {
            if (rowData.data_unique === $('#data_unique').val()) {
                $('#data_extent').val(rowData.data_extent);
            }
        });
    });
}

function createFileListBehaviour(field, multipleSelection) {
  $('[id^=p_ckb_]').change(function() {
      var newstate = $(this).is(":checked") ? ":not(:checked)" : ":checked";
      var id_leaf = $(this).attr("id").substring(2);
      $('[id^='+$(this).attr("id")+']'+newstate).click();
      $('[id^='+id_leaf+']'+newstate).click();
  });
  $('[id^=ckb_]').change(function() {
    var checked = $(this).is(":checked");
    var currentId = $(this).attr("id");
    var currentFile = $(this).val().substring($(this).val().lastIndexOf("/")+1);
    if(!multipleSelection) {
      var group = "input:checkbox[name='checkList']";
      $(group).prop("checked", false);
      $(this).prop("checked", checked);
      $("#" + field).val("");
    }
    populateTextField(field, checked, $(this).val());
    var dirId = currentId.replace(currentFile, "");
    checkTreeConsistency(dirId, checked);
  });
}

function checkTreeConsistency(parentDir, check) {
  var workingDir = parentDir;
  var allSelected = ($('[id^='+workingDir+']').length == $('[id^='+workingDir+']:checked').length);
  if((check && allSelected) || !check) {
    $("#p_"+workingDir).attr("checked", check);
    workingDir = workingDir.substring(0, workingDir.length -1);
    if (workingDir.indexOf("_") != -1)
      checkTreeConsistency(workingDir.substring(0, workingDir.lastIndexOf("_") + 1), check);
  }
}
