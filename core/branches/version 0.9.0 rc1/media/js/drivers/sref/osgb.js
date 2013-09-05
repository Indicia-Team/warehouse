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
 * A driver to provide OSGB specific functions.
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

  returns: ['wkt','precisions','gridNotation'], // sref

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
  },

  sreflenToPrecision: function(len) {
    switch(len) {
      case 4: return {display:'10km',metres:10000};
      case 5: return {display:'2km',metres:2000};
      case 6: return {display:'1km',metres:1000};
      case 8: return {display:'100m',metres:100};
      case 10: return {display:'10m',metres:10};
      case 12: return {display:'1m',metres:1};
    }
    return false;
  },
  
  /**
   * Converts an easting northing point to a grid ref.
   * Thanks to Chris Veness, http://www.movable-type.co.uk/scripts/latlong-gridref.html, for the original script.   
   */
  pointToGridNotation: function(point, digits) {
    var e=point.x, n=point.y;
    if (e==NaN || n==NaN) return '??';
    
    // get the 100km-grid indices
    var e100k = Math.floor(e/100000), n100k = Math.floor(n/100000);
    
    if (e100k<0 || e100k>6 || n100k<0 || n100k>12) return '';

    // translate those into numeric equivalents of the grid letters
    var l1 = (19-n100k) - (19-n100k)%5 + Math.floor((e100k+10)/5);
    var l2 = (19-n100k)*5%25 + e100k%5;

    // compensate for skipped 'I' and build grid letter-pairs
    if (l1 > 7) l1++;
    if (l2 > 7) l2++;
    var letPair = String.fromCharCode(l1+'A'.charCodeAt(0), l2+'A'.charCodeAt(0));

    // strip 100km-grid indices from easting & northing, and reduce precision
    e = Math.floor((e%100000)/Math.pow(10,5-digits/2));
    n = Math.floor((n%100000)/Math.pow(10,5-digits/2));

    var gridRef = letPair + e.padLz(digits/2) + n.padLz(digits/2);

    return gridRef;
  }
};

/** Pads a number with sufficient leading zeros to make it w chars wide */
if (typeof Number.prototype.padLz === 'undefined') {
  Number.prototype.padLz = function(w) {
    var n = this.toString();
    var l = n.length;
    for (var i=0; i<w-l; i++) n = '0' + n;
    return n;
  }
}