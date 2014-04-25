
/* Indicia, the OPAL Online Recording Toolkit.
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
 * @requires OpenLayers/Control.js
 */
 
(function ($) {
  "use strict";

  /**
   * Class: OpenLayers.Control.IndiciaGraticule
   * The Graticule displays a grid of latitude/longitude lines reprojected on
   * the map.  
   * 
   * Inherits from:
   *  - <OpenLayers.Control>
   *  
   */
  OpenLayers.Control.IndiciaGraticule = OpenLayers.Class(OpenLayers.Control, {

    /**
     * APIProperty: autoActivate
     * {Boolean} Activate the control when it is added to a map. Default is
     *     true. 
     */
    autoActivate: true,
    
    /**
    * APIProperty: intervals
    * {Array(Float)} A list of possible graticule widths in degrees. Can also be configured to 
    * contain an object with x and y properties, each holding the array of possible graticule widths
    * for that dimension, e.g. {"x":[ 50000,5000,500,50 ],"y":[ 100000,10000,1000,100 ]}
    */
    intervals: [100000,10000,1000,100],
    
    /**
    * APIProperty: intervalColours
    * {Array(string)} A list of possible CSS colours corresponding to the lines drawn for each graticule width.
    */
    intervalColours: ["#999999","#999999","#999999","#999999"],

    /**
     * APIProperty: displayInLayerSwitcher
     * {Boolean} Allows the Graticule control to be switched on and off by 
     *     LayerSwitcher control. Defaults is true.
     */
    displayInLayerSwitcher: true,

    /**
     * APIProperty: visible
     * {Boolean} should the graticule be initially visible (default=true)
     */
    visible: true,
    
    /**
     * APIProperty: projection
     * {Boolean} name of the projection to use for the output grid
     */
    projection: "EPSG:27700",
    
    /**
     * APIProperty: bounds
     * {Boolean} Bounding box (W,S,E,N) of the graticule overlay grid
     */
    bounds: [0,0,700000,1300000],

    /**
     * APIProperty: numPoints
     * {Integer} The number of points to use in each graticule line.  Higher
     * numbers result in a smoother curve for projected maps 
     */
    numPoints: 50,

    /**
     * APIProperty: targetSize
     * {Integer} The maximum size of the grid in pixels on the map
     */
    targetSize: 200,

    /**
     * APIProperty: layerName
     * {String} The name to be displayed in the layer switcher, default is set 
     *     by {<OpenLayers.Lang>}.
     */
    layerName: null,

    /**
     * APIProperty: lineStyle
     * {style} the style used to render lines
     */
    lineStyle: {
        strokeColor: "#222",
        strokeWidth: 1,
        strokeOpacity: 0.4
    },

    /**
     * Property: gratLayer
     * {OpenLayers.Layer.Vector} vector layer used to draw the graticule on
     */
    gratLayer: null,

    /**
     * Constructor: OpenLayers.Control.Graticule
     * Create a new graticule control to display a grid of latitude longitude
     * lines.
     * 
     * Parameters:
     * options - {Object} An optional object whose properties will be used
     *     to extend the control.
     */
    initialize: function(options) {
        options = options || {};
        options.layerName = options.layerName || OpenLayers.i18n("Map grid");
        OpenLayers.Control.prototype.initialize.apply(this, [options]);
    },

    /**
     * APIMethod: destroy
     */
    destroy: function() {
        this.deactivate();        
        OpenLayers.Control.prototype.destroy.apply(this, arguments);        
        if (this.gratLayer) {
            this.gratLayer.destroy();
            this.gratLayer = null;
        }
    },
    
    /**
     * Method: draw
     *
     * initializes the graticule layer and does the initial update
     * 
     * Returns:
     * {DOMElement}
     */
    draw: function() {
      OpenLayers.Control.prototype.draw.apply(this, arguments);
      if (!this.gratLayer) {
        this.gratLayer = new OpenLayers.Layer.Vector(this.layerName, {
          visibility: this.visible,
          displayInLayerSwitcher: this.displayInLayerSwitcher
        });
      }
      return this.div;
    },

     /**
     * APIMethod: activate
     */
    activate: function() {
      if (OpenLayers.Control.prototype.activate.apply(this, arguments)) {
        this.map.addLayer(this.gratLayer);
        this.map.events.register('moveend', this, this.update);     
        this.update();
        return true;            
      } else {
        return false;
      }
    },
    
    /**
     * APIMethod: deactivate
     */
    deactivate: function() {
      if (OpenLayers.Control.prototype.deactivate.apply(this, arguments)) {
        this.map.events.unregister('moveend', this, this.update);
        this.map.removeLayer(this.gratLayer);
        return true;
      } else {
        return false;
      }
    },
    
    buildGrid: function(xInterval, yInterval, mapCenterLL, llProj, mapProj, gridStyle) {
      var style = $.extend({}, this.lineStyle, gridStyle),
          mapBounds = this.map.getExtent();;
      //round the LL center to an even number based on the interval
      mapCenterLL.x = Math.floor(mapCenterLL.x/xInterval)*xInterval;
      mapCenterLL.y = Math.floor(mapCenterLL.y/yInterval)*yInterval;
      //TODO adjust for minutes/seconds?
      
      /* The following 2 blocks calculate the nodes of the grid along a 
       * line of constant longitude (then latitiude) running through the
       * center of the map until it reaches the map edge.  The calculation
       * goes from the center in both directions to the edge.
       */
      //get the central longitude line, increment the latitude
      var iter = 0;
      var centerLonPoints = [mapCenterLL.clone()];
      var newPoint = mapCenterLL.clone();
      var mapXY;
      do {
          newPoint = newPoint.offset(new OpenLayers.Pixel(0,yInterval));
          mapXY = OpenLayers.Projection.transform(newPoint.clone(), llProj, mapProj);
          centerLonPoints.unshift(newPoint);
      } while (mapBounds.top>=mapXY.y && ++iter<1000);
      newPoint = mapCenterLL.clone();
      do {          
          newPoint = newPoint.offset(new OpenLayers.Pixel(0,-yInterval));
          mapXY = OpenLayers.Projection.transform(newPoint.clone(), llProj, mapProj);
          centerLonPoints.push(newPoint);
      } while (mapBounds.bottom<=mapXY.y && ++iter<1000);
      
      //get the central latitude line, increment the longitude
      iter = 0;
      var centerLatPoints = [mapCenterLL.clone()];
      newPoint = mapCenterLL.clone();
      do {
          newPoint = newPoint.offset(new OpenLayers.Pixel(-xInterval, 0));
          mapXY = OpenLayers.Projection.transform(newPoint.clone(), llProj, mapProj);
          centerLatPoints.unshift(newPoint);
      } while (mapBounds.left<=mapXY.x && ++iter<1000);
      newPoint = mapCenterLL.clone();
      do {          
          newPoint = newPoint.offset(new OpenLayers.Pixel(xInterval, 0));
          mapXY = OpenLayers.Projection.transform(newPoint.clone(), llProj, mapProj);
          centerLatPoints.push(newPoint);
      } while (mapBounds.right>=mapXY.x && ++iter<1000);
      
      //now generate a line for each node in the central lat and lon lines
      //first loop over constant longitude
      var lines = [];
      for(var i=0; i < centerLatPoints.length; ++i) {
        var lon = centerLatPoints[i].x;
        if (lon<this.bounds[0] || lon>this.bounds[2]) {  //latitudes only valid between -90 and 90
            continue;
        }
        var pointList = [];
        var latEnd = Math.min(centerLonPoints[0].y, this.bounds[3]);
        var latStart = Math.max(centerLonPoints[centerLonPoints.length - 1].y, this.bounds[1]);
        var latDelta = (latEnd - latStart)/this.numPoints;
        var lat = latStart;
        for(var j=0; j<= this.numPoints; ++j) {
          var gridPoint = new OpenLayers.Geometry.Point(lon,lat);
          gridPoint.transform(llProj, mapProj);
          pointList.push(gridPoint);
          lat += latDelta;
        }
        var geom = new OpenLayers.Geometry.LineString(pointList);
        lines.push(new OpenLayers.Feature.Vector(geom, null, style));
      }
      
      //now draw the lines of constant latitude
      for (var j=0; j < centerLonPoints.length; ++j) {
        lat = centerLonPoints[j].y;
        if (lat<this.bounds[1] || lat>this.bounds[3]) {
            continue;
        }
        var pointList = [];
        var lonStart = Math.max(centerLatPoints[0].x, this.bounds[0]);
        var lonEnd = Math.min(centerLatPoints[centerLatPoints.length - 1].x, this.bounds[2]);
        var lonDelta = (lonEnd - lonStart)/this.numPoints;
        var lon = lonStart;
        for(var i=0; i <= this.numPoints ; ++i) {
          var gridPoint = new OpenLayers.Geometry.Point(lon,lat);
          gridPoint.transform(llProj, mapProj);
          pointList.push(gridPoint);
          lon += lonDelta;
        }
        var geom = new OpenLayers.Geometry.LineString(pointList);
        lines.push(new OpenLayers.Feature.Vector(geom, null, style));
      }
      this.gratLayer.addFeatures(lines);
    },
    
    /**
     * Method: update
     *
     * calculates the grid to be displayed and actually draws it
     * 
     * Returns:
     * {DOMElement}
     */
    update: function() {
      //wait for the map to be initialized before proceeding
      var mapBounds = this.map.getExtent();
      if (!mapBounds) {
        return;
      }
      
      //clear out the old grid
      this.gratLayer.destroyFeatures();
      
      //get the projection objects required
      var llProj = new OpenLayers.Projection(this.projection),
          mapProj = this.map.getProjectionObject(),
          mapRes = this.map.getResolution(),
          //get the map center in chosen projection
          mapCenter = this.map.getCenter(), //lon and lat here are really map x and y
          mapCenterLL = new OpenLayers.Pixel(mapCenter.lon, mapCenter.lat);
      OpenLayers.Projection.transform(mapCenterLL, mapProj, llProj);
      
      /* This block of code determines the lon/lat interval to use for the
       * grid by calculating the diagonal size of one grid cell at the map
       * center.  Iterates through the intervals array until the diagonal
       * length is less than the targetSize option.
       */
      //find lat/lon interval that results in a grid of less than the target size
      var testSq = this.targetSize*mapRes,
        xIntervals, yIntervals, xInterval, yInterval, xLargeInterval=false, yLargeInterval, colour, largeColour, xDelta, yDelta, p1, p2, distSq;
      testSq *= testSq;   //compare squares rather than doing a square root to save time
      // can either be a single array for both dims, or 2 arrays in the intervals
      if ($.isArray(this.intervals[0])) {
        xIntervals = this.intervals[0];
        yIntervals = this.intervals[1];
      } else {
        xIntervals = this.intervals;
        yIntervals = this.intervals;
      }
      for (var i=0; i<xIntervals.length; ++i) {
        xInterval = xIntervals[i];
        yInterval = yIntervals[i];
        colour = this.intervalColours[i];
        if (i>0) {
          xLargeInterval = xIntervals[i-1];
          yLargeInterval = yIntervals[i-1];
          largeColour = this.intervalColours[i-1];
        }
        xDelta = xInterval/2;
        yDelta = yInterval/2;  
        var p1 = mapCenterLL.offset(new OpenLayers.Pixel(-xDelta, -yDelta));  //test coords in EPSG:4326 space
        var p2 = mapCenterLL.offset(new OpenLayers.Pixel( xDelta,  yDelta));
        OpenLayers.Projection.transform(p1, llProj, mapProj); // convert them back to map projection
        OpenLayers.Projection.transform(p2, llProj, mapProj);
        var distSq = (p1.x-p2.x)*(p1.x-p2.x) + (p1.y-p2.y)*(p1.y-p2.y);
        if (distSq <= testSq) {
          break;
        }
      }
      this.buildGrid(xInterval, yInterval, mapCenterLL.clone(), llProj, mapProj, {strokeColor: colour, strokeOpacity: 0.4});
      if (xLargeInterval) {
        this.buildGrid(xLargeInterval, yLargeInterval, mapCenterLL, llProj, mapProj, {strokeColor: largeColour, strokeOpacity: 0.7});
      }
    },
    
    CLASS_NAME: "OpenLayers.Control.Graticule"
  });

}) (jQuery);