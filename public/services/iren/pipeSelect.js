
OpenLayers.Control.PIPESelect = OpenLayers.Class(OpenLayers.Control, {

    EVENT_TYPES: ["beforeSelect","afterSelect","selected"],
    type: OpenLayers.Control.TYPE_BUTTON,
    clearOnDeactivate: false,
    layers: null,
	pipelayer:null,
	distance:5,
    callbacks: null,
	serviceURL:null,
	exclude:[],
	popup:null,
	selectControl:null,
	highlightCtrl:null,
	selectedObject:null,
	
    selectionSymbolizer: {
        'Polygon': {strokeColor: '#FF0000'},
        'Line': {strokeColor: '#FF0000', strokeWidth: 2},
        'Point': {fill:false, graphicName: 'circle', strokeColor: '#FF0000', pointRadius: 5}
    },
	
    layerOptions: null,
	
    handlerOptions: null,
    sketchStyle: null,

    initialize: function(handler, options) {
        // concatenate events specific to this control with those from the base
        this.EVENT_TYPES =
            OpenLayers.Control.PIPESelect.prototype.EVENT_TYPES.concat(
            OpenLayers.Control.prototype.EVENT_TYPES
        );
        OpenLayers.Control.prototype.initialize.apply(this, [options]);

        this.callbacks = OpenLayers.Util.extend({done: this.select, 
            click: this.select}, this.callbacks);
        this.handlerOptions = this.handlerOptions || {};
        if (this.sketchStyle) {
            this.handlerOptions.layerOptions = OpenLayers.Util.applyDefaults(
                this.handlerOptions.layerOptions,
                {styleMap: new OpenLayers.StyleMap({"default": this.sketchStyle})}
            );
        }
        this.handler = new handler(this, this.callbacks, this.handlerOptions);
    },

    destroy: function() {
        OpenLayers.Control.prototype.destroy.apply(this, arguments);
    },

	select: function(geometry) {
		
            //se c'è un popup aperto non faccio nulla
            for(var i=0; i<this.map.popups.length; i++){
                if(this.map.popups[i].id == 'pipeselect-popup'){
                   alert('Chiudere tutte le finestre popup prima di effettuare una nuova ricerca valvole');
                   return;
                }
             }
            
            this.events.triggerEvent("beforeSelect", {

            });

		//var ctrl = this.map.getControlsByClass("OpenLayers.Control.LoadingPanel")[0];	
		//ctrl.maximizeControl();
		
		if (!(geometry instanceof OpenLayers.Geometry)) {
                    var resultLayer = this.getResultLayer();
                    if (resultLayer)
                        if (resultLayer.features.length > 0)
                            if (!confirm('Effettuare una nuova ricerca valvole?'))
                                return;
                    var point = this.map.getLonLatFromPixel(geometry.xy);
                    geometry = new OpenLayers.Geometry.Point(point.lon, point.lat);		
                    this.point = geometry;
        }

		//CHIAMATA AL SERVER PER AVERE DATO IL PUNTO LA TRATTA DEL GRAFO E L'ELENCO DEGLI OGGETTI INTERESSATI 
		if (true) {
				
				//Verifico se devo escludere qualche valvola
				//console.log(thisDv)
				//console.log(Ext.select("name=" + dv.id +" input"))
		
		
                    var options = {
                        url: this.serviceURL,
                        params: {
                            layer:this.pipelayer,
							distance:this.distance,
							srs:this.map.projection,
                            x:geometry.x,
                            y:geometry.y,
							exclude:this.exclude,
                            request: "tratta"
                        },
                        success: this.loadResult,
                        failure:function(){

                        	alert('Tempo di esecuzione scaduto, controllare il grafo')

                        },
                        scope: this
                    };
                    OpenLayers.Request.GET(options);
        }
		//this.deactivate();

    },
	
	
	
	loadResult:function(request){
	
		var formatJson = new OpenLayers.Format.JSON();
		var formatGeoJson = new OpenLayers.Format.GeoJSON();
		doc = request.responseText;

		if(!doc){
			this.events.triggerEvent("afterSelect",{msg:'Nessun oggetto trovato'});
			return;
		} 
		var result = formatJson.read(doc);
		var extent = result.features_extent;
    	this.resultFeatures = result;
    
		var resultLayer = this.getResultLayer();
		resultLayer.removeAllFeatures();
		
		for (key in result) {
			if(result[key].features) resultLayer.addFeatures(formatGeoJson.read(result[key].features));
		}

		
		this.map.zoomToExtent(new OpenLayers.Bounds.fromArray(extent));

		var self = this;

		if(typeof(Ext)!='undefined'){
			Ext.select('div.myTool').on('click', function() {
				self.exclude.push(this.id);
				self.select(self.point);
				
			});
		}
		else if(typeof($)!='undefined'){
			$('div.myTool').on('click', function() {
				self.exclude.push(this.id);
				self.select(self.point);
				
			});
		}

		if(this.active){
			this.highlightCtrl.activate();
			this.selectControl.activate();	
		}
		this.events.triggerEvent("afterSelect");

	},
	
	
	
	getResultLayer:function(){

		var resultLayer = this.map.getLayer('gc_pipe_vector_layer');
		
		if(!resultLayer){
		
			var valvoleStyle = new OpenLayers.Style();

			var rule_ok = new OpenLayers.Rule({
			  filter: new OpenLayers.Filter.Comparison({
				  type: OpenLayers.Filter.Comparison.EQUAL_TO,
				  property: "escluso",
				  value: 0
			  }),
			  symbolizer: {
								fill: false,
								fillColor: "#ff00ff",
								//fillOpacity: 0.4, 
								hoverFillColor: "white",
								hoverFillOpacity: 0.8,
								strokeColor: "#00FF00",
								strokeOpacity: 1,
								strokeWidth: 4,
								strokeLinecap: "round",
								strokeDashstyle: "solid",
								hoverStrokeColor: "red",
								hoverStrokeOpacity: 1,
								hoverStrokeWidth: 0.2,
								pointRadius: 6,
								hoverPointRadius: 1,
								hoverPointUnit: "%",
								pointerEvents: "visiblePainted",
								cursor: "inherit"
							}
			});

			var rule_escluso = new OpenLayers.Rule({
			  filter: new OpenLayers.Filter.Comparison({
				  type: OpenLayers.Filter.Comparison.EQUAL_TO,
				  property: "escluso",
				  value: 1
			  }),
			  symbolizer: {
					fill: false,
					fillColor: "#ff0000",
					//fillOpacity: 0.4, 
					hoverFillColor: "white",
					hoverFillOpacity: 0.8,
					strokeColor: "#ff0000",
					strokeOpacity: 1,
					strokeWidth: 4,
					strokeLinecap: "round",
					strokeDashstyle: "solid",
					hoverStrokeColor: "red",
					hoverStrokeOpacity: 1,
					hoverStrokeWidth: 0.2,
					pointRadius: 8,
					hoverPointRadius: 1,
					hoverPointUnit: "%",
					pointerEvents: "visiblePainted",
					cursor: "inherit"
				}
			});

			valvoleStyle.addRules([rule_ok, rule_escluso]);
			
			var styleMap = new OpenLayers.StyleMap({
				'default' : valvoleStyle,
				//'select' : valvoleStyle,
				//'temporary': valvoleStyle,
				'defaultvv': {
					fill: false,
					fillColor: "#ff00ff",
					//fillOpacity: 0.4, 
					hoverFillColor: "white",
					hoverFillOpacity: 0.8,
					strokeColor: "#00FF00",
					strokeOpacity: 1,
					strokeWidth: 4,
					strokeLinecap: "round",
					strokeDashstyle: "solid",
					hoverStrokeColor: "red",
					hoverStrokeOpacity: 1,
					hoverStrokeWidth: 0.2,
					pointRadius: 6,
					hoverPointRadius: 1,
					hoverPointUnit: "%",
					pointerEvents: "visiblePainted",
					cursor: "inherit"
				},
				'select': {
					fill: true,
					fillColor: "yellow",
					fillOpacity: 0.4, 
					hoverFillColor: "white",
					hoverFillOpacity: 0.8,
					strokeColor: "yellow",
					strokeOpacity: 1,
					strokeWidth: 4,
					strokeLinecap: "round",
					strokeDashstyle: "solid",
					hoverStrokeColor: "red",
					hoverStrokeOpacity: 1,
					hoverStrokeWidth: 0.2,
					pointRadius: 6,
					hoverPointRadius: 1,
					hoverPointUnit: "%",
					pointerEvents: "visiblePainted",
					cursor: "pointer"
				},
				'temporary': {
					fill: true,
					fillColor: "00ffff",
					fillOpacity: 0.2, 
					hoverFillColor: "white",
					hoverFillOpacity: 0.8,
					strokeColor: "#00ffff",
					strokeOpacity: 1,
					strokeLinecap: "round",
					strokeWidth: 4,
					strokeDashstyle: "solid",
					hoverStrokeColor: "red",
					hoverStrokeOpacity: 1,
					hoverStrokeWidth: 0.2,
					pointRadius: 6,
					hoverPointRadius: 1,
					hoverPointUnit: "%",
					pointerEvents: "visiblePainted",
					cursor: "pointer"
				}
			});
	
			resultLayer = new OpenLayers.Layer.Vector('Ricerca valvole' ,{
                styleMap: styleMap
			});
			resultLayer.id = 'gc_pipe_vector_layer';
			this.map.addLayer(resultLayer);
			
			var highlightCtrl = new OpenLayers.Control.SelectFeature(
				resultLayer,
				{
					hover: true,
					highlightOnly: true,
					renderIntent: "temporary",
					eventListeners: {
						//beforefeaturehighlighted: this.removePopup,
						featurehighlighted: this.addPopup,
						featureunhighlighted: this.removePopup
					},
					scope: this
				}
			);		
			var ctrl=this;
			var selectControl = new OpenLayers.Control.SelectFeature(
				resultLayer,
				{
					onSelect:function(feature){
					//SULLA SELEZIONE DISATTIVO TUTTO (??)
						if(feature.fid.indexOf('genova.ratraccia_v')!=-1) return false
						ctrl.selectedPipeObject = feature;
						highlightCtrl.deactivate();
						selectControl.deactivate();
						//ctrl.deactivate();
					}
				}
			);

			this.map.addControl(highlightCtrl);	
			this.map.addControl(selectControl);	

			this.events.register('activate',this,function(e){
				highlightCtrl.activate();		
				selectControl.activate();
			});
			this.events.register('deactivate',this,function(e){
                            highlightCtrl.deactivate();			
                            selectControl.deactivate();
                            if(this.clearOnDeactivate) {
                                var resultLayer = this.map.getLayer('gc_pipe_vector_layer');
                                if (resultLayer)
                                    resultLayer.removeAllFeatures();
                            }
			});
			
			this.selectControl = selectControl;
			this.highlightCtrl = highlightCtrl;		
		}
		
		return resultLayer;
			
	},
	
	addPopup: function (e) {
		var feature=e.feature;	
        var v = e.feature.fid.split('.');
		var attributes = e.feature.attributes;
		var id = v[1];
		var featureType = this.scope.resultFeatures[v[0]]["featureType"];
		var popupInfo = "<div><h3><u>"+ featureType.title +"</u></h3></div><br>";
		var property;
		for (key in featureType.properties) {
			property = featureType.properties[key];
			popupInfo += "<b>" + property.fieldHeader + ":</b>&nbsp;" + attributes[property.name] + "<br>";
		}

		if(v[0]!='condotta'){
			if(attributes["escluso"]==1)
				popupInfo += '<div style="margin-top:10px;text-align:center;color:white;background-color:grey;border-style:solid;border-color:white;">ESCLUSO</div>';
			else
				popupInfo += '<div id="' + id + '" class="myTool" style="margin-top:10px;text-align:center;color:white;background-color:grey;border-style:solid;border-color:white;">==> Escludi <==</div>';

		}

		var oPopupPos, leftOffset = 45, topOffset = 35, rightOffset=50;
		var oMapExtent = this.map.getExtent();
		var nReso = this.map.getResolution();
		var nMapXCenter = this.map.getExtent().getCenterPixel().x;
		var nFeatureXPos = feature.geometry.getBounds().getCenterPixel().x;
		var bLeft = nFeatureXPos >= nMapXCenter;

		if(bLeft){ // popup appears top-left position
			oPopupPos = new OpenLayers.LonLat(oMapExtent.left,oMapExtent.top);
			oPopupPos.lon += leftOffset * nReso;
		} else { // popup appears top-right position
			oPopupPos = new OpenLayers.LonLat(oMapExtent.right,oMapExtent.top);
			oPopupPos.lon -= rightOffset * nReso;
		}
		oPopupPos.lat -= topOffset * nReso;

		var popup = new OpenLayers.Popup.Anchored(
			"pipeselect-popup", 
			oPopupPos,
			new OpenLayers.Size(200,400),
			popupInfo,
			null, true);
		popup.setBackgroundColor("#bcd2ee");
		popup.setOpacity(.9);	
		feature.popup = popup;
		var self=this.scope;
		popup.onclick = function(){return false};

		if(self.popup)	this.map.removePopup(self.popup);
		this.map.addPopup(popup);
		self.popup = popup;

		popup.addCloseBox(function(e){
			//Event.stop(e)
			if(self.popup)	this.map.removePopup(self.popup);
			self.popup.destroy();
			self.popup = null;
			self.selectControl.unselect(self.selectedPipeObject);
			self.selectedPipeObject = null;
			//self.activate();
                        self.highlightCtrl.activate();		
                        self.selectControl.activate();
		});

		if(typeof(Ext)!='undefined'){
			Ext.select('div.myTool').on('click', function() {
				self.exclude.push(this.id);
				self.map.removePopup(popup);
                                self.select(self.point);
			});
		}
		else if(typeof($)!='undefined'){
			$('div.myTool').on('click', function() {
				self.exclude.push(this.id);
				self.map.removePopup(popup);
                                self.select(self.point);
			});		
		}

		
	},

	removePopup: function (e) {
		var feature=e.feature;
		if(!feature) return;
		if(feature.popup) this.map.removePopup(feature.popup);
		feature.popup.destroy();
		feature.popup = null;

	},

    CLASS_NAME: "OpenLayers.Control.PIPESelect"

});

function addPipeSelection(mapPanel){
	var selectPipeAction = new GeoExt.Action({
		control: new OpenLayers.Control.PIPESelect(
			OpenLayers.Handler.Click,
			{
				clearOnDeactivate:false,
				serviceURL:'../../services/iren/findPipes.php',
				pipelayer: 'RATRACCIA.traccia_table',
				distance:50,
				highLight: true
			}
		),
		tooltip: 'Seleziona condotta',
		map: mapPanel.map,
		mapPanel:mapPanel,
		text:'Ricerca Valvole',
		iconCls: 'play',
		toggleGroup: 'mapToolbar'
	});
	
	var tbar = mapPanel.getTopToolbar();
	tbar.add( " ","-"," ",selectPipeAction);
	
	selectPipeAction.control.events.register('beforeSelect',mapPanel,function(e){
		this.fireEvent('loading',{start:true,title:'Ricerca valvole',width:200,msg:'Ricerca informazioni in corso....'})
	});
	selectPipeAction.control.events.register('afterSelect',mapPanel,function(e){
		this.fireEvent('loading',{end:true})
		if(e.msg) Ext.MessageBox.alert('Info', e.msg);
	});
}


