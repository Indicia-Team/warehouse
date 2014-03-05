
jQuery(document).ready(function($) {
  //When the user clicks on the map we need to draw the map square. Initialise the map and then add a trigger to it to allow the user
  //to click on the map and then we automatically draw the plot square.
  mapInitialisationHooks.push(function (div) {
    indiciaData.mapdiv = div;
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {

      defaultHandlerOptions: {
        'single': true,
        'double': false,
        'pixelTolerance': 0,
        'stopSingle': false,
        'stopDouble': false
      },

      initialize: function(options) {
        this.handlerOptions = OpenLayers.Util.extend(
          {}, this.defaultHandlerOptions
        );
        OpenLayers.Control.prototype.initialize.apply(
          this, arguments
        );
        this.handler = new OpenLayers.Handler.Click(
          this, {
            'click': this.trigger
          }, this.handlerOptions
        );
      },

      trigger: function(e) {    
        if ($('#location\\:location_type_id').val()) {
          //When map is clicked on, then remove previous plots.
          var mapLayers = indiciaData.mapdiv.map.layers;
          for(var a = 0; a < mapLayers.length; a++ ){
            if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
              mapLayers[a].removeAllFeatures();
            }
          };
          $('#imp-boundary-geom').val('');
          //Only calculate a plot square/triangle for non-linear/vertical plots
          if ($('#location\\:location_type_id option:selected').text()!=='Vertical'&& $('#location\\:location_type_id option:selected').text()!=='Linear') {
            var attributes = {name: 'plot_map'};

            var polygon=square_calculator(e.xy);
            //Save the plot square to a hidden field for saving in the database
            $('#imp-boundary-geom').val(polygon);
            if (indiciaData.mapdiv.map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
              polygon.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
            }
            var feature = new OpenLayers.Feature.Vector(polygon, attributes);
            feature.geometry=polygon;
            plotSquareLayer.addFeatures([feature]);

            var bounds = new OpenLayers.Bounds();
            bounds = feature.geometry.getBounds();
            zoom = indiciaData.mapdiv.map.getZoomForExtent(bounds);
            indiciaData.mapdiv.map.setCenter(bounds.getCenterLonLat(), zoom); 
          }
        } else {
          alert('Please select a plot type before selecting the plot location.');
        }
      }
    });

    plotSquareLayer = new OpenLayers.Layer.Vector('Plot Square Layer');
    indiciaData.mapdiv.map.addLayer(plotSquareLayer); 
    var click = new OpenLayers.Control.Click();
    indiciaData.mapdiv.map.addControl(click);
    //This code is a workaround for an issue where the Plot's square draws correctly but
    //the grid reference click point appears in the wrong place when my custom code to automatically 
    //zoom into the Plot Square is present.
    //The click point would display in the same pixel position as it was clicked before the zoom occurred.
    //In the code below we force the custom trigger click.activate() to be activated before the standard
    //olControlClickSref control as items which are activated last are peformed first, this forces
    //the standard click code to run before my custom code which performs the zoom. The click point then works with the zoom.
    $.each(indiciaData.mapdiv.map.controls, function(idx, ctrl) {
      if (ctrl.displayClass==='olControlClickSref') {
        ctrl.deactivate();
      }
    });
    click.activate();
    $.each(indiciaData.mapdiv.map.controls, function(idx, ctrl) {
      if (ctrl.displayClass==='olControlClickSref') {
        ctrl.activate();
      }
    });
  });
  
  //This is the code that creates the plot square/rectangle. It is called by the trigger when the user clicks on the map.
  //Firstly get the initial south-west point in the various grid reference formats (4326=lat long, 27700 = British National Grid)
  function square_calculator(eventXY) {
    var squareSizes=indiciaData.squareSizes; 
    var widthLength = indiciaData.widthLength;
    var squareWidth;
    var squareLength;
    if (indiciaData.pssMode) {
      //In PSS mode, the user can change the length of the square's sizes to make it a rectangle
      squareWidth=$('#locAttr\\:'+ widthLength[0]).val();
      squareLength=$('#locAttr\\:'+ widthLength[1]).val();
    } else {
      squareWidth=squareSizes[$('#location\\:location_type_id').val()][0];
      squareLength=squareSizes[$('#location\\:location_type_id').val()][1];
    }
    var xy3857 = indiciaData.mapdiv.map.getLonLatFromPixel(eventXY),
    pt3857 = new OpenLayers.Geometry.Point(xy3857.lon, xy3857.lat),
    InitialClickPoint4326 = pt3857.clone().transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('epsg:4326')),
    InitialClickPoint27700 = pt3857.clone().transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('epsg:27700'));

    //Get an arbitrary point north of the original long, lat position. In our case this is 1 degree north but the amount doesn't really matter. Then convert to British National Grid
    northTestPointLatLon = InitialClickPoint4326.clone();
    northTestPointLatLon.y = northTestPointLatLon.y+1;
    northTestPoint27700 = northTestPointLatLon.clone().transform('epsg:4326', new OpenLayers.Projection('epsg:27700'));

    //Get a point the is at right angles to the original point and the arbitrary point north.
    //We can do this by taking the british national grid x value of the south point and combining it with the 
    //the y value of the north point. This will then create a right-angle triangle as the British National Grid is at an angle
    //compared to long lat.
    northRightAnglePoint27700 = northTestPoint27700.clone();
    northRightAnglePoint27700.x = InitialClickPoint27700.x;

    //We then work out the side lengths and angle of the right-angled triangle
    var opposite = northTestPoint27700.x - northRightAnglePoint27700.x;
    var adj = northRightAnglePoint27700.y - InitialClickPoint27700.y;
    var gridAngle = Math.atan(opposite/adj);
    //The hypotenuse is the distance north along the longitude line to our test point but in British National Grid 27700 metres.
    var hyp = adj/Math.cos(gridAngle);

    //As we now know the length in metres between the south point and our arbitrary north point (the hypotenuse), 
    //we can now use the percent value to work out the Y distance in Lat Long 4326 format for the corner of the square above the original click point.
    //This is because we know the distance in 4326 degrees, but now we also know the percentage the square length is along the line.
    var hypmetrePercent = squareLength/hyp;
    var actualSquareNorthWestPoint4326= InitialClickPoint4326.clone();
    actualSquareNorthWestPoint4326.y = InitialClickPoint4326.y+((northTestPointLatLon.y-InitialClickPoint4326.y)*hypmetrePercent);

    //Next we need to use the same technique along the side of the square. We just need to use X values rather than Y values.
    eastTestPointLatLon = InitialClickPoint4326.clone();
    eastTestPointLatLon.x = eastTestPointLatLon.x+1;
    eastTestPoint27700 = eastTestPointLatLon.clone().transform('epsg:4326', new OpenLayers.Projection('epsg:27700'));

    eastRightAnglePoint27700 = eastTestPoint27700.clone();
    eastRightAnglePoint27700.y = InitialClickPoint27700.y;

    var opposite =  eastRightAnglePoint27700.y-eastTestPoint27700.y;
    var adj = eastRightAnglePoint27700.x - InitialClickPoint27700.x;
    var gridAngle = Math.atan(opposite/adj);
    //The hypotenuse is the distance north along the latitude line to our east test point but in British National Grid 27700 metres.
    var hyp = adj/Math.cos(gridAngle);

    var hypmetrePercent = squareWidth/hyp;

    var actualSquareSouthEastPoint4326= InitialClickPoint4326.clone();
    actualSquareSouthEastPoint4326.x = InitialClickPoint4326.x+((eastTestPointLatLon.x-InitialClickPoint4326.x)*hypmetrePercent);

    //As we know 3 of the plot corners, we can work out the 4th and then convert the plot square back into a form the map can understand   
    actualSquareNorthEastPoint4326 = actualSquareSouthEastPoint4326.clone();
    actualSquareNorthEastPoint4326.y = actualSquareNorthWestPoint4326.y;
    //On the PSS site, the grid reference of the sqaure/rectangle needs to be in the middle.
    //Just shift the corners of the square/rectangle west and south by half a side of the rectangle/square.
    if (indiciaData.pssMode) {
      var westShift = (actualSquareSouthEastPoint4326.x - InitialClickPoint4326.x)/2;
      var southShift = (actualSquareNorthWestPoint4326.y - InitialClickPoint4326.y)/2;

      InitialClickPoint4326.x = InitialClickPoint4326.x - westShift;
      InitialClickPoint4326.y = InitialClickPoint4326.y - southShift;

      actualSquareNorthWestPoint4326.x = actualSquareNorthWestPoint4326.x - westShift;
      actualSquareNorthWestPoint4326.y = actualSquareNorthWestPoint4326.y - southShift;

      actualSquareSouthEastPoint4326.x = actualSquareSouthEastPoint4326.x - westShift;
      actualSquareSouthEastPoint4326.y = actualSquareSouthEastPoint4326.y - southShift;

      actualSquareNorthEastPoint4326.x = actualSquareNorthEastPoint4326.x - westShift;
      actualSquareNorthEastPoint4326.y = actualSquareNorthEastPoint4326.y - southShift;
    }

    mercOriginal = OpenLayers.Layer.SphericalMercator.forwardMercator(InitialClickPoint4326.x,InitialClickPoint4326.y);
    mercNorth = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareNorthWestPoint4326.x,actualSquareNorthWestPoint4326.y);
    mercEast = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareSouthEastPoint4326.x,actualSquareSouthEastPoint4326.y);  
    mercNorthEast = OpenLayers.Layer.SphericalMercator.forwardMercator(actualSquareNorthEastPoint4326.x,actualSquareNorthEastPoint4326.y);

    var polygonMetadata = 'POLYGON(('+mercOriginal.lon+' '+mercOriginal.lat+','+mercNorth.lon+' '+mercNorth.lat+','+mercNorthEast.lon+' '+mercNorthEast.lat+','+mercEast.lon+' '+mercEast.lat+'))';
    var polygon=OpenLayers.Geometry.fromWKT(polygonMetadata);
    return polygon;
  }
});

//When the user is drawing a linear or vertical plot on the PSS site, then if they change the location type to be a square/rectangle
//or to "Please Select" then we need to automatically the hide line drawing tool and select the sref click tool
function remove_line_tool() {
  $('.olControlDrawFeaturePathItemActive').addClass('olControlDrawFeaturePathItemInactive');
  $('.olControlDrawFeaturePathItemInactive').removeClass('olControlDrawFeaturePathItemActive');
  $('.olControlDrawFeaturePathItemInactive').hide();
  $('.olControlNavigationItemActive').addClass('olControlNavigationItemInactive');
  $('.olControlNavigationItemInactive').removeClass('olControlNavigationItemActive');
  $('.olControlNavigationItemInactive').hide();
  $('.olControlClickSrefItemInactive').addClass('olControlClickSrefItemActive');
  $('.olControlClickSrefItemActive').removeClass('olControlClickSrefItemInactive');
}
