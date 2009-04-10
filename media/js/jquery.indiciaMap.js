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
      indiciaSvc : "http://localhost/indicia",
      indiciaGeoSvc : "http://localhost:8080/geoserver",
      height: "450px",
      width: "600px",
      initial_lat: 7450000,
      initial_long: -410000,
      initial_zoom: 5,
      scroll_wheel_zoom: true,
      proxy: "http://localhost/cgi-bin/proxy.cgi?url=",
      displayFormat: "image/png",
      presetLayers: [],
      indiciaWMSLayers: {},
      indiciaWFSLayers : {},
      layers: [],
      controls: []
    };

    // Options to pass to the openlayers map constructor
    this.openLayersDefaults =
    {
      projection: new OpenLayers.Projection("EPSG:900913"),
               displayProjection: new OpenLayers.Projection("EPSG:4326"),
      units: "m",
      numZoomLevels: 18,
      maxResolution: 156543.0339,
      maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
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

    this.construct = function(options, oloptions)
    {
      var settings = {};
      var openLayersOptions = {};
      // Deep extend
      $.extend(settings, $.indiciaMap.defaults, options);
      $.extend(openLayersOptions, $.indiciaMap.openLayersDefaults, oloptions);

      return this.each(function()
      {
  this.settings = settings;

  // Sizes the div
  $(this).css('height', this.settings.height).css('width', this.settings.width);

  // If we're using a proxy
  if (this.settings.proxy)
  {
    OpenLayers.ProxyHost = this.settings.proxy;
  }

  // Constructs the map
  var map = new OpenLayers.Map($(this)[0], openLayersOptions);

  // Iterate over the preset layers, adding them to the map
  $.each(this.settings.presetLayers, function(i, item)
  {
    // Check whether this is a defined layer
    if ($.indiciaMap.presetLayers.hasOwnProperty(item))
    {
      var layer = $.indiciaMap.presetLayers[item]();
      map.addLayers([layer]);
    }
  });

  var div = this;
  // Convert indicia WMS/WFS layers into js objects
  $.each(this.settings.indiciaWMSLayers, function(key, value)
  {
  alert(div.settings.indiciaGeoSvc + '/wms');
  alert(value);
    div.settings.layers.push(new OpenLayers.Layer.WMS(key, div.settings.indiciaGeoSvc + '/wms', { layers: value, transparent: true }, { isBaseLayer: false, sphericalMercator: true}));
  });
  $.each(this.settings.indiciaWFSLayers, function(key, value)
  {
    div.settings.layers.push(new OpenLayers.Layer.WFS(key, div.settings.indiciaGeoSvc + '/wms', { typename: value, request: 'GetFeature' }, { sphericalMercator: true }));
  });

  map.addLayers(this.settings.layers);

  $.each(this.settings.controls, function(i, item)
  {
    map.addControl(item);
  });
  if ((this.settings.presetLayers.length + this.settings.layers.length) > 1)
  {
    map.addControl(new OpenLayers.Control.LayerSwitcher());
  }
  // Centre the map
  map.setCenter(new OpenLayers.LonLat(this.settings['initial_long'],this.settings['initial_lat']),this.settings['initial_zoom']);

  // Disable the scroll wheel from zooming if required
  if (!this.settings.scroll_wheel_zoom) {
    $.each(map.controls, function(i, control) {
      if (control instanceof OpenLayers.Control.Navigation) {
        control.disableZoomWheel();
      }
    });
  }
  this.map = map;
      });
    };
  }
  });

  $.fn.extend({
    indiciaMap : $.indiciaMap.construct
  });
})(jQuery);