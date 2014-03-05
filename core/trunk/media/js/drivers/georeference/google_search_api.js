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
 * Google AJAX Search API. 
 */

google.load("search", "1");

var Georeferencer;

(function ($) {
  Georeferencer = function(mapdiv, callback) {
    this.mapdiv = mapdiv;
    this.localSearch = new google.search.LocalSearch();
    this.localSearch.setResultSetSize(google.search.Search.LARGE_RESULTSET);
    // make the place search near the chosen location
    var tokens = [], near;
    if (this.mapdiv.georefOpts.georefPreferredArea!=='') {
      tokens.push(this.mapdiv.georefOpts.georefPreferredArea);
    }
    if (this.mapdiv.georefOpts.georefCountry!=='') {
      tokens.push(this.mapdiv.georefOpts.georefCountry);
    }
    near=tokens.join(', ');
    this.localSearch.setCenterPoint(near);

    this.callback = callback;

    this.localSearch.setSearchCompleteCallback(this,
      function() {
        // an array to store the responses in the required country, because Google search will not limit to a country
        var places = [], converted={};
        jQuery.each(this.localSearch.results, function(i,place) {
          converted = {
            name : place.titleNoFormatting,
            display : place.title,
            centroid: {
              x: place.lng,
              y: place.lat
            },
            // create a nominal bounding box
            boundingBox: {
              southWest: {
                x: parseFloat(place.lng)-0.01, 
                y: parseFloat(place.lat)-0.01
              },
              northEast: {
                x: parseFloat(place.lng)+0.01, 
                y: parseFloat(place.lat)+0.01 
              }
            },
            obj: place
          };
          places.push(converted);
        });
        this.callback(this.mapdiv, places);
      }
    );

  
    this.georeference = function(searchtext) {
      this.localSearch.execute(searchtext);
    };
  };
}) (jQuery);

/**
 * Default this.mapdiv.georefOpts for this driver
 */
jQuery.fn.indiciaMapPanel.georeferenceDriverSettings = {
  georefPreferredArea : '',
  georefCountry : 'UK'
};
