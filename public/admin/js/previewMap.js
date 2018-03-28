$(document).ready(function() {
	if(initPreviewMap != true ) {
		return;
	}
	
	$('#preview_map_dialog').dialog({
		title: 'Preview map',
		width: 775,
		height: 735,
		draggable: false,
		autoOpen: false,
		modal: true,
		open: function() {
			$(this).empty();
			var url = previewMapUrl;
			url += $('#layer').val() == undefined ? '?layergroup_id='+$('#layergroup').val() : '?layer_id='+$('#layer').val();
			$(this).html('<iframe src="'+url+'" width="745" height="685"></iframe>');
		},
		close: function() {
          $(this).empty();
		  var urlC = previewMapUrl;
		  urlC += $('#layer').val() == undefined ? '?layergroup_id='+$('#layergroup').val() : '?layer_id='+$('#layer').val();
		  urlC += "&closeWindow=1";
          $.ajax({
            async: true,
            url: urlC,
            success: function(result,status,xhr) {},
            error: function(xhr,status,error) {
              window.alert(error);
            }
          });
        }
	});
	$('a[data-action="preview_map"]').show().click(function(event) {
		event.preventDefault();
		$('#preview_map_dialog').dialog('open');
	});
});
