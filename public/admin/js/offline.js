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
        
        var params = {
            action: 'get-data',
            project: $('input#project').val(),
            map: map
        };

        $.ajax({
            url: 'ajax/offline.php',
            type: 'GET',
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
                        if (Object.keys(theme.mbtiles).length) {
                            html += '<td id="td_' + theme.name + '">';
                            switch (theme.mbtiles.state) {
                                case 'running':
                                    html += '<a href="#" data-action="check" data-target="mbtiles" data-map="' + map + '" data-theme="' + theme.name +'">Check</a>';
                                    html += '<a href="#" data-action="stop" data-target="mbtiles" data-map="' + map + '" data-theme="' + theme.name +'">Stop</a>';
                                    break;

                                case 'stopped':
                                    html += '<a href="#" data-action="clear" data-target="mbtiles" data-map="' + map + '" data-theme="' + theme.name +'">Clear</a>';
                                /* fall through */
                                case 'to-do':
                                    html += '<a href="#" data-action="generate" data-target="mbtiles" data-map="' + map + '" data-theme="' + theme.name +'">Generate</a>';
                            }

                            if (theme.mbtiles.progress) {
                                html += theme.mbtiles.progress + '%';
                            }
                            html += '</td>';
                        } else {
                            html += '<td>no tiles</td>';
                        }
                        if (Object.keys(theme.sqlite).length) {
                            html += '<td>';
                            switch (theme.sqlite.state) {
                                case 'running':
                                    html += '<a href="#" data-action="check" data-target="sqlite" data-map="' + map + '" data-theme="' + theme.name +'">Check</a>';
                                    html += '<a href="#" data-action="stop" data-target="sqlite" data-map="' + map + '" data-theme="' + theme.name +'">Stop</a>';
                                    break;

                                case 'stopped':
                                    html += '<a href="#" data-action="clear" data-target="sqlite" data-map="' + map + '" data-theme="' + theme.name +'">Clear</a>';
                                /* fall through */
                                case 'to-do':
                                    html += '<a href="#" data-action="generate" data-target="sqlite" data-map="' + map + '" data-theme="' + theme.name +'">Generate</a>';
                            }
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
                        loadMapView($(this).attr('data-map'));
                    });
                    $('div#offline_manager a[data-action="clear"]').button(
                        {icons:{primary:'ui-icon-close'}, text:false}
                    ).click(function () {
                        if (!confirm('Delete this?')) {
                            return;
                        }
                        var params = {
                            action: 'clear',
                            project: $('input#project').val(),
                            map: $(this).attr('data-map'),
                            target: $(this).attr('data-target'),
                            theme: $(this).attr('data-theme')
                        };
                        $.ajax({
                            url: 'ajax/offline.php',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(params.map);
                            }
                        });
                    });
                    $('div#offline_manager a[data-action="stop"]').button(
                        {icons:{primary:'ui-icon-stop'}, text:false}
                    ).click(function () {
                        var params = {
                            action: 'stop',
                            project: $('input#project').val(),
                            map: $(this).attr('data-map'),
                            target: $(this).attr('data-target'),
                            theme: $(this).attr('data-theme')
                        };
                        $.ajax({
                            url: 'ajax/offline.php',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(params.map);
                            }
                        });
                    });
                    $('div#offline_manager a[data-action="generate"]').button(
                        {icons:{primary:'ui-icon-play'}, text:false}
                    ).click(function () {
                        var params = {
                            action: 'start',
                            project: $('input#project').val(),
                            map: $(this).attr('data-map'),
                            target: $(this).attr('data-target'),
                            theme: $(this).attr('data-theme')
                        };
                        $.ajax({
                            url: 'ajax/offline.php',
                            type: 'GET',
                            dataType: 'json',
                            data: params,
                            success: function(response) {
                                loadMapView(params.map);
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

            var params = {
                action: 'download',
                project: $('input#project').val(),
                map: $(this).attr('data-map')
            };
            $.ajax({
                url: 'ajax/offline.php',
                type: 'GET',
                dataType: 'json',
                data: params,
                success: function(response) {
                    $(activeLink).show();
                    $('img', activeLinkContainer).remove();

                    console.log(response);
                }
            });
        });
    }

    $('a[data-action="offline_manager"]').click(function(event) {
        event.preventDefault();

        $('div#offline_manager').dialog('open');
    });
});