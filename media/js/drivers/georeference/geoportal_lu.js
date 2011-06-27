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
 * service at geoportal.lu. 
 */
 
function Georeferencer(mapdiv, callback) {
  
  this.georeference = function(searchtext) {
    // Send a request to the geoportal web service. Note we use the $.ajax call rather than $.getJSON since the former allows you to force Utf8 encoding
    // for the sent Localité parameter value. Otherwise it breaks in IE8.
    $.ajax({
      type: "GET",
      url: mapdiv.georefOpts.proxy,
      contentType: "application/json; charset=utf-8",
      dataType: "json",
      data: {"url":"http://map.geoportal.lu/locationsearch","query":searchtext,"lang":mapdiv.georefOpts.georefLang,"subtype":"Commune,Localité"},
      success: function(data) {
        // an array to store the responses in the required country, because responses will not limit to a country
        var places = [];
        var converted={};
        jQuery.each(data.results, function(i,place) {
          converted = {
            name : place.label,
            display : place.listlabel,
            epsg: 2169,
            centroid: {
              x: (place.bbox[0] + place.bbox[2])/2,
              y: (place.bbox[1] + place.bbox[3])/2
            },
            boundingBox: {
              southWest: {
                x: place.bbox[0], 
                y: place.bbox[1]
              },
              northEast: {
                x: place.bbox[2], 
                y: place.bbox[3]
              }
            }
          };
          places.push(converted);
        });
        callback(mapdiv, places);
      },
      error: function (xhr, textStatus, errorThrown) {
     
      }
    });
  };
}

/**
 * Default settings for this driver
 */
$.fn.indiciaMapPanel.georeferenceDriverSettings = {
  georefLang : 'en'
};
