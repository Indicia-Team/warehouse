/**
* Re-implementation of the map helper as a purely client-side library, because the mixed method in use was messy and
* going to be difficult to maintain.
* This code file supports read only maps. A separate plugin will then run on top of this to provide editing support
* and can be used in a chainable way. Likewise, another plugin will provide support for finding places.
*/

(function($)
{
  $.extend(indiciaMap : new function()
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
	    layers: [],
	    openLayersOptions: 
	    {
	      projection: new OpenLayers.Projection("EPSG:900913"),
	    displayProjection: new OpenLayers.Projection("EPSG:4326"),
	    units: "m",
	    numZoomLevels: 18,
	    maxResolution: 156543.0339,
	    maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
	    }
    }
    
    this.layers =
    {
      
  }
  );
}
)(jQuery);