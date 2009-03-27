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
	    systems : {4326 : "Lat/Long on th WGS84 Datum", OSGB : "Ordnance Survey British National Grid"},
	    placeControls : true,
	    controlPosition : 0,
	    boundaryStyle: new OpenLayers.Util.applyDefaults({ strokeWidth: 1, strokeColor: "#ff0000", fillOpacity: 0.3,
							       fillColor:"#ff0000" },
							       OpenLayers.Feature.Vector.style['default']) 
    };
    
    this.construct = function(options)
    {
      var settings = {};
      $.extend(true, settings, $.indiciaMap.defaults, $.indiciaMapEdit.defaults);
      return this.each(function()
      {
	this.settings = settings;
	
	// Add an editable layer to the map
	var editLayer = new OpenLayers.Layer.Vector(this.settings.layerName, {style: this.settings.boundaryStyle, 'sphericalMercator': true});
	this.map.editLayer = editLayer;
	this.map.addLayers([this.map.editLayer]);
	
	if (this.settings.wkt != null)
	{
	  showWktFeature(this);
	}
	
	if (this.settings.placeControls)
	{
	  placeControls(this);
	}
	
      });
    };
    
    // Private functions
    
    /**
    * Adds controls into the div in the specified position.
    */
    function placeControls(div)
    {
      var pos = div.settings.controlPosition;
      var systems = div.settings.systems;
      
      var html = "<span>";
      html += "<input type='text' id='" + div.settings.input_field_id + "' name='" + div.settings.input_field_id + "' />\n";
      if (systems.length == 1)
      {
	// Hidden field for the system
	html += "<input type='hidden' id='" + div.settings.systems_field_id + "' name='" + div.settings.systems_field_id + "'/>\n";
      }
      else
      {
	html += "<select id='" + div.settings.systems_field_id + "' name='" + div.settings.systems_field_id + "' value='" + "' />\n";
	$.each(systems, function(key, val) { html += "<option value='" + key + "'>" + val + "</option>\n" });
	html += "</select>\n";
      }
      html += "</span>";
      $(div).prepend(html);
    }
    
    function showWktFeature(div) {
      var editlayer = div.map.editLayer;
      var wkt = div.settings.wkt;
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
  }
  });
  
  $.fn.extend({ indiciaMapEdit : $.indiciaMapEdit.construct });
})(jQuery);