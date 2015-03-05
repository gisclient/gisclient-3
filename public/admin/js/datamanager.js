flashIsAvailable = function () {
    var hasFlash = false;
    try {
      var fo = new ActiveXObject('ShockwaveFlash.ShockwaveFlash');
      if (fo) {
        hasFlash = true;
      }
    } catch (e) {
      if (navigator.mimeTypes
            && navigator.mimeTypes['application/x-shockwave-flash'] !== undefined
            && navigator.mimeTypes['application/x-shockwave-flash'].enabledPlugin) {
        hasFlash = true;
      }
    }
    return hasFlash;
}

$(document).ready(function() {
	if(initDataManager != true || $('#catalog').length < 1) {
		return;
	}
	
	var dataManager = new GCDataManager($('#catalog').val());
	dataManager.checkAvailableImports();
	
	$('a[data-action="data_manager"]').show().click(function(event) {
        if (!flashIsAvailable()) {
            $('span.flash_is_missing_message').css('display', 'inline-block');
        }
		event.preventDefault();
		
		$('div#import_dialog').dialog('open');
		$('div#import_dialog table[data-role="columns"] td').parent().remove();
	});
	
	$('div#import_dialog div#import_dialog_tabs').tabs({
		select: function(event, ui) {
			dataManager.fileTypeSelected(ui.index);
		}
	});
	
	$('div#import_dialog').dialog({
		title: 'Import Data',
		width: 1000,
		height: 600,
		autoOpen: false,
		open: function() {
			$('div#import_dialog div#import_dialog_tabs').tabs('select', 0);
			dataManager.getFileList();
		}
	});
	
	$('#shp_file_upload').uploadify({
		uploader: 'js/jquery/uploadify/uploadify.swf',
		script: 'ajax/datamanager.php',
		cancelImg: 'js/jquery/uploadify/cancel.png',
		folder: 'import',
		auto: true,
		multi: true,
		onAllComplete: function(event, data) {
			dataManager.getFileList();
		},
		fileExt: '*.shp;*.shx;*.dbf;',
		fileDesc: 'Shapefiles',
		scriptData: {action:'upload-shp'}
	});
	
	$('#xls_file_upload').uploadify({
		uploader: 'js/jquery/uploadify/uploadify.swf',
		script: 'ajax/datamanager.php',
		cancelImg: 'js/jquery/uploadify/cancel.png',
		folder: 'import',
		auto: true,
		multi: true,
		onAllComplete: function(event, data) {
			dataManager.getFileList();
		},
		fileExt: '*.xls;*.xlsx;',
		fileDesc: 'Excel files',
		scriptData: {action:'upload-xls'}
	});
	
	$('#csv_file_upload').uploadify({
		uploader: 'js/jquery/uploadify/uploadify.swf',
		script: 'ajax/datamanager.php',
		cancelImg: 'js/jquery/uploadify/cancel.png',
		folder: 'import',
		auto: true,
		multi: true,
		onAllComplete: function(event, data) {
			dataManager.getFileList();
		},
		fileExt: '*.csv;',
		fileDesc: 'CSV files',
		scriptData: {action:'upload-csv'}
	});
	
	$('#raster_file_upload').uploadify({
		uploader: 'js/jquery/uploadify/uploadify.swf',
		script: 'ajax/datamanager.php',
		cancelImg: 'js/jquery/uploadify/cancel.png',
		folder: 'import',
		auto: false,
		multi: true,
		onAllComplete: function(event, data) {
			dataManager.getFileList();
		},
		onSelectOnce: function() {
			var directory = $('div#import_dialog input[name="dir_name"]').val();
			if(directory == '') {
				$('#raster_file_upload').uploadifyClearQueue();
				return alert('Empty directory');
			}
			$('#raster_file_upload').uploadifySettings('scriptData', {directory:directory});
			dataManager.checkUploadFolderName(directory);
		},
		fileExt: '*.tif;*.tiff;*.tfw;*.ecw;*.jpg;*.jpeg;*.jgw;*.png;*.pgw;*.gif;*.gfw;',
		fileDesc: 'Raster',
		scriptData: {action:'upload-raster', catalog_id:$('#catalog').val()}
	});
	
	$('div#import_dialog div#import_dialog_shp button[name="import"]').button().hide().click(function(event) {
		event.preventDefault();
		dataManager.importShp();
	});
	$('div#import_dialog div#import_dialog_xls button[name="import"]').button().hide().click(function(event) {
		event.preventDefault();
		dataManager.importXls();
	});
	$('div#import_dialog button[name="tileindex"]').button().hide().click(function(event) {
		event.preventDefault();
		dataManager.createTileindex();
		dataManager.createPyramidRaster();
	});
	
    var fieldTypeOptions = '';
    $.each(dataManager.columnTypes, function(dbType, type) {
        fieldTypeOptions += '<option value="'+dbType+'">'+type+'</option>';
    });
    
	$('div#import_dialog a[data-action="add_column"]').click(function(event) {
		event.preventDefault();
		
		var numColumns = $('div#import_dialog input[name="num_columns"]').val();
		var html = '<tr><td><input type="text" name="column_name_'+numColumns+'"></td><td><select name="column_type_'+numColumns+'">' +
            fieldTypeOptions + '</select></td></tr>';
		$('div#import_dialog table[data-role="columns"]').append(html);
		$('div#import_dialog input[name="num_columns"]').val(parseInt(numColumns)+1);
	});
    
    $('div#add_column_dialog').dialog({
		title: 'Add column',
		width: 500,
		height: 200,
		autoOpen: false
	});
    var html = '<tr><td><input type="text" name="column_name"></td><td><select name="column_type">' +
        fieldTypeOptions + '</select></td></tr>';
    $('div#add_column_dialog table[data-role="columns"]').append(html);
    
    $('div#add_column_dialog button[name="add_column"]').click(function(event) {
        event.preventDefault();
        
        dataManager.addColumn();
    });
    
	$('div#import_dialog button[name="create_table"]').click(function(event) {
		event.preventDefault();
		
		dataManager.createTable();
	});
	$('div#import_dialog input[name$="_insert_method"]').click(function(event) {
        var type = $(this).attr('name').substr(0, 3);
		dataManager.changeImportMethod(type, $(this).val());
	});
});

function GCDataManager(catalogId) {

	this.catalogId = catalogId;
	this.fileType = 'shp';
	this.tileTypes = {
		0: 'shp',
		1: 'raster',
		2: 'postgis',
		3: 'xls',
        4: 'csv'
	};
    
	this.columnTypes = {
		text: 'Text',
		date: 'Date',
		'double precision': 'Number'
	};
	
    this.showErrorReponseAlert = function(response) {
        if ('error' in response) {
            alert(response.error);
        } else {
            alert('Error');
        }
    }
    
	this.checkAvailableImports = function() {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'get-available-imports'},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
                    return;
				}
				
				$.each(self.tileTypes, function(index, name) {
					if(typeof(response.imports[index]) == 'undefined') {
						$('div#import_dialog div#import_dialog_tabs').tabs('disable', parseInt(index));
					}
				});
                
                //self.hasLastEditColumn = !!response.lastEditColumn;
                //self.hasMeasureColumn = !!response.measureColumn;
			}
		});
	}

	this.fileTypeSelected = function(index) {
		var self = this;
		
		switch(index) {
			case 0:
				self.fileType = 'shp';
			break;
			case 1:
				self.fileType = 'raster';
			break;
			case 2:
				self.fileType = 'postgis';
				return self.getTableList();
			break;
			case 3:
				self.fileType = 'xls';
			break;
			case 4:
				self.fileType = 'csv';
			break;
			default:
				alert('file type ' + index + 'Not implemented');
				return;
			break;
		}
		self.getFileList();
	};
	
	this.deleteFile = function(fileName) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		if(!confirm('Are you sure?')) return;
		
		self.ajaxRequest({
			data: {action:'delete-file', file_name:fileName, file_type:self.fileType},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				self.getFileList();
			}
		});
	};
	
	this.deleteTable = function(tableName) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		if(!confirm('Are you sure?')) return;
		
		self.ajaxRequest({
			data: {action:'delete-table', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				self.getTableList();
			}
		});
	};
    
	this.emptyTable = function(tableName) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		if(!confirm('Are you sure?')) return;
		
		self.ajaxRequest({
			data: {action:'empty-table', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				self.getTableList();
			}
		});
	};	
    
	this.addLastEditColumn = function(tableName) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'add-last-edit-column', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				self.getTableList();
			}
		});
	};
	
	this.addMeasureColumn = function(tableName) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'add-measure-column', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				self.getTableList();
			}
		});
	};
	
	this.getTableList = function() {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'get-postgis-tables'},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
				}
				
				var html = '<table><tr><th>Tablename</th><th>SRID</th><th>Type</th><th></th></tr>';
				$.each(response.data, function(e, table) {
					if(typeof(table.type) != 'undefined' && table.type != null) {
						html += '<tr><td>'+table.name+'</td><td>'+table.srid+'</td><td>'+table.type+' ('+table.dim+'d)</td>'+
							'<td><a href="#" class="button" data-action="delete" data-table="'+table.name+'">Delete</a>'+
                            '<a href="#" class="button" data-action="empty" data-table="'+table.name+'">Empty</a>'+
							'<a href="#" class="button" data-action="add_column" data-table="'+table.name+'">Add column</a>'+
							'<a href="#" class="button" data-action="export_shp" data-table="'+table.name+'">Export SHP</a>';
                        if(!table.has_last_edit_date_column && !table.has_last_edit_date_column) {
                            html += '<a href="#" class="button" data-action="add_lastedit_column" data-table="'+table.name+'">Add Last edit col</a>';
                        }
                        if(!table.has_length_column && !table.has_area_column && !table.has_pointx_column && !table.has_pointy_column) {
                            html += '<a href="#" class="button" data-action="add_measure_column" data-table="'+table.name+'">Add measure col</a>';
                        }
                        html += '</td></tr>';

					} else {
						html += '<tr><td>'+table.name+'</td><td></td><td>Alphanumeric</td>'+
							'<td><a href="#" class="button" data-action="delete" data-table="'+table.name+'">Delete</a>'+
                            '<a href="#" class="button" data-action="empty" data-table="'+table.name+'">Empty</a>'+
							'<a href="#" class="button" data-action="export_xls" data-table="'+table.name+'">Export XLS</a><a href="#" class="button" data-action="export_csv" data-table="'+table.name+'">CSV</a></td></tr>';
					}
				});
				html += '</table>';
				$('div#import_dialog div[data-role="table_list"]').empty().html(html);
				
				$('div#import_dialog div[data-role="table_list"] a[data-action="delete"]').button().click(function(event) {
					event.preventDefault();
					
                    $('span', $(this)).html('Loading..');
					var tableName = $(this).attr('data-table');
					self.deleteTable(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="export_shp"]').button().click(function(event) {
					event.preventDefault();
					
                    $('span', $(this)).html('Loading..');
					var tableName = $(this).attr('data-table');
					self.exportShp(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="export_xls"]').button().click(function(event) {
					event.preventDefault();
					
					var tableName = $(this).attr('data-table');
					self.exportXls(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="export_csv"]').button().click(function(event) {
					event.preventDefault();
					
					var tableName = $(this).attr('data-table');
					self.exportCsv(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="empty"]').button().click(function(event) {
					event.preventDefault();
					
					var tableName = $(this).attr('data-table');
					self.emptyTable(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="add_column"]').button().click(function(event) {
					event.preventDefault();
					
					var tableName = $(this).attr('data-table');
					self.showAddColumnDialog(tableName);
                    
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="add_lastedit_column"]').button().click(function(event) {
					event.preventDefault();
                    
                    $('span', $(this)).html('Loading..');
					
					var tableName = $(this).attr('data-table');
                    self.addLastEditColumn(tableName);
				});
				$('div#import_dialog div[data-role="table_list"] a[data-action="add_measure_column"]').button().click(function(event) {
					event.preventDefault();
                    
                    $('span', $(this)).html('Loading..');
					
					var tableName = $(this).attr('data-table');
                    self.addMeasureColumn(tableName);
				});
			}
		});
	};
	
	this.changeImportMethod = function(type, method) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		if(method == 'create') {
			$('div#import_dialog input[name="'+type+'_table_name"]').show();
			$('div#import_dialog input[name="'+type+'_srid"]').attr('disabled', false);
			$('div#import_dialog select[name="'+type+'_table_name_select"]').hide();
		} else {
			if($('div#import_dialog select[name="'+type+'_table_name_select"] option').length == 0) {
				self.populateTableListSelect(type);
			}
			$('div#import_dialog input[name="'+type+'_table_name"]').hide();
			$('div#import_dialog input[name="'+type+'_srid"]').attr('disabled', true);
			$('div#import_dialog select[name="'+type+'_table_name_select"]').show();
		}
	};
	
	this.populateTableListSelect = function(type) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
        
        var params = {
            action: 'get-postgis-tables',
            alhpaOnly: (type == 'xls' || type == 'csv') ? true : false,
            geomOnly: (type == 'shp') ? true : false
        };
		
		self.ajaxRequest({
			data: params,
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
				}
				
				$('div#import_dialog select[name="'+type+'_table_name_select"]').empty();
				var html = '<option value="" data-srid="" selected>Select</option>';
				$.each(response.data, function(e, table) {
					html += '<option value="'+table.name+'" data-srid="'+table.srid+'">'+table.name+'</option>';	
				});
				$('div#import_dialog select[name="'+type+'_table_name_select"]').html(html);
				$('div#import_dialog select[name="'+type+'_table_name_select"]').change(function(event) {
					var srid = $('div#import_dialog select[name="'+type+'_table_name_select"] option:selected').attr('data-srid');
					$('div#import_dialog input[name="'+type+'_srid"]').val(srid);
				});
			}
		});
	};
	
	this.checkUploadFolderName = function(directory) {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'check-upload-folder', directory:directory},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				if(response.data != 'ok') {
					$('#raster_file_upload').uploadifyClearQueue();
					$('div#import_dialog div.logs').html(response.data).focus();
					return;
				}
				$('#raster_file_upload').uploadifyUpload();
			}
		});
	};
	
	this.getFileList = function() {
		var self = this;
		
		$('div#import_dialog div.logs').empty();
		
		self.ajaxRequest({
			data: {action:'get-uploaded-files', file_type:self.fileType},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
                    self.showErrorReponseAlert(response);
					return;
				}
				
				var html = '<ul>';
				$.each(response.data, function(e, file) {
					html += '<li><a href="#" class="button" data-action="delete" data-file="'+file.file_name+'">Delete</a>';
					html += '<a href="#" class="button" data-action="select" data-file="'+file.file_name+'">Select</a>';
					html += file.file_name+'</li>';
				});
				html += '</ul>';
				$('div#import_dialog div[data-role="file_list"]').empty().html(html);
				
				$('div#import_dialog div[data-role="file_list"] a[data-action="select"]').button().click(function(event) {
					event.preventDefault();
					
					var fileName = $(this).attr('data-file');
					self.selectFile(fileName);
				});
				
				$('div#import_dialog div[data-role="file_list"] a[data-action="delete"]').button().click(function(event) {
					event.preventDefault();
					
					var fileName = $(this).attr('data-file');
					self.deleteFile(fileName);
				});
				
			}
		});
	};
	
	this.selectFile = function(fileName) {
		var self = this;
		
		switch(self.fileType) {
			case 'shp':
				$('div#import_dialog input[name="shp_file_name"]').val(fileName);
				var fileNameWoExtension = fileName.replace('.shp', '');
				$('div#import_dialog input[name="shp_table_name"]').val(fileNameWoExtension);
				$('div#import_dialog button[name="import"]').show();
			break;
			case 'raster':
				$('div#import_dialog input[name="raster_file_name"]').val(fileName);
				$('div#import_dialog input[name="raster_table_name"]').val('tile_'+fileName);
				$('div#import_dialog button[name="tileindex"]').show();
			break;
			case 'xls':
                var replace;
				$('div#import_dialog input[name="xls_file_name"]').val(fileName);
                if(fileName.substr(-4) == 'xlsx') replace = '.xlsx';
                else if(fileName.substr(-4) == '.xls') replace = '.xls';
                else return alert('Invalid filename');
				var fileNameWoExtension = fileName.replace(replace, '');
				$('div#import_dialog input[name="xls_table_name"]').val(fileNameWoExtension);
				$('div#import_dialog button[name="import"]').show();
			break;
			case 'csv':
				$('div#import_dialog input[name="csv_file_name"]').val(fileName);
				var fileNameWoExtension = fileName.replace('.csv', '');
				$('div#import_dialog input[name="csv_table_name"]').val(fileNameWoExtension);
				$('div#import_dialog button[name="import"]').show();
			break;
			default:
				return alert('unknown file type "' + self.fileType + '"');
			break;
		}
	};
	
	this.importShp = function() {
		var self = this;
		
		var customParams = {};
		
		customParams.mode = $('div#import_dialog input[name="shp_insert_method"]:checked').val();
		if(customParams.mode != 'create') {
			customParams.table_name = $('div#import_dialog select[name="shp_table_name_select"]').val();
		}
		
		customParams.charset = $('div#import_dialog select[name="shp_file_charset"]').val();
		
		self.ajaxImport('shp', 'import-shp', customParams);
	};
	
	this.importXls = function() {
		var self = this;
		
        var customParams = {};
        
		customParams.mode = $('div#import_dialog input[name="xls_insert_method"]:checked').val();
		if(customParams.mode != 'create') {
			customParams.table_name = $('div#import_dialog select[name="xls_table_name_select"]').val();
		}
        
		self.ajaxImport('xls', 'import-xls', customParams);
	};
	
	this.exportShp = function(tableName) {
		var self = this;
		
		self.ajaxRequest({
			type: 'POST',
			data: {action:'export-shp', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#import_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				
				$('div#import_dialog div.logs').html('Operation done succesfully<br><a href="'+response.filename+'" target="_blank">Click here</a> to download').focus();
			}
		});
	};
	
	this.exportXls = function(tableName) {
		var self = this;
		
		self.ajaxRequest({
			type: 'POST',
			data: {action:'export-xls', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#import_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				
				$('div#import_dialog div.logs').html('Operation done succesfully<br><a href="export/'+response.filename+'" target="_blank">Click here</a> to download').focus();
			}
		});
	};
	
	this.exportCsv = function(tableName) {
		var self = this;
		
		self.ajaxRequest({
			type: 'POST',
			data: {action:'export-csv', table_name:tableName},
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#import_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				
				$('div#import_dialog div.logs').html('Operation done succesfully<br><a href="export/'+response.filename+'" target="_blank">Click here</a> to download').focus();
			}
		});
	};
	
	this.createTileindex = function() {
		var self = this;
		
		self.ajaxImport('raster', 'create-tileindex');
	};

	this.createPyramidRaster = function() {
		var self = this;
		
		self.ajaxImport('raster', 'create-pyramid-raster');
	};
	
	this.ajaxImport = function(prefix, action, customParams) {
		var self = this;
		
		if(typeof(customParams) == 'undefined') customParams = {};
		
		var params = {
			action: action,
			file_name: null,
			table_name: null,
			srid: null,
			charset: null,
			mode: null
		};
		params = $.extend(params, customParams);
		
		if(params.file_name == null) {
			params.file_name = $('div#import_dialog input[name="'+prefix+'_file_name"]').val();
			if(params.file_name == '') return alert('Empty file name');	
		}
		
		if(params.table_name == null) {
			params.table_name = $('div#import_dialog input[name="'+prefix+'_table_name"]').val();
			if(params.table_name == '') return alert('Empty table name');
		}
		
		if(prefix != 'xls' && params.srid == null) {
			params.srid = $('div#import_dialog input[name="'+prefix+'_srid"]').val();
			if(params.srid == '') return alert('Empty srid');
		}
		
		
		self.ajaxRequest({
			type: 'POST',
			data: params,
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#import_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				
				$('div#import_dialog div.logs').html('Operation done succesfully').focus();
			}
		});
		
	};
	
	this.createTable = function() {
		var self = this;
		
		var tableName = $('div#import_dialog input[name="postgis_table_name"]').val();
		if(tableName == '') return alert('Empty table name');
		
		var srid = $('div#import_dialog input[name="postgis_table_srid"]').val();
		if(srid == '') return alert('Empty srid');
		if(parseInt(srid) != srid) return ('Invalid srid');
		
		var params = {
			action: 'create-table',
			table_name: tableName,
			srid: srid,
			geometry_type: $('div#import_dialog select[name="postgis_geometry_type"]').val(),
			coordinate_dimension: $('div#import_dialog select[name="coordinate_dimension"]').val(),
			columns: []
		};
		
		var numColumns = $('div#import_dialog input[name="num_columns"]').val();
		var column;
		for(var n = 0; n <= parseInt(numColumns); n++) {
			column = {
				name: $('div#import_dialog input[name="column_name_'+n+'"]').val(),
				type: $('div#import_dialog select[name="column_type_'+n+'"]').val()
			};
			params.columns.push(column);
		}
		
		self.ajaxRequest({
			type: 'POST',
			data: params,
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#import_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				
				$('div#import_dialog div.logs').html('Operation done succesfully').focus();
			}
		});		
	};
    
    this.showAddColumnDialog = function(tableName) {
        $('div#add_column_dialog span[data-role="tablename"]').html(tableName);
        $('div#add_column_dialog').dialog('open');
        console.log($('div#add_column_dialog'));
    };
	
    this.addColumn = function() {
        var self = this;
        
        var tableName = $('div#add_column_dialog span[data-role="tablename"]').html();
        var columnName = $('div#add_column_dialog input[name="column_name"]').val();
        var columnType = $('div#add_column_dialog select[name="column_type"]').val();
        
        if(columnName == '') return alert('Please insert a column name');
        
		var params = {
			action: 'add-column',
			table_name: tableName,
			column_name: columnName,
			column_type: columnType
		};
				
		self.ajaxRequest({
			type: 'POST',
			data: params,
			success: function(response) {
				if(typeof(response) != 'object' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					if(typeof(response.result) != 'undefined' && response.result == 'error' && typeof(response.error) != 'undefined') {
						return $('div#add_column_dialog div.logs').html(response.error).focus();
					}
					return alert('Error');
				}
				$('div#add_column_dialog').dialog('close');
			}
		});		
    };
    
	this.showLoading = function() {
		$('div#import_dialog div.loading').show();
	};
	this.hideLoading = function() {
		$('div#import_dialog div.loading').hide();
	};
	
	this.ajaxRequest = function(obj) {
		var self = this;
		
		var defaultObj = {
			type: 'GET',
			url: 'ajax/datamanager.php',
			dataType: 'json',
			data: {catalog_id: self.catalogId},
			success: null,
			beforeSend: function() { self.showLoading() },
			complete: function() { self.hideLoading() },
			error: function() {
				alert('Error');
			}
		};
		obj = $.extend(true, defaultObj, obj);
		$.ajax(obj);
	};
	
}