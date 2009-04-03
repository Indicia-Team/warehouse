/*
* Location finder plugin for jQuery.indiciaMap.
* @requires jquery
* @requires jquery.indiciaMap
* @requires jquery.indiciaMap.edit
*
*/

/**
* jQuery plugin to support Yahoo geoplanet lookup in the map control.
*/

(function($)
{
  $.extend( { locationFinder : new function()
  {
    this.defaults =
    {
      placeControls : true,
	    controlPosition : 0,
	    search_input_name : 'place_search',
	    search_box_name : 'place_search_box',
	    search_button_name : 'place_search_button',
	    search_output_name : 'place_search_output',
	    search_close_name : 'place_search_close',
	    preferredArea : 'gb',
	    country : 'United Kingdom',
	    lang : 'en-EN',
	    apiKey : ''
    };

    this.construct = function(options)
    {
      return this.each(function()
      {
	var settings = {};
	$.extend(settings, $.locationFinder.defaults, this.settings, options);
	this.settings = settings;

	if (this.settings.placeControls)
	{
	  placeControls(this);
	}

	registerControls(this);
      });
    };

    // Private functions

    function placeControls(div)
    {
      var pos = div.settings.controlPosition;
      var inputId = div.settings.search_input_name;
      var buttonId = div.settings.search_button_name;
      var searchDivId = div.settings.search_box_name;
      var outputDivId = div.settings.search_output_name;
      var closeId = div.settings.search_close_name;

      var html = "<div class='locationFinderControls'>";
      html += "<label for='"+inputId+"'>Search for place on map:</label>\n";
      html += "<input type='text' name='"+inputId+"' id='"+inputId+"' />\n";
      html += "<input type='button' name='"+buttonId+"' id='"+buttonId+"' style='margin-top: -2px' value='Search' />\n";
      html += "<div id='"+searchDivId+"' style='display: none'><div id='"+outputDivId+"' />\n";
      html += "<a href='#' id='"+closeId+"'>Close</a>\n";
      html += "</div></div>";

      if (pos == 0)
      {
	$(div).before(html);
      }
      else
      {
	$(div).after(html);
      }
    }

    function registerControls(div)
    {
      var inputId = '#'+div.settings.search_input_name;
      var buttonId = '#'+div.settings.search_button_name;
      var searchDivId = '#'+div.settings.search_box_name;
      var outputDivId = '#'+div.settings.search_output_name;
      var closeId = '#'+div.settings.search_close_name;

      $(buttonId).click(function()
      {
	locate(div);
      });

      $(inputId).keypress(function(e)
      {
	if (e.which == 13)
	{
	  locate(div);
	}
      });

      $(closeId).click(function()
      {
	$(searchDivId).hide('fast');
      });
    };

    function locate(div)
    {

      var pref_area = div.settings.preferredArea;
      var country = div.settings.country;
      var lang = div.settings.lang;
      var geoplanet_api_key = div.settings.apiKey;

      var inputId = '#'+div.settings.search_input_name;
      var searchDivId = '#'+div.settings.search_box_name;
      var outputDivId = '#'+div.settings.search_output_name;
      $(searchDivId).hide();
      $(outputDivId).empty();
      var ref;
      var searchtext = $(inputId).attr('value');
      if (searchtext != '') {
	var request = 'http://where.yahooapis.com/v1/places.q("' +
	searchtext + ' ' + pref_area + '", "' + country + '");count=10';
	$.getJSON(request + "?format=json&lang="+lang+"&appid="+geoplanet_api_key+"&callback=?", function(data){
	  // an array to store the responses in the required country, because GeoPlanet will not limit to a country
	  var found = { places: [], count: 0 };
	  jQuery.each(data.places.place, function(i,place) {
	    // Ingore places outside the chosen country, plus ignore places that were hit because they
	    // are similar to the country name we are searching in.
	    if (place.country.toUpperCase()==country.toUpperCase()
	      && (place.name.toUpperCase().indexOf(country.toUpperCase())==-1
	      || place.name.toUpperCase().indexOf(searchtext.toUpperCase())!=-1)) {
	      found.places.push(place);
	    found.count++;
	    }
	  });
	  if (found.count==1 && found.places[0].name.toLowerCase()==searchtext.toLowerCase()) {
	    ref=found.places[0].centroid.latitude + ', ' + found.places[0].centroid.longitude;
	    displayLocation(div, ref);
	  } else if (found.count!=0) {
	    $('<p>Select from the following places that were found matching your search, then click on the map to specify the exact location:</p>').appendTo(outputDivId);
	    var ol=$('<ol>');
	    $.each(found.places, function(i,place){
	      ref= place.centroid.latitude + ', ' + place.centroid.longitude;
	      placename = place.name+' (' + place.placeTypeName + ')';
	      if (place.admin1!='') placename = placename + ', '+place.admin1;
	      if (place.admin2!='') placename = placename + '\\' + place.admin2;

	      ol.append($("<li>").append($("<a href='#'>" + placename + "</a>").click((function(ref){return function() { displayLocation(div, ref); } })(ref))));
	    });
	    ol.appendTo(outputDivId);
	    $(searchDivId).show("slow");
	  } else {
	    $('<p>No locations found with that name. Try a nearby town name.</p>').appendTo(outputDivId);
	    $(searchDivId).show("slow");
	  }
	});
      }
    }

    function displayLocation(div, ref)
    {
      $.getJSON(
      div.settings.indiciaSvc + "/index.php/services/spatial/sref_to_wkt" + "?sref=" + ref + "&system=4326" + "&callback=?", function(data)
      {
	$.indiciaMapEdit.showWktFeature(div, data.wkt);
      }
      );
    }

  }});

  $.fn.extend( { locationFinder : $.locationFinder.construct } );
})(jQuery);