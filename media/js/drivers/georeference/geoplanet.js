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
 * A driver class to allow the georeference_lookup control to interface with the 
 * Yahoo! GeoPlanet API. 
 */

var Georeferencer;

(function ($) {
  Georeferencer = function(mapdiv, callback) {
    var settings = mapdiv.georefOpts;
    if (settings.geoplanet_api_key===undefined || settings.geoplanet_api_key.length===0) {
      alert('Incorrect configuration - Geoplanet API Key not specified.');
      throw('Incorrect configuration - Geoplanet API Key not specified.');
    }

    this.georeference = function(searchtext) {
      var split=searchtext.split(','), tokens=[searchtext], searchfor, searchedplace, request;
      // the name of the town/village to match etc might precede a comma
      searchedplace=split[0];
      searchtext = searchtext.replace(/,/gi, ' ');
      if (settings.georefPreferredArea!=='') {
        tokens.push(settings.georefPreferredArea);
      }
      if (settings.georefCountry!=='') {
        tokens.push(settings.georefCountry);
      }
      searchfor=tokens.join(' ');
      request = 'http://where.yahooapis.com/v1/places.q(' + searchfor + ');count=100';
      $.getJSON(request + "?format=json&lang="+settings.georefLang+
            "&appid="+settings.geoplanet_api_key+"&callback=?", function(data) {
          // an array to store the responses in the required country, because GeoPlanet will not limit to a country
          var places = [], converted={};
          if (data.places.place !== undefined) {
            jQuery.each(data.places.place, function(i,place) {
              // Ignore places outside the chosen country, plus ignore places that were hit because they
              // are similar to the country name or preferred area we are searching in.
              if ((!settings.georefCountry || place.country.toUpperCase()===settings.georefCountry.toUpperCase()) &&
                  (place.name.toUpperCase().indexOf(settings.georefCountry.toUpperCase())===-1 &&
                  (place.name.toUpperCase().indexOf(settings.georefPreferredArea.toUpperCase())===-1 || settings.georefPreferredArea === '') ||
                  place.name.toUpperCase().indexOf(searchedplace.toUpperCase())!==-1)) {
                // make the place object readable by indicia (i.e. standardised with all drivers)
                place.centroid.x = place.centroid.longitude;
                place.centroid.y = place.centroid.latitude;
                place.boundingBox.southWest.x = place.boundingBox.southWest.longitude;
                place.boundingBox.southWest.y = place.boundingBox.southWest.latitude;
                place.boundingBox.northEast.x = place.boundingBox.northEast.longitude;
                place.boundingBox.northEast.y = place.boundingBox.northEast.latitude;
                place.epsg=4326;
                places.push(place);
              }
            });
          }
          callback(mapdiv, places);
        }
      );
    };
  };
}) (jQuery);

/**
 * Default settings for this driver
 */
jQuery.fn.indiciaMapPanel.georeferenceDriverSettings = {
  georefPreferredArea : 'gb',
  georefCountry : 'United Kingdom',
  georefLang : 'en-EN',
  geoPlanetApiKey: ''
};
