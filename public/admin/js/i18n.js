$(document).ready(function() {
	$('div#i18n_dialog table').empty();
	
	if(typeof(initI18n) != 'undefined' && initI18n) {
		var level = $('input[name="livello"]').val();
		if($('input#project').length < 1) return;
		var project = $('input#project').val();
		var pKey = $('input#'+level).val();
		
		$.ajax({
			url: 'ajax/i18n.php',
			type: 'GET',
			dataType: 'json',
			data: {
				level: level,
				project: project,
				p_key: pKey
			},
			success: function(response) {
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined' || response.result != 'ok') {
					$('div#i18n_inline').append('<p style="color:red">Errore di sistema</p>');
					return;
				}
				var data = response.data;
				
				var formHtml = getTableHtml(data, 'edit');
				$('div#i18n_dialog table').append(formHtml);
				
				var viewHtml = getTableHtml(data, 'view');
				$('div#i18n_inline table').append(viewHtml);
			},
			error: function() {
				$('div#i18n_inline').append('<p style="color:red">Errore di sistema</p>');
			}
		});
	}

	$('input#i18n').click(function(event) {
		event.preventDefault();
		
		$('div#i18n_dialog').dialog({
			title: 'Traduzioni',
			width: 600,
			height: 600
		});
	});
	
	$('div#i18n_dialog input[name="submit"]').click(function(event) {
		event.preventDefault();
		
		var level = $('input[name="livello"]').val();
		if($('input#project').length < 1) return;
		var project = $('input#project').val();
		var pKey = $('input#'+level).val();
		
		var translations = {};
		$('div#i18n_dialog input[rel="translation"]').each(function() {
			var languageId = $(this).attr('data-language_id');
			var fieldId = $(this).attr('data-field_id');
			var translation = $(this).val();
			if(typeof(translations[fieldId]) == 'undefined') translations[fieldId] = {};
			translations[fieldId][languageId] = translation;
		});
		
		$.ajax({
			url: 'ajax/i18n.php',
			dataType: 'json',
			type: 'POST',
			data: {
				level: level,
				project: project,
				p_key: pKey,
				translations: translations
			},
			success: function(response) {
				if(typeof(response) != 'object' || typeof(response.result) == 'undefined' || response.result != 'ok') {
					alert('Errore di sistema');
					return;
				}
				alert('Traduzioni salvate correttamente');
				$('div#i18n_dialog').dialog('close');
			},
			error: function() {
				alert('Errore di sistema');
			}
		});
	});
});

function getTableHtml(data, mode) {
	var html = '';
	
	var fields = data.fields;
	var translations = data.translations;
	var defaultLanguage = data.defaultLanguage;
	var otherLanguages = [];
	
	var tableHeader = '<tr><th>Campo</th><th>'+data.languages[defaultLanguage]+'</th>';
	var count = 0;
	$.each(data.languages, function(languageId, languageName) {
		if(languageId == defaultLanguage) return;
		count += 1;
		tableHeader += '<th>'+languageName+'</th>';
		otherLanguages.push(languageId);
	});
	if(count == 0) {
		alert('Nessuna lingua definita per questo progetto');
		return;
	}
	
	count = 0;
	$.each(fields, function(fieldId, field) {
		count += 1;
	});
	if(count == 0) {
		alert('Nessun campo traducibile in questa pagina');
		return;
	}
	
	tableHeader += '</tr>';
	html += tableHeader;	
	
	$.each(fields, function(fieldId, field) {
		var fieldName = field['field_name'];
		var tableRow = '<tr><td><b>'+fieldName+'</b></td>';
		tableRow += '<td>'+translations[defaultLanguage][fieldName]+'</td>';
		$.each(otherLanguages, function(e, languageId) {
			tableRow += '<td>';
			if(mode == 'edit') {
				tableRow += '<input type="text" rel="translation" data-language_id="'+languageId+'" data-field_name="'+fieldName+'" data-field_id="'+fieldId+'"';
				if(typeof(translations[languageId][fieldName]) != 'undefined') {
					tableRow += 'value="'+translations[languageId][fieldName]+'"';
				}
				tableRow += '>';
			} else {
				if(typeof(translations[languageId][fieldName]) != 'undefined') {
					tableRow += translations[languageId][fieldName];
				}
			}
			tableRow += '</td>';
		});
		tableRow += '</tr>';
		html += tableRow;
	});
	return html;
}