var loadingGif = '<img src="../images/ajax_loading.gif">';
$(document).ready(function() {
    var dialog = $('div#offline_manager');
    dialog.dialog({
        autoOpen: false,
        title: $('div#offline_manager').attr('data-title'),
        width: 800,
        height: 600,
        open: initOfflineDialog
    });

    function loadMapView(map) {
        dialog.empty();
        dialog.append($('#offline_theme').html());
        
        var project = $('input#project').val();
        $.ajax({
            url: '../services/offline/'+project+'/'+map+'/get-data.json',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log(response);

                if (response.result != 'ok') {
                    if (response.result == 'error' && typeof(response.error) == 'object' && typeof(response.error.type) != 'undefined' && response.error.type == 'mapfile_errors') {
                        $('#error_dialog').html(response.error.text);
                        $('#error_dialog').dialog({
                            title: 'Error'
                        });
                        return;
                    }
                    return alert('Error');
                } else {
                    for (var layerType in response.data) {
                        var html = '';
                        for (var i = 0; i < response.data[layerType].length; i++) {
                            var layer = response.data[layerType][i];
                            html += '<tr>';
                            html += '<td>' + layer.title + ' (' + layer.name + ')</td>';

                            var formats = ['mbtiles', 'sqlite', 'mvt'];
                            for (var j in formats) {
                                var format = formats[j];

                                if (Object.keys(layer[format]).length) {
                                    html += '<td id="td_' + layer.name + '">';
                                    switch (layer[format].state) {
                                        case 'running':
                                            html += '<a href="#" data-action="check" data-target="'+format+'" data-map="' + map + '" data-layertype="' + layerType +'" data-layer="' + layer.name +'">Check</a>';
                                            html += '<a href="#" data-action="stop" data-target="'+format+'" data-map="' + map + '" data-layertype="' + layerType +'" data-layer="' + layer.name +'">Stop</a>';
                                            break;
    
                                        case 'stopped':
                                            html += '<a href="#" data-action="clear" data-target="'+format+'" data-map="' + map + '" data-layertype="' + layerType +'" data-layer="' + layer.name +'">Clear</a>';
                                        /* fall through */
                                        case 'to-do':
                                            html += '<a href="#" data-action="generate" data-target="'+format+'" data-map="' + map + '" data-layertype="' + layerType +'" data-layer="' + layer.name +'">Generate</a>';
                                    }
    
                                    if (layer[format].progress) {
                                        html += layer[format].progress + '%';
                                    }
                                    html += '</td>';
                                } else {
                                    html += '<td>no '+format+'</td>';
                                }
                            }
                            html += '</tr>';
                        }
                        var table = dialog.find('table[data-layer='+layerType+']')
                        if (html != '') {
                            $(table).append(html);
                            $(table).show();
                        } else {
                            $(table).hide();
                        }
                    }

                    $('div#offline_manager a[data-action="check"]').button(
                        {icons:{primary:'ui-icon-refresh'}, text:false}
                    ).click(function () {
                        loadMapView($(this).attr('data-map'));
                    });
                    $('div#offline_manager a[data-action="clear"]').button(
                        {icons:{primary:'ui-icon-close'}, text:false}
                    ).click(function () {
                        if (!confirm('Delete this?')) {
                            return;
                        }
                        var project = $('input#project').val();
                        var map = $(this).attr('data-map');
                        var params = {
                            target: $(this).attr('data-target'),
                            layertype: $(this).attr('data-layertype'),
                            layer: $(this).attr('data-layer')
                        };
                        $.ajax({
                            url: '../services/offline/'+project+'/'+map+'/clear.json',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(map);
                            },
                            error: function(responseObj) {
                                var response = JSON.parse(responseObj.responseText);
                                return alert(response.message);
                            }
                        });
                    });
                    $('div#offline_manager a[data-action="stop"]').button(
                        {icons:{primary:'ui-icon-stop'}, text:false}
                    ).click(function () {
                        var project = $('input#project').val();
                        var map = $(this).attr('data-map');
                        var params = {
                            target: $(this).attr('data-target'),
                            layertype: $(this).attr('data-layertype'),
                            layer: $(this).attr('data-layer')
                        };
                        $.ajax({
                            url: '../services/offline/'+project+'/'+map+'/stop.json',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(map);
                            },
                            error: function(responseObj) {
                                var response = JSON.parse(responseObj.responseText);
                                return alert(response.message);
                            }
                        });
                    });
                    $('div#offline_manager a[data-action="generate"]').button(
                        {icons:{primary:'ui-icon-play'}, text:false}
                    ).click(function () {
                        var project = $('input#project').val();
                        var map = $(this).attr('data-map');
                        var params = {
                            target: $(this).attr('data-target'),
                            layertype: $(this).attr('data-layertype'),
                            layer: $(this).attr('data-layer')
                        };
                        $.ajax({
                            url: '../services/offline/'+project+'/'+map+'/start.json',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(map);
                            },
                            error: function(responseObj) {
                                var response = JSON.parse(responseObj.responseText);
                                return alert(response.message);
                            }
                        });
                    });
                }
            },
            error: function() {
                alert('Error');
            }
        });
        


        $('#offline_theme_back').button().click(function (event) {
            event.preventDefault();

            initOfflineDialog();
        });
    }

    function initOfflineDialog() {
        dialog.empty();
        dialog.append($('#offline_mapset').html());

        $('div#offline_manager a[data-action="create"]').button(
            {icons:{primary:'ui-icon-arrowreturnthick-1-e'}, text:false}
        ).click(function (event) {
            loadMapView($(this).attr('data-map'));
        });

        $('div#offline_manager a[data-action="download"]').button(
            {icons:{primary:'ui-icon-arrowthickstop-1-s'}, text:false}
        ).click(function () {
            var activeLink = this;
            var activeLinkContainer = $(this).parent();
            $(activeLink).hide();
            $(activeLinkContainer).append(loadingGif);

            var project = $('input#project').val();
            var map = $(this).attr('data-map');
            location.href = '../services/offline/'+project+'/'+map+'/download.zip';
            /*$.ajax({
                url: ,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    $(activeLink).show();
                    $('img', activeLinkContainer).remove();
                    
                    console.log(response);
                }
            });*/
        });
    }

    $('a[data-action="offline_manager"]').click(function(event) {
        event.preventDefault();

        $('div#offline_manager').dialog('open');
    });
});
