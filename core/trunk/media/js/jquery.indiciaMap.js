/**
* Re-implementation of the map helper as a purely client-side library, because the mixed method in use was messy and
* going to be difficult to maintain.
* This code file supports read only maps. A separate plugin will then run on top of this to provide editing support
* and can be used in a chainable way. Likewise, another plugin will provide support for finding places.
*/

(function($)
{
  $.extend({indiciaMap : new function()
  {
    // Quite a lot of options here
    this.defaults = 
    {
      indiciaGeoSvc : "http://localhost:8080/geoserver",
	     height: "600px",
	     width: "800px",
	     initial_lat: 6700000,
	     initial_long: -100000,
	     initial_zoom: 7,
	     proxy: "http://localhost/cgi-bin/proxy.cgi?url=",
	     displayFormat: "image/png",
	     presetLayers: ['google_physical', 'google_streets', 'google_hybrid', 'google_satellite', 'openlayers_wms', 'virtual_earth'],
	     openLayersOptions: 
	     {
	       projection: new OpenLayers.Projection("EPSG:900913"),
	     displayProjection: new OpenLayers.Projection("EPSG:4326"),
	     units: "m",
	     numZoomLevels: 18,
	     maxResolution: 156543.0339,
	     maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
	     },
	    layers: [],
	    controls: []
    };
    
    // Potential layers to add to the map
    this.presetLayers =
    {
      google_physical : function() { return new OpenLayers.Layer.Google('Google Physical', {type: G_PHYSICAL_MAP, 'sphericalMercator': 'true'})},
	     google_streets : function() { return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true})},
	     google_hybrid : function() { return new OpenLayers.Layer.Google('Google Hybrid', {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true})},
	     google_satellite : function() { return new OpenLayers.Layer.Google('Google Satellite', {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true})},
	     openlayers_wms : function() { return new OpenLayers.Layer.WMS('OpenLayers WMS', 'http://labs.metacarta.com/wms/vmap0', {layers: 'basic', 'sphericalMercator': true})},
	     nasa_mosaic : function() { return new OpenLayers.Layer.WMS('NASA Global Mosaic', 'http://t1.hypercube.telascience.org/cgi-bin/landsat7', {layers: 'landsat7', 'sphericalMercator': true})},
	     virtual_earth : function() { return new OpenLayers.Layer.VirtualEarth('Virtual Earth', {'type': VEMapStyle.Aerial, 'sphericalMercator': true})},
	     multimap_default : function() { return new OpenLayers.Layer.MultiMap('MultiMap', {sphericalMercator: true})},
	     multimap_landranger : function() { return new OpenLayers.Layer.MultiMap('OS Landranger', {sphericalMercator: true, dataSource: 904})}
    };
    
    this.construct = function(options)
    {
      var settings = {};
      // Deep extend
      $.extend(true, settings, $.indiciaMap.defaults, options);
      return this.each(function()
      {
	this.settings = settings;
	
	// Sizes the div
	$(this).css('height', this.settings.height).css('width', this.settings.width);
	
	// If we're using a proxy
	if (var proxy = this.settings.proxy)
	{
	  OpenLayers.ProxyHost = proxy;
	}
	
	// Constructs the map
	var map = new OpenLayers.Map($(this).attr('id'), this.settings['openLayersOptions']);
	
	// Iterate over the preset layers, adding them to the map
	$.each(this.settings['presetLayers'], function(i, item)
	{
	  // Check whether this is a defined layer
	  if ($.indiciaMap.presetLayers.hasOwnProperty(item))
	  {
	    var layer = $.indiciaMap.presetLayers[item]();
	    map.addLayers([layer]);
	  }
	});
	$.each(this.settings['layers'], function(i, item)
	{
	  map.addLayers([item]);
	});
	$.each(this.settings['controls'], function(i, item)
	{
	  map.addControl(item);
	});
	if ((this.settings.presetLayers.length + this.settings.layers.length) > 1)
	{
	  map.addControl(new OpenLayers.Control.LayerSwitcher());
	}
	// Centre the map
	map.setCenter(new OpenLayers.LonLat(this.settings['initial_long'],this.settings['initial_lat']),this.settings['initial_zoom']);
	this.map = map;
      });
    };
  }
  });
  
  $.fn.extend({
    indiciaMap : $.indiciaMap.construct
  });
})(jQuery);