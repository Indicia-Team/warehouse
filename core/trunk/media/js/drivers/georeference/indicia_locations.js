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
 * list of locations in Indicia's warehouse.
 */
 
var Georeferencer;

(function ($) {
  Georeferencer = function(mapdiv, callback) {

    this.georeference = function(searchtext) {
      var request, queryStr, where=[];
      if (mapdiv.georefOpts['public']===undefined || mapdiv.georefOpts['public']==='f') {
        where.push("public='f'");
      }
      if (mapdiv.georefOpts.locationTypeId!==null) {
        where.push("location_type_id="+mapdiv.georefOpts.locationTypeId);
      }
      where.push("(name ilike '%" + searchtext + "%' or comment ilike '%" + searchtext + "%' or code ilike '%" + searchtext + "%' or centroid_sref ilike '%" + searchtext + "%')");
      queryStr=encodeURI(JSON.stringify({'where':[where.join(' and ')]}));
      request = mapdiv.georefOpts.warehouseUrl + 'index.php/services/data/location?mode=json&nonce=' + mapdiv.georefOpts.nonce +
            '&auth_token=' + mapdiv.georefOpts.auth_token +
            '&view=detail&query='+queryStr+'&callback=?';
      $.getJSON(request,
        null,
        function(response) {
          var places=[], converted, parser = new OpenLayers.Format.WKT(), feature, centroid, bb, box;
          jQuery.each(response, function(i,place) {
            if (place.boundary_geom===null) {
              feature = parser.read(place.centroid_geom);
              centroid = feature.geometry.getCentroid();
              box = {
                southWest: {
                  x: centroid.x-mapdiv.georefOpts.zoomToBoxForCentroid, 
                  y: centroid.y-mapdiv.georefOpts.zoomToBoxForCentroid
                },
                northEast: {
                  x: centroid.x+mapdiv.georefOpts.zoomToBoxForCentroid, 
                  y: centroid.y+mapdiv.georefOpts.zoomToBoxForCentroid
                }
              }; 
            } else {
              feature = parser.read(place.boundary_geom);
              centroid = feature.geometry.getCentroid();
              bb = feature.geometry.getBounds();
              box = {
                southWest: {
                  x: bb.left, 
                  y: bb.bottom
                },
                northEast: {
                  x: bb.right, 
                  y: bb.top
                }
              }; 
            }
            centroid = feature.geometry.getCentroid();
            var name, nameTokens = ['<strong>'+place.name+'</strong>'];
            if (place.code!==null) {
              nameTokens.push(place.code);
            }
            nameTokens.push(place.centroid_sref);
            name = nameTokens.join(' ');
            if (place.comment!==null) {
              nameTokens += '<em>'+place.comment+'</em>';
            }
            converted = {
              name : place.name,
              display : name,
              epsg: 3857,
              centroid: {
                x: centroid.x,
                y: centroid.y
              },
              boundingBox: box,
              obj: place
            };
            places.push(converted);
          });
          callback(mapdiv, places);
        }
      );
    };
  
  };
}) (jQuery);

/**
 * Default this.mapdiv.georefOpts for this driver
 */
jQuery.fn.indiciaMapPanel.georeferenceDriverSettings = {
  warehouseUrl: '',
  auth_token: '',
  nonce: '',
  zoomToBoxForCentroid: 1000, // set this to control how zoomed in the map will be if we only know the centroid.
  locationTypeId: null
};