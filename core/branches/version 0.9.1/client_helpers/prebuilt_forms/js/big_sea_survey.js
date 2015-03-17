jQuery(document).ready(function($) {
  var currentPageChanged = false, origGeom,
    toggle='start',  // clicking to set start or end?
    proj4326 = new OpenLayers.Projection('EPSG:4326')
    
  $('#gps'+toggle).css('border','solid red 1px');
  
  $('.select-transect').click(function(e) {
    // prevent losing changes by navigating. Geom handled separately as it doesn't trigger a change on an input when you modify the feature.
    if (currentPageChanged || origGeom!==$('#imp-geom').val()) {
      if (!confirm("You have made changes to the current transect? Are you sure you want to navigate away without saving them? Click Cancel to avoid losing your changes.")) {
        e.preventDefault();
        return false;
      }
    }
  });
  
  $('#entry_form :input').change(function(e) {
    // imp-sref gets changed on page load. So let's ignore that change.
    if (e.currentTarget.id!=='imp-sref') {
      currentPageChanged = true;
    }
  });
  
  mapInitialisationHooks.push(function(div) {
    var feature=div.map.editLayer.features[0],
      /**
       * Fill in the start and end transect boxes with the initial state.
       */
      copyFeatureComponentToSrefBox=function(id, feature, component) {
        var ptWGS84 = feature.geometry.components[component].clone().transform(div.map.projection, proj4326);
        precision = (typeof indiciaData.latLongNotationPrecision==="undefined") ?
            3 : indiciaData.latLongNotationPrecision;
        var SN = ptWGS84.y > 0 ? 'N' : 'S', EW = ptWGS84.x > 0 ? 'E' : 'W';
        $('#'+id).val(Math.abs(ptWGS84.y).toFixed(precision) + SN + ', ' + Math.abs(ptWGS84.x).toFixed(precision) + EW);
      };
    // if feature is a polygon, then it must just be the default loaded from the parent sample. So convert to a line.
    if (feature.geometry.CLASS_NAME==='OpenLayers.Geometry.Polygon') {
      var points = new Array(
        feature.geometry.getCentroid(),
        feature.geometry.getCentroid()
      );
      // make the default transect show as a line of sorts...
      points[0].x -= 5;
      points[1].x += 5;
      feature.destroy();
      feature = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(points));
      div.map.editLayer.addFeatures([feature]);
      div.map.zoomToExtent(div.map.editLayer);
      $('#imp-geom').val(feature.geometry.toString());
      $('#imp-sref-system').val('4326');
    } 
    else {
      copyFeatureComponentToSrefBox('gpsstart', feature, 0);
      copyFeatureComponentToSrefBox('gpsend', feature, 1);
    }
    origGeom = $('#imp-geom').val(),
    // clarify the transect line
    feature.attributes.type='boundary';
    if (typeof feature.style !== "undefined") {
      feature.style.strokeWidth=3;
      feature.style.strokeDashstyle='dash';
      feature.style.strokeDashstyle='dash';
      feature.style.strokeColorBoundary='#FF0000';
      div.map.editLayer.redraw();
    }    
    
    OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
      defaultHandlerOptions: {'single': true, 'double': false, 'pixelTolerance': 0, 'stopSingle': false, 'stopDouble': false},
      title: div.settings.hintClickSpatialRefTool,
      trigger: function(e) {
        var llMap=div.map.getLonLatFromPixel(e.xy), llWGS84 = llMap.clone().transform(div.map.projection, proj4326),
          notation=indiciaData.srefHandlers['4326'].pointToGridNotation({"x":llWGS84.lon,"y":llWGS84.lat}),
          geom=new OpenLayers.Geometry.Point(llMap.lon,llMap.lat);
          var component = (toggle==='start' ? 0 : 1);
        $('#gps'+toggle).val(notation);
        $('#gps'+toggle).css('border','');
        div.map.editLayer.features[0].geometry.components[component] = geom;
        div.map.editLayer.drawFeature(div.map.editLayer.features[0]);
        $('#imp-geom').val(feature.geometry.toString());
        // store the start in the sref field
        if (toggle==='start') {
          $('#imp-sref').val(notation);
        }
        $('#imp-sref-system').val('4326');
        // switch to other transect end
        toggle = (toggle==='start' ? 'end' : 'start');
        $('#gps'+toggle).css('border','solid red 1px');
      },
      initialize: function(options)
      {
        this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
        OpenLayers.Control.prototype.initialize.apply(this, arguments);
        this.handler = new OpenLayers.Handler.Click( this, {'click': this.trigger}, this.handlerOptions );
      }
    });
    var click = new OpenLayers.Control.Click({"displayClass":"olControlClickSref"});
    div.map.addControl(click);
    click.activate();
  });
  
  var version = $().jquery;
  var aryVersion = version.split('.');
  if (aryVersion[0] == 1 && aryVersion[1] < 6 ) {
    $('#gpsstart').attr('readonly', true);
    $('#gpsend').attr('readonly', true);
  } else {
    $('#gpsstart').prop('readonly', true);
    $('#gpsend').prop('readonly', true);
  } 
});