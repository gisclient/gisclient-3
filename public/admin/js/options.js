$(document).ready(function() {
	$('#options_dialog').dialog({
		title: 'Options',
		width: 300,
		height: 200,
		autoOpen: false,
		open: function() {
			$('#options_dialog div.logs').empty();
		}
	});
	$('a[data-action="options"]').click(function(event) {
		event.preventDefault();
		$('#options_dialog').dialog('open');
	});
	$('#options_dialog button[name="save"]').click(function(event) {
		event.preventDefault();
		$('#options_dialog div.logs').empty();

		var options = {
			save_to_tmp_map: $('#options_dialog input[name="save_to_tmp_map"]').attr('checked'),
			auto_refresh_mapfiles: $('#options_dialog input[name="auto_refresh_mapfiles"]').attr('checked')
		};
		$.ajax({
			url: 'ajax/options.php',
			type: 'POST',
			dataType: 'json',
			data: options,
			success: function(response) {
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined' || response.result != 'ok') {
					alert('Error');
				}
				$('#options_dialog div.logs').html('Ok');
			},
			error: function() {
				alert('Error');
			}
		});
	});
});