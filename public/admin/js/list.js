function GCList(field) {
	this.field = field;
	this.urls = {
		'ajax/dataList.php': ['data'],
		'ajax/lookupList.php': ['lookup_table'],
		'ajax/fieldList.php': ['class_text','label_angle','label_color','label_outlinecolor','label_size','label_font','label_priority','angle','color','outlinecolor','size','labelitem','labelsizeitem','classitem','classtitle','qtfield_name','field_name','data_field_1','data_field_2','data_field_3','table_field_1','table_field_2','table_field_3','filter_field_name'],
		'ajax/dbList.php': ['field_format','table_name','symbol_ttf_name','symbol_name'],
        'ajax/layerList.php': ['layer_id'],
        'ajax/fileList.php': ['filename']
	};
	this.requireSquareBrackets = ['class_text','label_angle','label_color','label_outlinecolor','label_size','label_font','label_priority','angle','color','outlinecolor','size','classtitle'];
	this.listData = {};
	this.selectedData = {};
	this.currentStep = null;
	this.totSteps = null;
	this.loadList = function(params) {
		var self = this;
		
		$('#list_dialog table').empty();
		self.listData = {};
		
		$.extend(self.selectedData, params);
		params.selectedField = self.field;
		
		var requestUrl = null;
		$.each(self.urls, function(url, fields) {
			if($.inArray(self.field, fields) > -1) {
				requestUrl = url;
				return false;
			}
		});
		if(requestUrl == null) {
			alert('Not implemented');
            console.log(params, self.field);
			return;
		}
		
		$.ajax({
			url: requestUrl,
			type: 'POST',
			dataType: 'json',
			data: params,
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok' || typeof(response.fields) != 'object' || typeof(response.data) != 'object' || typeof(response.step) == 'undefined' || typeof(response.steps) == 'undefined') {
					if(response != null && typeof(response.error) != 'undefined' && $.inArray(response.error, ['catalog_id','layertype_id','data']) > -1) {
						$('#list_dialog').dialog('close');
						return;
					}
					alert('Error');
					$('#list_dialog').dialog('close');
					return;
				}
				
				self.currentStep = response.step;
				self.totSteps = response.steps;
				
				var html = '<tr>';
				$.each(response.fields, function(fieldName, fieldTitle) {
					html += '<th>'+fieldTitle+'</th>';
				});
				html += '</tr>';
				$('#list_dialog table').append(html);
				
				$.each(response.data, function(rowId, rowData) {
					html = '<tr data-row_id='+rowId+'>';
					$.each(response.fields, function(fieldName, foo) {
						if(typeof(rowData[fieldName]) == 'undefined' || rowData[fieldName] == null) {
							html += '<td></td>';
							return;
						}
						html += '<td>'+rowData[fieldName]+'</td>';
					});
					html += '</tr>';
					$('#list_dialog table').append(html);
				});
				
				$.each(response.data_objects, function(rowId, rowData) {
					self.listData[rowId] = rowData;
				});
				
				$('#list_dialog table td').hover(function() {
					$(this).css('cursor', 'pointer');
				},function() {
					$(this).css('cursor', 'default');
				});
				$('#list_dialog table td').click(function(event) {
					var rowId = $(this).parent().attr('data-row_id');
					$.extend(self.selectedData, self.listData[rowId]);

					if(self.currentStep == self.totSteps || typeof(self.listData[rowId].is_final_step) != 'undefined' && self.listData[rowId].is_final_step == 1) {
						$.each(self.selectedData, function(key, val) {
							if($.inArray(key, self.requireSquareBrackets) > -1) val = '['+val+']';
							$('#'+key).val(val);
						});
						$('#list_dialog').dialog('close');
					} else {
						self.currentStep += 1;
						self.selectedData.step = self.currentStep;
						self.loadList(self.selectedData);
					}
				});
			},
			error: function() {
				alert('Error');
			}
		});
	};
};

function openList(txt_field, data) {
	if (txt_field.indexOf('.') > 0){
		var tmp = txt_field.split('.');
		var selectedField = tmp[0];
	} else {
		var selectedField = txt_field;
	}
	
	if(!$.isArray(data)) {
		if (dat.length>0) data = data.split('@');
		else data = new Array();
	}
	
	var params = {};
	$.each(data, function(e, field) {
		if($('#'+field).length > 0 && $('#'+field).val()) {
			params[field] = $('#'+field).val();
		}
	});
	
	$('#list_dialog').dialog({
		width:500,
		height:350,
		title:'',
		open: function() {
			var list = new GCList(selectedField);
			list.loadList(params);
		}
	});
}