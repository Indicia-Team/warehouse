/*
* Editable plugin for jQuery.indiciaMap.
* @requires jquery
* @requires jquery.indiciamap
*
*/

/**
* Extends the jQuery.indiciaMap plugin to provide support from editing within the map.
*/

(function($)
{
  $.extend({indiciaMapEdit : new function()
  {
    this.defaults = 
    {
      wkt : null,
	    layerName : "Current location boundary",
	    input_field_name : 'entered_sref',
	    geom_field_name : 'geom',
	    systems_field_name : 'entered_sref_systems',
	    systems : {4326 : "Lat/Long on the WGS84 Datum", OSGB : "Ordnance Survey British National Grid"},
	    placeControls : true,
	    controlPosition : 0,
	    boundaryStyle: new OpenLayers.Util.applyDefaults({ strokeWidth: 1, strokeColor: "#ff0000", fillOpacity: 0.3, fillColor:"#ff0000" }, OpenLayers.Feature.Vector.style['default']) 
    };
    
    this.construct = function(options)
    {
      var settings = {};
      $.extend(true, settings, $.indiciaMapEdit.defaults, $.indiciaMap.defaults);
      return this.each(function()
      {
	this.settings = settings;
	
	// Add an editable layer to the map
	var editLayer = new OpenLayers.Layer.Vector(this.settings.layerName, {style: this.settings.boundaryStyle, 'sphericalMercator': true});
	this.map.editLayer = editLayer;
	this.map.addLayers([this.map.editLayer]);
	
	if (this.settings.wkt != null)
	{
	  showWktFeature(this, this.settings.wkt);
	}
	
	if (this.settings.placeControls)
	{
	  placeControls(this);
	}
	
	registerControls(this);
	
      });
    };
    
   this.showWktFeature = function(div, wkt) {
      var editlayer = div.map.editLayer;
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read(wkt);
      var bounds=feature.geometry.getBounds();
      
      editlayer.destroyFeatures();
      editlayer.addFeatures([feature]);
      // extend the boundary to include a buffer, so the map does not zoom too tight.
      var dy = (bounds.top-bounds.bottom)/1.5;
      var dx = (bounds.right-bounds.left)/1.5;
      bounds.top = bounds.top + dy;
      bounds.bottom = bounds.bottom - dy;
      bounds.right = bounds.right + dx;
      bounds.left = bounds.left - dx;
      // Set the default view to show something triple the size of the grid square
      div.map.zoomToExtent(bounds);
      // if showing a point, don't zoom in too far
      if (dy==0 && dx==0) {
	div.map.zoomTo(11);
      }
    }
    
    // Private functions
    
    /**
    * Adds controls into the div in the specified position.
    */
    function placeControls(div)
    {
      var pos = div.settings.controlPosition;
      var systems = div.settings.systems;
      
      var html = "<span>";
      html += "<label for='"+div.settings.input_field_name+"'>Spatial Reference:</label>";
      html += "<input type='text' id='" + div.settings.input_field_name + "' name='" + div.settings.input_field_name + "' />\n";
      if (systems.length == 1)
      {
	// Hidden field for the system
	html += "<input type='hidden' id='" + div.settings.systems_field_name + "' name='" + div.settings.systems_field_name + "'/>\n";
      }
      else
      {
	html += "<label for='"+div.settings.systems_field_name+"'>Spatial Reference System:</label>";
	html += "<select id='" + div.settings.systems_field_name + "' name='" + div.settings.systems_field_name + "' >\n";
	$.each(systems, function(key, val) { html += "<option value='" + key + "'>" + val + "</option>\n" });
	html += "</select>\n";
      }
      html += "</span>";
      $(div).before(html);
    }
    
    /**
    * Registers controls with the map - binds functions to them and places correct data in if wkt has been supplied.
    */
    function registerControls(div)
    {
      var inputFld = '#' + div.settings.input_field_name;
      var geomFld = '#' + div.settings.geom_field_name;
      var systemsFld = '#' +div.settings.systems_field_name;
      var map = div.map;
      
      OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
	defaultHandlerOptions: { 'single': true, 'double': false, 'pixelTolerance': 0, 'stopSingle': false, 'stopDouble': false },
	  initialize: function(options) 
	  { 
	    this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
	    OpenLayers.Control.prototype.initialize.apply(this, arguments);
	    this.handler = new OpenLayers.Handler.Click( this, {'click': this.trigger}, this.handlerOptions );
	  },
	  trigger: function(e) 
	  {
	    var lonlat = map.getLonLatFromViewPortPx(e.xy);
	    // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
	    var precision = map.getScale()/200;
	    // now round to find appropriate square size
	    if (precision<30) {
	      precision=8;
	    } else if (precision<300) {
	      precision=6;
	    } else if (precision<3000) {
	      precision=4;
	    } else {
	      precision=2;
	    }
	    $.getJSON(div.settings.indiciaSvc + "/index.php/services/spatial/wkt_to_sref"+
	    "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
	    "&system=" + $(systemsFld).val() +
	    "&precision=" + precision +
	    "&callback=?", function(data)
	    {
	      $(inputFld).attr('value', data.sref);
	      map.editLayer.destroyFeatures();
	      $(geomFld).attr('value', data.wkt);
	      var parser = new OpenLayers.Format.WKT();
	      var feature = parser.read(data.wkt);
	      map.editLayer.addFeatures([feature]);
	    }
	    );
	  }
      });
      
      // Add the click control to the map.
      var click = new OpenLayers.Control.Click();
      map.addControl(click);
      click.activate();
      
      // Bind functions to the input field.
      $(inputFld).change(function()
      {
	$.getJSON(div.settings.indiciaSvc + "/index.php/services/spatial/sref_to_wkt"+
	"?sref=" + $(this).val() +
	"&system=" + $(systemsFld).val() +
	"&callback=?", function(data){
	  $(geomFld).attr('value', data.wkt);
	  showWktFeature(div, data.wkt);
	}
	);
      });
      
    }
  }
  });
  
  $.fn.extend({ indiciaMapEdit : $.indiciaMapEdit.construct });
})(jQuery);
