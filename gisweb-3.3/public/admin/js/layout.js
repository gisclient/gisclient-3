$(document).ready(function() {
/* jquerylayout */
	myLayout = $('#container').layout({
		north: { size: 90, spacing_open: 10, closable: false, resizable: false },
		south: { size: 20, spacing_open: 10, closable: false, resizable: false }
	});
	
	/* ui buttons */
	$('a.button , input[type|="button"] , input[type|="submit"] , button').button();
	$('a.logout').button({icons: { primary: 'ui-icon-power' }});
	$('#frm_label').buttonset();
	$('a.next').button({icons: { primary: 'ui-icon-carat-1-e' }});
	$('.tableHeader button').button({icons: { primary: 'ui-icon-circle-plus' }});
	$('.stiletabella a.edit').button({icons: { primary: 'ui-icon-pencil' },text: false});
	$('.stiletabella a.delete').button({icons: { primary: 'ui-icon-close' },text: false});
	$('.stiletabella a.info').button({icons: { primary: 'ui-icon-folder-open' },text: false});
	
	/* ui alert & info */
	$('span.alert , span.error').addClass('ui-state-error ui-corner-all').prepend('<span class="ui-icon ui-icon-alert" style="float: left; margin-right: .5em;"></span>');
	$('span.info').addClass('ui-state-highlight ui-corner-all').prepend('<span class="ui-icon ui-icon-info" style="float: left; margin-right: .5em;"></span>');
	
	if(typeof(errors) != 'undefined' && errors.length > 0) {
		$('#error_dialog').html(errors.join('<br>'));
		$('#error_dialog').dialog({
			title: 'Error'
		});
	}	
});