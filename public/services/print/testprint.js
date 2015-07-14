$(function() {

var initMap = function(){
    var map=this.map;
    var proj_3003 = new OpenLayers.Projection("EPSG:3003");
    var proj_3857 = new OpenLayers.Projection("EPSG:3857");
    var maxScale = 1000000000;
    var editMode = true;
    var formId = "printpanel";



    var btnPrint = new OpenLayers.Control.PrintMap({
      tbarpos:"first", 
      //type: OpenLayers.Control.TYPE_TOGGLE, 
      exclusiveGroup: 'sidebar',
      iconclass:"glyphicon-white glyphicon-print", 
      title:"Pannello di stampa",
      maxScale:maxScale,
      allowDrag: editMode,
      allowResize: editMode,
      allowRotation: $('input[name="opt_rotation"]').is(':checked'),
      autoCenter: $('[name="opt_center"]').is(':checked'),
      //styleBox: styleBox,
      serviceUrl:'/gisclient/services/print.php',
      eventListeners: {
          updateboxInfo: function(e){
              var bounds = this.printBox.geometry.getBounds();
              var center = bounds.getCenterLonLat().clone().transform(proj_3857, proj_3003);
              $('input[name="scale"]').val(Math.round(this.boxScale));
              $('input[name="coordx"]').val(Math.round(center.lon));
              $('input[name="coordy"]').val(Math.round(center.lat));
              $('input[name="boxw"]').val(Math.round(bounds.getWidth()));
              $('input[name="boxh"]').val(Math.round(bounds.getHeight()));
              $('input[name="boxr"]').val(Math.round(this.modifyControl.pageRotation));
              console.log(this)
          },
          printed: function (e){

            console.log(e)
            //$('a[role="pdf"],a[role="html"]').attr('href', '#');
            //$('span[role="icon"]').removeClass('glyphicon-white').addClass('glyphicon-disabled');
                
            if(e.format == 'HTML') {
                $('a[role="html"]').html("Apri stampa HTML")
                $('a[role="html"]').attr('href', e.file);
                $('a[role="html"] span[role="icon"]').removeClass('glyphicon-disabled').addClass('glyphicon-white');
            } else if(e.format == 'PDF') {
                $('a[role="pdf"]').html("Apri stampa PDF")
                $('a[role="pdf"]').attr('href', e.file);
                $('a[role="pdf"] span[role="icon"]').removeClass('glyphicon-disabled').addClass('glyphicon-white');
            }




          }

      }

    });
    
    $('#'+formId+' select[name="page_layout"]').change(function() {
        btnPrint.pageLayout = $(this).val();
        btnPrint.updatePrintBox();
    });
    $('#'+formId+' select[name="page_format"]').change(function() {
        btnPrint.pageFormat = $(this).val();
        btnPrint.updatePrintBox();
    });
    $('#'+formId+' input[name="print_format"]').change(function() {
        btnPrint.printFormat = $(this).val();
    });
    $('#'+formId+' select[name="page_legend"]').change(function() {
        btnPrint.pageLegend = $(this).val();
    });
    $('#'+formId+' select[name="print_resolution"]').change(function() {
        btnPrint.printResolution = $(this).val();
    });
    $('#'+formId+' input[name="opt_rotation"]').change(function() {
        btnPrint.allowRotation = $('#'+formId+' input[name="opt_rotation"]').is(':checked');
        btnPrint.updateMode();
    });
    $('#'+formId+' input[name="opt_center"]').change(function() {
        btnPrint.autoCenter = $('#'+formId+' input[name="opt_center"]').is(':checked');
    });
    $('#'+formId+' input[name="opt_northarrow"]').change(function() {
        btnPrint.northArrow = $('#'+formId+' input[name="opt_northarrow"]:checked').val();
    });


  //  printDate:null,
  //  printText:null,






    $('#'+formId+' input[name="scale"]').spinner({
      step: 100,
      min: 100,
      max: maxScale,
      numberFormat: "n",
      change: function( event, ui ) {
        btnPrint.boxScale = $(this).val();
        btnPrint.updatePrintBox();
      },
      spin: function( event, ui ) {
        btnPrint.boxScale = $(this).val();
        btnPrint.updatePrintBox();
      }
    });

    console.log(formId)
    $('#' + formId).on('click', 'button[role="print"]', function(event) {
        event.preventDefault();
        btnPrint.doPrint();
    });

    $('#'+formId).on('click', 'button[role="center"]', function(event) {
        var x = Math.round(parseFloat($('#'+formId+' input[name="coordx"]').val()));
        var y = Math.round(parseFloat($('#'+formId+' input[name="coordy"]').val()));
             console.log(x,y)

        if(x && y) {
          var position = new OpenLayers.LonLat(x,y).transform(proj_3003,proj_3857);
          btnPrint.movePrintBox(position);
          map.zoomToExtent(btnPrint.getBounds(),true);
          //map.setCenter(position,22,false,false);
        }
    });

    if($('#'+formId+' [name="page_layout"]').length)
      btnPrint.pageLayout = $('#'+formId+' [name="page_layout"]').val();
    if($('#'+formId+' [name="page_format"]').length)
      btnPrint.pageFormat = $('#'+formId+' [name="page_format"]').val();
    if($('#'+formId+' [name="page_legend"]').length)
      btnPrint.pageLegend = $('#'+formId+' [name="page_legend"]:checked').val()=='yes';
    if($('#'+formId+' [name="print_format"]').length)
      btnPrint.printFormat = $('#'+formId+' [name="print_format"]:checked').val();
    if($('#'+formId+' [name="print_resolution"]').length)
      btnPrint.printResolution = $('#'+formId+' [name="print_resolution"]').val();
    if($('#'+formId+' [name="opt_northarrow"]').length)
      btnPrint.northArrow = $('#'+formId+' [name="opt_northarrow"]:checked').val();



    //ricarico i dati salvati
    var x = Math.round(parseFloat($('#'+formId+' [name="coordx"]').attr('value')));
    var y = Math.round(parseFloat($('#'+formId+' [name="coordy"]').attr('value')));
    if($('#'+formId+' [name="scale"]').length>0)
      btnPrint.boxScale = Math.round(parseFloat($('#'+formId+' [name="scale"]').attr('value')));

    map.addControl(btnPrint);
    btnPrint.activate();
    if(x && y){
      btnPrint.movePrintBox(new OpenLayers.LonLat(x,y).transform(proj_3003, proj_3857));
      map.zoomToExtent(btnPrint.getBounds(),true);
    } 

    //VISUALIZZAZIONE DELLE COORDINATE
    var projection = this.mapOptions.displayProjection || this.mapOptions.projection;
    projection = new OpenLayers.Projection(projection);
    var v = projection.getCode().split(":");
    map.addControl(new OpenLayers.Control.MousePosition({
         element:document.getElementById("map-coordinates"),
         prefix: '<a target="_blank" ' + 'href="http://spatialreference.org/ref/epsg/' + v[1] + '/">' + projection + '</a> coordinate: '
     }));
    map.addControl(new OpenLayers.Control.LayerSwitcher())



}//END initMap

    var GisClientBaseUrl = "/gisclient/"
    OpenLayers.ImgPath = GisClientBaseUrl + "maps/resources/themes/openlayers/img/";

    $.ajax({
      url: GisClientBaseUrl + 'services/gcmap.php',
      dataType: "jsonp",
      data:{"mapset":"test"},
      jsonpCallback: "jsoncallback",
      async: false,
      success: function( response ) {
        //TODO gestire errori da server
        var googleCallback;
        if (response.mapProviders && response.mapProviders.length>0) {
            for (var i = 0, len = response.mapProviders.length; i < len; i++) {
                script = document.createElement('script');
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
      for (key in this.projdefs){
        if(!Proj4js.defs[key]) Proj4js.defs[key] = this.projdefs[key];
      }
    }
    var options = {
      useMapproxy:true,
      mapProxyBaseUrl:"/",
      baseUrl: GisClientBaseUrl,
      mapOptions:{
        controls:[
            new OpenLayers.Control.Navigation({zoomWheelEnabled:true}),
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




  function createDelegate(handler, obj)
  {
      obj = obj || this;
      return function() {
          handler.apply(obj, arguments);
      }
  }

});
