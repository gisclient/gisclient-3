$(document).ready(function() {
	$('#cache_manager').dialog({
		title: 'Cache',
		width: 500,
		height: 450,
		autoOpen: false,
		open: function() {
			
		}
	});
	$('a[data-action="cache_manager"]').click(function(event) {
		event.preventDefault();
        
        $('#cache_manager table tr[role!="header"]').empty();
        $.ajax({
            url: 'ajax/cache.php',
            type: 'POST',
            dataType: 'json',
            data: {
                project: $('input#project').val(),
                action: 'list'
            },
            success: function(response) {
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined') {
					return alert('Error');
				}
				if(response.result != 'ok') {
					if(response.result == 'error' && response.error) {
                        return alert(response.error);
					}
					return alert('Error');
				}
                
                var html = '';
                $.each(response.files, function(e, file) {
                    html += '<tr><td>'+file.layer+' ('+file.size+')</td><td><a href="#" action="empty" file="'+file.name+'">Refresh</a></td></tr>';
                });
                
                $('#cache_manager table').append(html);
                $('#cache_manager table a[action="empty"]').button({icons:{primary:'ui-icon-refresh'}, text:false}).click(function(event) {
                    var link = $(this);
                    event.preventDefault();
                    
                    $.ajax({
                        url: 'ajax/cache.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'empty',
                            project: $('input#project').val(),
                            file: link.attr('file')
                        },
                        success: function(response) {
                            if(typeof(response) != 'object' || typeof(response.result) == 'undefined' || response.result != 'ok') {
                                return alert('Error');
                            }
                            link.closest('tr').remove();
                        },
                        error: function() {
                            return alert('Error');
                        }
                    });
                });
            },
            error: function() {
                alert('System error');
            }
        });
        
		$('#cache_manager').dialog('open');
	});
});