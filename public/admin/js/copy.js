function GCCopy(level, project, parentId, mode) {
	this.level = level;
	this.project = project;
	this.parentId = parentId;
	this.mode = mode;
	this.lastLevel = null;
	this.url = 'ajax/copy.php';
	this.filters = {};
	this.emptyOption = '<option value="0">Select</option>';
	
	this.loadForm = function() {
		var self = this;
		
		$.ajax({
			type: 'GET',
			url: self.url,
			dataType: 'json',
			data: {level:self.level,action:'get-form',mode:self.mode},
			success: function(response) {
				if(typeof(response) == 'undefined' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					alert('Error');
					return;
				}
				
				$('#copy_dialog').append('<p>New name: <input type="text" name="newname"></p>');
				
				$.each(response.filters, function(e, filter) {
					self.filters[filter.level] = filter;
					self.addSelect(filter.level);
					self.lastLevel = filter.level;
				});
				
				parentId = null;
				if(response.has_project == 0) {
					parentId = self.project;
				}
				
				self.getData(response.filters[0].level, parentId);
			},
			error: function() {
				alert('Error');
			}
		});
	};
	
	this.addSelect = function(level) {
		var self = this;
		
		var parent = '';
		var levelData = self.filters[level];
		if(levelData.parent != '') parent = 'data-parent_level="'+levelData.parent+'"';
		
		var html = '<p>' + level + ': <select name="'+level+'" '+parent+'>'+self.emptyOption+'</select></p>';
		$('#copy_dialog').append(html);
		$('#copy_dialog select[name="'+level+'"]').change(function() {
			if($('#copy_dialog select[data-parent_level="'+level+'"]').length == 0 && $('#copy_dialog button[name="copy"]').length == 0) {
				var text = (self.mode == 'move') ? 'Move' : 'Copy';
				$('#copy_dialog').append('<button name="copy">'+text+'</button>');
				$('#copy_dialog button[name="copy"]').click(function(event) {
					event.preventDefault();
					
					if(self.mode == 'move') {
						var newId = $('#copy_dialog select[name="'+self.lastLevel+'"]').val();
					} else {
						var newId = $('#copy_dialog select[name="'+self.level+'"]').val(); 
					}
					
					self.copy(newId);
				});
				return;
			}
			
			var childLevel = $('#copy_dialog select[data-parent_level="'+level+'"]').attr('name');
			if($(this).val() == '0') {
				self.emptySelect(childLevel);
				return;
			}
			self.getData(childLevel, $(this).val());
		});
	};
	
	this.getData = function(level, parentId) {
		var self = this;
		
		if(parentId == null) parentId = '';
		
		$.ajax({
			type: 'GET',
			url: self.url,
			dataType: 'json',
			data: {action:'get-data', level: level, parent_id: parentId},
			success: function(response) {
				if(typeof(response) == 'undefined' || response == null || typeof(response.result) == 'undefined' || response.result != 'ok') {
					alert('Error');
					return;
				}
				
				$.each(response.data, function(pkey, title) {
					$('#copy_dialog select[name="'+level+'"]').append('<option value="'+pkey+'">'+title+'</option>');
				});
			},
			error: function() {
				alert('Error');
			}
		});
	};
	
	this.emptySelect = function(level) {
		var self = this;
		
		$('#copy_dialog select[name="'+level+'"]').html(self.emptyOption).val('0');
	};
	
	this.copy = function(id) {
		var self = this;

		$('#'+self.level+'_name').val($('#copy_dialog input[name="newname"]').val());
		$('#frm_data').append('<input type="hidden" name="dataction[old]" value="'+self.parentId+'" />');
		$('#frm_data').append('<input type="hidden" name="dataction[new]" value="'+id+'" />');
		var action = (self.mode == 'move') ? 'sposta' : 'copia';
		$('#frm_data').append('<input type="hidden" name="azione" value="'+action+'" />');
		$('#azione').val(action);
		$('#frm_data').submit();
		
	}
};

function openCopy(parentLevel) {
	if($('#prm_livello').length == 0 || $('#project').length == 0 || $('input[name="'+parentLevel+'"]').length == 0) return;
	
	var level = $('#prm_livello').val();
	var project = $('#project').val();
	var parentId = $('input[name="'+parentLevel+'"]').val();

	$('#copy_dialog').empty().dialog({
		width:500,
		height:350,
		title:'',
		open: function() {
			var copy = new GCCopy(level, project, parentId, 'copy');
			copy.loadForm();
		}
	});
}

function openMove(parentLevel) {
	if($('#prm_livello').length == 0 || $('#project').length == 0 || $('input[name="'+parentLevel+'"]').length == 0) return;
	
	var level = $('#prm_livello').val();
	var project = $('#project').val();
	var parentId = $('input[name="'+parentLevel+'"]').val();

	$('#copy_dialog').empty().dialog({
		width:500,
		height:350,
		title:'Copy',
		open: function() {
			var copy = new GCCopy(level, project, parentId, 'move');
			copy.loadForm();
		}
	});
}