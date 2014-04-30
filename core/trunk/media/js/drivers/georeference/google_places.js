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
 * Google Places API text search. 
 */

var Georeferencer;

(function ($) {
  Georeferencer = function(mapdiv, callback) {
    var settings = mapdiv.georefOpts;
    if (settings.google_api_key.length===0) {
      alert('Incorrect configuration - Google API Key not specified.');
      throw('Incorrect configuration - Google API Key not specified.');
    }
    this.mapdiv = mapdiv;
    // make the place search near the chosen location
    var tokens = [], near;
    if (this.mapdiv.georefOpts.georefPreferredArea!=='') {
      tokens.push(this.mapdiv.georefOpts.georefPreferredArea);
    }
    if (this.mapdiv.georefOpts.georefCountry!=='') {
      tokens.push(this.mapdiv.georefOpts.georefCountry);
    }
    near=tokens.join(', ');   
    
    this.georeference = function(searchtext) {
      $.ajax({
        dataType: "json",
        url: $.fn.indiciaMapPanel.georeferenceLookupSettings.proxy,
        data: {"url":"https://maps.googleapis.com/maps/api/place/textsearch/json","key":settings.google_api_key,"query":searchtext + ', ' + near, "sensor":"false"},
        success: function(data) {
          // an array to store the responses in the required country, because Google search will not limit to a country
          var places = [], converted={};
          jQuery.each(data.results, function(i,place) {
            converted = {
              name : place.formatted_address,
              display : place.formatted_address,
              centroid: {
                x: place.geometry.location.lng,
                y: place.geometry.location.lat
              },
              obj: place
            };
            // create a nominal bounding box
            if (typeof place.geometry.viewport!=="undefined") {
              converted.boundingBox = {
                southWest: {
                  x: place.geometry.viewport.southwest.lng, 
                  y: place.geometry.viewport.southwest.lat
                },
                northEast: {
                  x: place.geometry.viewport.northeast.lng, 
                  y: place.geometry.viewport.northeast.lat
                }
              };
            }
            else {
              converted.boundingBox = {
                southWest: {
                  x: place.geometry.location.lng-0.01,
                  y: place.geometry.location.lat-0.01
                },
                northEast: {
                  x: place.geometry.location.lng+0.01,
                  y: place.geometry.location.lat+0.01
                }
              };
            }
            places.push(converted);
          });
          callback(mapdiv, places);
        }
      });
    };
  };
}) (jQuery);

/**
 * Default this.mapdiv.georefOpts for this driver
 */
jQuery.fn.indiciaMapPanel.georeferenceDriverSettings = {
  georefPreferredArea : '',
  georefCountry : 'UK',
  google_api_key : '',
};
