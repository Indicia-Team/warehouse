var map = null;
var editlayer = null;
var format = 'image/png';
var current_sref=null;
var indicia_url;
var input_field_name;
var geom_field_name;
var geoplanet_api_key;

OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {
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

// When exiting an sref control, if the value was manually changed, update the map.
function exit_sref() {
	if (current_sref!=document.getElementById(input_field_name).value) {
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

// When entering an sref control, store its current value so we can detect changes.
function enter_sref() {
	current_sref = document.getElementById(input_field_name).value;
}

function show_wkt_feature(wkt) {
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

// When the document is ready, initialise the map. This needs to be passed the base url for services and the
// wkt of the object to display if any. If Google=TRUE, then the calling page must have the Google Maps API
// link in the header with a valid API key.
function init_map(base_url, wkt, field_name, geom_name, virtual_earth, google, geoplanet_key,
		init_lat, init_long, init_zoom, init_layer) {
	// store a couple of globals for future use
	indicia_url=base_url;
	input_field_name=field_name;
	geom_field_name = geom_name;
	geoplanet_api_key=geoplanet_key;

	var boundary_style = OpenLayers.Util.applyDefaults({
		strokeWidth: 1,
		strokeColor: "#ff0000",
		fillOpacity: 0.3,
		fillColor:"#ff0000"
	}, OpenLayers.Feature.Vector.style['default']);

	var options = {
		projection: new OpenLayers.Projection("EPSG:900913"),
		displayProjection: new OpenLayers.Projection("EPSG:4326"),
		units: "m",
		numZoomLevels: 18,
		maxResolution: 156543.0339,
		maxExtent: new OpenLayers.Bounds(
			-20037508, -20037508,
			20037508, 20037508.34)
	};
	map = new OpenLayers.Map('map', options);

	editlayer = new OpenLayers.Layer.Vector("Current location boundary",
		{style: boundary_style, 'sphericalMercator': true});
	if (virtual_earth) {
		var velayer = new OpenLayers.Layer.VirtualEarth(
			"Virtual Earth",
			{'type': VEMapStyle.Aerial, 'sphericalMercator': true}
			);
		map.addLayer(velayer);
	}
	if (google) {
		var gphy = new OpenLayers.Layer.Google(
			"Google Physical",
 			{type: G_PHYSICAL_MAP, 'sphericalMercator': true}
		);
		var gmap = new OpenLayers.Layer.Google(
			"Google Streets", // the default
			{numZoomLevels: 20, 'sphericalMercator': true}
		);
		var ghyb = new OpenLayers.Layer.Google(
			"Google Hybrid",
			{type: G_HYBRID_MAP, numZoomLevels: 20, 'sphericalMercator': true}
		);
		var gsat = new OpenLayers.Layer.Google(
			"Google Satellite",
			{type: G_SATELLITE_MAP, numZoomLevels: 20, 'sphericalMercator': true}
		);

		map.addLayers([gphy, gmap, ghyb, gsat]);
	}

	map.addLayer(editlayer);
	if (init_layer!='' && typeof(init_layer)!="undefined") {
		var layers = map.getLayersByName(init_layer);
		if (layers.length==1)
			map.setBaseLayer(layers[0]);
	}
  	map.addControl(new OpenLayers.Control.LayerSwitcher());
	if (wkt!=null) {
		show_wkt_feature(wkt);
	} else {
		map.setCenter(new OpenLayers.LonLat(init_long,init_lat),init_zoom);
	}
	var click = new OpenLayers.Control.Click();
	map.addControl(click);
	click.activate();
}

// Method called to find a place using the GeoPlanet API.
// Pref_area is the text to suffix to location searches to help keep them within the target region, e.g. gb or Dorset.
function find_place(pref_area, country)
{
	jQuery('#place_search_box').hide();
	jQuery('#place_search_output').empty();
	var ref;
	var searchtext = jQuery('#place_search').attr('value');
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
	    		jQuery('<p>Select from the following places that were found matching your search:</p>').appendTo('#place_search_output');
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
		  		ol.appendTo('#place_search_output');
		  		jQuery('#place_search_box').show("slow");
	    	} else {
	    		jQuery('<p>No locations found with that name. Try a nearby town name.</p>').appendTo('#place_search_output');
				jQuery('#place_search_box').show("slow");
	    	}
		});
	}
}

// Called from onkeypress on the find place text box. Disables the enter key from form submission, and
// redirects it to the find_place method (same as clicking the adjacent button).
function check_find_enter(e, pref_area, country)
{
	var key;
	if(window.event)
		key = window.event.keyCode; //IE
	else
		key = e.which; //firefox
	if (key == 13)
		find_place(pref_area, country);
	return (key != 13);
}

// Once a place has been found, places the spatial reference as a point on the map.
// The ref param is an x,y coordinate in WGS84 datum.
function show_found_place(ref) {
	jQuery.getJSON(indicia_url + "/index.php/services/spatial/sref_to_wkt" +
		"?sref=" + ref +
		"&system=4326" +
		"&callback=?",
		function(data){
			show_wkt_feature(data.wkt);
		}
	);
}