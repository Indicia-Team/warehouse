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
* Class: indiciaMap.grabAGridRef
* Extension of the indiciaMap class to allow grid reference grabbing in a fashion inspired
* by Keith Balmer's Grab a Grid Reference code. Thanks to Keith for his contribution on this.
*/

(function($)
{
  $.extend({grabAGridRef : new function()
  {
    this.ten_k=null;

    this.defaults =
    {
    };

    this.construct = function(options, oloptions)
    {
      return this.each(function()
      {
        var settings = {};
        $.extend(settings, $.grabAGridRef.defaults, this.settings, options);
        this.settings = settings;

        var dragMarkerLayer = new OpenLayers.Layer.Vector("DragMarkerLayer", {
          projection: new OpenLayers.Projection("EPSG:900913"),
          styleMap: new OpenLayers.StyleMap({
            externalGraphic: "http://openlayers.org/dev/img/marker-gold.png",
            backgroundGraphic: "http://openlayers.org/dev/examples/marker_shadow.png",
            graphicYOffset: -20,
            backgroundXOffset: 0,
            backgroundYOffset: -17,
            pointRadius: 10
          })
        });
        this.gridRefLayer = new OpenLayers.Layer.Vector("GridRefLayer", {
          projection: new OpenLayers.Projection("EPSG:900913")
        });
        this.map.addLayer(dragMarkerLayer);
        this.map.addLayer(this.gridRefLayer);
        this.marker = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.Point(0,7000000));
        dragMarkerLayer.addFeatures([this.marker]);
        controls = {
          drag: new OpenLayers.Control.DragFeature(dragMarkerLayer, {'onDrag': dragMarker})
        };
        for(var key in controls) {
          this.map.addControl(controls[key]);
        }
        controls.drag.activate();
        placeControls(this);
      });
    };

    // Private functions

    function dragMarker (feature,pixel) {
      var ll = this.map.getLonLatFromPixel(pixel);
      var parser = new OpenLayers.Format.WKT();
      var feature = parser.read('POLYGON((' + ll.lon-1000 + ' ' + ll.lat-1000 + ',' +
          ll.lon+1000 + ' ' + ll.lat-1000 + ',' +
          ll.lon+1000 + ' ' + ll.lat+1000 + ',' +
          ll.lon-1000 + ' ' + ll.lat+1000 + ',' +
          ll.lon-1000 + ' ' + ll.lat-1000 + '))'
      );
      this.gridRefLayer.addFeatures([feature]);
    };

    /**
     * Place the additional controls before and after the map div.
     */
    function placeControls(div) {
      var html = '<input id="gotomarker" type="submit" value="Goto Marker"/><br/>';
      html += '<input id="getmarker" type="submit"  value="Get The Marker"/>';
      $(div).after(html);
      // Bind handlers to the buttons
      $("#gotomarker").click(function() {
        // Goto Marker - Move the map to the marker
        div.map.setCenter(new OpenLayers.LonLat(div.marker.geometry.x, div.marker.geometry.y));
      });
      $("#getmarker").click(function() {
        // Get Marker - Move the marker to the map centre
        div.marker.move(div.map.getCenter());
      });
    };

  }});

  $.fn.extend({ grabAGridRef : $.grabAGridRef.construct });
})(jQuery);