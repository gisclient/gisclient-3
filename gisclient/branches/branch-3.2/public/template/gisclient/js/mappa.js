//parametri
var mapsetName = 'base';
var mapsetURL = "http://siti.provincia.sp.it/gisclient/30/";

// Evita le mattonelle rosa
OpenLayers.IMAGE_RELOAD_ATTEMPTS = 3;
OpenLayers.Util.onImageLoadErrorColor = "transparent";

//Resize del div 
jQuery(function(){ 
    $(window).resize(function(){ 
        var h = $(document).height(); 
        var w = $(document).width(); 
        $("#container").css('height',h-50); 
		$("#container").css('width',w-25); 
    }); 
}); 

jQuery(function(){
	//Regola le azioni del mouse sui bottoni della toolbar
	$(".fg-button:not(.ui-state-disabled)")
	.hover(
		function(){ 
			$(this).addClass("ui-state-hover"); 
		},
		function(){ 
			$(this).removeClass("ui-state-hover"); 
		}
	)
	.mousedown(function(){
			$(this).parents('.fg-buttonset-single:first').find(".fg-button.ui-state-active").removeClass("ui-state-active");
			if( $(this).is('.ui-state-active.fg-button-toggleable, .fg-buttonset-multi .ui-state-active') ){ $(this).removeClass("ui-state-active"); }
			else { $(this).addClass("ui-state-active"); }	
	})
	.mouseup(function(){
		if(! $(this).is('.fg-button-toggleable, .fg-buttonset-single .fg-button,  .fg-buttonset-multi .fg-button') ){
			$(this).removeClass("ui-state-active");
		}
	});
});

function toggleControl(element) {
    for(key in toolbarControls) {
	var control = toolbarControls[key];
	//alert ($(element).is('.ui-state-active'));
	if(element.name == key && jQuery(element).is('.ui-state-active')) {
	    control.activate();
	} else {
	    control.deactivate();
	}
    }
	document.getElementById('misure').innerHTML=''; 
}

jQuery(document).ready(function() {
		
	$.getJSON(mapsetURL + "admin/initmap.php?jsoncallback=?" + '&' +location.search.substring(1), {
		mapset:mapsetName
	}, function(settings) {
		if(settings.error>0){
			alert("ERROR:" + settings.error);
			return;
		}
		var options = {
			controls:[],
			units: "m",
			projection: new OpenLayers.Projection(settings.projection),
			displayProjection :new OpenLayers.Projection("EPSG:4326"),
			maxExtent: new OpenLayers.Bounds.fromArray(settings.maxExtent),
			resolutions: settings.resolutions,
			fractionalZoom: settings.fractionalZoom,
			allOverlays:settings.allOverlays
		};
		
		OpenLayers.DOTS_PER_INCH = settings.dpi; 
		
		//Istanza dell'oggetto mappa
		var Map = new OpenLayers.Map('map', options);//TODO SETTARE DA PLUGIN
		bounds = new OpenLayers.Bounds.fromArray(settings.maxExtent);
		
		//Layer base vuoto per gestire le risoluzioni settate (soluzioni alternative????)
		var baseLayer = new OpenLayers.Layer.Image('Base vuota', mapsetURL +'images/pixel.png', bounds, new OpenLayers.Size(1,1),
			{isBaseLayer:true,resolutions:settings.resolutions,displayInLayerSwitcher:settings.allOverlays?0:1});
		Map.addLayer(baseLayer);

		
		//carico gli altri layers provvisti da author
		$.each(settings.theme, function(i,theme){
			//singolo strato
			if(theme.parameters){
				var newlayer,maxRes,minRes;
				var visibility=0,display=0;
				var layerList=new Array();
				$.each(theme.layers, function(j,layer){
					maxRes=Math.min(settings.resolutions[0],layer.maxResolution);
					minRes=Math.max(settings.resolutions[settings.resolutions.length-1],layer.minResolution);
					layerList.push(layer.name);
					if(layer.visibility) visibility=1;
					if(layer.displayInLayerSwitcher) display=1;
					
				});
				theme.parameters["layers"] = layerList.join(",");
				theme.options={'visibility':visibility,'displayInLayerSwitcher':display,'maxResolution':maxRes,'minResolution':minRes,'singleTile':theme.singleTile};
				if(settings.projparam) theme.parameters["projparam"]=settings.projparam;
				newlayer=new OpenLayers.Layer.WMS(theme.title,theme.url,theme.parameters,theme.options);
				if (newlayer) Map.addLayer(newlayer);
			}
			else{
				$.each(theme.layers, function(j,layer){
					var newlayer;
					if(layer.type==1){	
						if(settings.projparam) layer.parameters["projparam"]=settings.projparam;
						newlayer=new OpenLayers.Layer.WMS(layer.title,layer.url,layer.parameters,layer.options);
					}
					else if(layer.type==2){
						newlayer=new OpenLayers.Layer.Google(layer.title,{'type': eval(layer.maptype), 'sphericalMercator': true, minZoomLevel:layer.minZoomLevel,maxZoomLevel:layer.maxZoomLevel});
					}
					else if(layer.type==3){
						newlayer=new OpenLayers.Layer.VirtualEarth(layer.title,{'type': eval(layer.maptype), 'sphericalMercator': true, minZoomLevel:layer.minZoomLevel,maxZoomLevel:layer.maxZoomLevel});
					}
					else if(layer.type==4){
						newlayer=new OpenLayers.Layer.Yahoo(layer.title,{'type': eval(layer.maptype), 'sphericalMercator': true, minZoomLevel:layer.minZoomLevel,maxZoomLevel:layer.maxZoomLevel});
					}
					else if(layer.type==5){
						if(layer.options.type=='Mapnik') newlayer=new OpenLayers.Layer.OSM.Mapnik(layer.title);
						if(layer.options.type=='Osmarender') newlayer=new OpenLayers.Layer.OSM.Osmarender(layer.title);
						if(layer.options.type=='CycleMap') newlayer=new OpenLayers.Layer.OSM.CycleMap(layer.title);
					}
					if (newlayer) Map.addLayer(newlayer);
				});
			}
		});
	

		//Centra la mappa all'apertura
		if (!Map.getCenter()) {
			Map.zoomToExtent(bounds);
		}
		
		
		//Aggiungo i controlli
		Map.addControl(new OpenLayers.Control.LayerSwitcher());
		Map.addControl(new OpenLayers.Control.PanZoomBar());
		Map.addControl(new OpenLayers.Control.KeyboardDefaults());
		Map.addControl(new OpenLayers.Control.MousePosition({div:document.getElementById("coord")}));
		Map.addControl(new OpenLayers.Control.Scale('scala'));	
		Map.addControl(new OpenLayers.Control.Navigation({'zoomWheelEnabled': true}));		
		
		toolbarControls = {
	    	line: new OpenLayers.Control.Measure(OpenLayers.Handler.Path, {persist: true}),
	    	polygon: new OpenLayers.Control.Measure(OpenLayers.Handler.Polygon, {persist: true}),
	    	pan: new OpenLayers.Control.Pan({title:"Pan"}),
	    	zoomin: new OpenLayers.Control.ZoomBox({title:"Zoom in box", out: false}),
	    	zoomout: new OpenLayers.Control.ZoomBox({title:"Zoom out box", out: true})
	    };
    
	    for(var key in toolbarControls) {
			control = toolbarControls[key];
			control.events.on({
			    "measure": handleMeasurements,
			    "measurepartial": handleMeasurements
			});
			Map.addControl(control);
	    }

	
	    //Storico navigazione
	    var history = new OpenLayers.Control.NavigationHistory();
	    Map.addControl(history);
    
	    var btnPrev = new OpenLayers.Control.Panel({
	        div: document.getElementById("btnPrev"),
	        displayClass:"prev"
	    });
	    var btnNext = new OpenLayers.Control.Panel({
	        div: document.getElementById("btnNext"),
	        displayClass:"next"
	    });
    
	    Map.addControl(btnPrev);
	    btnPrev.addControls([history.previous]);
	    Map.addControl(btnNext);
	    btnNext.addControls([history.next]);


	    if(InitMap) InitMap(Map);


	});

	
	
	$(window).resize();

	
});

function handleMeasurements(event) {
    var geometry = event.geometry;
    var units = event.units;
    var order = event.order;
    var measure = event.measure;
    var element = document.getElementById('misure'); 
    var out = "";
    if(order == 1) {
	out += "Lunghezza " + measure.toFixed(3) + " " + units;
    } else {
	out += "Area " + measure.toFixed(3) + " " + units + "2";
    }
    element.innerHTML = out;
}

