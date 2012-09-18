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

function Georeferencer(mapdiv, callback) {
  var settings = mapdiv.georefOpts;
  if (settings.geoplanet_api_key==undefined || settings.geoplanet_api_key.length===0) {
    alert('Incorrect configuration - Geoplanet API Key not specified.');
    throw('Incorrect configuration - Geoplanet API Key not specified.');
  }
  
  this.georeference = function(searchtext) {
    searchtext = searchtext.replace(/,/gi, ' ');
    var request = 'http://where.yahooapis.com/v1/places.q(' +
        searchtext + ' ' + settings.georefPreferredArea + ' ' + settings.georefCountry + ');count=10';
    $.getJSON(request + "?format=json&lang="+settings.georefLang+
            "&appid="+settings.geoplanet_api_key+"&callback=?", function(data){
          // an array to store the responses in the required country, because GeoPlanet will not limit to a country
          var places = [], converted={};
          jQuery.each(data.places.place, function(i,place) {
            // Ignore places outside the chosen country, plus ignore places that were hit because they
            // are similar to the country name or preferred area we are searching in.
            if (place.country.toUpperCase()==settings.georefCountry.toUpperCase() &&
                (place.name.toUpperCase().indexOf(settings.georefCountry.toUpperCase())==-1 &&
                (place.name.toUpperCase().indexOf(settings.georefPreferredArea.toUpperCase())==-1 || settings.georefPreferredArea == '') ||
                place.name.toUpperCase().indexOf(searchtext.toUpperCase())!=-1)) {
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
          callback(mapdiv, places)
        });
  }
};

/**
 * Default settings for this driver
 */
$.fn.indiciaMapPanel.georeferenceDriverSettings = {
  georefPreferredArea : 'gb',
  georefCountry : 'United Kingdom',
  georefLang : 'en-EN',
  geoPlanetApiKey: ''
};
