/**
* Reimplementation of some of the methods in spatial-ref.js to move towards compliance with map_helper, and to allow code to
* be used more generically (i.e. we should not be tied down to specific control names.)
*/

var MapMethods = function(map, editLayer, options){
  this.map = map;
  this.editLayer = editLayer;
  this.defaults = {
    indicia_url : 'http://localhost/indicia/',
    input_field_id : 'entered_sref',
    geom_field_id : 'geom'
  };
  var settings = {};
  // Extend the settings with defaults and options.
  jQuery.extend(settings, this.defaults, options);
  this.settings = settings;
};

// Click function handler
MapMethods.prototype.mapClickHandler = function(){
  var indicia_url = this.settings.indicia_url;
  var map = this.map;
  var editLayer = this.editLayer;
  return OpenLayers.Class(OpenLayers.Control, {
    defaultHandlerOptions: {
      'single': true,
			   'double': false,
			   'pixelTolerance': 0,
			   'stopSingle': false,
			   'stopDouble': false
    },
			   
			   initialize: function(options) {
			     this.handlerOptions = OpenLayers.Util.extend({}, this.defaultHandlerOptions);
			     OpenLayers.Control.prototype.initialize.apply(this, arguments);
			     this.handler = new OpenLayers.Handler.Click(
			     this,
									 {'click': this.trigger},
									 this.handlerOptions
									 );
			   },
			   
			   trigger: function(e) {
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
			     jQuery.getJSON(indicia_url + "/index.php/services/spatial/wkt_to_sref"+
			     "?wkt=POINT(" + lonlat.lon + "  " + lonlat.lat + ")"+
			     "&system=" + document.getElementById(input_field_name + "_system").value +
			     "&precision=" + precision +
			     "&callback=?",
								   function(data){
								     jQuery("#"+input_field_name).attr('value', data.sref);
								     editlayer.destroyFeatures();
								     jQuery("#"+geom_field_name).attr('value', data.wkt);
								     var parser = new OpenLayers.Format.WKT();
								     var feature = parser.read(data.wkt);
								     editlayer.addFeatures([feature]);
								   }
								   );
			   }
  });
};

MapMethods.prototype.exit_sref = function(){
  var map = this.map;
  var indicia_url = this.settings.indicia_url;
  var input_field_name = this.settings.input_field_name;
  var geom_field_name = this.settings.input_field_name;
  var show_wkt_feature = this.show_wkt_feature();
  return new function() {
    if (map.current_sref!=document.getElementById(input_field_name).value) {
      // Send an AJAX request for the wkt to draw on the map
      jQuery.getJSON(indicia_url + "/index.php/services/spatial/sref_to_wkt"+
      "?sref=" + document.getElementById(input_field_name).value +
      "&system=" + document.getElementById(input_field_name + "_system").value +
      "&callback=?",
		      function(data){
			jQuery("#"+geom_field_name).attr('value', data.wkt);
			show_wkt_feature(data.wkt);
		      }
		      );
    }
  }
  
};

MapMethods.prototype.enter_sref = function(){
  var map = this.map;
  var input_field_name = this.settings.input_field_name;
  return new function() {
    map.current_sref = document.getElementById(input_field_name).value;
  }
};

MapMethods.prototype.show_wkt_feature = function(){
  var map = this.map;
  var editlayer = this.editLayer;
  return new function(wkt) {
    var parser = new OpenLayers.Format.WKT();
    var feature = parser.read(wkt);
    editlayer.destroyFeatures();
    editlayer.addFeatures([feature]);
    var bounds=feature.geometry.getBounds();
    // extend the boundary to include a buffer, so the map does not zoom too tight.
    dy = (bounds.top-bounds.bottom)/1.5;
    dx = (bounds.right-bounds.left)/1.5;
    bounds.top = bounds.top + dy;
    bounds.bottom = bounds.bottom - dy;
    bounds.right = bounds.right + dx;
    bounds.left = bounds.left - dx;
    // Set the default view to show something triple the size of the grid square
    map.zoomToExtent(bounds);
    // if showing a point, don't zoom in too far
    if (dy==0 && dx==0) {
      map.zoomTo(11);
    }
    
  }
};

MapMethods.prototype.find_place = function(place_search_box, place_search_output, place_search)
{
  return new function(pref_area, country)
  {
    jQuery('#'+place_search_box).hide();
    jQuery('#'+place_search_output).empty();
    var ref;
    var searchtext = jQuery('#'+place_search).attr('value');
    if (searchtext != '') {
      var request = 'http://where.yahooapis.com/v1/places.q("' +
      searchtext + ' ' + pref_area + '", "' + country + '");count=10';
      jQuery.getJSON(request + "?format=json&appid="+geoplanet_api_key+"&callback=?", function(data){
	// an array to store the responses in the required country, because GeoPlanet will not limit to a country
	var found = { places: [], count: 0 };
	jQuery.each(data.places.place, function(i,place) {
	  // Ingore places outside the chosen country, plus ignore places that were hit because they
	  // are similar to the country name we are searching in.
	  if (place.country.toUpperCase()==country.toUpperCase()
	    && country.toUpperCase().toUpperCase() != place.name.substr(0, country.length).toUpperCase()) {
	    found.places.push(place);
	  found.count++;
	  }
	});
	if (found.count==1 && found.places[0].name.toLowerCase()==searchtext.toLowerCase()) {
	  ref=found.places[0].centroid.latitude + ', ' + found.places[0].centroid.longitude;
	  show_found_place(ref);
	} else if (found.count!=0) {
	  jQuery('<p>Select from the following places that were found matching your search:</p>').appendTo('#'+place_search_output);
	  var ol=jQuery('<ol>');
	  jQuery.each(found.places, function(i,place){
	    ref="'" + place.centroid.latitude + ', ' + place.centroid.longitude + "'";
	    placename = place.name+' (' + place.placeTypeName + ')';
	    if (place.admin1!='')
	      placename = placename + ', '+place.admin1
	      if (place.admin2!='')
		placename = placename + '\\' + place.admin2;
	      jQuery('<li><a href="#" onclick="show_found_place('+ref+');">' + placename + '</a></li>').appendTo(ol);
	  });
	  ol.appendTo('#'+place_search_output);
	  jQuery('#'+place_search_box).show("slow");
	} else {
	  jQuery('<p>No locations found with that name. Try a nearby town name.</p>').appendTo('#'+place_search_output);
	  jQuery('#'+place_search_box).show("slow");
	}
      });
    }
  };
};

MapMethods.prototype.check_find_enter = function()
{
  return new function(e, pref_area, country)
  {
    var key;
    if(window.event)
    {
      key = window.event.keyCode; //IE
    }
    else
    {
      key = e.which; //firefox
    }
    if (key == 13)
      find_place(pref_area, country);
    return (key != 13);
  }
};