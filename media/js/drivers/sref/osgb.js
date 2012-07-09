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
 * A driver to allow the georeference_lookup control to interface with the 
 * Yahoo! GeoPlanet API. 
 */

// Check IndiciaData setup, in case we are the first JS file to load
if (typeof indiciaData==="undefined") {
  indiciaData={onloadFns: []};
}
if (typeof indiciaData.srefHandlers==="undefined") {
  indiciaData.srefHandlers={};
}
  

indiciaData.srefHandlers['osgb'] = {
  
  srid: 27700,
  
  returns: ['wkt'], // sref
  
  /**
   * Receives a point after a click on the map and converts to a grid square
   */
  pointToSref: function(point, precisionInfo) {
    var sqrSize = Math.pow(10, (10-precisionInfo.precision)/2);
    var x=Math.floor(point.x/sqrSize)*sqrSize,
        y=Math.floor(point.y/sqrSize)*sqrSize;
    if (x>=0 && x<=700000-sqrSize && y>=0 && y<=1300000-sqrSize) {
      return {
        // @todo: sref: 
        wkt: 'POLYGON(('+
          x+' '+y+','+
          (x+sqrSize)+' '+y+','+
          (x+sqrSize)+' '+(y+sqrSize)+','+
          x+' '+(y+sqrSize)+','+
          x+' '+y+
          '))'
      };
    } else {
      return {
        error: 'Out of bounds'
      }
    }
  }
};