OpenLayers.Control.PrintMap = OpenLayers.Class(OpenLayers.Control.Button, {
    type: OpenLayers.Control.TYPE_TOGGLE,
    formId: null, //id del form di stampa
    loadingControl: null,
    offsetLeft:0, //pannelli che se aperti riducono l'area della mappa
    offsetRight:0,
    offsetTop:0,
    offsetBottom:0,
    margin:25,
    pageFormat:"A4", //controlli
    pageLayout:"vertical",
    printFormat:"HTML",
    printBoxScale:null,
    printLegend:0,
    printResolution:150,
    maxPrintScale:null,
    layerBox:null,
    editMode:false,
    styleBox:null,

    //waitFor: null, //se il pannello viene caricato async, il tool aspetta il caricamento prima di far partire la richiesta per il box
    pages: null,
    
    initialize: function(options) {
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
        OpenLayers.Feature.Vector.style['default']['strokeWidth'] = '2';
    },
    
    doPrint: function() {
        var me = this;
        var params = me.getParams();
        
        if(me.loadingControl) me.loadingControl.maximizeControl();
        
        $('#'+me.formId+' a[role="pdf"], #printpanel a[role="html"]').attr('href', '#');
        $('#'+me.formId+' span[role="icon"]').removeClass('glyphicon-white').addClass('glyphicon-disabled');
        
        $.ajax({
            url: this.serviceUrl,
            jsonpCallback: "callback",
            async: false,
            type: 'POST',
            data: params,
            dataType: 'jsonp',
            success: function(response) {
                if(typeof(response.result) != 'undefined' && response.result == 'ok') {
                    //$('#'+this.formId+' div.loading').hide();
                    
                    if(response.format == 'HTML') {
                        $('#'+me.formId+' a[role="html"]').attr('href', response.file);
                        $('#'+me.formId+' a[role="html"] span[role="icon"]').removeClass('glyphicon-disabled').addClass('glyphicon-white');
                    } else if(response.format == 'PDF') {
                        $('#'+me.formId+' a[role="pdf"]').attr('href', response.file);
                        $('#'+me.formId+' a[role="pdf"] span[role="icon"]').removeClass('glyphicon-disabled').addClass('glyphicon-white');
                    }
                    
                } else alert(OpenLayers.i18n('Error'));
                
                if(me.loadingControl) me.loadingControl.minimizeControl();
            },
            error: function() {
                alert(OpenLayers.i18n('Error'));
                if(me.loadingControl) me.loadingControl.minimizeControl();
            }
        });
    },
    
    setMap: function(map) {

        //si può spostare in initialize quando togliamo i parametri che dipendono da map


        var me = this;

        OpenLayers.Control.prototype.setMap.apply(me, arguments);

        this.layerbox = new OpenLayers.Layer.Vector("LayerBox",{styleMap:me.styleBox});    
        this.map.addLayer(this.layerbox);

        if(this.editMode){
            this.modifyControl = new OpenLayers.Control.ModifyFeature(this.layerbox);
            this.modifyControl.mode = OpenLayers.Control.ModifyFeature.RESIZE | OpenLayers.Control.ModifyFeature.DRAG;
            this.map.addControl(this.modifyControl);
            this.layerbox.events.register('featuremodified', this, this.onUpdateBox);
        }        

        
        var params = this.getConfigParams();
        params.request_type = 'get-box';

        $.ajax({
            url: this.serviceUrl,
            jsonpCallback: "callback",
            async: false,
            type: 'POST',
            dataType: 'jsonp',
            data: params,
            success: function(response) {

                if(typeof(response) != 'object' || response == null || typeof(response.result) != 'string' || response.result != 'ok' || typeof(response.pages) != 'object') {
                    return alert(OpenLayers.i18n('System error'));
                }
                me.pages = response.pages;
                me.drawPrintBox();

            },
            error: function() {
                return alert(OpenLayers.i18n('System error'));
            }
        });


    },
    
    getConfigParams: function() {


        //DA SISTEMARE !!!!!!!!!!!!
        var size  = this.map.getCurrentSize();
        var bounds = this.map.calculateBounds();
        var topLeft = new OpenLayers.Geometry.Point(bounds.top, bounds.left);
        var topRight = new OpenLayers.Geometry.Point(bounds.top, bounds.right);
        var distance = topLeft.distanceTo(topRight);
        var pixelsDistance  = size.w / distance;
        var scaleMode = $('[name="scale_mode"]:checked').attr('value') || 'user';
        var scale = $('[name="scale"]').attr('value');
        var currentScale = this.map.getScale();
        if(scaleMode == 'user') {
            pixelsDistance = pixelsDistance / (scale/currentScale);
        }
        
        if(this.printBox) {
            var boxBounds = new OpenLayers.Bounds.fromArray(this.printBox);
            var center = boxBounds.getCenterLonLat();
        } else {
            var center = this.map.getCenter();
        }
        
        
        var copyrightString = null;
        var searchControl = this.map.getControlsByClass('OpenLayers.Control.Attribution');
        if(searchControl.length > 0) {
            copyrightString = searchControl[0].div.innerText;
        }
        
        var srid = this.map.getProjection();
        if(srid == 'ESPG:900913') srid = 'EPSG:3857';
        
        var params = {
            viewport_size: [size.w, size.h],
            center: [center.lon, center.lat],
            format: $('#'+this.formId+' input[name="format"]:checked').val(),
            printFormat: $('#'+this.formId+' select[name="formato"]').val(),
            direction: $('#'+this.formId+' input[name="direction"]:checked').val(),
            scale_mode: scaleMode,
            scale: scale,
            current_scale: currentScale,
            text: $('#'+this.formId+' textarea[name="text"]').val(),
            extent: this.map.calculateBounds().toBBOX(),
            date: $('#'+this.formId+' input[name="date"]').val(),
            dpi: $('#'+this.formId+' select[name="print_resolution"]').val(),
            srid: srid,
            pixels_distance: pixelsDistance,
            copyrightString: copyrightString
        };
        return params;
        
    },


    getParams: function() {
        var self = this;
        
        var params = this.getConfigParams();
        
        var tiles = [];
        
        $.each(this.map.layers, function(key, layer) {
            if (!layer.getVisibility()) return;
            //if (!layer.calculateInRange()) return;
            var tile;
            if(layer.CLASS_NAME == 'OpenLayers.Layer.TMS') {
                tile = {
                    url: layer.url.replace('/tms/', '/wms/'),
                    parameters: {
                        service: 'WMS',
                        request: 'GetMap',
                        project: gisclient.getProject(),
                        map: gisclient.getMapOptions().mapsetName,
                        layers: [layer.layername.substr(0, layer.layername.indexOf('@'))],
                        version: '1.1.1',
                        format: 'image/png'
                    },
                    type: 'WMS',
                    opacity: layer.opacity ? (layer.opacity * 100) : 100
                };
            } else if(layer.CLASS_NAME == 'OpenLayers.Layer.WMS') {
                tile = {
                    url: layer.url,
                    type: 'WMS',
                    parameters: layer.params,
                    opacity: layer.opacity ? (layer.opacity * 100) : 100
                };
            } else if(layer.CLASS_NAME == 'OpenLayers.Layer.WMTS') {
                var params = {
                    LAYERS: [layer.name],
                    FORMAT: 'image/png',
                    SRS: layer.projection.projCode,
                    TRANSPARENT: true,
                    SERVICE: 'WMS',
                    VERSION: '1.1.1'
                };
                tile = {
                    url: GisClientMap.mapProxyBaseUrl+'/'+GisClientMap.mapsetName+'/service?',
                    type: 'WMS',
                    parameters: params,
                    opacity: layer.opacity ? (layer.opacity * 100) : 100
                };
            } else if(layer.CLASS_NAME == 'OpenLayers.Layer.OSM' ||
                layer.CLASS_NAME == 'OpenLayers.Layer.Google') {

                tile = {
                    type: 'external_provider',
                    externalProvider: layer.CLASS_NAME.replace('OpenLayers.Layer.', ''),
                    name: layer.name,
                    project: GisClientMap.projectName,
                    map: GisClientMap.mapsetName
                };
            } else console.log(layer.name+' '+layer.CLASS_NAME+' non riconosciuto per stampa');
            if(tile) {
                if(layer.options.theme_id) {
                    tile.options = {
                        theme_id: layer.options.theme_id,
                        theme_title: layer.options.theme_title || ''
                    };
                }
                tiles.push(tile);
            }
        });

        if($('#'+this.formId+' input[name="legend"]:checked').val() == 'yes') {
            params.legend = 'yes';
            
        }
        tiles.reverse();
        params.tiles = tiles;


        return params;
    },

    
    onUpdateBox: function(e){


        var pageSize=this.pages[this.pageLayout][this.pageFormat];
        //si dovrebbero passare già in float
        var pageW = parseFloat(pageSize.w);
        var pageH = parseFloat(pageSize.h);

        var bounds = e.feature.geometry.getBounds();
        this.printBoxScale = Math.abs(bounds.right-bounds.left)/pageW*100;
                console.log(this.printBoxScale)

        if(this.printBoxScale > this.maxScale) {
            this.printBoxScale = this.maxScale;
            this.updatePrintBox();
        }

        this.events.triggerEvent("updatebox");

    },

    
    activate: function() {
        var activated = OpenLayers.Control.prototype.activate.call(this);
        if(activated) {
            console.log('ACTIVATED')
            //.................

        }
    },


    drawPrintBox: function() {
        
        console.log("INSERIMENTO POLIGONO")
        

        //console.log(this.pages)
        //console.log(this.pageLayout)
        //console.log(this.pageFormat)





        //calcolo l'area libera per il box di stampa
        var boxW = this.map.size.w - this.offsetLeft - this.offsetRight - 2*this.margin;
        var boxH = this.map.size.h - this.offsetTop - this.offsetBottom - 2*this.margin;

        var pageSize=this.pages[this.pageLayout][this.pageFormat];
        //si dovrebbero passare già in float
        var pageW = parseFloat(pageSize.w);
        var pageH = parseFloat(pageSize.h);

        //normalizzo rispetto al rapporto dimensionale della stampa
        if(pageW/pageH > boxW/boxH)
            boxH = boxW*pageH/pageW;
        else
            boxW = boxH*pageW/pageH;

        var leftPix = parseInt((this.map.size.w - boxW)/2);
        var topPix = parseInt((this.map.size.h - boxH)/2);

        var lb = this.map.getLonLatFromPixel(new OpenLayers.Pixel(leftPix, topPix + boxH));
        var rt = this.map.getLonLatFromPixel(new OpenLayers.Pixel(leftPix + boxW, topPix));

        //vedo che scala è uscita
        //occhio all'unità di misura meglio portare tutto in pollici??
        //comunque per ora va tutto im metri
        if(!this.printBoxScale) this.printBoxScale = Math.abs(lb.lon-rt.lon)/pageW*100;
        
        var boxScale = Math.abs(lb.lon-rt.lon)/pageW*100;

        var bounds = new OpenLayers.Bounds(lb.lon, lb.lat, rt.lon, rt.lat);

        if(this.printBoxScale){
            if(this.printBoxScale > this.maxScale) {
                //scalo e risetto la scala
                bounds = bounds.scale(this.maxScale/boxScale)
                this.printBoxScale = this.maxScale;
            }
            else{
                bounds = bounds.scale(this.printBoxScale/boxScale)
            }
        }

        boxW = bounds.getWidth();
        boxH = bounds.getHeight();


        //DA CAPIRE PERCHE NON SONO  RIUSCITO A FARE UN SEMPLICE MOVE
        //if(this.centerBox) bounds = new OpenLayers.Bounds(this.centerBox.lon - boxW/2, this.centerBox.lat - boxH/2, this.centerBox.lon + boxW/2,  this.centerBox.lat + boxH/2);
        this.printBox = new OpenLayers.Feature.Vector(bounds.toGeometry());
        this.layerbox.addFeatures(this.printBox);
        if(this.editMode) {
            this.modifyControl.activate();
            //this.events.triggerEvent("updatebox");
        }

    },
    
    updatePrintBox: function(){

        //se cambio le dimensioni voglio comunque mantenere la scala di stampa!!!
        //non ruoto semplicemente il box perchè le dimensioni potrebbero essere diverse
        //console.log(this.pageFormat)
        //console.log(this.pageLayout)
        var pageSize=this.pages[this.pageLayout][this.pageFormat];
        //si dovrebbero passare già in float
        var pageW = parseFloat(pageSize.w);
        var pageH = parseFloat(pageSize.h);

        var boxW = pageW*this.printBoxScale/100;
        var boxH = pageH*this.printBoxScale/100;

        var bounds = this.printBox.geometry.getBounds();
        var center = bounds.getCenterLonLat();
        var newBounds = new OpenLayers.Bounds(center.lon - boxW/2, center.lat - boxH/2, center.lon + boxW/2,  center.lat + boxH/2);

        //????????????????????????????????????????? non aggiorna
        //BOH NON RIESCO A MODIFICARE LA FEATURE. QUINDI LA TOLGO E LA RIAGGIUNGO POI VEDIAMO
        if(this.modifyControl.feature) this.modifyControl.unselectFeature(this.printBox);
        this.printBox.destroy();
        this.printBox = new OpenLayers.Feature.Vector(newBounds.toGeometry());
        this.layerbox.addFeatures(this.printBox);
        this.events.triggerEvent("updatebox");



    },

    movePrintBox: function(position){
        //if(!this.editMode) return;
        if(this.modifyControl && this.modifyControl.feature) this.modifyControl.unselectFeature(this.printBox);
        this.printBox.move(position);

    },

    getBounds: function(){
        return this.printBox.geometry.getBounds();

    }



    
});



