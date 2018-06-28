$(document).ready(function() {
	if(initCheckFields != true ) {
		return;
	}
	var loadingDiv = document.createElement('div');
	loadingDiv.id = 'loading_checkfields';
	loadingDiv.className = 'loading_div';
	document.getElementsByTagName('body')[0].appendChild(loadingDiv);
	loadingDiv.style.display = 'none';
    $('a[data-action="check_fields"]').show().click(function(event) {
		event.preventDefault();
		loadingDiv.style.display = 'block';
		$.ajax({
		  url: 'ajax/checkfields.php',
		  type: 'POST',
		  dataType: 'json',
		  data: {layer_id: $('#layer').val()},
		  success: function(response) {
			if(typeof(response) != 'object' || typeof(response.result) == 'undefined') {
				loadingDiv.style.display = 'none';
				return alert('Controllo campi fallito');
			}
			else if(response.result != 'ok') {
			  if(response.result = 'error' && typeof(response.error) == 'object' && typeof(response.error.type)!='undefined' && response.error.type == 'checkfields_errors') {
				$('#error_dialog').html(response.error.text);
				$('#error_dialog').dialog({
				  title: 'Errore nel controllo dei campi',
				  width: 550,
				  height: 250
				});
			  }
			}
			else {
				var errorMsg = '';
				if (response.data.error_fields.length > 0) {
					errorMsg += '<p>Alcuni campi del layer risultano errati e sono stati evidenziati in rosso. </p><br>';
					for (var i = 0; i < response.data.error_fields.length; i++) {
						var linkFunct = "javascript:link('" + response.data.error_fields[i] + "','field')";
						$('a[href="' + linkFunct + '"]').parent().parent().css('background', 'red');
					}
				}
				if (response.data.missing_fields.length > 0) {
					errorMsg += '<p style="color:black;">';
					errorMsg += 'Le seguenti colonne sulla tabella non sono state utilizzate come campi del layer:<ul style="color:black;">';
					for (var j = 0; j < response.data.missing_fields.length; j++) {
						errorMsg += '<li>' + response.data.missing_fields[j] + '</li>';
					}
					errorMsg += '</ul></p>';
				}
				if (errorMsg.length > 0) {
					$('#error_dialog').html(errorMsg);
					$('#error_dialog').dialog({
					  title: 'Risultato del controllo campi Layer',
					  width: 550,
					  height: 600
					});
				}
			}
			loadingDiv.style.display = 'none';
		  },
		  error: function(errResponse) {
			alert('Error');
			loadingDiv.style.display = 'none';
		  }
		});
	});
});
