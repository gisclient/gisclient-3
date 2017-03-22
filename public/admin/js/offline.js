$(document).ready(function() {
    var dialog = $('div#offline_manager');
    dialog.dialog({
        autoOpen: false,
        title: $('div#offline_manager').attr('data-title'),
        width: 800,
        height: 600,
        open: initOfflineDialog
    });

    function mapsetView(mapset) {
        dialog.empty();
        dialog.append($('#offline_theme').html());
        
        var params = {
            action: 'get-data',
            project: $('input#project').val(),
            mapset: mapset
        };

        $.ajax({
            url: 'ajax/offline.php',
            type: 'POST',
            dataType: 'json',
            data: params,
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
                    var html = '';

                    for (var i = 0; i < response.themes.length; i++) {
                        var theme = response.themes[i];
                        html += '<tr>';
                        html += '<td>' + theme.title + ' (' + theme.name + ')</td>';
                        if (theme.hasMbTiles) {
                            html += '<td id="td_' + theme.name + '">';
                            switch (theme.mbTilesState) {
                                case 'running':
                                    html += '<a href="#" data-action="check" data-target="mbtiles" data-mapset="' + mapset + '">Check</a>';
                                    html += '<a href="#" data-action="stop" data-target="mbtiles" data-mapset="' + mapset + '">Stop</a>';
                                    break;

                                case 'stopped':
                                    html += '<a href="#" data-action="clear" data-target="mbtiles" data-mapset="' + mapset + '">Clear</a>';
                                /* fall through */
                                case 'to-do':
                                    html += '<a href="#" data-action="generate" data-target="mbtiles" data-mapset="' + mapset + '">Generate</a>';
                            }
                            html += '</td>';
                        } else {
                            html += '<td>no tiles</td>';
                        }
                        if (theme.hasSqlite) {
                            html += '<td>';
                            html += '<a href="#" data-action="clear" data-target="sqlite" data-mapset="' + mapset + '">Clear</a>';
                            html += '<a href="#" data-action="generate" data-target="sqlite" data-mapset="' + mapset + '">Generate</a>';
                            html += '</td>';
                        } else {
                            html += '<td>no sqlite</td>';
                        }
                        html += '</tr>';
                    }
                    dialog.find('table').append(html);

                    $('div#offline_manager a[data-action="check"]').button(
                        {icons:{primary:'ui-icon-refresh'}, text:false}
                    ).click(function () {
                        mapsetView($(this).attr('data-mapset'));
                    });
                    $('div#offline_manager a[data-action="clear"]').button({icons:{primary:'ui-icon-close'}, text:false});
                    $('div#offline_manager a[data-action="stop"]').button(
                        {icons:{primary:'ui-icon-stop'}, text:false}
                    ).click(function () {
                        var params = {
                            action: 'stop',
                            project: $('input#project').val(),
                            map: $(this).attr('data-mapset')
                        };
                        $.ajax({
                            url: '../services/test.php',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                mapsetView(params.map);
                            }
                        });
                    });
                    $('div#offline_manager a[data-action="generate"]').button(
                        {icons:{primary:'ui-icon-play'}, text:false}
                    ).click(function () {
                        var params = {
                            action: 'start',
                            project: $('input#project').val(),
                            map: $(this).attr('data-mapset')
                        };
                        $.ajax({
                            url: '../services/test.php',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                mapsetView(params.map);
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
            mapsetView($(this).attr('data-mapset'));
        });

        $('div#offline_manager a[data-action="download"]').button({icons:{primary:'ui-icon-arrowthickstop-1-s'}, text:false});
    }

    $('a[data-action="offline_manager"]').click(function(event) {
        event.preventDefault();

        $('div#offline_manager').dialog('open');
    });
});