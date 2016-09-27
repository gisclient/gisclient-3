//Client GISCLIENT per plomino gestione stralci di mappa
(function ($) {
  "use strict";

var MAPSET = "mappa_stralci";
var GISCLIENT_URL = "http://testgrg.gisclient.net/gisclient/";
var MAPPROXY_URL = "http://testgrg.gisclient.net/";
OpenLayers.ImgPath = GISCLIENT_URL + "services/iren/ireti/openlayers/img/";

//########################

var GisClientMap;
var mycontrol,ismousedown;

$(function() {

  var comuneExtent='';

  var initDialog = function(_, container){
    var elencoVie = [];
    var elencoCivici = [];

  	function matchStart (term, text) {
  	  if (text.toUpperCase().indexOf(term.toUpperCase()) == 0) {
  		return true;
  	  }
  	  return false;
  	}

    //Combo con l'elenco dei comuni
    $('select[name="comune"]').select2({
      allowClear: true,
      placeholder: '---',
      matcher: matchStart
    }).on("change", function(e) { 
      $.ajax({
        'url':"resources/elencoVie",
        'type':'GET',
        'data':{"comune":$(this).val()},
        'dataType':'JSON',
        'success':function(data, textStatus, jqXHR){
          elencoVie = data.results;
          $('input[name="via"]').select2('data', elencoVie);
          $('input[name="via"]').select2('val', null);
          $('input[name="civico"]').select2('val', null);
          //$("#civico_geometry").val('');
          comuneExtent = data.extent;
        }
      });
    });


    $('input[name="via"]').select2({
          placeholder: '---',
          allowClear: true,
          minimumInputLength: 2,
          width:'off',      
          query: function (query){
            var data = {results: []};
            var re = RegExp(query.term, 'i');
            $.each(elencoVie, function(){
              if (re.test(this.text)){
                data.results.push({id: this.text, text: this.text, coords: this.coord, idvia:this.id});
              }
            });
            query.callback(data);
        },
        //PER INSERIRE U N VALORE NON IN ELENCO (COMBO EDITABILE)
        createSearchChoice:function(term, data) {
          if ($(data).filter(function() {return this.text.localeCompare(term)===0;}).length===0) {
            return {id:term, text:term};
          } 
        },
        initSelection : function (element, callback) {
          var data ={id: element.val(), text: element.val(), coords:'' } ;
          callback(data);
        }
    }).on("change", function(e){
      //$("#civico_nomevia").val(e.added.text);
      if(!e.added) return;
      $.ajax({
        'url':"resources/elencoCivici",
        'type':'GET',
        'data':{"via":e.added.idvia},
        'dataType':'JSON',
        'success':function(data, textStatus, jqXHR){
          elencoCivici = data.results;
          $('input[name="civico"]').select2('data', elencoCivici);
          $('input[name="civico"]').select2('val', null);
          //$("#civico_geometry").val('');
          if(e.added.coords){
            var v = e.added.coords.split(";");
            var p1 = v[0].split(",");
            var p2 = v[1].split(",");
            var x = parseFloat(p1[0]) + (parseFloat(p2[0]) - parseFloat(p1[0]))/2;
            var y = parseFloat(p1[1]) + (parseFloat(p2[1]) - parseFloat(p1[1]))/2;
            if (x && y){
              $('input[name="coordx"]').val(Math.round(x));
              $('input[name="coordy"]').val(Math.round(y));
              $('input[name="x_civico"]').val(Math.round(x));
              $('input[name="y_civico"]').val(Math.round(y));
            }
          }
        }
      });
    });
    
    $('input[name="civico"]').select2({
          placeholder: '---',
          allowClear: true,
          minimumInputLength: 0,
          width:'off',      
          query: function (query){
            var data = {results: []};
            var re = RegExp('^' + query.term, 'i');
            $.each(elencoCivici, function(){
              if (re.test(this.text)){
                data.results.push({id: this.text, text: this.text, x: this.x, y: this.y});
              }
            });
            query.callback(data);
        },
        //PER INSERIRE U N VALORE NON IN ELENCO (COMBO EDITABILE)
        createSearchChoice:function(term, data) {
          if ($(data).filter(function() {return this.text.localeCompare(term)===0;}).length===0) {
            return {id:term, text:term};
          } 
        },
        initSelection : function (element, callback) {
          var data ={id: element.val(), text: element.val(), x:'', y:'' } ;
          callback(data);
        }
    }).on("change", function(e){
        if(!(e.added && e.added.x && e.added.y)) return;
        $('input[name="coordx"]').val(Math.round(e.added.x));
        $('input[name="coordy"]').val(Math.round(e.added.y));
        $('input[name="x_civico"]').val(Math.round(e.added.x));
        $('input[name="y_civico"]').val(Math.round(e.added.y));
    });

    //INVIO DOMANDA
    $('#btn_invia_richiesta').attr('disabled','disabled');
    $('input.accettazione').bind('change',function(){
      var send = true;
      $.each($('input.accettazione'),function(k,v){
        send = send && $(this).is(':checked');
      });

      if (send){
        $('#btn_invia_richiesta').removeAttr('disabled');
      }
      else
        $('#btn_invia_richiesta').attr('disabled','disabled');
    });

 
  } //initDialog
  

  //GisClient initMap
  var initMap = function(){
    var map=this.map;
    //SE HO SETTATO LA NAVIGAZIONE VELOCE????
    if(this.mapsetTiles){
        for(i=0;i<map.layers.length;i++){
            if(!map.layers[i].isBaseLayer && map.layers[i].visibility){
                map.layers[i].setVisibility(false);
                this.activeLayers.push(map.layers[i]);
            }
        }
        
        $(".dataLayersDiv").hide();
        this.mapsetTileLayer.setVisibility(true);
    }

    var editMode = $('input[name="coordx"]').length;
    if(editMode==0){
      $('#center-button').hide();
      map.getControlsByClass("OpenLayers.Control.Navigation")[0].disableZoomWheel();
    } 

    var styleBox = new OpenLayers.StyleMap({
            'select': {
                fill: true,
                fillColor: "#ff00FF",
                fillOpacity: 0.1,
                strokeColor: "yellow",
                strokeOpacity: 0.4,
                strokeWidth: 4,
                strokeLinecap: "round",
                pointRadius: 6,
                hoverPointRadius: 1,
                hoverPointUnit: "%",
                pointerEvents: "visiblePainted",
                cursor: "inherit"
            },
            'default': {
                fill: true,
                fillColor: "#ff00FF",
                fillOpacity: 0.2,
                strokeColor: "red",
                strokeOpacity: 1,
                strokeWidth: 4,
                strokeLinecap: "round",
                strokeDashstyle: "solid",
                hoverStrokeColor: "red",
                hoverStrokeOpacity: 1,
                hoverStrokeWidth: 0.4,
                pointRadius: 8,
                hoverPointRadius: 1,
                hoverPointUnit: "%",
                pointerEvents: "visiblePainted",
                cursor: "pointer"
            }
        });

    var updateCoords = true;
    
    //nuovo plugin per la stampa
    var btnPrint = new OpenLayers.Control.PrintMap({
        tbarpos:"first", 
        //type: OpenLayers.Control.TYPE_TOGGLE, 
        formId: 'printpanel',
        exclusiveGroup: 'sidebar',
        iconclass:"glyphicon-white glyphicon-print", 
        title:"Pannello di stampa",
        maxScale:1000,
        editMode: editMode,
        styleBox: styleBox,
        serviceUrl:GISCLIENT_URL + 'services/print.php',
        eventListeners: {
            updatebox: function(e){
                var bounds = this.printBox.geometry.getBounds();
                var center = bounds.getCenterLonLat().clone().transform("EPSG:3857","EPSG:3003");
                $('input[name="scale"]').val(Math.round(this.printBoxScale));
                $('input[name="boxw"]').val(Math.round(bounds.getWidth()));
                $('input[name="boxh"]').val(Math.round(bounds.getHeight()));
                if(updateCoords){
                  $('input[name="coordx"]').val(Math.round(center.lon));
                  $('input[name="coordy"]').val(Math.round(center.lat));
                }
                updateCoords = true;

            }

        }


    });
    
    $('select[name="page_layout"]').change(function() {
        btnPrint.pageLayout = $(this).val();
        updateCoords = false;
        btnPrint.updatePrintBox(false);
    });
    $('select[name="page_format"]').change(function() {
        btnPrint.pageFormat = $(this).val();
        updateCoords = false;
        btnPrint.updatePrintBox(false);
    });
    $('select[name="page_legend"]').change(function() {
        btnPrint.pageLegend = $(this).val();
    });
    $('#printpanel').on('click', 'button[role="print"]', function(event) {
        event.preventDefault();
        btnPrint.doPrint();
    });


    $('input[name="scale"]').spinner({
      step: 100,
      min: 100,
      max: 1000,
      numberFormat: "n",
      change: function( event, ui ) {
        btnPrint.printBoxScale = $(this).val();
        btnPrint.updatePrintBox();
      },
      spin: function( event, ui ) {
        btnPrint.printBoxScale = ui.value;
        btnPrint.updatePrintBox();
      }
    });

    $('#center-button').on('click',function(){
        //SE NON C'È VIA CENTRO SU EXTENT DEL COMUNE
        if($('input[name="via"]').select2('val') == ''){
          map.zoomToExtent(OpenLayers.Bounds.fromString(comuneExtent));
          return;
          //console.log(coords);
          //var ll = new OpenLayers.LonLat(coord[0],coord[1]).transform("EPSG:3003","EPSG:3857");
          //var ur = new OpenLayers.LonLat(coord[2],coord[3]).transform("EPSG:3003","EPSG:3857");


        }
        else{
          var x = Math.round(parseFloat($('input[name="x_civico"]').attr('value')));
          var y = Math.round(parseFloat($('input[name="y_civico"]').attr('value')));
          if(x && y) {
            var position = new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857");
            btnPrint.movePrintBox(new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857"));
            map.zoomToExtent(btnPrint.getBounds(),true);
            //map.setCenter(position,22,false,false);
          }
        }
    })

    btnPrint.pageLayout = $('[name="page_layout"]').attr('value');
    btnPrint.pageLegend = $('[name="page_legend"]').attr('value');
    btnPrint.pageFormat = $('[name="page_format"]').attr('value');

    //ricarico i dati salvati
    var x = Math.round(parseFloat($('[name="coordx"]').attr('value')));
    var y = Math.round(parseFloat($('[name="coordy"]').attr('value')));
    btnPrint.printBoxScale = Math.round(parseFloat($('[name="scale"]').attr('value')));

    //Attivazione del controllo e creazione printBox
    map.addControl(btnPrint);
    btnPrint.events.register('activate', btnPrint, function(e){
        if(x && y){   
            this.centerBox = new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857");
            this.movePrintBox(new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857"));
            map.zoomToExtent(this.getBounds(),true);
        }
    });
    if(x && y && btnPrint.printBox){   
        btnPrint.centerBox = new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857");
        btnPrint.movePrintBox(new OpenLayers.LonLat(x,y).transform("EPSG:3003","EPSG:3857"));
        map.zoomToExtent(btnPrint.getBounds(),true);
    }
    

    //VISUALIZZAZIONE DELLE COORDINATE
    var projection = this.mapOptions.displayProjection || this.mapOptions.projection;
    var v = projection.split(":");
    map.addControl(new OpenLayers.Control.MousePosition({
        element:document.getElementById("map-coordinates"),
        prefix: '<a target="_blank" ' + 'href="http://spatialreference.org/ref/epsg/' + v[1] + '/">' + projection + '</a> coordinate: '
    }));

    if(editMode){

      map.events.register("moveend", map, function(){
        if($('[name="opt_ricentra"]').attr("checked")){
          var center = map.getCenter();
          btnPrint.movePrintBox(center);
          center = center.transform("EPSG:3857","EPSG:3003");
          var bounds = btnPrint.printBox.geometry.getBounds();
          $('input[name="coordx"]').val(Math.round(center.lon));
          $('input[name="coordy"]').val(Math.round(center.lat));
          $('input[name="boxw"]').val(Math.round(bounds.getWidth()));
          $('input[name="boxh"]').val(Math.round(bounds.getHeight()));
        } 
      });
      
      if(btnPrint.printBox){
          var bounds = btnPrint.printBox.geometry.getBounds();
          var center = bounds.getCenterLonLat().clone().transform("EPSG:3857","EPSG:3003");
          $('input[name="coordx"]').val(Math.round(center.lon));
          $('input[name="coordy"]').val(Math.round(center.lat));
          $('input[name="boxw"]').val(Math.round(bounds.getWidth()));
          $('input[name="boxh"]').val(Math.round(bounds.getHeight()));
      }
    }


  }//END initMap

  initDialog();
  //richiesta in jsonp della configurazione gisclient
  $.ajax({
    url: GISCLIENT_URL + 'services/gcmap.php',
    dataType: "jsonp",
    data:{"mapset":MAPSET},
    jsonpCallback: "jsoncallback",
    async: false,
    success: function( response ) {
      //TODO gestire errori da server
      var googleCallback;
      if (response.mapProviders && response.mapProviders.length>0) {
          for (var i = 0, len = response.mapProviders.length; i < len; i++) {
              var script = document.createElement('script');
              script.type = "text/javascript";
              script.src = response.mapProviders[i];
              if(response.mapProviders[i].indexOf('google')>0){
                  script.src += "&callback=OpenLayers.GisClient.CallBack";
                  OpenLayers.GisClient.CallBack = createDelegate(initGC,response);
                  googleCallback=true;
              } 
              document.getElementsByTagName('head')[0].appendChild(script);
          }   
      }
      if(!googleCallback) initGC.apply(response);      
    }

  })

  function initGC(){

    //console.log(this)
    //In this c'è l'oggetto response
    Proj4js.defs["EPSG:3857"] = Proj4js.defs["GOOGLE"];
    if(this.projdefs){
      for (var key in this.projdefs){
        if(!Proj4js.defs[key]) Proj4js.defs[key] = this.projdefs[key];
      }
    }
    var options = {
      useMapproxy:true,
      mapProxyBaseUrl: MAPPROXY_URL,
      baseUrl: GISCLIENT_URL,
      mapOptions:{
        controls:[
            new OpenLayers.Control.Navigation(),
            new OpenLayers.Control.Attribution(),
            new OpenLayers.Control.LoadingPanel(),
            new OpenLayers.Control.PanZoomBar(),
            new OpenLayers.Control.ScaleLine()

            /*
            new OpenLayers.Control.TouchNavigation({
                dragPanOptions: {
                    enableKinetic: true
                }
            }),
            //new OpenLayers.Control.PinchZoom(),
*/

        ]
        //scale:2000,
        //center:[8.92811, 44.41320]
      },
      callback:initMap
    };
    OpenLayers.Util.extend(options,this);
    GisClientMap = new OpenLayers.GisClient(null,'map',options)

  }

  function createDelegate(handler, obj){
      obj = obj || this;
      return function() {
          handler.apply(obj, arguments);
      }
  }


});

})(jQuery);


