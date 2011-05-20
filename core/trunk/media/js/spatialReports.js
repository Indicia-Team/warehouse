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
 * Method which copies the features on a layer into a WKT in a form input.
 */
function storeGeomsInHiddenInput(layer, inputId) {
  var geoms=[], featureClass='', geom;
  if (layer.features.length===0) {
    evt.preventDefault();
    alert('Please supply a search area for the report.');
  }
  $.each(layer.features, function(i, feature) {
    if (i===0) {
      // grab the first feature's type
      featureClass = feature.geometry.CLASS_NAME;
    }
    // for subsequent features, ignore them unless the same type as the first, accepting that multipolygons and polygons are compatible
    if (featureClass.replace('Multi', '') == feature.geometry.CLASS_NAME.replace('Multi', '')) {
      geoms.push(feature.geometry);
    }
  });
  if (featureClass === 'OpenLayers.Geometry.Polygon') {
    geom = new OpenLayers.Geometry.MultiPolygon(geoms);
  } else if (featureClass === 'OpenLayers.Geometry.LineString') {
    geom = new OpenLayers.Geometry.MultiLineString(geoms);
  } else if (featureClass === 'OpenLayers.Geometry.Point') {
    geom = new OpenLayers.Geometry.MultiPoint(geoms);
  }
  $('#'+inputId).val(geom.toString());
}

jQuery(document).ready(function() {
  // add a mapinitialisation hook to add a layer for buffered versions of polygons
  mapInitialisationHooks.push(function(div) {
    var style = $.extend({}, div.settings.boundaryStyle);
    style.strokeDashstyle = 'dash';
    style.fillOpacity = 0.2;
    style.fillColor = '#aaaaaa';
    bufferLayer = new OpenLayers.Layer.Vector(
        'buffer outlines',
        {style: style, 'sphericalMercator': true, displayInLayerSwitcher: false}
    );
    div.map.addLayer(bufferLayer);
  });
  // When exiting the buffer input, recreate all the buffer polygons.
  $('#geom_buffer').blur(function() {
    bufferLayer.removeAllFeatures();
    // re-add each object from the edit layer using the spatial buffering service
    $.each(mapDiv.map.editLayer.features, function(idx, feature) {
      $.get(mapDiv.settings.indiciaSvc + 'index.php/services/spatial/buffer', 
          {'wkt':feature.geometry.toString(), 'buffer':$('#geom_buffer').val()},
          function(buffered) {
            bufferLayer.addFeatures([new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(buffered))]);
          }
      );
    })

  });
});