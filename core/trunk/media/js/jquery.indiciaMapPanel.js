/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

/**
* Class: indiciaMapPanel
* JavaScript & OpenLayers based map implementation class for Indicia data entry forms.
* This code file supports read only maps. A separate plugin will then run on top of this to provide editing support
* and can be used in a chainable way. Likewise, another plugin will provide support for finding places.
*/


(function($) {
  $.fn.indiciaMapPanel = function(options, olOptions) {
    // Extend our default options with those provided, basing this on an empty object
    // so the defaults don't get changed.
    var opts = $.extend({}, $.fn.indiciaMapPanel.defaults, options);
    var olOpts = $.extend({}, $.fn.indiciaMapPanel.openLayersDefaults, olOptions);

    return this.each(function() {
      this.settings = opts;

      // Sizes the div
      $(this).css('height', this.settings.height).css('width', this.settings.width);

      // If we're using a proxy
      if (this.settings.proxy)
      {
        OpenLayers.ProxyHost = this.settings.proxy;
      }
      
      // Keep a reference to this, to simplify scoping issues.
      var div = this;

      // Constructs the map
      div.map = new OpenLayers.Map($(this)[0], olOpts);
      
      // Iterate over the preset layers, adding them to the map
      $.each(this.settings.presetLayers, function(i, item)
      {
        // Check whether this is a defined layer
        if ($.fn.indiciaMapPanel.presetLayers.hasOwnProperty(item))
        {
          var layer = $.fn.indiciaMapPanel.presetLayers[item]();
          div.map.addLayers([layer]);
          if (item=='multimap_landranger') {
        	  // Landranger is not just a simple layer - need to set a Multimap option
        	  _enableMMLandranger();
          }
        }
      });
      
      // Convert indicia WMS/WFS layers into js objects
      $.each(this.settings.indiciaWMSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WMS(key, div.settings.indiciaGeoSvc + '/wms', { layers: value, transparent: true }, { isBaseLayer: false, sphericalMercator: true}));
      });
      $.each(this.settings.indiciaWFSLayers, function(key, value)
      {
        div.settings.layers.push(new OpenLayers.Layer.WFS(key, div.settings.indiciaGeoSvc + '/wms', { typename: value, request: 'GetFeature' }, { sphericalMercator: true }));
      });

      div.map.addLayers(this.settings.layers); 
      
      // Centre the map      
      var center = new OpenLayers.LonLat(this.settings.initial_long, this.settings.initial_lat);      
      center.transform(div.map.displayProjection, div.map.projection);      
      div.map.setCenter(center, this.settings.initial_zoom);

      if (this.settings.editLayer) {
        // Add an editable layer to the map
        var editLayer = new OpenLayers.Layer.Vector(this.settings.editLayerName, {style: this.settings.boundaryStyle, 'sphericalMercator': true});
        div.map.editLayer = editLayer;
        div.map.addLayers([div.map.editLayer]);
        
        if (this.settings.initialFeatureWkt == null ) {
        	// if no initial feature specified, but there is a populated imp-geom hidden input, 
        	// use the value from the hidden geom        	
        	this.settings.initialFeatureWkt = $('#imp-geom').val();
        }

        // Draw the feature to be loaded on startup, if present
        if (this.settings.initialFeatureWkt)
        {
          _showWktFeature(this, this.settings.initialFeatureWkt);
        }
      }

      // Add any map controls
      $.each(this.settings.controls, function(i, item) {
        div.map.addControl(item);
      });

      // Add a layer switcher if there are multiple layers
      if ((this.settings.presetLayers.length + this.settings.layers.length) > 1) {
        div.map.addControl(new OpenLayers.Control.LayerSwitcher());
      }

      // Disable the scroll wheel from zooming if required
      if (!this.settings.scroll_wheel_zoom) {
        $.each(div.map.controls, function(i, control) {
          if (control instanceof OpenLayers.Control.Navigation) {
            control.disableZoomWheel();
          }
        });
      }      
      _bindControls(this);   
    });
    
    /**
     * Enable a multimap landranger preference.
     */
    function _enableMMLandranger() {
	  var landrangerData = 904;
	  var prefs = MMDataResolver.getDataPreferences(MM_WORLD_MAP);
	
	  // Remove the landranger data where it is present
	  for (i=0; i<prefs.length; i++) {
	    if (landrangerData == prefs[i]) prefs.splice(i, 1);
	  }
	
	  // Add to beginning of array (highest priority)
	  prefs.unshift(landrangerData);
 
      MMDataResolver.setDataPreferences( MM_WORLD_MAP, prefs );
    }

    /**
     * Add a well known text definition of a feature to the map.
     * @access private
     */
    function _showWktFeature(div, wkt) {
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
      // if showing a point, don't zoom in too far
      if (dy===0 && dx===0) {
        div.map.setCenter(bounds.getCenterLonLat(), 13);        
      } else {
        // Set the default view to show something triple the size of the grid square
        div.map.zoomToExtent(bounds);
      }
    }

    /**
     * Use jQuery selectors to locate any other related controls on the page which need to have events
     * bound to them to associate them with the map.
     */
    function _bindControls(div) {
      // Setup a click event handler for the map
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
          var lonlat = div.map.getLonLatFromViewPortPx(e.xy);
          // get approx metres accuracy we can expect from the mouse click - about 5mm accuracy.
          var precision, metres = div.map.getScale()/200;
          // now round to find appropriate square size
          if (metres<30) {
            precision=8;
          } else if (metres<300) {
            precision=6;
          } else if (metres<3000) {
            precision=4;
          } else {
            precision=2;
          }          
          // enforce precision limits if specifid in the settings
          if (div.settings.clickedSrefPrecisionMin!=='') {
        	precision=Math.max(div.settings.clickedSrefPrecisionMin, precision);
          }
          if (div.settings.clickedSrefPrecisionMax!=='') { 
          	precision=Math.min(div.settings.clickedSrefPrecisionMax, precision);
          }
          $.getJSON(opts.indiciaSvc + "/index.php/services/spatial/wkt_to_sref"+
            "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
            "&system=" + _getSystem() +
            "&precision=" + precision +
            "&callback=?", function(data)
            {
              $('#'+opts.srefId).val(data.sref);              
              div.map.editLayer.destroyFeatures();
              $('#'+opts.geomId).val(data.wkt);
              var parser = new OpenLayers.Format.WKT();
              var feature = parser.read(data.wkt);
              div.map.editLayer.addFeatures([feature]);              
            }
          );
        }
      });

      // Add the click control to the map.
      var click = new OpenLayers.Control.Click();
      div.map.addControl(click);
      click.activate();

      // If the spatial ref input control exists, bind it to the map, so entering a ref updates the map
      $('#'+opts.srefId).change(function() {
        $.getJSON(div.settings.indiciaSvc + "/index.php/services/spatial/sref_to_wkt"+
          "?sref=" + $(this).val() +
          "&system=" + _getSystem() +
          "&callback=?", function(data) {
            // store value in saved field?
            _showWktFeature(div, data.wkt);
            $('#'+opts.geomId).val(data.wkt);
          }
        );
      });

      // If a place search (georeference) control exists, bind it to the map.
      $('#'+opts.georefSearchId).keypress(function(e) {
        if (e.which==13) {
          _georeference(div);
          return false;
        }
      });

      $('#'+opts.georefSearchBtnId).click(function() {
        _georeference(div);
      });

      $('#'+opts.georefCloseBtnId).click(function()
      {
        $('#'+opts.georefDivId).hide('fast');
      });

      $('#imp-location').change(function()
      {
        // Change the location control requests the location's geometry to place on the map.
        $.getJSON(div.settings.indiciaSvc + "/index.php/services/data/location/"+this.value +
          "?mode=json&view=detail" + div.settings.readAuth + "&callback=?", function(data) {
            // store value in saved field?
            if (data.length>0) {
              _showWktFeature(div, data[0].centroid_geom);
            }
          }
        );
      });
    }

    function _georeference(div) {
      if (opts.geoPlanetApiKey.length==0) {
        alert('Incorrect configuration - Geoplanet API Key not specified.');
        throw('Incorrect configuration - Geoplanet API Key not specified.');
      }
      $('#' + opts.georefDivId).hide();
      $('#' + opts.georefOutputDivId).empty();
      var ref;
      var searchtext = $('#' + opts.georefSearchId).val();
      if (searchtext != '') {
        var request = 'http://where.yahooapis.com/v1/places.q("' +
        searchtext + ' ' + opts.georefPreferredArea + '", "' + opts.georefCountry + '");count=10';
        $.getJSON(request + "?format=json&lang="+opts.georefLang+"&appid="+opts.geoPlanetApiKey+"&callback=?", function(data){
          // an array to store the responses in the required country, because GeoPlanet will not limit to a country
          var found = { places: [], count: 0 };
          jQuery.each(data.places.place, function(i,place) {
            // Ingore places outside the chosen country, plus ignore places that were hit because they
            // are similar to the country name we are searching in.
            if (place.country.toUpperCase()==opts.georefCountry.toUpperCase()
                && (place.name.toUpperCase().indexOf(opts.georefCountry.toUpperCase())==-1
                || place.name.toUpperCase().indexOf(searchtext.toUpperCase())!=-1)) {
                found.places.push(place);
              found.count++;
            }
          });
          if (found.count==1 && found.places[0].name.toLowerCase()==searchtext.toLowerCase()) {
            ref=found.places[0].centroid.latitude + ', ' + found.places[0].centroid.longitude;
            _displayLocation(div, ref);
          } else if (found.count!=0) {
            $('<p>Select from the following places that were found matching your search, then click on the map to specify the exact location:</p>')
                    .appendTo('#'+opts.georefOutputDivId);
            var ol=$('<ol>');
            $.each(found.places, function(i,place){
              ref= place.centroid.latitude + ', ' + place.centroid.longitude;
              placename = place.name+' (' + place.placeTypeName + ')';
              if (place.admin1!='') placename = placename + ', '+place.admin1;
              if (place.admin2!='') placename = placename + '\\' + place.admin2;

              ol.append($("<li>").append($("<a href='#'>" + placename + "</a>").click((
                function(ref){return function() { _displayLocation(div, ref); } })(ref))
              ));
            });
            ol.appendTo('#'+opts.georefOutputDivId);
            $('#'+opts.georefDivId).show("fast");
          } else {
            $('<p>No locations found with that name. Try a nearby town name.</p>').appendTo(outputDivId);
            $('#'+opts.georefDivId).show("fast");
          }
        });
      }
    }

    /**
    * After georeferencing a place, display a point on the map representing that place.
    * @access private
    */
    function _displayLocation(div, ref)
    {
      $.getJSON(
        opts.indiciaSvc + "/index.php/services/spatial/sref_to_wkt" + "?sref=" + ref + "&system=4326" + "&callback=?", function(data) {
          _showWktFeature(div, data.wkt);
        }
      );
    }

    /**
     * Return the system, by loading from the system control. If not present, revert to the default.
     */
    function _getSystem() {
      var selector=$('#'+opts.srefSystemId);
      if (selector.length===0) {
        return opts.defaultSystem;
      }
      else {
        return selector.val();
      }
    }


  };

})(jQuery);

/**
 * Main default options for the map
 */
$.fn.indiciaMapPanel.defaults = {
    indiciaSvc : "http://localhost/indicia",
    indiciaGeoSvc : "http://localhost:8080/geoserver",
    readAuth : '',
    height: "470px",
    width: "600px",
    initial_lat: 55.1,
    initial_long: -2,
    initial_zoom: 5,
    scroll_wheel_zoom: true,
    proxy: "http://localhost/cgi-bin/proxy.cgi?url=",
    displayFormat: "image/png",
    presetLayers: [],
    indiciaWMSLayers: {},
    indiciaWFSLayers : {},
    layers: [],
    controls: [],
    editLayer: true,
    editLayerName: 'Selection layer',
    initialFeatureWkt: null,
    defaultSystem: 'OSGB',
    georefPreferredArea : 'gb',
    georefCountry : 'United Kingdom',
    georefLang : 'en-EN',
    geoPlanetApiKey: '',
    srefId: 'imp-sref',
    srefSystemId: 'imp-sref-system',
    geomId: 'imp-geom',
    georefSearchId: 'imp-georef-search',
    georefSearchBtnId: 'imp-georef-search-btn',
    georefCloseBtnId: 'imp-georef-close-btn',
    georefOutputDivId: 'imp-georef-output-div',
    georefDivId: 'imp-georef-div',
    clickedSrefPrecisionMin: '', // depends on sref system, but for OSGB this would be 2,4,6,8,10 etc = length of grid reference
    clickedSrefPrecisionMax: ''
    /* Intention is to also implement hoveredSrefPrecisionMin and Max for a square size shown when you hover, and also a 
     * displayedSrefPrecisionMin and Mx for a square size output into a list box as you hover. Both of these could either be
     * absolute numbers, or a number preceded by - or + to be relative to the default square size for this zoom level. */
};

/**
 * Default options to pass to the openlayers map constructor
 */
$.fn.indiciaMapPanel.openLayersDefaults = {
    projection: new OpenLayers.Projection("EPSG:900913"),
    displayProjection: new OpenLayers.Projection("EPSG:4326"),
    units: "m",
    numZoomLevels: 18,
    maxResolution: 156543.0339,
    maxExtent: new OpenLayers.Bounds(-20037508, -20037508, 20037508, 20037508.34)
};

/**
 * Some pre-configured layers that can be added to the map.
 */
$.fn.indiciaMapPanel.presetLayers = {
    google_physical : function() { return new OpenLayers.Layer.Google('Google Physical', {type: G_PHYSICAL_MAP, 'sphericalMercator': 'true'}); },
    google_streets : function() { return new OpenLayers.Layer.Google('Google Streets', {numZoomLevels : 20, 'sphericalMercator': true}); },
    google_hybrid : function() { return new OpenLayers.Layer.Google('Google Hybrid', {type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true}); },
    google_satellite : function() { return new OpenLayers.Layer.Google('Google Satellite', {type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true}); },
    openlayers_wms : function() { return new OpenLayers.Layer.WMS('OpenLayers WMS', 'http://labs.metacarta.com/wms/vmap0', {layers: 'basic', 'sphericalMercator': true}); },
    nasa_mosaic : function() { return new OpenLayers.Layer.WMS('NASA Global Mosaic', 'http://t1.hypercube.telascience.org/cgi-bin/landsat7', {layers: 'landsat7', 'sphericalMercator': true}); },
    virtual_earth : function() { return new OpenLayers.Layer.VirtualEarth('Virtual Earth', {'type': VEMapStyle.Aerial, 'sphericalMercator': true}); },
    multimap_default : function() { return new OpenLayers.Layer.MultiMap('MultiMap', {sphericalMercator: true}); },
    multimap_landranger : function() { return new OpenLayers.Layer.MultiMap('Multimap OS Landranger', {sphericalMercator: true}); }
};