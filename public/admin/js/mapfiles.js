var loadingGif = '<img src="../images/ajax_loading.gif">';
$(document).ready(function() {

	$('div#mapfiles_manager').dialog({
		autoOpen: false,
		title: $('div#mapfiles_manager').attr('data-title'),
		width: 800,
		height: 600
	});
	
	$('div#mapfiles_manager a[data-action="view_map"]').button({icons:{primary:'ui-icon-extlink'}, text:false});
	
	$('div#mapfiles_manager a[data-action="refresh"]').button({icons:{primary:'ui-icon-refresh'}, text:false}).click(function(event) {
		event.preventDefault();
		
		var activeLink = this;
		var activeLinkContainer = $(this).parent();
		$(activeLink).hide();
		$(activeLinkContainer).append(loadingGif);
		
		var params = {
			action: 'refresh',
			target: $(this).attr('data-target'),
			project: $('input#project').val(),
			mapset: $(this).attr('data-mapset')
		}
		
		$.ajax({
			url: 'ajax/mapfiles.php',
			type: 'POST',
			dataType: 'json',
			data: params,
			success: function(response) {
				$(activeLink).show();
				$('img', activeLinkContainer).remove();
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined') {
					return alert('Error');
				}
				if(response.result != 'ok') {
					if(response.result == 'error' && typeof(response.error) == 'object' && typeof(response.error.type) != 'undefined' && response.error.type == 'mapfile_errors') {
						$('#error_dialog').html(response.error.text);
						$('#error_dialog').dialog({
							title: 'Error'
						});
						return;
					}
					return alert('Error');
				}
			},
			error: function() {
				$(activeLink).show();
				$('img', activeLinkContainer).remove();
				alert('Error');
			}
		});
	});

	$('a[data-action="mapfiles_manager"]').click(function(event) {
		event.preventDefault();

		$('div#mapfiles_manager').dialog('open');
	});
});