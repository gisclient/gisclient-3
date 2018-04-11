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
			target: $(this).attr('data-target'),
			project: $('input#project').val(),
			mapset: $(this).attr('data-mapset')
		}
		
		$.ajax({
			url: '../services/refresh_mapfile',
			type: 'POST',
			dataType: 'json',
			data: params,
			success: function(response) {
				$(activeLink).show();
				$('img', activeLinkContainer).remove();
				if (typeof response !== 'object' || typeof response.result === 'undefined' || response.result !== 'ok') {
					alert('Error');
				}
			},
			error: function(response) {
				$(activeLink).show();
                $('img', activeLinkContainer).remove();
                if (response.result === 'error' && typeof response.error !== 'undefined') {
                    $('#error_dialog').html(response.error);
                    $('#error_dialog').dialog({title: 'Error'});
                    return;
                }
				alert('Error');
			}
		});
	});

	$('a[data-action="mapfiles_manager"]').click(function(event) {
		event.preventDefault();

		$('div#mapfiles_manager').dialog('open');
	});
});