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
 * A driver to provide WGS84 specific functions.
 * Specify indiciaData.latLongNotationPrecision to change the number of decimal places
 * shown from the default of 3.
 */

// Check IndiciaData setup, in case we are the first JS file to load
if (typeof indiciaData==="undefined") {
  indiciaData={onloadFns: []};
}
if (typeof indiciaData.srefHandlers==="undefined") {
  indiciaData.srefHandlers={};
}


indiciaData.srefHandlers['4326'] = {

  srid: 4326,

  returns: ['precisions','gridNotation'], // sref

  sreflenToPrecision: function(len) {
    return {display:'Lat/Long', metres:1};
  },
  
  /**
   * Format an x, y into a lat long 
   */
  pointToGridNotation: function(point, digits) {
    precision = (typeof indiciaData.latLongNotationPrecision==="undefined") ?
      3 : indiciaData.latLongNotationPrecision;
    var SN = point.y > 0 ? 'N' : 'S', EW = point.x > 0 ? 'E' : 'W';
    return Math.abs(point.y).toFixed(precision) + SN + ', ' + Math.abs(point.x).toFixed(precision) + EW;
  }
};