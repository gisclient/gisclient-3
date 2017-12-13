function GCCopy(level, project, parentId, mode) {
  this.level = level;
  this.project = project;
  this.parentId = parentId;
  this.mode = mode;
  this.lastLevel = null;
  this.url = 'ajax/copy.php';
  this.filters = {};
  this.emptyOption = '<option value="Select">Select</option>';
	
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
        $('#copy_dialog').append('<div class="copyDivLeft">name:</div><div class="copyDivRight"><input type="text" name="newname"></div><div style="clear: both;"></div>');
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
    if(levelData.parent != '')
      parent = 'data-parent_level="'+levelData.parent+'"';
		
    var html = '<div class="copyDivLeft">' + level + ':</div><div class="copyDivRight"><select name="'+level+'" '+parent+'>'+self.emptyOption+'</select></div><div style="clear: both;"></div>';
    $('#copy_dialog').append(html);
    $('#copy_dialog select[name="'+level+'"]').change(function() {
      if($('#copy_dialog select[data-parent_level="'+level+'"]').length == 0 && $('#copy_dialog button[name="copy"]').length == 0) {
        var text = (self.mode == 'move') ? 'Move' : 'Copy';
        $('#copy_dialog').append('<div class="divButton"><button name="copy">'+text+'</button></div>');
        $('#copy_dialog button[name="copy"]').click(function(event) {
          event.preventDefault();
          var newId = (self.mode == 'move') ? $('#copy_dialog select[name="'+self.lastLevel+'"]').val() : $('#copy_dialog select[name="'+self.level+'"]').val(); 
          self.copy(newId);
        });
        return;
      }
      var childLevel = $('#copy_dialog select[data-parent_level="'+level+'"]').attr('name');
      self.emptySelect(childLevel);
      
      if($(this).val() != 'Select')
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
    $('#copy_dialog select[name="'+level+'"]').find('option').remove().end()
      .append(self.emptyOption)
      .val('Select');
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
  open(parentLevel, 'copy');
}

function openMove(parentLevel) {
  open(parentLevel, 'move');
}

function open(parentLevel, operation) {
  if($('#prm_livello').length == 0 || $('#project').length == 0 || $('input[name="'+parentLevel+'"]').length == 0) return;
  var level = $('#prm_livello').val();
  var project = $('#project').val();
  var parentId = $('input[name="'+parentLevel+'"]').val();
  $('#copy_dialog').empty().dialog({
    width:500,
    height:350,
    title:'Copy',
    open: function() {
      var copy = new GCCopy(level, project, parentId, operation);
      copy.loadForm();
    }
  });
}
