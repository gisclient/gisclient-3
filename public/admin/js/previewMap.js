$(document).ready(function() {
	if(initPreviewMap != true ) {
		return;
	}
	
	$('#preview_map_dialog').dialog({
		title: 'Preview map',
		width: 480,
		height: 500,
		draggable: false,
		autoOpen: false,
		open: function() {
			$(this).empty();
			var url = previewMapUrl;
			url += '?layergroup_id='+$('#layergroup').val();
			$(this).html('<iframe src="'+url+'" width="450" height="450"></iframe>');
		}
	});
	$('a[data-action="preview_map"]').show().click(function(event) {
		event.preventDefault();
		$('#preview_map_dialog').dialog('open');
	});
});